# Spun Web Archive Forge MCP Server

Professional WordPress Plugin Development and Management Server for Spun Web Archive Forge

## Overview

The Spun Web Archive Forge MCP Server is a comprehensive development and management tool designed specifically for the Spun Web Archive Forge WordPress plugin. It provides automated plugin development, testing, deployment, and IRC bot integration capabilities.

## Features

### 🚀 Plugin Management
- **Create New Plugins**: Generate WordPress plugins with proper structure and IRC bot integration
- **Install/Activate/Deactivate**: Full plugin lifecycle management via WP-CLI
- **Package Creation**: Create distributable plugin packages (zip files)
- **Version Management**: Handle plugin versioning and migrations

### 🔧 Development Tools
- **Code Analysis**: Security, performance, and standards compliance checking
- **Testing Framework**: Automated testing for syntax errors and functionality
- **Documentation Generation**: Auto-generate comprehensive documentation
- **Validation**: WordPress.org standards compliance validation

### 📦 Deployment & Backup
- **Remote Deployment**: SSH-based deployment to production servers
- **Backup Management**: Automated WordPress and plugin backups
- **Restore Capabilities**: Complete restoration from backups
- **Migration Support**: Database and plugin migration tools

### 🤖 IRC Bot Integration
- **Libera Chat Integration**: Connect to #spunwebtechnology channel
- **WordPress Commands**: Control WordPress from IRC
- **Archive Management**: Submit and monitor archive submissions via IRC
- **Real-time Monitoring**: Live status updates and notifications

### 📊 Performance & Monitoring
- **Benchmarking**: Performance testing and optimization
- **Memory Monitoring**: Track and optimize memory usage
- **Queue Management**: Monitor and manage archive submission queues
- **Error Tracking**: Comprehensive error logging and recovery

## Installation

### Prerequisites

- Python 3.8 or higher
- WordPress installation with WP-CLI
- Node.js (for IRC bot functionality)
- Git (for version control)

### Quick Start

1. **Clone or Download** the MCP Server files to your plugin directory
2. **Install Dependencies**:
   ```bash
   pip install -r requirements.txt
   ```
3. **Configure Settings**:
   ```bash
   cp mcp-server.conf.example mcp-server.conf
   # Edit mcp-server.conf with your settings
   ```
4. **Start the Server**:
   ```bash
   python start-mcp-server.py
   ```

### Configuration

Edit `mcp-server.conf` to configure:

```ini
[wordpress]
wp_path = "C:/Users/disru/Studio/plugin-test"
wp_url = "http://localhost:8881"
wp_admin_user = "admin"
wp_admin_password = "TCeglApnp@Ef29JVWrx6xIQn"

[irc]
server = "irc.libera.chat"
port = 6667
nickname = "spun_web"
channels = ["#spunwebtechnology"]

[archive]
api_base_url = "https://web.archive.org/save/"
availability_url = "https://archive.org/wayback/available"
```

## Usage

### Plugin Management

#### Create a New Plugin
```python
# Via MCP protocol
{
    "method": "tools/call",
    "params": {
        "name": "wp_plugin_create",
        "arguments": {
            "name": "My Awesome Plugin",
            "slug": "my-awesome-plugin",
            "author": "Your Name",
            "description": "Plugin description",
            "include_irc_bot": true
        }
    }
}
```

#### Install and Activate Plugin
```python
{
    "method": "tools/call",
    "params": {
        "name": "wp_plugin_install",
        "arguments": {
            "plugin_path": "/path/to/plugin",
            "wp_path": "/path/to/wordpress",
            "activate": true
        }
    }
}
```

### IRC Bot Commands

Once the IRC bot is running, you can use these commands in the #spunwebtechnology channel:

- `!help` - Show available commands
- `!status` - Check bot and WordPress status
- `!posts` - List recent WordPress posts
- `!archive <post_id>` - Archive a specific post
- `!queue` - Check archive submission queue status

### Archive Management

#### Submit URL for Archiving
```python
{
    "method": "tools/call",
    "params": {
        "name": "archive_submit",
        "arguments": {
            "url": "https://example.com",
            "capture_all": true,
            "capture_outlinks": true
        }
    }
}
```

#### Check Archive Status
```python
{
    "method": "tools/call",
    "params": {
        "name": "archive_status",
        "arguments": {
            "url": "https://example.com"
        }
    }
}
```

## API Reference

### WordPress Plugin Tools

| Tool | Description | Required Parameters |
|------|-------------|-------------------|
| `wp_plugin_create` | Create new WordPress plugin | name, slug, author |
| `wp_plugin_install` | Install plugin via WP-CLI | plugin_path, wp_path |
| `wp_plugin_activate` | Activate WordPress plugin | plugin_slug, wp_path |
| `wp_plugin_deactivate` | Deactivate WordPress plugin | plugin_slug, wp_path |
| `wp_plugin_list` | List all WordPress plugins | wp_path |
| `wp_plugin_package` | Create plugin package | plugin_dir, output_path |
| `wp_plugin_test` | Test plugin functionality | plugin_dir, wp_path |
| `wp_plugin_deploy` | Deploy to remote server | plugin_package, server_host, server_user, server_path |
| `wp_plugin_backup` | Create WordPress backup | wp_path, backup_path |
| `wp_plugin_restore` | Restore from backup | backup_path, wp_path |
| `wp_plugin_analyze` | Analyze plugin quality | plugin_dir |
| `wp_plugin_generate_docs` | Generate documentation | plugin_dir |
| `wp_plugin_validate` | Validate against standards | plugin_dir |
| `wp_plugin_migrate` | Migrate plugin version | plugin_dir, wp_path, from_version, to_version |
| `wp_plugin_benchmark` | Benchmark performance | plugin_dir, wp_path |

### IRC Bot Tools

| Tool | Description | Required Parameters |
|------|-------------|-------------------|
| `nodejs_irc_bot_create` | Create IRC bot | bot_name |
| `nodejs_irc_bot_start` | Start IRC bot | bot_dir |
| `nodejs_irc_bot_stop` | Stop IRC bot | bot_dir |
| `nodejs_irc_bot_status` | Check bot status | bot_dir |
| `nodejs_irc_bot_config` | Configure bot settings | bot_dir, config_updates |
| `nodejs_irc_bot_logs` | View bot logs | bot_dir |

## Development

### Project Structure

```
mcp-server/
├── mcp-server.py          # Main MCP Server implementation
├── start-mcp-server.py   # Startup script
├── mcp-server.conf        # Configuration file
├── requirements.txt       # Python dependencies
├── README.md              # This file
├── logs/                  # Log files
├── temp/                  # Temporary files
└── backups/               # Backup files
```

### Adding New Tools

To add a new tool to the MCP Server:

1. **Add the method** to the `SpunWebArchiveForgeMCPServer` class
2. **Define the tool schema** in the `TOOLS` list
3. **Update the documentation** in this README

Example:
```python
async def my_new_tool(self, param1: str, param2: int) -> Dict[str, Any]:
    """My new tool description"""
    try:
        # Tool implementation
        return {"success": True, "result": "success"}
    except Exception as e:
        return {"success": False, "error": str(e)}
```

### Testing

Run the test suite:
```bash
python -m pytest tests/
```

Run specific tests:
```bash
python -m pytest tests/test_plugin_management.py
```

## Security

The MCP Server implements comprehensive security measures:

- **Input Validation**: All inputs are validated and sanitized
- **Authentication**: WordPress admin credentials required
- **Authorization**: Capability checks for all operations
- **Nonce Verification**: CSRF protection for all requests
- **SQL Injection Prevention**: Prepared statements and parameterized queries
- **Output Escaping**: All outputs are properly escaped
- **File Security**: Restricted file operations and malware scanning

## Performance

### Optimization Features

- **Asynchronous Processing**: Non-blocking operations for better performance
- **Caching**: Intelligent caching of frequently accessed data
- **Queue Management**: Background processing for heavy operations
- **Memory Monitoring**: Automatic memory usage tracking and optimization
- **Connection Pooling**: Efficient database and API connections

### Monitoring

The server provides comprehensive monitoring:

- **Health Checks**: Regular system health monitoring
- **Performance Metrics**: Response time and throughput tracking
- **Error Tracking**: Detailed error logging and alerting
- **Resource Usage**: Memory, CPU, and disk usage monitoring

## Troubleshooting

### Common Issues

#### Server Won't Start
- Check Python version (3.8+ required)
- Verify all dependencies are installed
- Check configuration file syntax
- Ensure WordPress path is correct

#### IRC Bot Connection Issues
- Verify IRC server settings
- Check network connectivity
- Ensure nickname is available
- Verify channel permissions

#### WordPress Integration Issues
- Check WP-CLI installation
- Verify WordPress admin credentials
- Ensure plugin paths are correct
- Check file permissions

### Debug Mode

Enable debug mode in `mcp-server.conf`:
```ini
[server]
debug = true
log_level = "DEBUG"
```

### Log Files

Check these log files for issues:
- `mcp-server.log` - Main server logs
- `mcp-server-startup.log` - Startup process logs
- `logs/` - Additional log files

## Support

### Getting Help

- **IRC Support**: Join #spunwebtechnology on irc.libera.chat
- **Email Support**: support@spunwebtechnology.com
- **Phone Support**: +1 (888) 264-6790
- **Website**: https://spunwebtechnology.com

### Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

### License

This MCP Server is licensed under the GPL v2 or later, same as the Spun Web Archive Forge plugin.

## Changelog

### Version 1.0.0
- Initial release
- Complete WordPress plugin management
- IRC bot integration
- Archive.org API integration
- Comprehensive testing and validation
- Security and performance optimizations

## Roadmap

### Upcoming Features

- **GUI Interface**: Web-based admin interface
- **Cloud Integration**: AWS, Google Cloud, Azure support
- **Advanced Analytics**: Detailed usage and performance analytics
- **Multi-site Support**: WordPress multisite network management
- **API Extensions**: Additional third-party integrations
- **Mobile App**: Mobile management interface

---

**Spun Web Technology** - Professional WordPress Solutions
Website: https://spunwebtechnology.com
Support: support@spunwebtechnology.com
IRC: #spunwebtechnology on irc.libera.chat
