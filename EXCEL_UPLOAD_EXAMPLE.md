# Excel Upload Examples with Connection Fields

## How Connection Fields Appear in Excel

When you download the sample Excel, ALL custom fields you created will appear as columns, including connection fields.

---

## Example 1: Basic Setup with Connection Fields

### Custom Fields Defined:
**For Sub-Communities:**
1. Department Name (Text, Required)
2. Location (Text, Optional)
3. Manager (Text, Required)

**For Members:**
1. Full Name (Text, Required)
2. Email (Text, Required)
3. Mobile Number (Phone, Optional)
4. Select Department (Sub-Community Selector, Required)
5. Department Name (Auto-Populate from Sub-Community "Department Name")
6. Office Location (Auto-Populate from Sub-Community "Location")

### Excel Download for Members Will Look Like:

**Sheet: Members**

| Full Name * | Email * | Mobile Number | Select Department * | Department Name | Office Location |
|-------------|---------|---------------|---------------------|-----------------|-----------------|
| John Doe    | john@   | 1234567890   | IT Department       | (auto-fills)    | (auto-fills)    |

**Sheet: Instructions**
```
INSTRUCTIONS FOR BULK MEMBER IMPORT

Custom Fields (defined by Group Admin):
- Full Name (Required) - Type: text
- Email (Required) - Type: text
- Mobile Number (Optional) - Type: phone
- Select Department (Required) - Type: sub_community_selector - Enter sub-community name exactly as it appears
- Department Name (Optional) - Type: auto_populate - Can be left empty (will auto-fill from sub-community)
- Office Location (Optional) - Type: auto_populate - Can be left empty (will auto-fill from sub-community)

Notes:
- Do not modify the header row
- Fill in all required fields (marked with *)
- For Sub-Community Selector: Enter the exact sub-community name
- For Auto-Populate fields: Leave empty or enter custom value
- Delete the sample row before uploading
```

---

## Example 2: Filling the Excel

### Scenario: Registering 3 employees

| Full Name    | Email              | Mobile Number | Select Department | Department Name | Office Location |
|--------------|-------------------|---------------|-------------------|-----------------|-----------------|
| John Smith   | john@company.com  | 9876543210   | IT Department     |                 |                 |
| Jane Doe     | jane@company.com  | 9876543211   | HR Department     |                 |                 |
| Bob Wilson   | bob@company.com   | 9876543212   | IT Department     | IT Dept        | Building A      |

### What Happens During Import:

**Row 1 (John Smith):**
- System finds "IT Department" sub-community
- Creates user with name, email, mobile
- Assigns to IT Department
- Auto-fills: Department Name = "Information Technology" (from sub-community)
- Auto-fills: Office Location = "Building A, Floor 3" (from sub-community)

**Row 2 (Jane Doe):**
- System finds "HR Department" sub-community
- Creates user
- Assigns to HR Department
- Auto-fills: Department Name = "Human Resources"
- Auto-fills: Office Location = "Building B, Floor 1"

**Row 3 (Bob Wilson):**
- System finds "IT Department"
- Creates user
- Assigns to IT Department
- Uses manual values: Department Name = "IT Dept", Office Location = "Building A"
- (Manual values override auto-populate)

---

## Example 3: Sub-Community Selector Options

### If You Have These Sub-Communities:
1. IT Department
2. HR Department
3. Finance Department
4. Marketing Department

### In Excel, You Must Enter EXACTLY:
| Select Department |
|-------------------|
| IT Department     | ✅ Correct
| Hr Department     | ❌ Wrong (case mismatch)
| IT Dept           | ❌ Wrong (doesn't match exact name)
| Finance           | ❌ Wrong (incomplete name)

**Important:** The name must match EXACTLY as it appears in your sub-communities list.

---

## Example 4: Multiple Field Types Together

| Employee Name * | Email * | Select Branch * | Branch Name | Role | Department | Join Date |
|-----------------|---------|-----------------|-------------|------|------------|-----------|
| Alice Johnson   | alice@  | Mumbai Branch   | (auto)      | Dev  | IT         | 2024-01-15|

**Field Types:**
- Employee Name: Text
- Email: Text
- Select Branch: Sub-Community Selector
- Branch Name: Auto-Populate
- Role: Dropdown (Dev, Manager, Lead)
- Department: Text
- Join Date: Date

---

## Common Questions

### Q1: Can I leave auto-populate fields empty?
**Yes!** They will automatically fill from the sub-community.

### Q2: Can I override auto-populate fields?
**Yes!** If you enter a value, it will use your value instead of auto-populating.

### Q3: What if I enter wrong sub-community name?
**Error:** Import will fail for that row with error "Sub-community not found"

### Q4: Can I have multiple sub-community selector fields?
**Technically yes**, but only one should be used (member can only belong to one sub-community)

### Q5: Do I need to fill all columns?
- **Required fields (marked with *)**: YES
- **Optional fields**: NO
- **Auto-populate fields**: NO (but you can if you want custom values)

---

## Step-by-Step: Excel Upload Process

### Step 1: Download Sample
1. Go to Bulk Operations
2. Click "Download Sample Format"
3. Choose "Members"
4. Excel file downloads with your custom fields as columns

### Step 2: Fill Excel
1. Open Excel
2. Check Instructions sheet for field types
3. Fill data starting from Row 2 (Row 1 is headers)
4. For Sub-Community Selector: Enter exact sub-community name
5. For Auto-Populate: Leave empty or enter custom value
6. Delete the sample row

### Step 3: Upload
1. Save Excel file
2. Go to Bulk Operations → Bulk Import Members
3. Upload file
4. Preview shows:
   - NEW badge for new members
   - Validation errors if any
   - Auto-populated values
5. Confirm import
6. Members created and linked to sub-communities

---

## Visual Flow

```
Custom Fields Defined
        ↓
Download Sample Excel
        ↓
Excel has columns for ALL custom fields
(including Sub-Community Selector & Auto-Populate)
        ↓
Fill Excel with data
        ↓
Upload Excel
        ↓
System processes each row:
  1. Looks up sub-community by name
  2. Creates user account
  3. Assigns to sub-community
  4. Auto-fills auto-populate fields
  5. Saves all custom field values
        ↓
Members successfully imported!
```

---

## Error Examples

### Error: "Sub-community not found"
```excel
| Full Name | Select Department |
|-----------|-------------------|
| John Doe  | IT Dept           | ❌ Should be "IT Department"
```

### Error: "Email is required"
```excel
| Full Name | Email | Select Department |
|-----------|-------|-------------------|
| John Doe  |       | IT Department     | ❌ Email cannot be empty
```

### Error: "Member name is required"
```excel
| Full Name | Email        | Select Department |
|-----------|--------------|-------------------|
|           | john@co.com  | IT Department     | ❌ Name cannot be empty
```

---

## Best Practices

1. **Always check Instructions sheet** before filling Excel
2. **Keep sub-community names consistent** - exact spelling matters
3. **Leave auto-populate fields empty** unless you need custom values
4. **Validate email format** before uploading
5. **Test with 1-2 rows first** before bulk uploading 100+ rows
6. **Keep backup** of your Excel file
7. **Use Undo feature** if import goes wrong

---

## Summary

✅ **Connection fields DO appear in Excel**
✅ **Sub-Community Selector**: Enter exact sub-community name
✅ **Auto-Populate**: Can leave empty (auto-fills) or enter custom value
✅ **Instructions sheet** guides you on each field type
✅ **Sample data** shows format examples
✅ **All custom fields** you define appear as columns

**The system is fully dynamic - whatever fields you create will appear in the Excel!**
