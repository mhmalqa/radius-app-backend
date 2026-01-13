@echo off
REM Install Laravel Scheduler as Windows Task
REM Run this script as Administrator

echo Installing Laravel Scheduler Task...

schtasks /create /tn "Laravel Scheduler" /tr "cmd /c \"D:\backend-wifi\radius-app-backend\run-scheduler.bat\"" /sc minute /mo 1 /ru SYSTEM /f

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ Task created successfully!
    echo.
    echo To start the task:
    echo   schtasks /run /tn "Laravel Scheduler"
    echo.
    echo To check task status:
    echo   schtasks /query /tn "Laravel Scheduler"
    echo.
    echo To delete the task:
    echo   schtasks /delete /tn "Laravel Scheduler" /f
) else (
    echo.
    echo ❌ Failed to create task. Make sure you're running as Administrator.
    echo.
    pause
)




