# Composer and PHPSpreadsheet Installation Guide

**Date:** 2025-12-25
**Purpose:** Install dependencies for Excel (.xlsx) export/import functionality

---

## Step 1: Install Composer

### Option A: Windows Installer (Recommended)

1. **Download Composer Installer:**
   - Go to: https://getcomposer.org/Composer-Setup.exe
   - Or visit: https://getcomposer.org/download/

2. **Run the Installer:**
   - Double-click `Composer-Setup.exe`
   - Follow the installation wizard
   - It will automatically detect your PHP installation
   - Click "Next" through all steps
   - Click "Install"

3. **Verify Installation:**
   - Open Command Prompt (cmd)
   - Type: `composer --version`
   - You should see: `Composer version 2.x.x`

### Option B: Manual Installation (If Option A Fails)

1. **Download composer.phar:**
   - Go to: https://getcomposer.org/composer.phar
   - Save to: `C:\composer\composer.phar`

2. **Create batch file:**
   - Create `C:\composer\composer.bat` with this content:
   ```batch
   @php "%~dp0composer.phar" %*
   ```

3. **Add to PATH:**
   - Right-click "This PC" → Properties
   - Advanced System Settings → Environment Variables
   - Edit "Path" → Add: `C:\composer`
   - Click OK

---

## Step 2: Install PHPSpreadsheet

1. **Open Command Prompt as Administrator:**
   - Press Windows Key
   - Type: `cmd`
   - Right-click "Command Prompt"
   - Select "Run as administrator"

2. **Navigate to Project Folder:**
   ```bash
   cd "c:\Users\albin\Desktop\Projects\Lottery System\lottery-system"
   ```

3. **Install PHPSpreadsheet:**
   ```bash
   composer install
   ```

   This will:
   - Download PHPSpreadsheet library
   - Download all dependencies
   - Create `vendor/` folder
   - Create `vendor/autoload.php`

4. **Wait for Installation:**
   - This may take 2-5 minutes
   - You'll see: "Generating autoload files"
   - Finally: "Installation completed successfully"

---

## Step 3: Verify Installation

1. **Check vendor folder:**
   ```bash
   dir vendor
   ```
   You should see:
   - `autoload.php`
   - `phpoffice/` folder
   - `composer/` folder

2. **Check PHPSpreadsheet:**
   ```bash
   dir vendor\phpoffice\phpspreadsheet
   ```
   You should see the PHPSpreadsheet library files

---

## Step 4: Security - Protect vendor folder

The `vendor/` folder should NOT be accessible from the web.

**Already Protected:**
- The vendor folder is OUTSIDE the `public/` directory
- It's at project root level, not in web root
- Web server cannot access it directly

**Verification:**
Try accessing: `http://your-domain.com/vendor/autoload.php`
- Should show: **404 Not Found** ✅
- If accessible: Contact me for .htaccess configuration

---

## What Gets Installed

### PHPSpreadsheet Library
- **Location:** `vendor/phpoffice/phpspreadsheet/`
- **Size:** ~15 MB
- **Purpose:** Create and read Excel files (.xlsx, .xls)

### Dependencies
- `psr/simple-cache`
- `psr/http-client`
- `psr/http-factory`
- `maennchen/zipstream-php`
- And other supporting libraries

### Autoloader
- **File:** `vendor/autoload.php`
- **Purpose:** Automatically loads all library classes
- **Usage:** `require_once __DIR__ . '/vendor/autoload.php';`

---

## After Installation

Once installation is complete, I will:

1. ✅ Update Excel export files to generate true `.xlsx` files
2. ✅ Update Excel upload processor to read `.xlsx` files
3. ✅ Add proper cell formatting (colors, borders, fonts)
4. ✅ Support multiple worksheets
5. ✅ Add data validation
6. ✅ Improve Excel 2019 compatibility

---

## Troubleshooting

### Error: "composer: command not found"
**Solution:** Composer not installed or not in PATH
- Repeat Step 1
- Make sure to add Composer to system PATH
- Restart Command Prompt after installation

### Error: "PHP is not recognized"
**Solution:** PHP not installed or not in PATH
- Install PHP from: https://windows.php.net/download
- Or use XAMPP/WAMP which includes PHP
- Add PHP to system PATH

### Error: "Your requirements could not be resolved"
**Solution:** PHP version too old
- PHPSpreadsheet requires PHP 7.4 or higher
- Check PHP version: `php -v`
- Upgrade PHP if needed

### Error: "proc_open() has been disabled"
**Solution:** PHP security restriction
- Edit `php.ini`
- Find: `disable_functions`
- Remove `proc_open` from the list
- Restart web server

### Installation Stuck / Very Slow
**Solution:** Network or memory issue
- Check internet connection
- Try: `composer install --prefer-dist`
- Or: `composer install --no-dev`
- Increase PHP memory limit in php.ini: `memory_limit = 512M`

---

## For Production Server (zatana.in)

When deploying to your live server:

1. **Upload vendor folder:**
   - Upload entire `vendor/` folder to server
   - Path: `/home/u717011923/domains/zatana.in/public_html/vendor/`

2. **Or install on server:**
   ```bash
   cd /home/u717011923/domains/zatana.in/public_html
   composer install --no-dev --optimize-autoloader
   ```

3. **Set permissions:**
   ```bash
   chmod -R 755 vendor/
   ```

---

## File Size Information

After installation, the `vendor/` folder will be approximately:
- **Total Size:** 20-25 MB
- **Files:** ~500 files
- **Folders:** ~50 folders

This is normal and required for Excel functionality.

---

## Next Steps

After you complete the installation, let me know and I'll:

1. Update all Excel export code to use `.xlsx` format
2. Update all Excel upload code to read `.xlsx` files
3. Test the functionality
4. Create documentation for the new features

---

**Ready to begin?** Run the commands in Step 2!
