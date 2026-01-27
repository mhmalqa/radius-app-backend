# 🚀 سكربت Deployment لـ Hostinger
# استخدم: .\deploy.ps1

param(
    [string]$Environment = "production"
)

Write-Host "🚀 Starting deployment to Hostinger..." -ForegroundColor Green
Write-Host "Environment: $Environment" -ForegroundColor Cyan
Write-Host ""

# التحقق من Git
Write-Host "📥 Checking Git status..." -ForegroundColor Yellow
$gitStatus = git status --porcelain
if ($gitStatus) {
    Write-Host "⚠️  You have uncommitted changes!" -ForegroundColor Red
    $response = Read-Host "Do you want to continue? (y/n)"
    if ($response -ne "y") {
        Write-Host "❌ Deployment cancelled" -ForegroundColor Red
        exit 1
    }
}

# Pull latest changes
Write-Host "📥 Pulling latest changes from Git..." -ForegroundColor Yellow
git pull origin main
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Git pull failed!" -ForegroundColor Red
    exit 1
}

# Install/Update dependencies
Write-Host "📦 Installing/Updating Composer dependencies..." -ForegroundColor Yellow
composer install --no-dev --optimize-autoloader
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Composer install failed!" -ForegroundColor Red
    exit 1
}

# Clear caches locally (optional)
Write-Host "🧹 Clearing local caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan route:clear
php artisan view:clear

Write-Host ""
Write-Host "✅ Local preparation completed!" -ForegroundColor Green
Write-Host ""
Write-Host "📤 Next steps:" -ForegroundColor Cyan
Write-Host "   1. Upload files to Hostinger via FTP/SFTP" -ForegroundColor White
Write-Host "   2. SSH to server and run:" -ForegroundColor White
Write-Host "      cd ~/public_html/app.weventex.com" -ForegroundColor Gray
Write-Host "      git pull origin main" -ForegroundColor Gray
Write-Host "      composer install --no-dev --optimize-autoloader" -ForegroundColor Gray
Write-Host "      php artisan migrate --force" -ForegroundColor Gray
Write-Host "      php artisan config:cache" -ForegroundColor Gray
Write-Host "      php artisan route:cache" -ForegroundColor Gray
Write-Host "      php artisan view:cache" -ForegroundColor Gray
Write-Host ""
Write-Host "💡 Tip: If you have SSH access, you can automate this with GitHub Actions!" -ForegroundColor Yellow
Write-Host ""
