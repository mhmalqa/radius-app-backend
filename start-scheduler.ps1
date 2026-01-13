# Laravel Scheduler PowerShell Script for Windows
# This script runs Laravel scheduler continuously

$projectPath = "D:\backend-wifi\radius-app-backend"
$logFile = "$projectPath\storage\logs\scheduler.log"

Write-Host "Starting Laravel Scheduler..." -ForegroundColor Green
Write-Host "Project Path: $projectPath" -ForegroundColor Yellow
Write-Host "Log File: $logFile" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop" -ForegroundColor Red
Write-Host ""

# Create log file if it doesn't exist
if (-not (Test-Path $logFile)) {
    New-Item -Path $logFile -ItemType File -Force | Out-Null
}

while ($true) {
    try {
        Set-Location $projectPath
        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        Add-Content -Path $logFile -Value "`n[$timestamp] Running scheduler..."
        
        php artisan schedule:run 2>&1 | Out-File -Append -FilePath $logFile
        
        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        Add-Content -Path $logFile -Value "[$timestamp] Scheduler completed`n"
        
        Start-Sleep -Seconds 60
    }
    catch {
        $errorMsg = $_.Exception.Message
        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        Add-Content -Path $logFile -Value "[$timestamp] ERROR: $errorMsg`n"
        Start-Sleep -Seconds 60
    }
}




