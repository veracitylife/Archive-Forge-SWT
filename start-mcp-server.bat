@echo off
REM Spun Web Archive Forge MCP Server Startup Script
REM Professional WordPress Plugin Development and Management Server
REM 
REM Author: Ryan Dickie Thompson
REM Company: Spun Web Technology
REM Version: 1.0.0
REM License: GPL v2 or later

echo.
echo ========================================
echo Spun Web Archive Forge MCP Server
echo Professional WordPress Plugin Development
echo ========================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8 or higher
    pause
    exit /b 1
)

REM Check if we're in the right directory
if not exist "mcp-server.py" (
    echo ERROR: mcp-server.py not found
    echo Please run this script from the MCP Server directory
    pause
    exit /b 1
)

REM Check if configuration file exists
if not exist "mcp-server.conf" (
    echo WARNING: mcp-server.conf not found
    echo Creating default configuration...
    echo Please edit mcp-server.conf with your settings
    pause
)

REM Check if requirements.txt exists
if exist "requirements.txt" (
    echo Installing/updating Python dependencies...
    python -m pip install -r requirements.txt
    if errorlevel 1 (
        echo WARNING: Some dependencies may not have installed correctly
        echo Continuing anyway...
    )
) else (
    echo WARNING: requirements.txt not found
    echo Skipping dependency installation...
)

echo.
echo Starting MCP Server...
echo.

REM Start the MCP Server
python start-mcp-server.py

REM Check if server started successfully
if errorlevel 1 (
    echo.
    echo ERROR: MCP Server failed to start
    echo Check the logs for more information
    echo.
    pause
    exit /b 1
)

echo.
echo MCP Server started successfully!
echo Server is running and ready to accept connections
echo.
echo Press any key to stop the server...
pause >nul

REM Stop the server gracefully
echo Stopping MCP Server...
taskkill /f /im python.exe >nul 2>&1

echo MCP Server stopped.
pause
