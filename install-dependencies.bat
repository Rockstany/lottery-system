@echo off
echo ========================================
echo  Lottery System - Dependency Installer
echo ========================================
echo.

REM Check if composer is installed
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Composer is not installed!
    echo.
    echo Please install Composer first:
    echo 1. Download from: https://getcomposer.org/Composer-Setup.exe
    echo 2. Run the installer
    echo 3. Restart Command Prompt
    echo 4. Run this script again
    echo.
    pause
    exit /b 1
)

echo Composer found:
composer --version
echo.

echo Installing PHPSpreadsheet and dependencies...
echo This may take 2-5 minutes...
echo.

composer install --prefer-dist --optimize-autoloader

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  Installation completed successfully!
    echo ========================================
    echo.
    echo The vendor folder has been created with:
    echo - PHPSpreadsheet library
    echo - All required dependencies
    echo - Autoloader
    echo.
    echo Next steps:
    echo 1. Notify Claude that installation is complete
    echo 2. Claude will update the Excel export/import code
    echo.
) else (
    echo.
    echo ========================================
    echo  Installation failed!
    echo ========================================
    echo.
    echo Please check the error messages above.
    echo Common issues:
    echo - No internet connection
    echo - PHP not installed
    echo - PHP version too old (need 7.4+)
    echo.
    echo Read SETUP_COMPOSER.md for troubleshooting help.
    echo.
)

pause
