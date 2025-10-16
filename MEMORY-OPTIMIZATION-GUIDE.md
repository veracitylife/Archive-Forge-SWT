# Memory Optimization Guide

## Overview

The Spun Web Archive Forge plugin has been enhanced with comprehensive memory optimization features to ensure reliable performance when handling large datasets and high-volume archive operations. This guide covers the memory monitoring utilities, dashboard interface, and recent bug fixes.

## Memory Monitoring Features

### 1. Memory Utility Functions

The plugin includes a dedicated `SWAP_Memory_Utils` class that provides:

- **Real-time memory monitoring**: Track current memory usage, peak usage, and available memory
- **Dynamic batch sizing**: Automatically adjust processing batch sizes based on memory availability
- **Memory threshold warnings**: Log warnings when memory usage exceeds safe limits
- **Memory-efficient operations**: Optimized array processing and data handling

### 2. Memory Dashboard

A comprehensive admin dashboard provides:

- **Current memory status**: Real-time display of memory usage statistics
- **Memory usage history**: Log of memory warnings and threshold breaches
- **Interactive controls**: Refresh memory stats and clear logs
- **Visual indicators**: Color-coded status indicators for memory health

#### Accessing the Memory Dashboard

1. Navigate to **WordPress Admin > Tools > Memory Monitor**
2. View current memory statistics and usage trends
3. Use the "Refresh Stats" button for real-time updates
4. Clear memory logs using the "Clear Log" button when needed

### 3. Memory Thresholds

The plugin uses the following memory thresholds:

- **Warning Level**: 80% of available memory
- **Critical Level**: 90% of available memory
- **Emergency Stop**: 95% of available memory

When thresholds are exceeded:
- Warnings are logged to the database
- Processing batch sizes are automatically reduced
- Critical operations may be temporarily suspended

## Technical Implementation

### Memory Monitoring Functions

```php
// Get comprehensive memory statistics
$stats = SWAP_Memory_Utils::get_memory_stats();

// Check if memory usage is within safe limits
$is_safe = SWAP_Memory_Utils::check_memory_usage();

// Get dynamic batch size based on current memory
$batch_size = SWAP_Memory_Utils::get_dynamic_batch_size($base_size);
```

### Memory Dashboard Integration

The memory dashboard is automatically loaded as part of the plugin's core components and provides:

- AJAX-powered real-time updates
- Database logging of memory events
- Administrative interface for monitoring

### Database Tables

The plugin creates a `wp_swap_memory_log` table to store:
- Memory warning events
- Timestamp of occurrences
- Memory usage levels at time of warning
- Automatic cleanup of old entries (30+ days)

## Recent Bug Fixes

### SWAP_Auto_Version Constructor Fix

**Issue**: Fatal error when activating the plugin due to attempting to instantiate a singleton class with a private constructor.

**Error Message**: 
```
Fatal error: Uncaught Error: Call to private SWAP_Auto_Version::__construct()
```

**Solution**: Implemented proper singleton pattern handling in the component initialization system:

- Modified `initialize_components()` method to detect singleton classes
- Added conditional logic to use `get_instance()` for singleton classes
- Maintained backward compatibility for regular classes

**Technical Details**:
```php
// Handle singleton classes that use get_instance() method
if ($config['class'] === 'SWAP_Auto_Version' && method_exists($config['class'], 'get_instance')) {
    $this->{$property} = $config['class']::get_instance();
} else {
    $this->{$property} = new $config['class'](...$config['args']);
}
```

## Performance Recommendations

### For Large Sites

1. **Monitor Memory Usage**: Regularly check the memory dashboard for usage patterns
2. **Adjust Batch Sizes**: Allow dynamic batch sizing to optimize performance
3. **Schedule Heavy Operations**: Run large archive operations during low-traffic periods
4. **Clear Memory Logs**: Periodically clear old memory logs to maintain database performance

### For High-Volume Operations

1. **Enable Memory Monitoring**: Ensure memory monitoring is active during bulk operations
2. **Use Progressive Processing**: Break large operations into smaller chunks
3. **Monitor Thresholds**: Watch for memory threshold warnings and adjust accordingly
4. **Optimize Server Resources**: Consider increasing PHP memory limits for heavy operations

## Troubleshooting

### Memory-Related Issues

**High Memory Usage**:
- Check the memory dashboard for usage patterns
- Review memory logs for recurring warnings
- Consider reducing batch sizes for heavy operations
- Optimize database queries and data processing

**Plugin Activation Errors**:
- Ensure PHP memory limit is adequate (recommended: 256MB+)
- Check for conflicts with other memory-intensive plugins
- Verify server resources meet plugin requirements

**Performance Degradation**:
- Monitor memory usage during peak operations
- Use dynamic batch sizing to optimize processing
- Clear memory logs regularly
- Consider server-level optimizations

## Version History

### Version 0.6.1
- Added comprehensive memory monitoring utilities
- Implemented memory usage dashboard
- Fixed SWAP_Auto_Version constructor error
- Added dynamic batch sizing for memory optimization
- Enhanced error logging and admin notifications

## Support

For additional support with memory optimization features:

1. Check the memory dashboard for real-time diagnostics
2. Review memory logs for patterns and issues
3. Consult the plugin documentation for advanced configuration
4. Contact support with specific memory usage statistics if needed

---

*This guide covers the memory optimization features added in version 0.6.1 and later. For general plugin documentation, refer to the main README file.*