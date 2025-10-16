# Live Production Queue Test Plan

**Target Site**: https://disruptarian.com/blog  
**Expected Queue Size**: 330+ pending submissions  
**Test Date**: January 24, 2025

## Test Objectives

1. **Verify Queue Processing on Live Site**
   - Test with real production data
   - Validate performance with large queue
   - Confirm AJAX endpoints work in production

2. **Performance Testing**
   - Measure processing time with 330+ items
   - Test memory usage and server load
   - Validate error handling with real data

3. **Production Validation**
   - Confirm queue management interface works
   - Test all queue management buttons
   - Validate queue statistics accuracy

## Test Steps

### Step 1: Access Production Site
- SSH to server: `ssh Disrutparian@66.94.125.162`
- Navigate to WordPress directory
- Upload test script to server

### Step 2: Run Queue Test Script
```bash
cd /home/disruptarian/public_html/blog
php live-production-queue-test.php
```

### Step 3: Test AJAX Endpoints
- Test Process Queue button via AJAX
- Test Refresh Stats button
- Test Clear Completed/Failed buttons

### Step 4: Monitor Performance
- Measure processing time
- Monitor server resources
- Check for memory issues

## Expected Results

### ✅ Success Criteria
- Queue processing works with 330+ items
- AJAX endpoints respond correctly
- Performance is acceptable (< 30 seconds for processing)
- No memory errors or timeouts

### ⚠️ Potential Issues
- Large queue may cause timeouts
- Memory usage might be high
- Server load during processing
- API rate limiting from Archive.org

## Test Script Features

The `live-production-queue-test.php` script will:

1. **Display Current Queue Status**
   - Show pending, processing, completed, failed counts
   - Display total queue size

2. **Test Queue Processing**
   - Process queue items
   - Show processing results
   - Display errors (first 10)

3. **Test AJAX Endpoints**
   - Test Process Queue AJAX
   - Test Refresh Stats AJAX
   - Validate responses

4. **Show Queue Items Sample**
   - Display first 10 queue items
   - Show status and metadata

5. **Performance Metrics**
   - Measure execution time
   - Monitor processing speed

## Risk Mitigation

### Before Testing
- Backup database
- Monitor server resources
- Have rollback plan ready

### During Testing
- Monitor server load
- Watch for memory usage
- Check error logs

### After Testing
- Verify queue integrity
- Check for any data corruption
- Monitor site performance

## Success Metrics

- ✅ Queue processing completes without errors
- ✅ AJAX endpoints respond correctly
- ✅ Performance is acceptable (< 30 seconds)
- ✅ No memory or timeout issues
- ✅ Queue statistics update correctly

## Test Report Template

After testing, document:
- Queue size before/after processing
- Processing time and performance
- Any errors encountered
- AJAX endpoint responses
- Recommendations for optimization

---

**Note**: This test will process actual queue items on the production site. Ensure proper backups and monitoring are in place before proceeding.
