# Spun Web Archive Forge MCP Server Installation Guide

Professional WordPress Plugin Development and Management Server

## Quick Installation

### Windows (Recommended)

1. **Download the MCP Server files** to your plugin directory
2. **Run the batch file**:
   ```cmd
   start-mcp-server.bat
   ```
3. **Or use PowerShell**:
   ```powershell
   .\start-mcp-server.ps1 -Install
   ```

### Linux/macOS

1. **Make scripts executable**:
   ```bash
   chmod +x start-mcp-server.py
   chmod +x test-mcp-server.py
   ```
2. **Install dependencies**:
   ```bash
   pip install -r requirements.txt
   ```
3. **Start the server**:
   ```bash
   python start-mcp-server.py
   ```

## Detailed Installation

### Prerequisites

#### Required Software
- **Python 3.8+**: Download from [python.org](https://python.org)
- **WordPress**: Local installation with WP-CLI
- **Node.js**: For IRC bot functionality (optional)
- **Git**: For version control (optional)

#### WordPress Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- WP-CLI installed and configured

### Step-by-Step Installation

#### 1. Download MCP Server Files

Download or copy these files to your plugin development directory:
```
mcp-server/
├── mcp-server.py              # Main server implementation
├── start-mcp-server.py       # Startup script
├── start-mcp-server.bat      # Windows batch file
├── start-mcp-server.ps1       # PowerShell script
├── test-mcp-server.py         # Test suite
├── mcp-server.conf.example    # Example configuration
├── requirements.txt           # Python dependencies
├── MCP-SERVER-README.md       # Documentation
└── README.md                  # This file
```

#### 2. Configure Environment

**Copy the example configuration**:
```bash
cp mcp-server.conf.example mcp-server.conf
```

**Edit `mcp-server.conf`** with your settings:
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
```

#### 3. Install Python Dependencies

**Automatic installation** (recommended):
```bash
pip install -r requirements.txt
```

**Manual installation** of core dependencies:
```bash
pip install asyncio aiofiles aiohttp requests httpx pydantic
pip install zipfile38 py7zr wordpress-api wp-cli
pip install irc python-irc sqlalchemy alembic
pip install cryptography bcrypt structlog sentry-sdk
```

#### 4. Verify Installation

**Run the test suite**:
```bash
python test-mcp-server.py
```

**Expected output**:
```
Spun Web Archive Forge MCP Server Test Suite
==================================================
[PASS] File Existence: mcp-server.py
[PASS] File Existence: start-mcp-server.py
[PASS] File Existence: mcp-server.conf
[PASS] Python Syntax: mcp-server.py
[PASS] Python Syntax: start-mcp-server.py
[PASS] Module Imports: Standard library imports
[PASS] Configuration File: Configuration sections
[PASS] MCP Server Class: MCP Server class
[PASS] Tool Definitions: Found 25 tools
[PASS] WordPress Environment: WP-CLI found
[PASS] IRC Bot Creation: IRC bot creation method
[PASS] Plugin Management Methods: Found 7 methods
[PASS] Async Functionality: Async test passed
==================================================
Test Results: 12/12 tests passed
🎉 All tests passed! MCP Server is ready to use.
```

#### 5. Start the MCP Server

**Windows**:
```cmd
start-mcp-server.bat
```

**PowerShell**:
```powershell
.\start-mcp-server.ps1
```

**Python directly**:
```bash
python start-mcp-server.py
```

**Expected output**:
```
Spun Web Archive Forge MCP Server Startup
==================================================
Running startup checks...
[INFO] Loading configuration...
[INFO] Checking dependencies...
[INFO] Validating configuration...
[INFO] Checking WordPress environment...
[INFO] Checking plugin environment...
[INFO] Creating directories...
[INFO] Starting MCP Server...
[INFO] MCP Server started successfully (PID: 12345)
Server is running and ready to accept connections
```

## Configuration

### WordPress Settings

Configure your WordPress installation in `mcp-server.conf`:

```ini
[wordpress]
# WordPress installation path
wp_path = "C:/Users/disru/Studio/plugin-test"

# WordPress URL
wp_url = "http://localhost:8881"

# Admin credentials
wp_admin_user = "admin"
wp_admin_password = "TCeglApnp@Ef29JVWrx6xIQn"

# WP-CLI path
wp_cli_path = "wp"

# Plugin development paths
plugin_development_path = "C:/Users/disru/Documents/wordpress plugins/Spun Web Archive Forge Cursor 2"
repository_path = "C:/Users/disru/Documents/wordpress plugins/Spun Web Archive Forge Repository"
backup_path = "C:/Users/disru/Documents/wordpress plugins/backups"
```

### IRC Bot Settings

Configure IRC bot integration:

```ini
[irc]
# IRC server settings
server = "irc.libera.chat"
port = 6667
nickname = "spun_web"
channels = ["#spunwebtechnology"]
password = ""
ssl = false

# Bot features
enable_wordpress_integration = true
enable_archive_commands = true
enable_queue_monitoring = true
enable_status_reports = true
```

### Archive.org API Settings

Configure Archive.org integration:

```ini
[archive]
# API endpoints
api_base_url = "https://web.archive.org/save/"
availability_url = "https://archive.org/wayback/available"
s3_test_url = "https://s3.us.archive.org/"

# API settings
api_timeout = 30
max_retries = 3
rate_limit_delay = 1

# Credentials (loaded from WordPress options)
access_key = ""
secret_key = ""
callback_token = ""
```

## Usage Examples

### Create a New Plugin

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
            "description": "A great WordPress plugin",
            "include_irc_bot": true
        }
    }
}
```

### Install and Activate Plugin

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

### Create IRC Bot

```python
{
    "method": "tools/call",
    "params": {
        "name": "nodejs_irc_bot_create",
        "arguments": {
            "bot_name": "WordPressBot",
            "irc_channels": ["#wordpress", "#development"],
            "irc_nickname": "wpbot",
            "irc_server": "irc.libera.chat",
            "wp_integration": true
        }
    }
}
```

## Troubleshooting

### Common Issues

#### Python Not Found
**Error**: `Python is not installed or not in PATH`
**Solution**: 
1. Install Python 3.8+ from [python.org](https://python.org)
2. Add Python to your system PATH
3. Restart your terminal/command prompt

#### WP-CLI Not Found
**Error**: `WP-CLI not found in PATH`
**Solution**:
1. Install WP-CLI: [wp-cli.org](https://wp-cli.org)
2. Add WP-CLI to your system PATH
3. Test with: `wp --version`

#### Configuration File Missing
**Error**: `mcp-server.conf not found`
**Solution**:
1. Copy the example: `cp mcp-server.conf.example mcp-server.conf`
2. Edit the configuration with your settings
3. Ensure all required sections are present

#### Dependencies Installation Failed
**Error**: `Some dependencies may not have installed correctly`
**Solution**:
1. Upgrade pip: `python -m pip install --upgrade pip`
2. Install dependencies manually: `pip install -r requirements.txt`
3. Check for specific package errors

#### WordPress Path Not Found
**Error**: `WordPress path does not exist`
**Solution**:
1. Verify the WordPress installation path in `mcp-server.conf`
2. Ensure the path contains `wp-config.php`
3. Check file permissions

### Debug Mode

Enable debug mode for detailed logging:

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

### Getting Help

- **IRC Support**: Join #spunwebtechnology on irc.libera.chat
- **Email Support**: support@spunwebtechnology.com
- **Phone Support**: +1 (888) 264-6790
- **Website**: https://spunwebtechnology.com

## Advanced Configuration

### Custom Tool Development

To add custom tools to the MCP Server:

1. **Add method** to `SpunWebArchiveForgeMCPServer` class
2. **Define tool schema** in `TOOLS` list
3. **Update documentation**

### Plugin Integration

Integrate with existing WordPress plugins:

1. **Configure plugin paths** in `mcp-server.conf`
2. **Set up WordPress credentials**
3. **Enable plugin-specific features**

### IRC Bot Customization

Customize IRC bot behavior:

1. **Edit bot configuration** in `mcp-server.conf`
2. **Modify bot commands** in `bot.js`
3. **Add custom integrations**

## Security Considerations

### Credentials Management

- Store sensitive credentials in environment variables
- Use WordPress options for API keys
- Enable encryption for stored passwords

### File Permissions

- Restrict access to configuration files
- Use proper file permissions (600 for config files)
- Enable file security scanning

### Network Security

- Use SSL/TLS for IRC connections
- Enable firewall rules for server ports
- Implement rate limiting

## Performance Optimization

### Memory Management

- Set appropriate memory limits
- Enable memory monitoring
- Use efficient data structures

### Caching

- Enable caching for frequently accessed data
- Use appropriate cache TTL values
- Implement cache invalidation

### Database Optimization

- Use prepared statements
- Implement connection pooling
- Optimize database queries

## Maintenance

### Regular Updates

- Update Python dependencies regularly
- Keep WordPress and plugins updated
- Monitor security advisories

### Backup Strategy

- Enable automatic backups
- Test backup restoration
- Store backups securely

### Monitoring

- Monitor server performance
- Track error rates
- Set up alerting

---

**Spun Web Technology** - Professional WordPress Solutions
Website: https://spunwebtechnology.com
Support: support@spunwebtechnology.com
IRC: #spunwebtechnology on irc.libera.chat
