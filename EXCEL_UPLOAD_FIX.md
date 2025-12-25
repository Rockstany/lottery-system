# Excel Upload Fix - PHPSpreadsheet Removed

**Date:** 2025-12-25
**Issue:** "Failed to open stream: No such file or directory vendor/autoload.php"
**Status:** ✅ FIXED

---

## Problem

When uploading Excel files, the system showed this error:

```
Fatal error: Failed opening required '/home/u717011923/domains/zatana.in/public_html/
public/group-admin/../../vendor/autoload.php' (include_path='...')
in lottery-reports-excel-upload.php on line 52
```

### Root Cause

The upload processor (`lottery-reports-excel-upload.php`) was trying to use **PHPSpreadsheet** library to read Excel files:

```php
require_once __DIR__ . '/../../vendor/autoload.php';  // ❌ Doesn't exist!

use PhpOffice\PhpSpreadsheet\IOFactory;
$spreadsheet = IOFactory::load($file['tmp_name']);  // ❌ Library not installed
```

**But:**
- PHPSpreadsheet library was never installed
- Would require Composer (`composer require phpoffice/phpspreadsheet`)
- The Excel files we're generating are **HTML format**, not binary XLSX

---

## Solution Applied

**File:** `lottery-reports-excel-upload.php` (Lines 51-82, 107-146, 193-209)

### Changed Upload Parser from PHPSpreadsheet to HTML Parser

**Before (Required PHPSpreadsheet):**
```php
require_once __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load($file['tmp_name']);
$sheet = $spreadsheet->getActiveSheet();
$row = $sheet->rangeToArray('A' . $rowNumber . ':Z' . $rowNumber)[0];
```

**After (Uses Built-in PHP DOMDocument):**
```php
// Read file as HTML (since that's what we're generating)
$fileContent = file_get_contents($file['tmp_name']);

// Load HTML into DOMDocument (built-in PHP class)
$dom = new DOMDocument();
@$dom->loadHTML($fileContent);

// Find the data table
$tables = $dom->getElementsByTagName('table');
// ... find table with "Book Number" header ...

// Parse rows
$rows = $dataTable->getElementsByTagName('tr');
foreach ($rows as $tr) {
    $cells = $tr->getElementsByTagName('td');
    $row = [];
    foreach ($cells as $cell) {
        $row[] = trim($cell->textContent);
    }
    // ... process row ...
}
```

---

## Key Changes

### 1. Removed PHPSpreadsheet Dependency
- ❌ No more `require_once vendor/autoload.php`
- ❌ No more `use PhpOffice\PhpSpreadsheet`
- ✅ Uses built-in PHP `DOMDocument` class

### 2. HTML Table Parser
- ✅ Reads uploaded .xls file as HTML
- ✅ Parses HTML tables using DOM methods
- ✅ Finds correct table by looking for "Book Number" header
- ✅ Extracts cell data using `textContent`

### 3. Date Parsing Updated
**Before (Excel date format):**
```php
if (is_numeric($paymentDateStr)) {
    $paymentDate = Date::excelToDateTimeObject($paymentDateStr)->format('Y-m-d');
}
```

**After (Multiple formats):**
```php
// DD-MM-YYYY format
if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $paymentDateStr, $matches)) {
    $paymentDate = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
}
// DD/MM/YYYY format
elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $paymentDateStr, $matches)) {
    $paymentDate = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
}
// Try standard strtotime
else {
    $timestamp = strtotime($paymentDateStr);
    $paymentDate = date('Y-m-d', $timestamp);
}
```

### 4. Loop Structure Changed
**Before (Excel row iteration):**
```php
$rowNumber = 2;
$maxRows = $sheet->getHighestRow();

while ($rowNumber <= $maxRows) {
    $row = $sheet->rangeToArray('A' . $rowNumber . ':Z' . $rowNumber)[0];
    $rowNumber++;
}
```

**After (HTML table iteration):**
```php
$rows = $dataTable->getElementsByTagName('tr');
foreach ($rows as $tr) {
    $cells = $tr->getElementsByTagName('td');
    if ($cells->length === 0) continue; // Skip header

    $row = [];
    foreach ($cells as $cell) {
        $row[] = trim($cell->textContent);
    }
}
```

---

## Benefits

### ✅ No External Dependencies
- No Composer required
- No vendor folder needed
- Works on any PHP server (built-in classes only)

### ✅ Matches File Format
- Excel files are HTML format
- Parser reads HTML directly
- No format conversion needed

### ✅ Simpler Code
- Fewer lines of code
- Easier to understand
- Easier to debug

### ✅ Better Performance
- No library overhead
- Direct HTML parsing
- Faster execution

---

## How It Works Now

### 1. User Downloads Excel Template
- System generates HTML-format Excel file
- File has `.xls` extension
- Contains tables with data

### 2. User Edits in Excel
- Opens file in Microsoft Excel
- Excel recognizes HTML format
- User edits cells

### 3. User Re-uploads File
- Uploads edited `.xls` file
- PHP reads file as HTML
- DOMDocument parses HTML structure

### 4. System Processes Data
- Finds main data table (has "Book Number" column)
- Loops through rows
- Extracts cell values
- Updates database

---

## Testing

### Test Cases:

1. **✅ Download Template**
   - Download Excel template with data
   - File should open in Excel without error

2. **✅ Edit Data**
   - Change member name
   - Change payment amount
   - Change payment date

3. **✅ Upload File**
   - Upload edited file
   - No PHPSpreadsheet error
   - Data successfully updated

4. **✅ Multiple Formats**
   - Test payment date: "25-12-2025"
   - Test payment date: "25/12/2025"
   - Test payment date: "Dec 25, 2025"
   - All should parse correctly

---

## Technical Details

### DOMDocument vs PHPSpreadsheet

| Feature | DOMDocument | PHPSpreadsheet |
|---------|-------------|----------------|
| **Installation** | Built-in PHP | Requires Composer |
| **File Size** | ~100KB | ~5MB+ (entire library) |
| **Memory Usage** | Low | High |
| **Speed** | Fast | Slower |
| **File Formats** | HTML/XML | XLSX, XLS, CSV, etc. |
| **Our Use Case** | ✅ Perfect | ❌ Overkill |

### Why DOMDocument is Better for This Case:

1. **File Format Match:**
   - We generate HTML-format Excel files
   - DOMDocument is designed for HTML/XML
   - No format conversion needed

2. **Server Compatibility:**
   - DOMDocument is built into PHP
   - No server-side installation required
   - Works on any hosting

3. **Simplicity:**
   - We only need to read tables
   - Don't need Excel formulas, charts, etc.
   - DOMDocument is sufficient

---

## Troubleshooting

### If Upload Still Fails:

**Check 1: File Format**
```
Error: "Could not find data table in Excel file"
Cause: File doesn't have expected HTML structure
Solution: Re-download template, don't create file from scratch
```

**Check 2: Column Order**
```
Error: "Book number 'BK0001' not found"
Cause: Columns are in wrong order
Solution: Don't rearrange columns in Excel template
```

**Check 3: Date Format**
```
Error: Payment date not recognized
Cause: Date in unsupported format
Solution: Use DD-MM-YYYY or DD/MM/YYYY format
```

---

## Alternative Considered (Not Used)

### Option: Install PHPSpreadsheet

**Pros:**
- Could read true .xlsx files
- More features (formulas, formatting)
- Industry standard

**Cons:**
- ❌ Requires Composer installation
- ❌ Large file size (5MB+)
- ❌ Higher memory usage
- ❌ Overkill for our simple use case
- ❌ Doesn't match current HTML file format

**Why Not Used:** We're already generating HTML-format files successfully. Adding PHPSpreadsheet would require changing BOTH download and upload logic, and add unnecessary complexity.

---

## Files Modified

1. **lottery-reports-excel-upload.php**
   - Lines 51-82: Removed PHPSpreadsheet, added DOMDocument
   - Lines 107-146: Changed loop from Excel rows to HTML table rows
   - Lines 193-209: Updated date parsing (removed Excel date format)

2. **No Other Files Changed**
   - Template generation still uses HTML format
   - Export still uses HTML format
   - Only upload parser changed

---

## Summary

**Before:**
- ❌ Required PHPSpreadsheet library (not installed)
- ❌ Fatal error on file upload
- ❌ Unable to process Excel files

**After:**
- ✅ Uses built-in PHP DOMDocument
- ✅ No external dependencies
- ✅ Successfully processes HTML-format Excel files
- ✅ All features working

**Result:** Excel upload now works without any library installation!
