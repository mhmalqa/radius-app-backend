@echo off
REM Laravel Scheduler Batch Script for Windows
REM This script runs Laravel scheduler every minute

cd /d "D:\backend-wifi\radius-app-backend"
php artisan schedule:run >> storage\logs\scheduler.log 2>&1




