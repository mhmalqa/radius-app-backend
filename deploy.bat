@echo off
chcp 65001 >nul
echo 🚀 Starting deployment to Hostinger...
echo.

echo 📥 Pulling latest changes from Git...
git pull origin main
if errorlevel 1 (
    echo ❌ Git pull failed!
    pause
    exit /b 1
)

echo.
echo 📦 Installing/Updating Composer dependencies...
composer install --no-dev --optimize-autoloader
if errorlevel 1 (
    echo ❌ Composer install failed!
    pause
    exit /b 1
)

echo.
echo 🧹 Clearing local caches...
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo ✅ Local preparation completed!
echo.
echo 📤 Next steps:
echo    1. Upload files to Hostinger via FTP/SFTP
echo    2. SSH to server and run:
echo       cd ~/public_html/app.weventex.com
echo       git pull origin main
echo       composer install --no-dev --optimize-autoloader
echo       php artisan migrate --force
echo       php artisan config:cache
echo       php artisan route:cache
echo       php artisan view:cache
echo.
echo 💡 Tip: If you have SSH access, you can automate this with GitHub Actions!
echo.
pause
