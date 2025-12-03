# Deletion Request System Documentation

## Overview
A secure deletion workflow that requires Super Admin approval for deleting lottery events and payment transactions. Group Admins can request deletions with a reason, and Super Admins review and approve/reject these requests.

## Features

### For Group Admins
- **Request Delete** button for lottery events and payment transactions
- Required to provide detailed reason for deletion
- Cannot directly delete items (security measure)
- Receive confirmation when request is submitted
- Prevented from submitting duplicate requests

### For Super Admins
- **Deletion Requests** page to review all requests
- Filter by status: Pending, Approved, Rejected, All
- Real-time notification badge showing pending requests count
- Approve requests with optional notes
- Reject requests with required reason
- Automatic deletion of item when approved
- Full audit trail in activity logs

## Database Structure

### Table: `deletion_requests`
```sql
CREATE TABLE `deletion_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_type` ENUM('lottery_event', 'transaction') NOT NULL,
  `item_id` INT NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `requested_by` INT NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT NULL,
  `review_notes` TEXT NULL,
  `reviewed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Files Created/Modified

### New Files Created

1. **`database/migrations/add_deletion_requests_table.sql`**
   - Database schema for deletion requests
   - Activity log type definitions

2. **`public/group-admin/lottery-delete-request.php`**
   - Handler for lottery event deletion requests
   - Validates event exists and belongs to community
   - Prevents duplicate pending requests
   - Sends email notification to admin

3. **`public/group-admin/transaction-delete-request.php`**
   - Handler for payment transaction deletion requests
   - Validates transaction exists
   - Prevents duplicate pending requests
   - Sends email notification to admin

4. **`public/admin/deletion-requests.php`**
   - Super Admin dashboard for managing deletion requests
   - Filter by status (Pending/Approved/Rejected/All)
   - Shows requester details, reason, timestamps
   - Approve/Reject modals with notes

5. **`public/admin/deletion-request-approve.php`**
   - Processes approval and performs actual deletion
   - Handles lottery events (cascading delete of books, distributions, payments)
   - Handles payment transactions
   - Uses database transactions for safety
   - Logs activity

6. **`public/admin/deletion-request-reject.php`**
   - Processes rejection with required reason
   - Updates request status
   - Logs activity

### Files Modified

1. **`public/group-admin/lottery.php`**
   - Added "Request Delete" button for group_admin role
   - Shows "Delete" button only for super admin
   - Added deletion request modal with reason textarea
   - JavaScript handlers for request submission
   - Success/error messages for deletion requests

2. **`public/group-admin/lottery-payment-transactions.php`**
   - Modified delete button logic by role
   - Super admin: Direct delete
   - Group admin: Request delete
   - Added deletion request modal
   - JavaScript handlers for request submission
   - Success/error messages

3. **`public/admin/dashboard.php`**
   - Added pending deletion requests count query
   - Navigation menu link with badge count
   - Quick action button with visual indicator
   - Highlights in red when pending requests exist

## User Workflows

### Group Admin: Request Event Deletion

1. Navigate to **Lottery System** page
2. Find the lottery event to delete
3. Click **"Request Delete"** button (red button)
4. Modal opens asking for reason
5. Enter detailed reason (required)
6. Click **"Submit Request"**
7. Confirmation message: "Deletion request submitted successfully! Super Admin will review your request."
8. Email sent to Super Admin

### Group Admin: Request Transaction Deletion

1. Navigate to **Payment Transactions** page for a book
2. Find the transaction to delete
3. Click **"Request Delete"** button
4. Modal opens showing transaction details (amount, date)
5. Enter detailed reason (required)
6. Click **"Submit Request"**
7. Confirmation message displayed
8. Email sent to Super Admin

### Super Admin: Review Deletion Requests

1. Login to Admin Dashboard
2. See notification badge if pending requests exist
3. Click **"Deletion Requests"** (navigation or quick action)
4. View all pending requests with details:
   - Item type and name
   - Requester information
   - Reason provided
   - Request date
5. Filter by status if needed (Pending/Approved/Rejected/All)

### Super Admin: Approve Deletion

1. On pending request, click **"Approve & Delete"** button
2. Confirmation modal opens
3. Optionally add admin notes
4. Click **"Approve & Delete"**
5. Item is permanently deleted from database
6. Request status updated to "approved"
7. Activity logged
8. Success message: "Deletion request approved and item deleted successfully."

### Super Admin: Reject Deletion

1. On pending request, click **"Reject Request"** button
2. Rejection modal opens
3. Enter rejection reason (required)
4. Click **"Reject Request"**
5. Request status updated to "rejected"
6. Rejection reason stored for reference
7. Activity logged
8. Success message: "Deletion request rejected successfully."

## Security Features

1. **Role-Based Access Control**
   - Group admins cannot directly delete
   - Only super admin can approve deletions
   - Prevents unauthorized deletions

2. **Audit Trail**
   - All requests logged with requester ID
   - Reviewer ID and timestamp recorded
   - Activity logs for all actions
   - Reasons preserved for both request and rejection

3. **Duplicate Prevention**
   - Checks for existing pending requests
   - Prevents multiple requests for same item

4. **Data Validation**
   - All inputs sanitized
   - Required fields enforced
   - Item existence verified before processing

5. **Cascading Delete Safety**
   - Uses database transactions
   - Rollback on any error
   - Deletes in correct order (payments → distributions → books → events)

## Email Notifications

When a deletion request is submitted, an email is sent to the admin:

```
Subject: Deletion Request: [Lottery Event/Payment Transaction]

A deletion request has been submitted:

Type: [Lottery Event/Payment Transaction]
Event/Transaction: [Name/Details]
Requested By: User ID [X]
Reason: [User's reason]

Please review this request at:
https://zatana.in/public/admin/deletion-requests.php
```

Email address configured in `config.php`:
```php
define('ADMIN_EMAIL', 'info@careerplanning.fun');
```

## Status Badge Colors

- **Pending**: Yellow/Warning (requires action)
- **Approved**: Green/Success
- **Rejected**: Red/Danger

## Modal Validations

### Request Delete Modal
- Reason field: Required, min 10 characters recommended
- Placeholder provides guidance on what to include

### Approve Modal
- Admin notes: Optional
- Permanent deletion warning displayed

### Reject Modal
- Rejection reason: Required
- Must provide explanation to requester

## Database Cleanup

The system does NOT auto-delete approved/rejected requests. This maintains:
- Complete audit history
- Ability to review past decisions
- Reference for similar future requests

If cleanup is needed, Super Admin can manually run:
```sql
DELETE FROM deletion_requests
WHERE status IN ('approved', 'rejected')
AND reviewed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

## Activity Log Types

Added to `activity_log_types`:
1. `deletion_requested` - When group admin submits request
2. `deletion_approved` - When super admin approves and deletes
3. `deletion_rejected` - When super admin rejects request

## Error Handling

### Common Errors and Messages

| Error Code | Message | Cause |
|------------|---------|-------|
| `duplicate_request` | "A deletion request for this event/transaction is already pending review" | User tried to request deletion of already-pending item |
| `request_failed` | "Failed to submit deletion request. Please try again." | Database error during request creation |
| `notfound` | "Event/Transaction not found" | Item doesn't exist or doesn't belong to user's community |
| `invalid` | "Invalid request" | Missing required parameters |
| `delete_failed` | "Failed to delete the item. Please try again." | Error during actual deletion |
| `update_failed` | "Failed to update request status. Please try again." | Error updating request to approved/rejected |

## Future Enhancements (Optional)

1. **Email notifications to requesters**
   - Notify when request is approved
   - Notify when request is rejected with reason

2. **Bulk operations**
   - Approve multiple requests at once
   - Reject multiple requests at once

3. **Auto-expire old pending requests**
   - Auto-reject requests pending for >30 days
   - Send reminder to admin after 7 days

4. **Request comments/discussion**
   - Allow back-and-forth between requester and reviewer
   - Request more information before decision

5. **Soft delete option**
   - Mark as deleted but keep in database
   - Ability to restore within timeframe

## Testing Checklist

- [ ] Group admin can request lottery event deletion
- [ ] Group admin can request transaction deletion
- [ ] Duplicate requests are prevented
- [ ] Super admin sees pending count badge
- [ ] Super admin can filter by status
- [ ] Super admin can approve with/without notes
- [ ] Super admin can reject with reason
- [ ] Lottery event deletion removes all related data
- [ ] Transaction deletion works correctly
- [ ] Email notifications are sent
- [ ] Activity logs are created
- [ ] Error messages display correctly
- [ ] Modals close on Escape key
- [ ] All validations work (required fields)

## Setup Instructions

1. **Import Database Migration**
   ```bash
   mysql -u username -p database_name < database/migrations/add_deletion_requests_table.sql
   ```

2. **Verify Email Configuration**
   - Check `config/config.php` has `ADMIN_EMAIL` defined
   - Test email delivery works

3. **Test as Group Admin**
   - Login as group_admin
   - Verify "Request Delete" buttons appear
   - Submit a test deletion request

4. **Test as Super Admin**
   - Login as admin
   - Verify badge count appears
   - Review, approve, and reject test requests

5. **Verify Cascading Deletion**
   - Create test lottery event with books and payments
   - Request deletion as group admin
   - Approve as super admin
   - Verify all related data is deleted

## Support

For issues or questions about the deletion request system:
- Check error messages in browser console
- Review PHP error logs for backend issues
- Verify database migration was successful
- Ensure proper role assignments (admin vs group_admin)
