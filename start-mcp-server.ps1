# Spun Web Archive Forge MCP Server PowerShell Startup Script
# Professional WordPress Plugin Development and Management Server
# 
# Author: Ryan Dickie Thompson
# Company: Spun Web Technology
# Version: 1.0.0
# License: GPL v2 or later

param(
    [switch]$Install,
    [switch]$Update,
    [switch]$Debug,
    [switch]$Help
)

# Set error action preference
$ErrorActionPreference = "Stop"

# Script configuration
$ScriptName = "Spun Web Archive Forge MCP Server"
$Version = "1.0.0"
$RequiredPythonVersion = "3.8"

# Colors for output
$Colors = @{
    Success = "Green"
    Warning = "Yellow"
    Error = "Red"
    Info = "Cyan"
    Header = "Magenta"
}

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

function Show-Header {
    Write-ColorOutput "`n========================================" $Colors.Header
    Write-ColorOutput $ScriptName $Colors.Header
    Write-ColorOutput "Professional WordPress Plugin Development" $Colors.Header
    Write-ColorOutput "Version: $Version" $Colors.Header
    Write-ColorOutput "========================================`n" $Colors.Header
}

function Show-Help {
    Show-Header
    Write-ColorOutput "Usage: .\start-mcp-server.ps1 [OPTIONS]" $Colors.Info
    Write-ColorOutput "`nOptions:" $Colors.Info
    Write-ColorOutput "  -Install    Install/update Python dependencies" $Colors.Info
    Write-ColorOutput "  -Update     Update MCP Server to latest version" $Colors.Info
    Write-ColorOutput "  -Debug      Enable debug mode" $Colors.Info
    Write-ColorOutput "  -Help       Show this help message" $Colors.Info
    Write-ColorOutput "`nExamples:" $Colors.Info
    Write-ColorOutput "  .\start-mcp-server.ps1                    # Start server normally" $Colors.Info
    Write-ColorOutput "  .\start-mcp-server.ps1 -Install          # Install dependencies and start" $Colors.Info
    Write-ColorOutput "  .\start-mcp-server.ps1 -Debug            # Start in debug mode" $Colors.Info
}

function Test-PythonInstallation {
    Write-ColorOutput "Checking Python installation..." $Colors.Info
    
    try {
        $pythonVersion = python --version 2>&1
        if ($LASTEXITCODE -ne 0) {
            throw "Python not found in PATH"
        }
        
        # Extract version number
        $versionMatch = $pythonVersion -match "Python (\d+)\.(\d+)"
        if ($versionMatch) {
            $majorVersion = [int]$matches[1]
            $minorVersion = [int]$matches[2]
            
            if ($majorVersion -lt 3 -or ($majorVersion -eq 3 -and $minorVersion -lt 8)) {
                throw "Python 3.8 or higher is required. Found: $pythonVersion"
            }
            
            Write-ColorOutput "✓ Python $pythonVersion found" $Colors.Success
            return $true
        } else {
            throw "Could not parse Python version"
        }
    } catch {
        Write-ColorOutput "✗ $($_.Exception.Message)" $Colors.Error
        Write-ColorOutput "Please install Python 3.8 or higher from https://python.org" $Colors.Error
        return $false
    }
}

function Test-RequiredFiles {
    Write-ColorOutput "Checking required files..." $Colors.Info
    
    $requiredFiles = @(
        "mcp-server.py",
        "start-mcp-server.py",
        "mcp-server.conf"
    )
    
    $missingFiles = @()
    foreach ($file in $requiredFiles) {
        if (Test-Path $file) {
            Write-ColorOutput "✓ $file found" $Colors.Success
        } else {
            Write-ColorOutput "✗ $file missing" $Colors.Error
            $missingFiles += $file
        }
    }
    
    if ($missingFiles.Count -gt 0) {
        Write-ColorOutput "Missing required files: $($missingFiles -join ', ')" $Colors.Error
        return $false
    }
    
    return $true
}

function Install-Dependencies {
    Write-ColorOutput "Installing Python dependencies..." $Colors.Info
    
    if (-not (Test-Path "requirements.txt")) {
        Write-ColorOutput "⚠ requirements.txt not found, skipping dependency installation" $Colors.Warning
        return $true
    }
    
    try {
        # Upgrade pip first
        Write-ColorOutput "Upgrading pip..." $Colors.Info
        python -m pip install --upgrade pip
        
        # Install requirements
        Write-ColorOutput "Installing requirements..." $Colors.Info
        python -m pip install -r requirements.txt
        
        Write-ColorOutput "✓ Dependencies installed successfully" $Colors.Success
        return $true
    } catch {
        Write-ColorOutput "✗ Error installing dependencies: $($_.Exception.Message)" $Colors.Error
        return $false
    }
}

function Test-WordPressEnvironment {
    Write-ColorOutput "Checking WordPress environment..." $Colors.Info
    
    try {
        # Check if wp-cli is available
        $wpCliVersion = wp --version 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-ColorOutput "✓ WP-CLI found: $wpCliVersion" $Colors.Success
        } else {
            Write-ColorOutput "⚠ WP-CLI not found, some features may be limited" $Colors.Warning
        }
        
        # Check configuration file
        if (Test-Path "mcp-server.conf") {
            $config = Get-Content "mcp-server.conf" -Raw
            if ($config -match "wp_path\s*=\s*(.+?)$") {
                $wpPath = $matches[1].Trim()
                if (Test-Path $wpPath) {
                    Write-ColorOutput "✓ WordPress path found: $wpPath" $Colors.Success
                } else {
                    Write-ColorOutput "⚠ WordPress path not found: $wpPath" $Colors.Warning
                }
            }
        }
        
        return $true
    } catch {
        Write-ColorOutput "⚠ WordPress environment check failed: $($_.Exception.Message)" $Colors.Warning
        return $true  # Non-critical
    }
}

function Start-MCPServer {
    param([bool]$DebugMode = $false)
    
    Write-ColorOutput "Starting MCP Server..." $Colors.Info
    
    try {
        if ($DebugMode) {
            Write-ColorOutput "Debug mode enabled" $Colors.Warning
            $process = Start-Process -FilePath "python" -ArgumentList "start-mcp-server.py" -NoNewWindow -PassThru
        } else {
            $process = Start-Process -FilePath "python" -ArgumentList "start-mcp-server.py" -NoNewWindow -PassThru
        }
        
        # Wait a moment to check if it started
        Start-Sleep -Seconds 2
        
        if (-not $process.HasExited) {
            Write-ColorOutput "✓ MCP Server started successfully (PID: $($process.Id))" $Colors.Success
            Write-ColorOutput "Server is running and ready to accept connections" $Colors.Success
            
            # Wait for user input to stop
            Write-ColorOutput "`nPress any key to stop the server..." $Colors.Info
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
            
            # Stop the server
            Write-ColorOutput "`nStopping MCP Server..." $Colors.Info
            Stop-Process -Id $process.Id -Force
            Write-ColorOutput "✓ MCP Server stopped" $Colors.Success
        } else {
            Write-ColorOutput "✗ MCP Server failed to start" $Colors.Error
            return $false
        }
        
        return $true
    } catch {
        Write-ColorOutput "✗ Error starting MCP Server: $($_.Exception.Message)" $Colors.Error
        return $false
    }
}

function Update-MCPServer {
    Write-ColorOutput "Updating MCP Server..." $Colors.Info
    
    try {
        # This would typically involve git pull or downloading updates
        Write-ColorOutput "Update functionality not yet implemented" $Colors.Warning
        Write-ColorOutput "Please check for updates manually" $Colors.Info
        return $true
    } catch {
        Write-ColorOutput "✗ Error updating MCP Server: $($_.Exception.Message)" $Colors.Error
        return $false
    }
}

# Main execution
if ($Help) {
    Show-Help
    exit 0
}

Show-Header

# Check Python installation
if (-not (Test-PythonInstallation)) {
    exit 1
}

# Check required files
if (-not (Test-RequiredFiles)) {
    exit 1
}

# Install dependencies if requested
if ($Install) {
    if (-not (Install-Dependencies)) {
        Write-ColorOutput "Dependency installation failed, but continuing..." $Colors.Warning
    }
}

# Update if requested
if ($Update) {
    if (-not (Update-MCPServer)) {
        exit 1
    }
}

# Check WordPress environment
Test-WordPressEnvironment

# Start the MCP Server
if (-not (Start-MCPServer -DebugMode $Debug)) {
    exit 1
}

Write-ColorOutput "`nMCP Server startup completed successfully!" $Colors.Success
