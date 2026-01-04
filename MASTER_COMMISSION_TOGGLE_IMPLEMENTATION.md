# Master Commission Toggle Implementation

## Overview
Added a prominent **Master Commission Control** toggle button to the commission setup page that allows group admins to easily enable/disable the entire commission system for an event.

## Implementation Date
January 4, 2026

---

## Changes Made

### 1. Commission Setup Page ([lottery-commission-setup.php](public/group-admin/lottery-commission-setup.php))

#### Backend Changes (Lines 48-71)
- Added new POST action handler `master_commission`
- Handles toggling of the `commission_enabled` field in the `commission_settings` table
- Supports both INSERT (new settings) and UPDATE (existing settings)
- Redirects with success message after saving

```php
// Handle master commission toggle
if ($action === 'master_commission') {
    $masterEnabled = isset($_POST['commission_enabled']) ? 1 : 0;

    if ($settings) {
        $updateQuery = "UPDATE commission_settings SET
                       commission_enabled = :enabled
                       WHERE event_id = :event_id";
    } else {
        $updateQuery = "INSERT INTO commission_settings
                       (event_id, commission_enabled)
                       VALUES (:event_id, :enabled)";
    }
    $stmt = $db->prepare($updateQuery);
    $stmt->bindParam(':event_id', $eventId);
    $stmt->bindParam(':enabled', $masterEnabled);

    if ($stmt->execute()) {
        header("Location: ?id=$eventId&success=master_saved");
        exit;
    } else {
        $error = 'Failed to save master commission settings';
    }
}
```

#### Frontend Changes (Lines 332-376)
Added a prominent card section at the top of the page featuring:

**Visual Design:**
- Large card with dynamic border (green when enabled, gray when disabled)
- Green background tint when enabled
- Gear emoji icon (⚙️) and "Master Commission Control" heading
- Status badge showing "ENABLED" or "DISABLED"
- Toggle switch that submits form on change
- Confirmation dialog before toggling

**Information Display:**
- **When ENABLED:** Shows "✓ Commission system is active" with green checkmark
- **When DISABLED:** Shows "✗ Commission system is disabled" with red cross
- Context-aware info box explaining the current state
- Auto-submit form with JavaScript confirmation

**User Experience:**
- One-click toggle with immediate visual feedback
- Confirmation prompt prevents accidental changes
- Clear status indicators throughout
- Responsive design works on all devices

### 2. Events List Page ([lottery.php](public/group-admin/lottery.php))

#### Query Enhancement (Lines 19-30)
Modified the events query to include commission status:

```php
$query = "SELECT
            le.*,
            cs.commission_enabled
          FROM lottery_events le
          LEFT JOIN commission_settings cs ON le.event_id = cs.event_id
          WHERE le.community_id = :community_id
          ORDER BY le.created_at DESC";
```

#### Visual Indicators (Lines 241-247)
Added commission status badge next to event name:

```php
<?php if ($event['commission_enabled']): ?>
    <span class="badge badge-success" style="font-size: 12px;" title="Commission system is enabled for this event">
        💰 Commission ON
    </span>
<?php endif; ?>
```

#### Enhanced Commission Button (Lines 317-319)
Updated the Commission button styling to indicate status:
- **Green button with checkmark (✓)** when commission is enabled
- **Gray button** when commission is disabled
- Tooltip shows current status on hover

```php
<a href="/public/group-admin/lottery-commission-setup.php?id=<?php echo $event['event_id']; ?>"
   class="btn btn-sm"
   style="background: <?php echo $event['commission_enabled'] ? '#10b981' : '#6b7280'; ?>; color: white;"
   title="<?php echo $event['commission_enabled'] ? 'Commission is enabled' : 'Commission is disabled'; ?>">
    <span>💰</span> <span>Commission <?php echo $event['commission_enabled'] ? '✓' : ''; ?></span>
</a>
```

---

## Database Field Usage

### `commission_settings.commission_enabled`
- **Type:** TINYINT(1)
- **Default:** 0 (disabled)
- **Purpose:** Master switch for entire commission system
- **Comment:** "Group admin can toggle commission on/off"

### How It Works Across the System
The `commission_enabled` field is checked in multiple locations:

1. **Payment Collection** ([lottery-payment-collect.php:131](public/group-admin/lottery-payment-collect.php#L131))
   ```sql
   SELECT * FROM commission_settings
   WHERE event_id = :event_id AND commission_enabled = 1
   ```

2. **Excel Upload** ([lottery-reports-excel-upload.php:326](public/group-admin/lottery-reports-excel-upload.php#L326), [line 662](public/group-admin/lottery-reports-excel-upload.php#L662))
   ```sql
   SELECT * FROM commission_settings
   WHERE event_id = :event_id AND commission_enabled = 1
   ```

3. **Commission Sync** ([lottery-commission-sync.php:38](public/group-admin/lottery-commission-sync.php#L38))
   ```sql
   SELECT * FROM commission_settings
   WHERE event_id = :event_id AND commission_enabled = 1
   ```

**Impact:** When `commission_enabled = 0`, NO commissions are calculated anywhere in the system, regardless of individual commission type settings.

---

## User Workflow

### How Admins Use This Feature

1. **Navigate to Events List**
   - See commission status badge next to event name (if enabled)
   - Commission button shows green with ✓ if enabled, gray if disabled

2. **Click Commission Button**
   - Opens commission setup page
   - Master Commission Control card is prominently displayed at the top

3. **Toggle Commission System**
   - Click the toggle switch
   - Confirm the action in popup dialog
   - Page refreshes with success message
   - All visual indicators update immediately

4. **Configure Individual Types** (only when master is enabled)
   - Early Payment Commission
   - Standard Payment Commission
   - Extra Books Commission
   - Each can be enabled/disabled independently

---

## Benefits

### 1. Easy Access
- Prominent placement at top of commission setup page
- One-click toggle with instant feedback
- No need to navigate through complex settings

### 2. Clear Visual Feedback
- Color-coded borders and backgrounds
- Status badges throughout the system
- Tooltip hints on buttons
- Confirmation dialogs prevent accidents

### 3. System-Wide Integration
- Events list shows commission status at a glance
- Commission button indicates current state
- All commission calculations respect the master switch

### 4. Admin-Friendly
- Simple on/off toggle (no complex configuration)
- Can quickly enable/disable for events
- Clear explanation of what the toggle does
- Safe with confirmation prompts

---

## Testing Checklist

When testing this feature:

- [ ] Navigate to Events List page
- [ ] Verify commission status badge appears for events with commission enabled
- [ ] Click Commission button and verify it opens setup page
- [ ] Verify Master Commission Control card is visible at top
- [ ] Toggle commission from disabled to enabled
  - [ ] Confirm dialog appears
  - [ ] Success message shows after save
  - [ ] Card changes to green border/background
  - [ ] Badge updates to "ENABLED"
- [ ] Toggle commission from enabled to disabled
  - [ ] Confirm dialog appears
  - [ ] Success message shows after save
  - [ ] Card changes to gray border/white background
  - [ ] Badge updates to "DISABLED"
- [ ] Return to Events List
  - [ ] Verify badge appears/disappears correctly
  - [ ] Verify Commission button color updates
- [ ] Verify maintenance tools only show when commission is enabled
- [ ] Test individual commission type toggles still work
- [ ] Verify commission calculations respect the master switch

---

## Database Query to Check Status

To check the commission status for Event 5:

```sql
SELECT
    event_id,
    commission_enabled,
    early_commission_enabled,
    standard_commission_enabled,
    extra_books_commission_enabled,
    early_payment_date,
    standard_payment_date,
    extra_books_date
FROM commission_settings
WHERE event_id = 5;
```

To enable commission for Event 5:

```sql
UPDATE commission_settings
SET commission_enabled = 1
WHERE event_id = 5;

-- Or insert if doesn't exist:
INSERT INTO commission_settings (event_id, commission_enabled)
VALUES (5, 1)
ON DUPLICATE KEY UPDATE commission_enabled = 1;
```

---

## Files Modified

1. [public/group-admin/lottery-commission-setup.php](public/group-admin/lottery-commission-setup.php)
   - Added master commission toggle handler (lines 48-71)
   - Added success message for master toggle (line 184)
   - Added Master Commission Control UI section (lines 332-376)

2. [public/group-admin/lottery.php](public/group-admin/lottery.php)
   - Enhanced query to fetch commission status (lines 19-30)
   - Added commission badge to event name (lines 241-247)
   - Enhanced Commission button styling (lines 317-319)

---

## Future Enhancements

Potential improvements for future versions:

1. **Bulk Commission Toggle**
   - Enable/disable commission for multiple events at once
   - From events list page

2. **Commission Dashboard**
   - Overview of all events with commission status
   - Quick toggle from dashboard

3. **Audit Log**
   - Track when commission was enabled/disabled
   - Who made the change
   - Timestamp of changes

4. **Email Notifications**
   - Notify relevant users when commission is enabled
   - Alert when commission settings change

---

## Support

For issues or questions about this feature:
- Check the database status with the SQL queries above
- Verify commission_enabled field exists in commission_settings table
- Ensure the migration file `update_commission_individual_controls.sql` was run
- Check browser console for JavaScript errors
- Verify database connection is working

---

## Conclusion

The Master Commission Toggle feature provides a simple, intuitive way for group admins to control the commission system for each event. With clear visual indicators throughout the interface and a prominent toggle on the setup page, admins can easily manage commission settings without confusion.

The feature is fully integrated with the existing commission system and respects the master switch across all commission calculation points in the application.
