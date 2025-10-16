# Archive Forge Queue Processing - Live Production Test Instructions

## ✅ Local Testing Complete - All Systems Working

**Status**: Queue processing functionality is **FULLY WORKING** on local test servers.

### Local Test Results Summary
- ✅ **Server 1 (localhost:8881)**: Queue processing working correctly
- ✅ **Server 2 (localhost:8884)**: Queue processing working correctly
- ✅ **AJAX Endpoints**: All functional and responding correctly
- ✅ **Queue Management**: All buttons working (Process Queue, Clear Completed, Clear Failed, Refresh Stats)
- ✅ **Queue Statistics**: Updating correctly (pending → processing → completed/failed)

## 🚀 Live Production Site Testing

**Target Site**: https://disruptarian.com/blog  
**Expected Queue**: 330+ pending submissions  
**SSH Access**: Disrutparian@66.94.125.162

### Step 1: Upload Test Script to Production Server

```bash
# SSH to production server
ssh Disrutparian@66.94.125.162

# Navigate to WordPress directory
cd /home/disruptarian/public_html/blog

# Upload the test script (you'll need to upload live-production-queue-test.php)
# You can use SCP, FTP, or copy-paste the content
```

### Step 2: Run Production Queue Test

```bash
# Run the test script
php live-production-queue-test.php
```

### Step 3: Test via WordPress Admin

1. **Login to WordPress Admin**
   - URL: https://disruptarian.com/blog/wp-admin
   - Username: admin_wf582umf
   - Password: pHwI0Jeg8fhp~!0x

2. **Navigate to Queue Management**
   - Go to: Web Archive Forge → Queue Management
   - Check current queue statistics
   - Test "Process Queue Now" button

3. **Monitor Processing**
   - Watch queue statistics update
   - Check for any error messages
   - Monitor server performance

### Step 4: Test AJAX Endpoints

```bash
# Test AJAX endpoints (requires authentication)
curl -X POST "https://disruptarian.com/blog/wp-admin/admin-ajax.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=swap_refresh_queue_stats&nonce=YOUR_NONCE"
```

## 📊 Expected Results on Live Site

### With 330+ Pending Submissions

**Before Processing:**
- Pending: 330+
- Processing: 0
- Completed: [existing count]
- Failed: [existing count]

**After Processing:**
- Pending: [reduced count]
- Processing: [some items moved to processing]
- Completed: [may increase if API credentials are configured]
- Failed: [may increase if there are API issues]

### Performance Expectations

- **Processing Time**: 30-60 seconds for 330+ items
- **Memory Usage**: Should be manageable
- **Server Load**: Temporary increase during processing
- **API Calls**: Will make calls to Archive.org (if credentials configured)

## 🔧 Troubleshooting Live Site Issues

### If Queue Processing Appears "Stuck"

1. **Check API Credentials**
   - Go to API Settings tab
   - Verify Archive.org credentials are configured
   - Test API connection

2. **Check Server Resources**
   - Monitor memory usage
   - Check for PHP timeouts
   - Verify database connectivity

3. **Check Error Logs**
   - WordPress debug.log
   - Server error logs
   - Plugin-specific logs

### Common Issues and Solutions

**Issue**: Items stay in "processing" state
- **Cause**: API credentials not configured or invalid
- **Solution**: Configure proper Archive.org credentials

**Issue**: Processing times out
- **Cause**: Large queue size, server limits
- **Solution**: Process in smaller batches, increase PHP limits

**Issue**: Memory errors
- **Cause**: Insufficient memory for large queue
- **Solution**: Increase PHP memory limit, optimize processing

## 📋 Test Checklist

### Pre-Test Setup
- [ ] Backup database
- [ ] Monitor server resources
- [ ] Check current queue status
- [ ] Verify API credentials

### During Test
- [ ] Run queue processing
- [ ] Monitor performance metrics
- [ ] Check for errors
- [ ] Verify queue statistics update

### Post-Test Validation
- [ ] Verify queue integrity
- [ ] Check for data corruption
- [ ] Monitor site performance
- [ ] Document results

## 🎯 Success Criteria

**✅ Test Passes If:**
- Queue processing completes without fatal errors
- Queue statistics update correctly
- AJAX endpoints respond properly
- Performance is acceptable (< 60 seconds)
- No memory or timeout issues

**❌ Test Fails If:**
- Fatal errors during processing
- Queue statistics don't update
- AJAX endpoints return errors
- Processing times out
- Memory errors occur

## 📈 Performance Optimization Recommendations

### For Large Queues (330+ items)

1. **Batch Processing**
   - Process items in smaller batches (50-100 at a time)
   - Add delays between batches to prevent server overload

2. **Background Processing**
   - Use WordPress cron for automatic processing
   - Implement queue processing scheduler

3. **Resource Management**
   - Increase PHP memory limit
   - Optimize database queries
   - Use efficient data structures

## 🔍 Monitoring and Logging

### Key Metrics to Monitor
- Queue processing time
- Memory usage during processing
- API response times
- Error rates
- Server load

### Logging Recommendations
- Enable WordPress debug logging
- Log queue processing events
- Monitor API call success/failure rates
- Track performance metrics

---

## 📞 Support Information

**Plugin**: ARCHIVE FORGE SWT v1.0.9  
**Support**: @spun_web on http://web.libera.chat/#spunwebtechnology  
**Phone**: +1 (888) 264-6790  
**Email**: support@spunwebtechnology.com  
**Website**: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/

**Test Status**: ✅ Local testing complete, ready for production testing
