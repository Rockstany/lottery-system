<?php
/**
 * Toast Notification Handler
 * Converts PHP success/error messages to modern toast notifications
 */

$toastError = $error ?? ($_GET['error'] ?? '');
$toastSuccess = $success ?? ($_GET['success'] ?? '');

// Convert URL parameter errors to readable messages
$errorMessages = [
    'invalid' => 'Invalid request parameters',
    'notfound' => 'Record not found',
    'deletefailed' => 'Failed to delete record',
    'cannotdeleteyourself' => 'You cannot delete yourself',
    'lastadmin' => 'Cannot delete the last admin user',
    'usernotfound' => 'User not found'
];

// Convert URL parameter success to readable messages
$successMessages = [
    'deleted' => 'Deleted successfully!',
    'created' => 'Created successfully!',
    'updated' => 'Updated successfully!',
    'password_reset' => 'Password reset successfully!'
];

if ($toastError && isset($errorMessages[$toastError])) {
    $toastError = $errorMessages[$toastError];
}

if ($toastSuccess && isset($successMessages[$toastSuccess])) {
    $toastSuccess = $successMessages[$toastSuccess];
}
?>

<?php if ($toastError): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Toast.error(<?php echo json_encode($toastError); ?>);
        });
    </script>
<?php endif; ?>

<?php if ($toastSuccess): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Toast.success(<?php echo json_encode($toastSuccess); ?>);
        });
    </script>
<?php endif; ?>
