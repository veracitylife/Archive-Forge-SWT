<?php
defined('ABSPATH') || exit;

class SWP_Archiver {
    /** @var wpdb */
    protected $db;
    /** @var string */
    protected $table;

    public function __construct( $wpdb_param = null ) {
        global $wpdb;
        $this->db    = $wpdb_param ?: $wpdb;
        $this->table = $this->db->prefix . 'swap_submissions';
    }

    /* ==== Low-level Wayback helpers ===================================== */

    /**
     * Extract Save Page Now job id from HTML containing spn.watchJob("JOB_ID", ...)
     */
    public function extract_job_id( $html ) {
        if ( preg_match('/spn\.watchJob\("([^"]+)"/', $html, $m ) ) {
            return $m[1];
        }
        return null;
    }

    /**
     * Poll Save Page Now job status.
     * Returns array|WP_Error.
     */
    public function get_save_status( $job_id ) {
        $url = sprintf('https://web.archive.org/save/status/%s?_t=%d', rawurlencode($job_id), time());
        $res = wp_remote_get( $url, [
            'timeout' => 15,
            'headers' => [ 'Accept' => 'application/json' ],
        ]);
        if ( is_wp_error( $res ) ) return $res;

        $code = wp_remote_retrieve_response_code( $res );
        $body = wp_remote_retrieve_body( $res );
        if ( $code !== 200 ) return new WP_Error('wayback_http_code', 'Non-200 from Save status: '.$code);
        $json = json_decode( $body, true );
        if ( !is_array($json) || empty($json['status']) ) {
            return new WP_Error('wayback_bad_json', 'Malformed status JSON');
        }
        return $json;
    }

    /**
     * Availability API confirmation.
     * Returns closest snapshot array or null|WP_Error.
     */
    public function check_availability( $original_url ) {
        $endpoint = add_query_arg( 'url', rawurlencode( $original_url ), 'https://archive.org/wayback/available' );
        $res = wp_remote_get( $endpoint, [ 'timeout' => 15, 'headers' => [ 'Accept' => 'application/json' ] ] );
        if ( is_wp_error($res) ) return $res;

        $code = wp_remote_retrieve_response_code( $res );
        if ( $code !== 200 ) return new WP_Error('wayback_http_code', 'Availability non-200: '.$code);
        $json = json_decode( wp_remote_retrieve_body($res), true );
        return $json['archived_snapshots']['closest'] ?? null;
    }

    /**
     * Optional: HEAD the snapshot to ensure it's actually retrievable.
     */
    public function head_snapshot( $snapshot_url ) {
        $res = wp_remote_head( $snapshot_url, [ 'timeout' => 15 ] );
        if ( is_wp_error($res) ) return $res;
        return wp_remote_retrieve_response_code( $res );
    }

    /* ==== Persistence helpers ========================================== */

    public function mark_archived( $id, $snapshot_url, $timestamp, $args = [] ) {
        $needs_audit = !empty($args['needs_audit']) ? 1 : 0;
        return $this->db->update(
            $this->table,
            [
                'status'       => 'archived',
                'snapshot_url' => $snapshot_url,
                'snapshot_ts'  => $timestamp,
                'needs_audit'  => $needs_audit,
                'updated_at'   => current_time('mysql'),
            ],
            ['id' => (int) $id],
            ['%s','%s','%s','%d','%s'],
            ['%d']
        );
    }

    public function mark_failed( $id, $error_code = 'unknown_error' ) {
        return $this->db->update(
            $this->table,
            [
                'status'     => 'failed',
                'error_code' => $error_code,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => (int) $id],
            ['%s','%s','%s'],
            ['%d']
        );
    }

    /* ==== Reconciliation logic ========================================= */

    /**
     * Reconcile a single submission row object.
     * Expected fields: id, original_url, job_id, status
     */
    public function reconcile_submission( $row ) {
        if ( empty($row->original_url) ) return 'invalid_row';

        // No job id? Try availability-only path.
        if ( empty($row->job_id) ) {
            $closest = $this->check_availability( $row->original_url );
            if ( is_wp_error($closest) ) return 'availability_error';
            if ( $closest && !empty($closest['available']) ) {
                $this->mark_archived( $row->id, $closest['url'], $closest['timestamp'] );
                return 'archived_via_availability';
            }
            return 'still_processing_or_missing';
        }

        // Poll job status
        $status = $this->get_save_status( $row->job_id );
        if ( is_wp_error($status) ) return 'status_check_error';

        if ( $status['status'] === 'pending' ) return 'pending';

        if ( $status['status'] === 'success' && !empty($status['timestamp']) ) {
            $snapshot_url = sprintf(
                'https://web.archive.org/web/%s/%s',
                $status['timestamp'],
                $status['original_url'] ?? $row->original_url
            );

            // Double-check availability
            $closest = $this->check_availability( $row->original_url );
            if ( is_wp_error($closest) ) {
                // Soft-success with audit flag
                $this->mark_archived( $row->id, $snapshot_url, $status['timestamp'], ['needs_audit' => 1] );
                return 'archived_needs_audit_availability_error';
            }

            if ( $closest && !empty($closest['available']) ) {
                // Optionally check HEAD:
                $code = $this->head_snapshot( $snapshot_url );
                if ( !is_wp_error($code) && (int)$code === 200 ) {
                    $this->mark_archived( $row->id, $snapshot_url, $status['timestamp'] );
                    return 'archived';
                }
                // Snapshot may be warming: mark archived but audit
                $this->mark_archived( $row->id, $snapshot_url, $status['timestamp'], ['needs_audit' => 1] );
                return 'archived_needs_audit_head_non200';
            }

            // Availability didn't confirm — soft mark with audit
            $this->mark_archived( $row->id, $snapshot_url, $status['timestamp'], ['needs_audit' => 1] );
            return 'archived_needs_audit';
        }

        if ( $status['status'] === 'error' ) {
            $this->mark_failed( $row->id, $status['error'] ?? 'save_error' );
            return 'failed';
        }

        return 'unexpected_state';
    }

    /* ==== Batch sweep for stuck jobs =================================== */

    public function sweep_stuck_processing( $older_than_minutes = 15, $limit = 50 ) {
        $table = $this->table;
        $rows  = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM $table
                 WHERE status = %s
                   AND submitted_at < (NOW() - INTERVAL %d MINUTE)
                 ORDER BY submitted_at ASC
                 LIMIT %d",
                'processing', $older_than_minutes, $limit
            )
        );

        $results = [];
        if ( $rows ) {
            foreach ( $rows as $row ) {
                $results[$row->id] = $this->reconcile_submission( $row );
            }
        }
        return $results;
    }
    
    /**
     * Reset stuck processing items to pending status
     * Useful for manual intervention when items are truly stuck
     */
    public function reset_stuck_items( $older_than_minutes = 60, $limit = 100 ) {
        $table = $this->table;
        
        // Reset items that have been processing for too long
        $updated = $this->db->query(
            $this->db->prepare(
                "UPDATE $table 
                 SET status = 'pending', 
                     job_id = NULL,
                     updated_at = NOW()
                 WHERE status = 'processing' 
                   AND submitted_at < (NOW() - INTERVAL %d MINUTE)
                 LIMIT %d",
                $older_than_minutes, $limit
            )
        );
        
        error_log('SWP_Archiver: Reset ' . $updated . ' stuck items to pending status');
        
        return $updated;
    }
}
