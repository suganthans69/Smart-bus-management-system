@echo off
title SmartBus Web Server
cls
echo ========================================================
echo   SmartBus - Bus Reservation and Management System
echo ========================================================
echo.

set PHP_EXE=C:\xampp\php\php.exe

if not exist "%PHP_EXE%" (
    echo [ERROR] PHP was not found at %PHP_EXE%
    echo Please make sure XAMPP is installed in C:\xampp
    pause
    exit /b 1
)

echo [1/3] Checking and setting up SmartBus database...
"%PHP_EXE%" database/install.php
echo.

echo [2/3] Launching your web browser...
start http://localhost:8000
echo.

echo [3/3] Starting SmartBus server on http://localhost:8000 ...
echo [INFO] Server is now RUNNING. Leave this window open while using SmartBus.
echo [INFO] Press Ctrl+C anytime to stop the server.
echo.
"%PHP_EXE%" -S localhost:8000 -t public
pause

