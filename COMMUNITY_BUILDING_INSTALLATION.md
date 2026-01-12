# Community Building System - Installation & Testing Guide

## 📋 Overview

This guide will help you install and test the new **Community Building System** feature (v4.1) for the GetToKnow platform.

---

## ✅ Prerequisites

Before installing, ensure you have:
- ✅ GetToKnow v4.0 installed and running
- ✅ Database access (MySQL/MariaDB)
- ✅ Admin account credentials
- ✅ At least one community created

---

## 🚀 Installation Steps

### Step 1: Run Database Migration

1. Navigate to your database management tool (phpMyAdmin, MySQL Workbench, etc.)
2. Select your GetToKnow database (`u717011923_gettoknow_db`)
3. Import the schema file:
   ```sql
   -- File location: database/community_building_schema.sql
   ```
4. Execute the SQL file

**This will create:**
- 5 new tables: `sub_communities`, `custom_field_definitions`, `sub_community_members`, `member_custom_data`, `sub_community_custom_data`
- Feature registration in `features` table

### Step 2: Verify Database Tables

Run this query to verify installation:

```sql
SHOW TABLES LIKE '%sub_community%';
SHOW TABLES LIKE '%custom_field%';
```

You should see 5 tables listed.

### Step 3: Verify Feature Registration

Check if the feature was registered:

```sql
SELECT * FROM features WHERE feature_key = 'community_building';
```

You should see:
- `feature_name`: Community Building
- `feature_icon`: 👥
- `feature_key`: community_building
- `is_active`: 1

---

## 🎯 Enable the Feature

### Method 1: Using Admin UI (Recommended)

1. Login as **Admin**
2. Go to **Admin Dashboard**
3. Click **"⚙️ Manage Features"** button
4. Select your community from dropdown
5. Find **"Community Building"** card
6. Click **"✓ Enable Feature"**
7. Confirm the action

### Method 2: Using SQL

```sql
-- Get community_id and feature_id
SELECT community_id FROM communities WHERE community_name = 'Your Community Name';
SELECT feature_id FROM features WHERE feature_key = 'community_building';

-- Enable feature (replace IDs with actual values)
INSERT INTO community_features (community_id, feature_id, is_enabled, enabled_by, enabled_date)
VALUES (1, 2, 1, 1, NOW());
```

### Verify Feature is Enabled

Login as **Group Admin** for that community:
- You should see **"👥 Community Building"** card on the dashboard
- Click it to access the feature

---

## 🧪 Testing Guide

### Test 1: Create Sub-Communities

1. Login as **Group Admin**
2. Click **"👥 Community Building"** card
3. Click **"🏘️ Sub-Communities"**
4. Click **"+ Create Sub-Community"**
5. Fill in:
   - Sub-Community Name: "IT Department"
   - Description: "Information Technology team"
   - Status: Active
6. Click **"Create Sub-Community"**
7. Verify: Sub-community appears in list

**Expected Result:** ✅ Sub-community created successfully

### Test 2: Define Custom Fields

1. From Community Building dashboard
2. Click **"📝 Custom Fields"**
3. Create fields for members:

   **Field 1:**
   - Field Label: "Designation"
   - Field Type: Text
   - Applies To: Member
   - Required: ✓ Checked

   **Field 2:**
   - Field Label: "Mobile Number"
   - Field Type: Phone
   - Applies To: Member
   - Required: ✓ Checked

   **Field 3:**
   - Field Label: "Department"
   - Field Type: Dropdown
   - Dropdown Options: `IT, HR, Finance, Marketing, Operations`
   - Applies To: Member
   - Required: ✓ Checked

   **Field 4:**
   - Field Label: "Date of Joining"
   - Field Type: Date
   - Applies To: Member
   - Required: ✗ Unchecked

4. Click **"Create Field"** for each

**Expected Result:** ✅ All fields appear in "Member Fields" panel

### Test 3: Register New Member

1. From Community Building dashboard
2. Click **"👤 Members"**
3. Click **"+ Register New Member"**
4. Ensure **"📝 Create New Member"** is selected
5. Fill in:
   - Sub-Community: Select "IT Department"
   - Full Name: "John Doe"
   - Email: "john.doe@example.com"
   - Phone Number: "1234567890"
   - Designation: "Software Engineer"
   - Mobile Number: "9876543210"
   - Department: "IT"
   - Date of Joining: "2024-01-15"
6. Click **"Register Member"**

**Expected Result:** ✅ Member registered and appears in member list

### Test 4: Use Existing Database (Lottery System Integration)

**Prerequisites:** Create some users in the lottery system first

1. From member registration page
2. Click **"👥 Use Existing Database"** button
3. Select Sub-Community: "IT Department"
4. Select Existing User from dropdown
5. Fill in custom fields
6. Click **"Register Member"**

**Expected Result:** ✅ Existing user assigned to sub-community

### Test 5: View Sub-Community Details

1. Go to **"Sub-Communities"**
2. Click **"View"** on any sub-community
3. Verify:
   - Sub-community details displayed
   - Member count is correct
   - Members list shows all assigned members

**Expected Result:** ✅ All details displayed correctly

### Test 6: Filter Members

1. Go to **"👤 Members"**
2. Use **"Filter by Sub-Community"** dropdown
3. Select "IT Department"
4. Verify only IT Department members shown
5. Use **"Filter by Status"** dropdown
6. Test filtering by Active/Inactive

**Expected Result:** ✅ Filters work correctly

### Test 7: Edit Sub-Community

1. Go to **"Sub-Communities"**
2. Click **"Edit"** on a sub-community
3. Modify:
   - Change name
   - Update description
   - Change status to Inactive
4. Click **"Update Sub-Community"**
5. Verify changes saved

**Expected Result:** ✅ Sub-community updated successfully

### Test 8: Delete Custom Field

1. Go to **"Custom Fields"**
2. Click **"Delete"** on a field
3. Confirm deletion
4. Verify field removed from list

**Expected Result:** ✅ Field deleted (associated data also removed)

### Test 9: Remove Member from Sub-Community

1. Go to **"Members"**
2. Click **"Remove"** on a member
3. Confirm action
4. Verify member no longer in list

**Expected Result:** ✅ Member removed from sub-community

### Test 10: Data Isolation Test (Multiple Communities)

**Prerequisites:** Have at least 2 communities with the feature enabled

1. Login as Group Admin for **Community A**
2. Create sub-community "Team A"
3. Register member "User A"
4. Logout
5. Login as Group Admin for **Community B**
6. Verify:
   - Cannot see "Team A" sub-community
   - Cannot see "User A" member
   - Only sees Community B's data

**Expected Result:** ✅ Complete data isolation between communities

---

## 🎨 UI/UX Verification

Check these elements:

### Dashboard
- ✅ Feature card displays with 👥 icon
- ✅ "Community Building" title
- ✅ Description visible
- ✅ "Access Feature" button works

### Community Building Main Page
- ✅ Stats cards show correct numbers
- ✅ Action cards clickable
- ✅ Recent sub-communities list displays
- ✅ Responsive design (test on mobile)

### Sub-Communities Page
- ✅ Grid layout displays cards
- ✅ Status badges show colors (green=active, red=inactive)
- ✅ Member count displays
- ✅ Edit/View/Delete buttons work

### Custom Fields Page
- ✅ Split view shows member fields and sub-community fields
- ✅ Form builder validates input
- ✅ Dropdown options toggle shows/hides
- ✅ Field type badges display correctly

### Member Registration
- ✅ Data source selector works
- ✅ Dynamic fields render based on definitions
- ✅ Required fields show asterisk
- ✅ Dropdown options populate correctly
- ✅ Date picker works

---

## 🐛 Troubleshooting

### Issue 1: Feature Card Not Showing

**Problem:** Community Building doesn't appear on Group Admin dashboard

**Solutions:**
1. Verify feature is enabled:
   ```sql
   SELECT * FROM community_features
   WHERE community_id = YOUR_COMMUNITY_ID
   AND feature_id = (SELECT feature_id FROM features WHERE feature_key = 'community_building');
   ```
2. Check `is_enabled = 1`
3. If not enabled, run enable SQL or use Admin UI

### Issue 2: Tables Not Created

**Problem:** Error when accessing feature pages

**Solutions:**
1. Re-run `database/community_building_schema.sql`
2. Check for SQL errors in migration
3. Verify foreign key constraints

### Issue 3: Cannot Register Member

**Problem:** "Sub-community name is required" error

**Solutions:**
1. Verify sub-communities exist
2. Check sub-community status is 'active'
3. Ensure user isn't already in another sub-community:
   ```sql
   SELECT * FROM sub_community_members WHERE user_id = USER_ID;
   ```

### Issue 4: Custom Fields Not Showing

**Problem:** Fields don't appear in registration form

**Solutions:**
1. Check field status is 'active'
2. Verify `applies_to = 'member'`
3. Check community_id matches

### Issue 5: Dropdown Options Not Working

**Problem:** Dropdown shows no options

**Solutions:**
1. Verify `field_options` contains valid JSON:
   ```sql
   SELECT field_options FROM custom_field_definitions
   WHERE field_type = 'dropdown';
   ```
2. Should look like: `["IT", "HR", "Finance"]`

---

## 📊 Database Verification Queries

### Check Feature Status
```sql
SELECT c.community_name, f.feature_name, cf.is_enabled
FROM communities c
JOIN community_features cf ON c.community_id = cf.community_id
JOIN features f ON cf.feature_id = f.feature_id
WHERE f.feature_key = 'community_building';
```

### View All Sub-Communities
```sql
SELECT sc.sub_community_name, c.community_name, sc.status,
       COUNT(scm.user_id) as member_count
FROM sub_communities sc
JOIN communities c ON sc.community_id = c.community_id
LEFT JOIN sub_community_members scm ON sc.sub_community_id = scm.sub_community_id
GROUP BY sc.sub_community_id;
```

### View All Custom Fields
```sql
SELECT community_id, field_label, field_type, applies_to, is_required, status
FROM custom_field_definitions
ORDER BY community_id, applies_to, display_order;
```

### View Members with Sub-Communities
```sql
SELECT u.full_name, u.email, sc.sub_community_name, c.community_name
FROM users u
JOIN sub_community_members scm ON u.user_id = scm.user_id
JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
JOIN communities c ON sc.community_id = c.community_id
ORDER BY c.community_name, sc.sub_community_name, u.full_name;
```

### View Member Custom Data
```sql
SELECT u.full_name, cfd.field_label, mcd.field_value
FROM users u
JOIN member_custom_data mcd ON u.user_id = mcd.user_id
JOIN custom_field_definitions cfd ON mcd.field_id = cfd.field_id
WHERE u.user_id = YOUR_USER_ID;
```

---

## ✨ Feature Highlights

### What Makes This Feature Special:

1. **Dynamic Schema**
   - No hardcoded fields
   - Group Admin controls data structure
   - Infinite flexibility

2. **Database Integration**
   - Reuses existing users from lottery system
   - No duplicate data entry
   - Seamless integration

3. **SAAS Compliant**
   - Complete data isolation
   - Per-community configuration
   - Feature-based access control

4. **User-Friendly**
   - Visual form builder
   - Drag-and-drop like experience
   - Beautiful UI/UX

---

## 📝 Post-Installation Checklist

- [ ] Database tables created successfully
- [ ] Feature registered in `features` table
- [ ] Feature enabled for test community
- [ ] Group Admin can access Community Building dashboard
- [ ] Can create sub-communities
- [ ] Can define custom fields
- [ ] Can register members (both new and existing)
- [ ] Can view member directory
- [ ] Can filter members by sub-community
- [ ] Can edit/delete sub-communities
- [ ] Data isolation verified between communities
- [ ] UI responsive on mobile devices
- [ ] All forms validate correctly
- [ ] Breadcrumb navigation works

---

## 🎓 Best Practices

### For Group Admins:

1. **Define Fields First**
   - Create all custom fields before registering members
   - Plan your data structure carefully

2. **Use Descriptive Names**
   - Clear sub-community names
   - Meaningful field labels

3. **Start Small**
   - Test with 1-2 sub-communities first
   - Add more as you understand the system

### For Developers:

1. **Always Filter by community_id**
   ```php
   WHERE community_id = :community_id
   ```

2. **Validate Input**
   - Check required fields
   - Validate field types
   - Sanitize output

3. **Use Transactions**
   - For multi-step operations
   - Rollback on error

---

## 🚀 Next Steps After Installation

1. **Production Deployment**
   - Backup database
   - Run migration on production
   - Enable feature for live communities

2. **User Training**
   - Train Group Admins on custom fields
   - Demonstrate member registration
   - Show reporting capabilities

3. **Monitor Usage**
   - Track feature adoption
   - Gather user feedback
   - Identify improvement areas

---

## 📞 Support

If you encounter issues:
1. Check troubleshooting section above
2. Review database verification queries
3. Check error logs in PHP
4. Verify database constraints

---

**Installation Complete! 🎉**

You now have a fully functional Community Building System that integrates seamlessly with your existing lottery system and provides flexible, dynamic member management for your communities.
