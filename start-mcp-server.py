#!/usr/bin/env python3
"""
Spun Web Archive Forge MCP Server Startup Script
Professional WordPress Plugin Development and Management Server

This script initializes and starts the MCP Server with proper configuration
and dependency management.

Author: Ryan Dickie Thompson
Company: Spun Web Technology
Version: 1.0.0
License: GPL v2 or later
"""

import os
import sys
import subprocess
import asyncio
import logging
from pathlib import Path
from typing import Optional, Dict, Any
import json
import configparser

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('mcp-server-startup.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

class MCPServerStartup:
    """MCP Server startup and configuration manager"""
    
    def __init__(self):
        self.script_dir = Path(__file__).parent
        self.config_file = self.script_dir / "mcp-server.conf"
        self.requirements_file = self.script_dir / "requirements.txt"
        self.server_script = self.script_dir / "mcp-server.py"
        self.config = None
        
    def load_config(self) -> bool:
        """Load configuration from config file"""
        try:
            if not self.config_file.exists():
                logger.error(f"Configuration file not found: {self.config_file}")
                return False
            
            self.config = configparser.ConfigParser()
            self.config.read(self.config_file)
            
            logger.info("Configuration loaded successfully")
            return True
            
        except Exception as e:
            logger.error(f"Error loading configuration: {e}")
            return False
    
    def check_dependencies(self) -> bool:
        """Check if all required dependencies are installed"""
        try:
            # Check Python version
            if sys.version_info < (3, 8):
                logger.error("Python 3.8 or higher is required")
                return False
            
            # Check if requirements.txt exists
            if not self.requirements_file.exists():
                logger.warning("requirements.txt not found, skipping dependency check")
                return True
            
            # Check if virtual environment is active
            if hasattr(sys, 'real_prefix') or (hasattr(sys, 'base_prefix') and sys.base_prefix != sys.prefix):
                logger.info("Virtual environment detected")
            else:
                logger.warning("No virtual environment detected, consider using one")
            
            # Check critical dependencies
            critical_deps = ['asyncio', 'json', 'pathlib', 'subprocess']
            missing_deps = []
            
            for dep in critical_deps:
                try:
                    __import__(dep)
                except ImportError:
                    missing_deps.append(dep)
            
            if missing_deps:
                logger.error(f"Missing critical dependencies: {missing_deps}")
                return False
            
            logger.info("Dependencies check passed")
            return True
            
        except Exception as e:
            logger.error(f"Error checking dependencies: {e}")
            return False
    
    def install_dependencies(self) -> bool:
        """Install Python dependencies from requirements.txt"""
        try:
            if not self.requirements_file.exists():
                logger.warning("requirements.txt not found, skipping dependency installation")
                return True
            
            logger.info("Installing Python dependencies...")
            
            # Install dependencies
            result = subprocess.run([
                sys.executable, "-m", "pip", "install", "-r", str(self.requirements_file)
            ], capture_output=True, text=True)
            
            if result.returncode == 0:
                logger.info("Dependencies installed successfully")
                return True
            else:
                logger.error(f"Error installing dependencies: {result.stderr}")
                return False
                
        except Exception as e:
            logger.error(f"Error installing dependencies: {e}")
            return False
    
    def check_wordpress_environment(self) -> bool:
        """Check WordPress environment and WP-CLI availability"""
        try:
            if not self.config:
                logger.error("Configuration not loaded")
                return False
            
            wp_path = self.config.get('wordpress', 'wp_path', fallback='')
            wp_cli_path = self.config.get('development', 'wp_cli_path', fallback='wp')
            
            if not wp_path:
                logger.error("WordPress path not configured")
                return False
            
            wp_path = Path(wp_path)
            if not wp_path.exists():
                logger.error(f"WordPress path does not exist: {wp_path}")
                return False
            
            # Check if wp-cli is available
            try:
                result = subprocess.run([wp_cli_path, "--version"], 
                                      cwd=str(wp_path), 
                                      capture_output=True, text=True)
                if result.returncode == 0:
                    logger.info(f"WP-CLI available: {result.stdout.strip()}")
                else:
                    logger.warning("WP-CLI not available, some features may be limited")
            except FileNotFoundError:
                logger.warning("WP-CLI not found in PATH, some features may be limited")
            
            # Check WordPress installation
            wp_config = wp_path / "wp-config.php"
            if wp_config.exists():
                logger.info("WordPress installation detected")
            else:
                logger.warning("wp-config.php not found, WordPress may not be properly installed")
            
            logger.info("WordPress environment check completed")
            return True
            
        except Exception as e:
            logger.error(f"Error checking WordPress environment: {e}")
            return False
    
    def check_plugin_environment(self) -> bool:
        """Check plugin development environment"""
        try:
            if not self.config:
                logger.error("Configuration not loaded")
                return False
            
            plugin_path = self.config.get('wordpress', 'plugin_development_path', fallback='')
            
            if not plugin_path:
                logger.error("Plugin development path not configured")
                return False
            
            plugin_path = Path(plugin_path)
            if not plugin_path.exists():
                logger.error(f"Plugin development path does not exist: {plugin_path}")
                return False
            
            # Check for main plugin file
            main_plugin_file = plugin_path / "spun-web-archive-forge.php"
            if main_plugin_file.exists():
                logger.info("Main plugin file found")
            else:
                logger.warning("Main plugin file not found")
            
            # Check for includes directory
            includes_dir = plugin_path / "includes"
            if includes_dir.exists():
                logger.info("Plugin includes directory found")
            else:
                logger.warning("Plugin includes directory not found")
            
            logger.info("Plugin environment check completed")
            return True
            
        except Exception as e:
            logger.error(f"Error checking plugin environment: {e}")
            return False
    
    def create_directories(self) -> bool:
        """Create necessary directories"""
        try:
            if not self.config:
                logger.error("Configuration not loaded")
                return False
            
            # Create backup directory
            backup_path = self.config.get('wordpress', 'backup_path', fallback='')
            if backup_path:
                backup_dir = Path(backup_path)
                backup_dir.mkdir(parents=True, exist_ok=True)
                logger.info(f"Backup directory created/verified: {backup_dir}")
            
            # Create logs directory
            logs_dir = self.script_dir / "logs"
            logs_dir.mkdir(exist_ok=True)
            logger.info(f"Logs directory created/verified: {logs_dir}")
            
            # Create temp directory
            temp_dir = self.script_dir / "temp"
            temp_dir.mkdir(exist_ok=True)
            logger.info(f"Temp directory created/verified: {temp_dir}")
            
            return True
            
        except Exception as e:
            logger.error(f"Error creating directories: {e}")
            return False
    
    def validate_configuration(self) -> bool:
        """Validate configuration settings"""
        try:
            if not self.config:
                logger.error("Configuration not loaded")
                return False
            
            # Check required sections
            required_sections = ['server', 'wordpress', 'archive', 'irc']
            missing_sections = [section for section in required_sections 
                              if not self.config.has_section(section)]
            
            if missing_sections:
                logger.error(f"Missing required configuration sections: {missing_sections}")
                return False
            
            # Check required WordPress settings
            wp_required = ['wp_path', 'wp_url']
            wp_missing = [setting for setting in wp_required 
                         if not self.config.get('wordpress', setting, fallback='')]
            
            if wp_missing:
                logger.error(f"Missing required WordPress settings: {wp_missing}")
                return False
            
            # Check required server settings
            server_required = ['host', 'port']
            server_missing = [setting for setting in server_required 
                            if not self.config.get('server', setting, fallback='')]
            
            if server_missing:
                logger.error(f"Missing required server settings: {server_missing}")
                return False
            
            logger.info("Configuration validation passed")
            return True
            
        except Exception as e:
            logger.error(f"Error validating configuration: {e}")
            return False
    
    def start_server(self) -> bool:
        """Start the MCP Server"""
        try:
            if not self.server_script.exists():
                logger.error(f"Server script not found: {self.server_script}")
                return False
            
            logger.info("Starting MCP Server...")
            
            # Start the server
            process = subprocess.Popen([
                sys.executable, str(self.server_script)
            ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
            
            # Wait a moment to check if it started successfully
            import time
            time.sleep(2)
            
            if process.poll() is None:
                logger.info("MCP Server started successfully")
                logger.info(f"Server PID: {process.pid}")
                return True
            else:
                stdout, stderr = process.communicate()
                logger.error(f"Server failed to start: {stderr}")
                return False
                
        except Exception as e:
            logger.error(f"Error starting server: {e}")
            return False
    
    def run_startup_checks(self) -> bool:
        """Run all startup checks"""
        logger.info("Running startup checks...")
        
        checks = [
            ("Loading configuration", self.load_config),
            ("Checking dependencies", self.check_dependencies),
            ("Validating configuration", self.validate_configuration),
            ("Checking WordPress environment", self.check_wordpress_environment),
            ("Checking plugin environment", self.check_plugin_environment),
            ("Creating directories", self.create_directories)
        ]
        
        for check_name, check_func in checks:
            logger.info(f"Running check: {check_name}")
            if not check_func():
                logger.error(f"Check failed: {check_name}")
                return False
            logger.info(f"Check passed: {check_name}")
        
        logger.info("All startup checks passed")
        return True
    
    def install_missing_dependencies(self) -> bool:
        """Install missing dependencies if needed"""
        try:
            logger.info("Checking for missing dependencies...")
            
            # Try to import the server module
            try:
                import mcp_server
                logger.info("MCP Server module available")
                return True
            except ImportError:
                logger.info("MCP Server module not available, installing dependencies...")
                return self.install_dependencies()
                
        except Exception as e:
            logger.error(f"Error installing missing dependencies: {e}")
            return False

def main():
    """Main startup function"""
    logger.info("Spun Web Archive Forge MCP Server Startup")
    logger.info("=" * 50)
    
    startup = MCPServerStartup()
    
    # Run startup checks
    if not startup.run_startup_checks():
        logger.error("Startup checks failed, exiting")
        sys.exit(1)
    
    # Install missing dependencies if needed
    if not startup.install_missing_dependencies():
        logger.error("Failed to install dependencies, exiting")
        sys.exit(1)
    
    # Start the server
    if not startup.start_server():
        logger.error("Failed to start server, exiting")
        sys.exit(1)
    
    logger.info("MCP Server startup completed successfully")
    logger.info("Server is running and ready to accept connections")

if __name__ == "__main__":
    main()
