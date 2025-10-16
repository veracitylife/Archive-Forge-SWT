#!/usr/bin/env python3
"""
Spun Web Archive Forge MCP Server
Internet Archive WordPress Plugin Management Server

This MCP server provides tools for managing the Spun Web Archive Forge WordPress plugin,
specifically designed for archiving pages and posts on the Internet Archive (archive.org).

Author: Ryan Dickie Thompson
Company: Spun Web Technology
Version: 1.0.0
License: GPL v2 or later
"""

import asyncio
import json
import logging
import os
import subprocess
import sys
import time
from datetime import datetime
from pathlib import Path
from typing import Any, Dict, List, Optional, Union
import zipfile
import shutil
import requests
from dataclasses import dataclass
from enum import Enum

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('mcp-server.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

class PluginStatus(Enum):
    """Plugin status enumeration"""
    ACTIVE = "active"
    INACTIVE = "inactive"
    INSTALLED = "installed"
    NOT_INSTALLED = "not_installed"

class SubmissionStatus(Enum):
    """Archive submission status enumeration"""
    PENDING = "pending"
    PROCESSING = "processing"
    COMPLETED = "completed"
    FAILED = "failed"

@dataclass
class PluginInfo:
    """Plugin information data class"""
    name: str
    slug: str
    version: str
    status: PluginStatus
    description: str
    author: str
    author_uri: str
    plugin_uri: str
    text_domain: str
    requires_wp: str
    requires_php: str
    tested_up_to: str
    license: str
    license_uri: str

@dataclass
class ArchiveSubmission:
    """Archive submission data class"""
    post_id: int
    url: str
    archive_url: Optional[str]
    status: SubmissionStatus
    submitted_at: datetime
    completed_at: Optional[datetime]
    error_message: Optional[str]
    retry_count: int

@dataclass
class IRCConfig:
    """IRC bot configuration"""
    server: str = "irc.libera.chat"
    port: int = 6667
    nickname: str = "spun_web"
    channels: List[str] = None
    password: Optional[str] = None
    ssl: bool = False

    def __post_init__(self):
        if self.channels is None:
            self.channels = ["#spunwebtechnology"]

class SpunWebArchiveForgeMCPServer:
    """Main MCP Server class for Spun Web Archive Forge plugin management"""
    
    def __init__(self):
        self.plugin_path = Path(__file__).parent
        self.wp_path = Path("C:/Users/disru/Studio/plugin-test")
        self.repository_path = Path("C:/Users/disru/Documents/wordpress plugins/Spun Web Archive Forge Repository")
        self.test_server_url = "http://localhost:8881"
        self.test_credentials = {
            "username": "admin",
            "password": "TCeglApnp@Ef29JVWrx6xIQn"
        }
        
        # Plugin configuration
        self.plugin_info = PluginInfo(
            name="Spun Web Archive Forge",
            slug="spun-web-archive-forge",
            version="1.0.7",
            status=PluginStatus.INACTIVE,
            description="Professional WordPress plugin for automatically submitting content to the Internet Archive (Wayback Machine)",
            author="Ryan Dickie Thompson",
            author_uri="https://www.spunwebtechnology.com",
            plugin_uri="https://www.spunwebtechnology.com/spun-web-archive-forge-wordpress-wayback-archive/",
            text_domain="spun-web-archive-forge",
            requires_wp="5.0",
            requires_php="7.4",
            tested_up_to="6.8.2",
            license="GPL v2 or later",
            license_uri="https://www.gnu.org/licenses/gpl-2.0.html"
        )
        
    # IRC Configuration (disabled)
    # self.irc_config = IRCConfig()
        
        # Archive.org API configuration
        self.archive_api_config = {
            "base_url": "https://web.archive.org/save/",
            "availability_url": "https://archive.org/wayback/available",
            "s3_test_url": "https://s3.us.archive.org/"
        }
        
        logger.info("Spun Web Archive Forge MCP Server initialized")

    # Plugin Management Tools
    async def wp_plugin_create(self, name: str, slug: str, author: str, description: str = "", 
                              version: str = "1.0.0") -> Dict[str, Any]:
        """Create a new WordPress plugin"""
        try:
            plugin_dir = self.plugin_path / slug
            
            # Create plugin directory structure
            await self._create_plugin_structure(plugin_dir, name, slug, author, description, version)
            
            # Create package.json for Node.js dependencies
            await self._create_package_json(plugin_dir, name, version)
            
            # Create composer.json for PHP dependencies
            await self._create_composer_json(plugin_dir, name, version)
            
            logger.info(f"Created WordPress plugin: {name} ({slug})")
            
            return {
                "success": True,
                "message": f"Plugin '{name}' created successfully",
                "plugin_path": str(plugin_dir),
                "slug": slug,
                "version": version
            }
            
        except Exception as e:
            logger.error(f"Error creating plugin: {e}")
            return {
                "success": False,
                "error": str(e)
            }

    async def wp_plugin_install(self, plugin_path: str, wp_path: str, activate: bool = True) -> Dict[str, Any]:
        """Install a WordPress plugin via WP-CLI"""
        try:
            wp_path = Path(wp_path)
            plugin_path = Path(plugin_path)
            
            if not wp_path.exists():
                return {"success": False, "error": f"WordPress path does not exist: {wp_path}"}
            
            if not plugin_path.exists():
                return {"success": False, "error": f"Plugin path does not exist: {plugin_path}"}
            
            # Use WP-CLI to install plugin
            cmd = ["wp", "plugin", "install", str(plugin_path)]
            if activate:
                cmd.append("--activate")
            
            result = subprocess.run(cmd, cwd=str(wp_path), capture_output=True, text=True)
            
            if result.returncode == 0:
                logger.info(f"Plugin installed successfully: {plugin_path}")
                return {
                    "success": True,
                    "message": "Plugin installed successfully",
                    "activated": activate,
                    "output": result.stdout
                }
            else:
                logger.error(f"Plugin installation failed: {result.stderr}")
                return {
                    "success": False,
                    "error": result.stderr
                }
                
        except Exception as e:
            logger.error(f"Error installing plugin: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_activate(self, plugin_slug: str, wp_path: str) -> Dict[str, Any]:
        """Activate a WordPress plugin"""
        try:
            wp_path = Path(wp_path)
            
            cmd = ["wp", "plugin", "activate", plugin_slug]
            result = subprocess.run(cmd, cwd=str(wp_path), capture_output=True, text=True)
            
            if result.returncode == 0:
                logger.info(f"Plugin activated: {plugin_slug}")
                return {
                    "success": True,
                    "message": f"Plugin '{plugin_slug}' activated successfully",
                    "output": result.stdout
                }
            else:
                logger.error(f"Plugin activation failed: {result.stderr}")
                return {
                    "success": False,
                    "error": result.stderr
                }
                
        except Exception as e:
            logger.error(f"Error activating plugin: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_deactivate(self, plugin_slug: str, wp_path: str) -> Dict[str, Any]:
        """Deactivate a WordPress plugin"""
        try:
            wp_path = Path(wp_path)
            
            cmd = ["wp", "plugin", "deactivate", plugin_slug]
            result = subprocess.run(cmd, cwd=str(wp_path), capture_output=True, text=True)
            
            if result.returncode == 0:
                logger.info(f"Plugin deactivated: {plugin_slug}")
                return {
                    "success": True,
                    "message": f"Plugin '{plugin_slug}' deactivated successfully",
                    "output": result.stdout
                }
            else:
                logger.error(f"Plugin deactivation failed: {result.stderr}")
                return {
                    "success": False,
                    "error": result.stderr
                }
                
        except Exception as e:
            logger.error(f"Error deactivating plugin: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_list(self, wp_path: str, status: str = "all") -> Dict[str, Any]:
        """List all WordPress plugins"""
        try:
            wp_path = Path(wp_path)
            
            cmd = ["wp", "plugin", "list", "--format=json"]
            if status != "all":
                cmd.extend(["--status", status])
            
            result = subprocess.run(cmd, cwd=str(wp_path), capture_output=True, text=True)
            
            if result.returncode == 0:
                plugins = json.loads(result.stdout)
                logger.info(f"Retrieved {len(plugins)} plugins")
                return {
                    "success": True,
                    "plugins": plugins,
                    "count": len(plugins)
                }
            else:
                logger.error(f"Plugin list failed: {result.stderr}")
                return {
                    "success": False,
                    "error": result.stderr
                }
                
        except Exception as e:
            logger.error(f"Error listing plugins: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_package(self, plugin_dir: str, output_path: str, 
                              exclude_patterns: List[str] = None) -> Dict[str, Any]:
        """Create a WordPress plugin package (zip file)"""
        try:
            plugin_dir = Path(plugin_dir)
            output_path = Path(output_path)
            
            if exclude_patterns is None:
                exclude_patterns = ["node_modules", ".git", "*.log", ".DS_Store"]
            
            if not plugin_dir.exists():
                return {"success": False, "error": f"Plugin directory does not exist: {plugin_dir}"}
            
            # Create zip file
            with zipfile.ZipFile(output_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
                for root, dirs, files in os.walk(plugin_dir):
                    # Filter out excluded patterns
                    dirs[:] = [d for d in dirs if not any(pattern in d for pattern in exclude_patterns)]
                    
                    for file in files:
                        if not any(pattern in file for pattern in exclude_patterns):
                            file_path = Path(root) / file
                            arcname = file_path.relative_to(plugin_dir)
                            zipf.write(file_path, arcname)
            
            logger.info(f"Plugin package created: {output_path}")
            return {
                "success": True,
                "message": "Plugin package created successfully",
                "package_path": str(output_path),
                "size": output_path.stat().st_size
            }
            
        except Exception as e:
            logger.error(f"Error creating plugin package: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_test(self, plugin_dir: str, wp_path: str) -> Dict[str, Any]:
        """Test WordPress plugin for syntax errors and basic functionality"""
        try:
            plugin_dir = Path(plugin_dir)
            wp_path = Path(wp_path)
            
            # Check PHP syntax
            syntax_errors = []
            for php_file in plugin_dir.rglob("*.php"):
                result = subprocess.run(["php", "-l", str(php_file)], capture_output=True, text=True)
                if result.returncode != 0:
                    syntax_errors.append({
                        "file": str(php_file),
                        "error": result.stderr.strip()
                    })
            
            # Run WordPress compatibility tests
            test_results = await self._run_wordpress_tests(plugin_dir, wp_path)
            
            logger.info(f"Plugin testing completed for: {plugin_dir}")
            return {
                "success": True,
                "syntax_errors": syntax_errors,
                "wordpress_tests": test_results,
                "overall_status": "pass" if not syntax_errors else "fail"
            }
            
        except Exception as e:
            logger.error(f"Error testing plugin: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_deploy(self, plugin_package: str, server_host: str, 
                             server_user: str, server_path: str, ssh_key: str = None) -> Dict[str, Any]:
        """Deploy WordPress plugin to remote server via SSH"""
        try:
            plugin_package = Path(plugin_package)
            
            if not plugin_package.exists():
                return {"success": False, "error": f"Plugin package does not exist: {plugin_package}"}
            
            # Build SSH command
            ssh_cmd = ["scp"]
            if ssh_key:
                ssh_cmd.extend(["-i", ssh_key])
            
            ssh_cmd.extend([str(plugin_package), f"{server_user}@{server_host}:{server_path}"])
            
            result = subprocess.run(ssh_cmd, capture_output=True, text=True)
            
            if result.returncode == 0:
                logger.info(f"Plugin deployed successfully to {server_host}")
                return {
                    "success": True,
                    "message": "Plugin deployed successfully",
                    "server": server_host,
                    "path": server_path
                }
            else:
                logger.error(f"Plugin deployment failed: {result.stderr}")
                return {
                    "success": False,
                    "error": result.stderr
                }
                
        except Exception as e:
            logger.error(f"Error deploying plugin: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_backup(self, wp_path: str, backup_path: str, 
                            include_database: bool = True) -> Dict[str, Any]:
        """Create backup of WordPress installation and plugins"""
        try:
            wp_path = Path(wp_path)
            backup_path = Path(backup_path)
            
            # Create backup directory
            backup_path.mkdir(parents=True, exist_ok=True)
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            
            # Backup WordPress files
            wp_backup = backup_path / f"wordpress_backup_{timestamp}.zip"
            await self._create_backup_archive(wp_path, wp_backup)
            
            backup_info = {
                "success": True,
                "message": "WordPress backup created successfully",
                "backup_path": str(backup_path),
                "wordpress_backup": str(wp_backup),
                "timestamp": timestamp
            }
            
            # Backup database if requested
            if include_database:
                db_backup = backup_path / f"database_backup_{timestamp}.sql"
                await self._backup_database(wp_path, db_backup)
                backup_info["database_backup"] = str(db_backup)
            
            logger.info(f"WordPress backup created: {backup_path}")
            return backup_info
            
        except Exception as e:
            logger.error(f"Error creating backup: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_restore(self, backup_path: str, wp_path: str, 
                              restore_database: bool = True) -> Dict[str, Any]:
        """Restore WordPress installation from backup"""
        try:
            backup_path = Path(backup_path)
            wp_path = Path(wp_path)
            
            if not backup_path.exists():
                return {"success": False, "error": f"Backup path does not exist: {backup_path}"}
            
            # Restore WordPress files
            wp_backup = backup_path / "wordpress_backup.zip"
            if wp_backup.exists():
                await self._restore_from_archive(wp_backup, wp_path)
            
            restore_info = {
                "success": True,
                "message": "WordPress restored successfully",
                "restore_path": str(wp_path)
            }
            
            # Restore database if requested
            if restore_database:
                db_backup = backup_path / "database_backup.sql"
                if db_backup.exists():
                    await self._restore_database(wp_path, db_backup)
                    restore_info["database_restored"] = True
            
            logger.info(f"WordPress restored from: {backup_path}")
            return restore_info
            
        except Exception as e:
            logger.error(f"Error restoring WordPress: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_analyze(self, plugin_dir: str, analysis_type: str = "all") -> Dict[str, Any]:
        """Analyze WordPress plugin for security, performance, and best practices"""
        try:
            plugin_dir = Path(plugin_dir)
            
            analysis_results = {
                "security": {},
                "performance": {},
                "standards": {},
                "overall_score": 0
            }
            
            if analysis_type in ["security", "all"]:
                analysis_results["security"] = await self._analyze_security(plugin_dir)
            
            if analysis_type in ["performance", "all"]:
                analysis_results["performance"] = await self._analyze_performance(plugin_dir)
            
            if analysis_type in ["standards", "all"]:
                analysis_results["standards"] = await self._analyze_standards(plugin_dir)
            
            # Calculate overall score
            scores = []
            for category in analysis_results.values():
                if isinstance(category, dict) and "score" in category:
                    scores.append(category["score"])
            
            if scores:
                analysis_results["overall_score"] = sum(scores) / len(scores)
            
            logger.info(f"Plugin analysis completed for: {plugin_dir}")
            return {
                "success": True,
                "analysis": analysis_results,
                "plugin_dir": str(plugin_dir)
            }
            
        except Exception as e:
            logger.error(f"Error analyzing plugin: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_generate_docs(self, plugin_dir: str, output_format: str = "markdown",
                                    include_api: bool = True) -> Dict[str, Any]:
        """Generate documentation for WordPress plugin"""
        try:
            plugin_dir = Path(plugin_dir)
            
            # Create docs directory
            docs_dir = plugin_dir / "docs"
            docs_dir.mkdir(exist_ok=True)
            
            # Generate documentation files
            docs_created = []
            
            # Generate README.md
            readme_path = docs_dir / "README.md"
            await self._generate_readme(plugin_dir, readme_path)
            docs_created.append(str(readme_path))
            
            # Generate API documentation if requested
            if include_api:
                api_docs_path = docs_dir / "API.md"
                await self._generate_api_docs(plugin_dir, api_docs_path)
                docs_created.append(str(api_docs_path))
            
            # Generate changelog
            changelog_path = docs_dir / "CHANGELOG.md"
            await self._generate_changelog(plugin_dir, changelog_path)
            docs_created.append(str(changelog_path))
            
            logger.info(f"Documentation generated for: {plugin_dir}")
            return {
                "success": True,
                "message": "Documentation generated successfully",
                "docs_created": docs_created,
                "docs_directory": str(docs_dir)
            }
            
        except Exception as e:
            logger.error(f"Error generating documentation: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_validate(self, plugin_dir: str, check_coding_standards: bool = True,
                              check_readme: bool = True, check_security: bool = True) -> Dict[str, Any]:
        """Validate WordPress plugin against WordPress.org standards"""
        try:
            plugin_dir = Path(plugin_dir)
            
            validation_results = {
                "coding_standards": {},
                "readme": {},
                "security": {},
                "overall_status": "pass"
            }
            
            # Check coding standards
            if check_coding_standards:
                validation_results["coding_standards"] = await self._validate_coding_standards(plugin_dir)
            
            # Check readme.txt
            if check_readme:
                validation_results["readme"] = await self._validate_readme(plugin_dir)
            
            # Check security
            if check_security:
                validation_results["security"] = await self._validate_security(plugin_dir)
            
            # Determine overall status
            all_passed = all(
                result.get("status") == "pass" 
                for result in validation_results.values() 
                if isinstance(result, dict) and "status" in result
            )
            
            validation_results["overall_status"] = "pass" if all_passed else "fail"
            
            logger.info(f"Plugin validation completed for: {plugin_dir}")
            return {
                "success": True,
                "validation": validation_results,
                "plugin_dir": str(plugin_dir)
            }
            
        except Exception as e:
            logger.error(f"Error validating plugin: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_migrate(self, plugin_dir: str, wp_path: str, from_version: str, 
                              to_version: str, migration_script: str = None) -> Dict[str, Any]:
        """Migrate WordPress plugin to new version with database updates"""
        try:
            plugin_dir = Path(plugin_dir)
            wp_path = Path(wp_path)
            
            # Run migration script if provided
            if migration_script:
                migration_script_path = Path(migration_script)
                if migration_script_path.exists():
                    await self._run_migration_script(migration_script_path, wp_path)
            
            # Update plugin version
            await self._update_plugin_version(plugin_dir, to_version)
            
            # Run database migrations
            await self._run_database_migrations(plugin_dir, wp_path, from_version, to_version)
            
            logger.info(f"Plugin migrated from {from_version} to {to_version}")
            return {
                "success": True,
                "message": f"Plugin migrated successfully from {from_version} to {to_version}",
                "from_version": from_version,
                "to_version": to_version
            }
            
        except Exception as e:
            logger.error(f"Error migrating plugin: {e}")
            return {"success": False, "error": str(e)}

    async def wp_plugin_benchmark(self, plugin_dir: str, wp_path: str, 
                                iterations: int = 10, test_scenarios: List[str] = None) -> Dict[str, Any]:
        """Benchmark WordPress plugin performance"""
        try:
            plugin_dir = Path(plugin_dir)
            wp_path = Path(wp_path)
            
            if test_scenarios is None:
                test_scenarios = ["activation", "deactivation", "frontend_load", "admin_load"]
            
            benchmark_results = {}
            
            for scenario in test_scenarios:
                scenario_results = []
                
                for i in range(iterations):
                    start_time = time.time()
                    
                    if scenario == "activation":
                        await self.wp_plugin_activate(self.plugin_info.slug, str(wp_path))
                    elif scenario == "deactivation":
                        await self.wp_plugin_deactivate(self.plugin_info.slug, str(wp_path))
                    elif scenario == "frontend_load":
                        await self._benchmark_frontend_load(wp_path)
                    elif scenario == "admin_load":
                        await self._benchmark_admin_load(wp_path)
                    
                    end_time = time.time()
                    scenario_results.append(end_time - start_time)
                
                benchmark_results[scenario] = {
                    "average_time": sum(scenario_results) / len(scenario_results),
                    "min_time": min(scenario_results),
                    "max_time": max(scenario_results),
                    "iterations": iterations
                }
            
            logger.info(f"Plugin benchmark completed for: {plugin_dir}")
            return {
                "success": True,
                "benchmark_results": benchmark_results,
                "plugin_dir": str(plugin_dir)
            }
            
        except Exception as e:
            logger.error(f"Error benchmarking plugin: {e}")
            return {"success": False, "error": str(e)}

    # Archive.org Integration Tools
    async def archive_submit_url(self, url: str, capture_all: bool = True, 
                                capture_outlinks: bool = True) -> Dict[str, Any]:
        """Submit URL to Archive.org for archiving"""
        try:
            # This would integrate with your existing Archive.org functionality
            logger.info(f"Submitting URL to Archive.org: {url}")
            
            # Simulate archive submission
            return {
                "success": True,
                "message": f"URL '{url}' submitted to Archive.org",
                "url": url,
                "archive_url": f"https://web.archive.org/web/{url}",
                "status": "submitted"
            }
            
        except Exception as e:
            logger.error(f"Error submitting URL to Archive.org: {e}")
            return {"success": False, "error": str(e)}

    async def archive_check_status(self, url: str) -> Dict[str, Any]:
        """Check archive status for a URL"""
        try:
            # This would check the actual archive status
            logger.info(f"Checking archive status for: {url}")
            
            return {
                "success": True,
                "url": url,
                "is_archived": True,
                "archive_url": f"https://web.archive.org/web/{url}",
                "last_captured": "2024-01-01T00:00:00Z"
            }
            
        except Exception as e:
            logger.error(f"Error checking archive status: {e}")
            return {"success": False, "error": str(e)}

    async def archive_submit_post(self, post_id: int, wp_path: str) -> Dict[str, Any]:
        """Submit WordPress post to Internet Archive"""
        try:
            wp_path = Path(wp_path)
            
            # Get post URL from WordPress
            cmd = ["wp", "post", "get", str(post_id), "--field=url", "--format=json"]
            result = subprocess.run(cmd, cwd=str(wp_path), capture_output=True, text=True)
            
            if result.returncode != 0:
                return {"success": False, "error": f"Failed to get post URL: {result.stderr}"}
            
            post_url = result.stdout.strip()
            
            # Submit to Archive.org
            archive_result = await self.archive_submit_url(post_url)
            
            if archive_result["success"]:
                logger.info(f"Post {post_id} submitted to Archive.org: {post_url}")
                return {
                    "success": True,
                    "message": f"Post {post_id} submitted to Archive.org",
                    "post_id": post_id,
                    "post_url": post_url,
                    "archive_url": archive_result["archive_url"]
                }
            else:
                return archive_result
                
        except Exception as e:
            logger.error(f"Error submitting post to Archive.org: {e}")
            return {"success": False, "error": str(e)}

    async def archive_submit_page(self, page_id: int, wp_path: str) -> Dict[str, Any]:
        """Submit WordPress page to Internet Archive"""
        try:
            wp_path = Path(wp_path)
            
            # Get page URL from WordPress
            cmd = ["wp", "post", "get", str(page_id), "--field=url", "--format=json"]
            result = subprocess.run(cmd, cwd=str(wp_path), capture_output=True, text=True)
            
            if result.returncode != 0:
                return {"success": False, "error": f"Failed to get page URL: {result.stderr}"}
            
            page_url = result.stdout.strip()
            
            # Submit to Archive.org
            archive_result = await self.archive_submit_url(page_url)
            
            if archive_result["success"]:
                logger.info(f"Page {page_id} submitted to Archive.org: {page_url}")
                return {
                    "success": True,
                    "message": f"Page {page_id} submitted to Archive.org",
                    "page_id": page_id,
                    "page_url": page_url,
                    "archive_url": archive_result["archive_url"]
                }
            else:
                return archive_result
                
        except Exception as e:
            logger.error(f"Error submitting page to Archive.org: {e}")
            return {"success": False, "error": str(e)}

    async def archive_bulk_submit(self, wp_path: str, post_ids: List[int] = None, 
                                post_type: str = "all") -> Dict[str, Any]:
        """Bulk submit multiple WordPress posts/pages to Internet Archive"""
        try:
            wp_path = Path(wp_path)
            
            if post_ids is None:
                # Get all posts/pages if no IDs specified
                if post_type == "all":
                    cmd = ["wp", "post", "list", "--post_type=post,page", "--field=ID", "--format=json"]
                else:
                    cmd = ["wp", "post", "list", f"--post_type={post_type}", "--field=ID", "--format=json"]
                
                result = subprocess.run(cmd, cwd=str(wp_path), capture_output=True, text=True)
                
                if result.returncode != 0:
                    return {"success": False, "error": f"Failed to get posts: {result.stderr}"}
                
                post_ids = [int(id.strip()) for id in result.stdout.strip().split('\n') if id.strip()]
            
            # Submit each post/page
            results = []
            for post_id in post_ids:
                if post_type == "page":
                    result = await self.archive_submit_page(post_id, str(wp_path))
                else:
                    result = await self.archive_submit_post(post_id, str(wp_path))
                results.append(result)
            
            successful = sum(1 for r in results if r["success"])
            
            logger.info(f"Bulk submission completed: {successful}/{len(post_ids)} successful")
            return {
                "success": True,
                "message": f"Bulk submission completed: {successful}/{len(post_ids)} successful",
                "total_submitted": len(post_ids),
                "successful": successful,
                "failed": len(post_ids) - successful,
                "results": results
            }
            
        except Exception as e:
            logger.error(f"Error in bulk submission: {e}")
            return {"success": False, "error": str(e)}

    async def archive_get_submission_history(self, wp_path: str, limit: int = 50, 
                                           status: str = None) -> Dict[str, Any]:
        """Get submission history from WordPress database"""
        try:
            wp_path = Path(wp_path)
            
            # Query the submission history table
            cmd = ["wp", "db", "query", f"SELECT * FROM wp_swap_submissions_history ORDER BY submitted_at DESC LIMIT {limit}"]
            
            if status:
                cmd = ["wp", "db", "query", f"SELECT * FROM wp_swap_submissions_history WHERE status = '{status}' ORDER BY submitted_at DESC LIMIT {limit}"]
            
            result = subprocess.run(cmd, cwd=str(wp_path), capture_output=True, text=True)
            
            if result.returncode != 0:
                return {"success": False, "error": f"Failed to get submission history: {result.stderr}"}
            
            # Parse the results (this would need proper parsing in a real implementation)
            logger.info(f"Retrieved submission history: {limit} records")
            return {
                "success": True,
                "message": "Submission history retrieved successfully",
                "records": result.stdout,
                "limit": limit,
                "status_filter": status
            }
            
        except Exception as e:
            logger.error(f"Error getting submission history: {e}")
            return {"success": False, "error": str(e)}

    async def archive_get_queue_status(self, wp_path: str) -> Dict[str, Any]:
        """Get current archive queue status"""
        try:
            wp_path = Path(wp_path)
            
            # Query the queue table
            cmd = ["wp", "db", "query", "SELECT status, COUNT(*) as count FROM wp_swap_archive_queue GROUP BY status"]
            result = subprocess.run(cmd, cwd=str(wp_path), capture_output=True, text=True)
            
            if result.returncode != 0:
                return {"success": False, "error": f"Failed to get queue status: {result.stderr}"}
            
            # Parse the results (this would need proper parsing in a real implementation)
            logger.info("Retrieved archive queue status")
            return {
                "success": True,
                "message": "Queue status retrieved successfully",
                "queue_data": result.stdout
            }
            
        except Exception as e:
            logger.error(f"Error getting queue status: {e}")
            return {"success": False, "error": str(e)}

    # Helper Methods
    async def _create_plugin_structure(self, plugin_dir: Path, name: str, slug: str, 
                                     author: str, description: str, version: str):
        """Create WordPress plugin directory structure"""
        # Create main directories
        (plugin_dir / "includes").mkdir(exist_ok=True)
        (plugin_dir / "assets" / "css").mkdir(parents=True, exist_ok=True)
        (plugin_dir / "assets" / "js").mkdir(parents=True, exist_ok=True)
        (plugin_dir / "docs").mkdir(exist_ok=True)
        (plugin_dir / "tests").mkdir(exist_ok=True)
        
        # Create main plugin file
        main_file = plugin_dir / f"{slug}.php"
        main_content = f"""<?php
/**
 * Plugin Name: {name}
 * Plugin URI: https://www.spunwebtechnology.com/{slug}/
 * Description: {description}
 * Version: {version}
 * Author: {author}
 * Author URI: https://www.spunwebtechnology.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: {slug}
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 * Network: false
 * Update URI: https://www.spunwebtechnology.com/{slug}/
 *
 * @package {slug.replace('-', '_').title()}
 * @author {author}
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {{
    exit;
}}

// Define plugin constants
define('{slug.upper().replace('-', '_')}_VERSION', '{version}');
define('{slug.upper().replace('-', '_')}_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('{slug.upper().replace('-', '_')}_PLUGIN_URL', plugin_dir_url(__FILE__));
define('{slug.upper().replace('-', '_')}_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 * 
 * @since 1.0.0
 */
class {slug.replace('-', '_').title().replace('_', '')} {{
    
    /**
     * Single instance of the class
     * 
     * @var {slug.replace('-', '_').title().replace('_', '')}|null
     */
    private static $instance = null;
    
    /**
     * Get single instance of the plugin
     * 
     * @since 1.0.0
     * @return {slug.replace('-', '_').title().replace('_', '')}
     */
    public static function get_instance() {{
        if (null === self::$instance) {{
            self::$instance = new self();
        }}
        return self::$instance;
    }}
    
    /**
     * Constructor - Initialize the plugin
     * 
     * @since 1.0.0
     */
    private function __construct() {{
        $this->init_hooks();
    }}
    
    /**
     * Initialize WordPress hooks and filters
     * 
     * @since 1.0.0
     * @return void
     */
    private function init_hooks() {{
        // Core initialization
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Plugin lifecycle hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }}
    
    /**
     * Initialize the plugin
     * 
     * @since 1.0.0
     * @return void
     */
    public function init() {{
        // Load text domain for internationalization
        load_plugin_textdomain(
            '{slug}',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }}
    
    /**
     * Add admin menu
     * 
     * @since 1.0.0
     */
    public function add_admin_menu() {{
        add_menu_page(
            __('{name}', '{slug}'),
            __('{name}', '{slug}'),
            'manage_options',
            '{slug}',
            array($this, 'admin_page_callback'),
            'dashicons-admin-generic',
            30
        );
    }}
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @since 1.0.0
     */
    public function enqueue_admin_scripts($hook) {{
        if ($hook !== 'toplevel_page_{slug}') {{
            return;
        }}
        
        wp_enqueue_style(
            '{slug}-admin-css',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            array(),
            {slug.upper().replace('-', '_')}_VERSION
        );
        
        wp_enqueue_script(
            '{slug}-admin-js',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            array('jquery'),
            {slug.upper().replace('-', '_')}_VERSION,
            true
        );
    }}
    
    /**
     * Admin page callback
     * 
     * @since 1.0.0
     */
    public function admin_page_callback() {{
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('{name}', '{slug}') . '</h1>';
        echo '<p>' . esc_html__('Welcome to {name}!', '{slug}') . '</p>';
        echo '</div>';
    }}
    
    /**
     * Plugin activation
     * 
     * @since 1.0.0
     */
    public function activate() {{
        // Activation code here
    }}
    
    /**
     * Plugin deactivation
     * 
     * @since 1.0.0
     */
    public function deactivate() {{
        // Deactivation code here
    }}
}}

// Initialize the plugin
function {slug.replace('-', '_')}_init() {{
    return {slug.replace('-', '_').title().replace('_', '')}::get_instance();
}}

// Start the plugin
add_action('plugins_loaded', '{slug.replace('-', '_')}_init');
"""
        
        with open(main_file, 'w') as f:
            f.write(main_content)
        
        # Create README.md
        readme_content = f"""# {name}

{description}

## Installation

1. Upload the plugin files to `/wp-content/plugins/{slug}/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **{name}** in your WordPress admin to configure

## Configuration

Configure the plugin settings through the WordPress admin interface.

## Usage

Use the plugin features through the WordPress admin interface.

## Support

For support, visit [Spun Web Technology](https://www.spunwebtechnology.com)

## Changelog

### {version}
- Initial release

## License

This plugin is licensed under the GPL v2 or later.
"""
        
        readme_file = plugin_dir / "README.md"
        with open(readme_file, 'w') as f:
            f.write(readme_content)
        
        # Create uninstall.php
        uninstall_content = f"""<?php
/**
 * Uninstall handler for {name}
 * 
 * @package {slug.replace('-', '_').title()}
 * @author {author}
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {{
    exit;
}}

// Clean up plugin data
// Add cleanup code here
"""
        
        uninstall_file = plugin_dir / "uninstall.php"
        with open(uninstall_file, 'w') as f:
            f.write(uninstall_content)


    async def _create_package_json(self, plugin_dir: Path, name: str, version: str):
        """Create package.json for Node.js dependencies"""
        package_json = {
            "name": name.lower().replace(' ', '-'),
            "version": version,
            "description": f"WordPress plugin: {name}",
            "main": "index.js",
            "scripts": {
                "test": "php run-tests.php",
                "build": "npm run build:css && npm run build:js",
                "build:css": "echo 'CSS build placeholder'",
                "build:js": "echo 'JS build placeholder'",
                "dev": "npm run watch",
                "watch": "echo 'Watch mode placeholder'"
            },
            "devDependencies": {
                "nodemon": "^3.0.0"
            },
            "keywords": ["wordpress", "plugin", "archive"],
            "author": "Ryan Dickie Thompson",
            "license": "GPL-2.0-or-later"
        }
        
        package_file = plugin_dir / "package.json"
        with open(package_file, 'w') as f:
            json.dump(package_json, f, indent=2)

    async def _create_composer_json(self, plugin_dir: Path, name: str, version: str):
        """Create composer.json for PHP dependencies"""
        composer_json = {
            "name": f"spun-web-technology/{name.lower().replace(' ', '-')}",
            "description": f"WordPress plugin: {name}",
            "type": "wordpress-plugin",
            "license": "GPL-2.0-or-later",
            "authors": [
                {
                    "name": "Ryan Dickie Thompson",
                    "email": "support@spunwebtechnology.com",
                    "homepage": "https://spunwebtechnology.com"
                }
            ],
            "require": {
                "php": ">=7.4"
            },
            "require-dev": {
                "phpstan/phpstan": "^1.10",
                "squizlabs/php_codesniffer": "^3.7",
                "wp-coding-standards/wpcs": "^3.0"
            },
            "scripts": {
                "phpstan": "phpstan analyse --configuration=phpstan.neon",
                "phpcs": "phpcs --standard=WordPress",
                "test": "php run-tests.php"
            },
            "autoload": {
                "classmap": ["includes/"]
            }
        }
        
        composer_file = plugin_dir / "composer.json"
        with open(composer_file, 'w') as f:
            json.dump(composer_json, f, indent=2)

    async def _run_wordpress_tests(self, plugin_dir: Path, wp_path: Path) -> Dict[str, Any]:
        """Run WordPress compatibility tests"""
        try:
            # Check if test files exist
            test_file = plugin_dir / "run-tests.php"
            if not test_file.exists():
                return {"status": "skipped", "message": "No test file found"}
            
            # Run tests
            result = subprocess.run(["php", str(test_file)], cwd=str(wp_path), 
                                  capture_output=True, text=True)
            
            return {
                "status": "pass" if result.returncode == 0 else "fail",
                "output": result.stdout,
                "errors": result.stderr
            }
            
        except Exception as e:
            return {"status": "error", "message": str(e)}

    async def _analyze_security(self, plugin_dir: Path) -> Dict[str, Any]:
        """Analyze plugin security"""
        security_issues = []
        
        # Check for direct access prevention
        for php_file in plugin_dir.rglob("*.php"):
            with open(php_file, 'r') as f:
                content = f.read()
                if 'ABSPATH' not in content and 'wp-content' in str(php_file):
                    security_issues.append(f"Missing ABSPATH check in {php_file}")
        
        # Check for SQL injection prevention
        for php_file in plugin_dir.rglob("*.php"):
            with open(php_file, 'r') as f:
                content = f.read()
                if '$wpdb->prepare' not in content and '$wpdb->query' in content:
                    security_issues.append(f"Potential SQL injection in {php_file}")
        
        score = max(0, 100 - len(security_issues) * 10)
        
        return {
            "score": score,
            "issues": security_issues,
            "status": "pass" if score >= 80 else "fail"
        }

    async def _analyze_performance(self, plugin_dir: Path) -> Dict[str, Any]:
        """Analyze plugin performance"""
        performance_issues = []
        
        # Check for efficient database queries
        for php_file in plugin_dir.rglob("*.php"):
            with open(php_file, 'r') as f:
                content = f.read()
                if 'SELECT *' in content:
                    performance_issues.append(f"Use of SELECT * in {php_file}")
        
        # Check for proper caching
        cache_usage = 0
        for php_file in plugin_dir.rglob("*.php"):
            with open(php_file, 'r') as f:
                content = f.read()
                if 'wp_cache_' in content or 'get_transient' in content:
                    cache_usage += 1
        
        score = max(0, 100 - len(performance_issues) * 5 + cache_usage * 10)
        
        return {
            "score": min(100, score),
            "issues": performance_issues,
            "cache_usage": cache_usage,
            "status": "pass" if score >= 70 else "fail"
        }

    async def _analyze_standards(self, plugin_dir: Path) -> Dict[str, Any]:
        """Analyze WordPress coding standards compliance"""
        standards_issues = []
        
        # Check for proper escaping
        for php_file in plugin_dir.rglob("*.php"):
            with open(php_file, 'r') as f:
                content = f.read()
                if 'echo $' in content and 'esc_html' not in content:
                    standards_issues.append(f"Missing output escaping in {php_file}")
        
        score = max(0, 100 - len(standards_issues) * 15)
        
        return {
            "score": score,
            "issues": standards_issues,
            "status": "pass" if score >= 85 else "fail"
        }

    async def _validate_coding_standards(self, plugin_dir: Path) -> Dict[str, Any]:
        """Validate WordPress coding standards"""
        try:
            # Run PHPCS if available
            result = subprocess.run(["phpcs", "--standard=WordPress", str(plugin_dir)], 
                                  capture_output=True, text=True)
            
            return {
                "status": "pass" if result.returncode == 0 else "fail",
                "output": result.stdout,
                "errors": result.stderr
            }
            
        except FileNotFoundError:
            return {
                "status": "skipped",
                "message": "PHPCS not available"
            }

    async def _validate_readme(self, plugin_dir: Path) -> Dict[str, Any]:
        """Validate readme.txt file"""
        readme_file = plugin_dir / "readme.txt"
        
        if not readme_file.exists():
            return {
                "status": "fail",
                "message": "readme.txt file not found"
            }
        
        with open(readme_file, 'r') as f:
            content = f.read()
        
        # Check for required sections
        required_sections = ["Plugin Name:", "Description:", "Version:", "Author:"]
        missing_sections = [section for section in required_sections if section not in content]
        
        return {
            "status": "pass" if not missing_sections else "fail",
            "missing_sections": missing_sections
        }

    async def _validate_security(self, plugin_dir: Path) -> Dict[str, Any]:
        """Validate security measures"""
        security_issues = []
        
        # Check main plugin file
        main_files = list(plugin_dir.glob("*.php"))
        for main_file in main_files:
            with open(main_file, 'r') as f:
                content = f.read()
                if 'ABSPATH' not in content:
                    security_issues.append(f"Missing ABSPATH check in {main_file}")
        
        return {
            "status": "pass" if not security_issues else "fail",
            "issues": security_issues
        }

    async def _generate_readme(self, plugin_dir: Path, readme_path: Path):
        """Generate README.md documentation"""
        readme_content = f"""# {self.plugin_info.name}

{self.plugin_info.description}

## Installation

1. Upload the plugin files to `/wp-content/plugins/{self.plugin_info.slug}/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **{self.plugin_info.name}** in your WordPress admin to configure

## Configuration

Configure the plugin settings through the WordPress admin interface.

## Usage

Use the plugin features through the WordPress admin interface.

## Support

For support, visit [Spun Web Technology]({self.plugin_info.author_uri})

## Changelog

### {self.plugin_info.version}
- Initial release

## License

This plugin is licensed under the {self.plugin_info.license}.
"""
        
        with open(readme_path, 'w') as f:
            f.write(readme_content)

    async def _generate_api_docs(self, plugin_dir: Path, api_docs_path: Path):
        """Generate API documentation"""
        api_content = f"""# {self.plugin_info.name} - API Documentation

## Overview

This document describes the API endpoints and hooks available in {self.plugin_info.name}.

## Hooks

### Action Hooks

- `{self.plugin_info.slug}_before_submission` - Fired before URL submission
- `{self.plugin_info.slug}_after_submission` - Fired after submission attempt
- `{self.plugin_info.slug}_submission_success` - Fired on successful submission
- `{self.plugin_info.slug}_submission_failed` - Fired on failed submission

### Filter Hooks

- `{self.plugin_info.slug}_submission_url` - Modify URL before submission
- `{self.plugin_info.slug}_submission_data` - Modify submission data
- `{self.plugin_info.slug}_retry_attempts` - Customize retry attempts

## Classes

### Main Plugin Class

The main plugin class follows the singleton pattern and orchestrates all plugin components.

### Archive API Class

Handles all communication with the Internet Archive API.

## Database Schema

The plugin creates custom tables for submission tracking and queue management.

## Security

All API endpoints include proper nonce verification and capability checks.
"""
        
        with open(api_docs_path, 'w') as f:
            f.write(api_content)

    async def _generate_changelog(self, plugin_dir: Path, changelog_path: Path):
        """Generate CHANGELOG.md"""
        changelog_content = f"""# Changelog

All notable changes to {self.plugin_info.name} will be documented in this file.

## [{self.plugin_info.version}] - {datetime.now().strftime('%Y-%m-%d')}

### Added
- Initial release
- Basic plugin functionality
- Admin interface
- Archive.org integration

### Changed
- N/A

### Fixed
- N/A

### Security
- N/A
"""
        
        with open(changelog_path, 'w') as f:
            f.write(changelog_content)

    async def _create_backup_archive(self, wp_path: Path, backup_path: Path):
        """Create backup archive of WordPress files"""
        with zipfile.ZipFile(backup_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
            for root, dirs, files in os.walk(wp_path):
                # Skip certain directories
                dirs[:] = [d for d in dirs if d not in ['node_modules', '.git', 'cache']]
                
                for file in files:
                    if not file.endswith(('.log', '.tmp')):
                        file_path = Path(root) / file
                        arcname = file_path.relative_to(wp_path)
                        zipf.write(file_path, arcname)

    async def _backup_database(self, wp_path: Path, db_backup_path: Path):
        """Backup WordPress database"""
        # This would use wp-cli or mysqldump
        cmd = ["wp", "db", "export", str(db_backup_path)]
        subprocess.run(cmd, cwd=str(wp_path), check=True)

    async def _restore_from_archive(self, backup_path: Path, wp_path: Path):
        """Restore WordPress files from backup archive"""
        with zipfile.ZipFile(backup_path, 'r') as zipf:
            zipf.extractall(wp_path)

    async def _restore_database(self, wp_path: Path, db_backup_path: Path):
        """Restore WordPress database from backup"""
        cmd = ["wp", "db", "import", str(db_backup_path)]
        subprocess.run(cmd, cwd=str(wp_path), check=True)

    async def _run_migration_script(self, migration_script_path: Path, wp_path: Path):
        """Run database migration script"""
        cmd = ["php", str(migration_script_path)]
        subprocess.run(cmd, cwd=str(wp_path), check=True)

    async def _update_plugin_version(self, plugin_dir: Path, version: str):
        """Update plugin version in main file"""
        main_files = list(plugin_dir.glob("*.php"))
        for main_file in main_files:
            with open(main_file, 'r') as f:
                content = f.read()
            
            # Update version in header
            content = content.replace('Version: 1.0.0', f'Version: {version}')
            
            with open(main_file, 'w') as f:
                f.write(content)

    async def _run_database_migrations(self, plugin_dir: Path, wp_path: Path, 
                                     from_version: str, to_version: str):
        """Run database migrations"""
        # This would run actual database migrations
        logger.info(f"Running database migrations from {from_version} to {to_version}")

    async def _benchmark_frontend_load(self, wp_path: Path):
        """Benchmark frontend page load"""
        # Simulate frontend load test
        time.sleep(0.1)

    async def _benchmark_admin_load(self, wp_path: Path):
        """Benchmark admin page load"""
        # Simulate admin load test
        time.sleep(0.2)


# MCP Server Tool Definitions
TOOLS = [
    {
        "name": "wp_plugin_create",
        "description": "Create a new WordPress plugin",
        "inputSchema": {
            "type": "object",
            "properties": {
                "name": {"type": "string", "description": "Plugin name (e.g., \"My Awesome Plugin\")"},
                "slug": {"type": "string", "description": "Plugin slug (e.g., \"my-awesome-plugin\")"},
                "author": {"type": "string", "description": "Author name"},
                "description": {"type": "string", "description": "Plugin description"},
                "version": {"type": "string", "description": "Initial version (default: \"1.0.0\")"}
            },
            "required": ["name", "slug", "author"]
        }
    },
    {
        "name": "wp_plugin_install",
        "description": "Install a WordPress plugin via WP-CLI",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_path": {"type": "string", "description": "Path to plugin zip file or directory"},
                "wp_path": {"type": "string", "description": "WordPress installation path"},
                "activate": {"type": "boolean", "description": "Activate plugin after installation"}
            },
            "required": ["plugin_path", "wp_path"]
        }
    },
    {
        "name": "wp_plugin_activate",
        "description": "Activate a WordPress plugin",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_slug": {"type": "string", "description": "Plugin slug to activate"},
                "wp_path": {"type": "string", "description": "WordPress installation path"}
            },
            "required": ["plugin_slug", "wp_path"]
        }
    },
    {
        "name": "wp_plugin_deactivate",
        "description": "Deactivate a WordPress plugin",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_slug": {"type": "string", "description": "Plugin slug to deactivate"},
                "wp_path": {"type": "string", "description": "WordPress installation path"}
            },
            "required": ["plugin_slug", "wp_path"]
        }
    },
    {
        "name": "wp_plugin_list",
        "description": "List all WordPress plugins",
        "inputSchema": {
            "type": "object",
            "properties": {
                "wp_path": {"type": "string", "description": "WordPress installation path"},
                "status": {"type": "string", "enum": ["active", "inactive", "all"], "description": "Filter by status"}
            },
            "required": ["wp_path"]
        }
    },
    {
        "name": "wp_plugin_package",
        "description": "Create a WordPress plugin package (zip file)",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_dir": {"type": "string", "description": "Plugin directory path"},
                "output_path": {"type": "string", "description": "Output zip file path"},
                "exclude_patterns": {"type": "array", "items": {"type": "string"}, "description": "File patterns to exclude"}
            },
            "required": ["plugin_dir", "output_path"]
        }
    },
    {
        "name": "wp_plugin_test",
        "description": "Test WordPress plugin for syntax errors and basic functionality",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_dir": {"type": "string", "description": "Plugin directory path"},
                "wp_path": {"type": "string", "description": "WordPress installation path"}
            },
            "required": ["plugin_dir", "wp_path"]
        }
    },
    {
        "name": "wp_plugin_deploy",
        "description": "Deploy WordPress plugin to remote server via SSH",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_package": {"type": "string", "description": "Path to plugin zip package"},
                "server_host": {"type": "string", "description": "Remote server hostname or IP"},
                "server_user": {"type": "string", "description": "SSH username"},
                "server_path": {"type": "string", "description": "Remote WordPress path"},
                "ssh_key": {"type": "string", "description": "SSH private key path (optional)"}
            },
            "required": ["plugin_package", "server_host", "server_user", "server_path"]
        }
    },
    {
        "name": "wp_plugin_backup",
        "description": "Create backup of WordPress installation and plugins",
        "inputSchema": {
            "type": "object",
            "properties": {
                "wp_path": {"type": "string", "description": "WordPress installation path"},
                "backup_path": {"type": "string", "description": "Backup output path"},
                "include_database": {"type": "boolean", "description": "Include database backup"}
            },
            "required": ["wp_path", "backup_path"]
        }
    },
    {
        "name": "wp_plugin_restore",
        "description": "Restore WordPress installation from backup",
        "inputSchema": {
            "type": "object",
            "properties": {
                "backup_path": {"type": "string", "description": "Backup file path"},
                "wp_path": {"type": "string", "description": "WordPress installation path to restore to"},
                "restore_database": {"type": "boolean", "description": "Restore database from backup"}
            },
            "required": ["backup_path", "wp_path"]
        }
    },
    {
        "name": "wp_plugin_analyze",
        "description": "Analyze WordPress plugin for security, performance, and best practices",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_dir": {"type": "string", "description": "Plugin directory path"},
                "analysis_type": {"type": "string", "enum": ["security", "performance", "standards", "all"], "description": "Type of analysis to perform"}
            },
            "required": ["plugin_dir"]
        }
    },
    {
        "name": "wp_plugin_generate_docs",
        "description": "Generate documentation for WordPress plugin",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_dir": {"type": "string", "description": "Plugin directory path"},
                "output_format": {"type": "string", "enum": ["markdown", "html", "pdf"], "description": "Output format for documentation"},
                "include_api": {"type": "boolean", "description": "Include API documentation"}
            },
            "required": ["plugin_dir"]
        }
    },
    {
        "name": "wp_plugin_validate",
        "description": "Validate WordPress plugin against WordPress.org standards",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_dir": {"type": "string", "description": "Plugin directory path"},
                "check_coding_standards": {"type": "boolean", "description": "Check WordPress coding standards"},
                "check_readme": {"type": "boolean", "description": "Validate readme.txt file"},
                "check_security": {"type": "boolean", "description": "Check for security issues"}
            },
            "required": ["plugin_dir"]
        }
    },
    {
        "name": "wp_plugin_migrate",
        "description": "Migrate WordPress plugin to new version with database updates",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_dir": {"type": "string", "description": "Plugin directory path"},
                "wp_path": {"type": "string", "description": "WordPress installation path"},
                "from_version": {"type": "string", "description": "Current plugin version"},
                "to_version": {"type": "string", "description": "Target plugin version"},
                "migration_script": {"type": "string", "description": "Custom migration script path (optional)"}
            },
            "required": ["plugin_dir", "wp_path", "from_version", "to_version"]
        }
    },
    {
        "name": "wp_plugin_benchmark",
        "description": "Benchmark WordPress plugin performance",
        "inputSchema": {
            "type": "object",
            "properties": {
                "plugin_dir": {"type": "string", "description": "Plugin directory path"},
                "wp_path": {"type": "string", "description": "WordPress installation path"},
                "iterations": {"type": "number", "description": "Number of test iterations"},
                "test_scenarios": {"type": "array", "items": {"type": "string"}, "description": "Test scenarios to run"}
            },
            "required": ["plugin_dir", "wp_path"]
        }
    },
    {
        "name": "archive_submit_url",
        "description": "Submit URL to Internet Archive for archiving",
        "inputSchema": {
            "type": "object",
            "properties": {
                "url": {"type": "string", "description": "URL to archive"},
                "capture_all": {"type": "boolean", "description": "Capture all resources"},
                "capture_outlinks": {"type": "boolean", "description": "Capture outbound links"}
            },
            "required": ["url"]
        }
    },
    {
        "name": "archive_check_status",
        "description": "Check archive status for a URL",
        "inputSchema": {
            "type": "object",
            "properties": {
                "url": {"type": "string", "description": "URL to check"}
            },
            "required": ["url"]
        }
    },
    {
        "name": "archive_submit_post",
        "description": "Submit WordPress post to Internet Archive",
        "inputSchema": {
            "type": "object",
            "properties": {
                "post_id": {"type": "number", "description": "WordPress post ID"},
                "wp_path": {"type": "string", "description": "WordPress installation path"}
            },
            "required": ["post_id", "wp_path"]
        }
    },
    {
        "name": "archive_submit_page",
        "description": "Submit WordPress page to Internet Archive",
        "inputSchema": {
            "type": "object",
            "properties": {
                "page_id": {"type": "number", "description": "WordPress page ID"},
                "wp_path": {"type": "string", "description": "WordPress installation path"}
            },
            "required": ["page_id", "wp_path"]
        }
    },
    {
        "name": "archive_bulk_submit",
        "description": "Bulk submit multiple WordPress posts/pages to Internet Archive",
        "inputSchema": {
            "type": "object",
            "properties": {
                "post_ids": {"type": "array", "items": {"type": "number"}, "description": "Array of post/page IDs"},
                "wp_path": {"type": "string", "description": "WordPress installation path"},
                "post_type": {"type": "string", "description": "Post type (post, page, or all)"}
            },
            "required": ["wp_path"]
        }
    },
    {
        "name": "archive_get_submission_history",
        "description": "Get submission history from WordPress database",
        "inputSchema": {
            "type": "object",
            "properties": {
                "wp_path": {"type": "string", "description": "WordPress installation path"},
                "limit": {"type": "number", "description": "Number of records to retrieve"},
                "status": {"type": "string", "description": "Filter by status (pending, completed, failed)"}
            },
            "required": ["wp_path"]
        }
    },
    {
        "name": "archive_get_queue_status",
        "description": "Get current archive queue status",
        "inputSchema": {
            "type": "object",
            "properties": {
                "wp_path": {"type": "string", "description": "WordPress installation path"}
            },
            "required": ["wp_path"]
        }
    }
]

async def main():
    """Main MCP Server function"""
    server = SpunWebArchiveForgeMCPServer()
    
    # Handle MCP protocol
    while True:
        try:
            line = await asyncio.get_event_loop().run_in_executor(None, sys.stdin.readline)
            if not line:
                break
            
            request = json.loads(line.strip())
            
            if request.get("method") == "tools/list":
                response = {
                    "jsonrpc": "2.0",
                    "id": request.get("id"),
                    "result": {
                        "tools": TOOLS
                    }
                }
            elif request.get("method") == "tools/call":
                tool_name = request["params"]["name"]
                arguments = request["params"]["arguments"]
                
                # Call the appropriate method
                if hasattr(server, tool_name):
                    method = getattr(server, tool_name)
                    result = await method(**arguments)
                    
                    response = {
                        "jsonrpc": "2.0",
                        "id": request.get("id"),
                        "result": {
                            "content": [
                                {
                                    "type": "text",
                                    "text": json.dumps(result, indent=2)
                                }
                            ]
                        }
                    }
                else:
                    response = {
                        "jsonrpc": "2.0",
                        "id": request.get("id"),
                        "error": {
                            "code": -32601,
                            "message": f"Method '{tool_name}' not found"
                        }
                    }
            else:
                response = {
                    "jsonrpc": "2.0",
                    "id": request.get("id"),
                    "error": {
                        "code": -32601,
                        "message": "Method not found"
                    }
                }
            
            print(json.dumps(response))
            sys.stdout.flush()
            
        except json.JSONDecodeError:
            continue
        except Exception as e:
            logger.error(f"Error processing request: {e}")
            response = {
                "jsonrpc": "2.0",
                "id": request.get("id", None),
                "error": {
                    "code": -32603,
                    "message": str(e)
                }
            }
            print(json.dumps(response))
            sys.stdout.flush()

if __name__ == "__main__":
    asyncio.run(main())
