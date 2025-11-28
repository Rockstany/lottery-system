<?php
/**
 * Input Validation Utility
 * Sanitize and validate user inputs
 */

class Validator {

    private $errors = [];

    /**
     * Validate required field
     * @param string $field
     * @param mixed $value
     * @param string $label
     */
    public function required($field, $value, $label = null) {
        $label = $label ?? ucfirst($field);

        if (empty($value) && $value !== '0') {
            $this->errors[$field] = "{$label} is required";
        }

        return $this;
    }

    /**
     * Validate mobile number (Indian format)
     * @param string $field
     * @param string $value
     */
    public function mobile($field, $value) {
        if (!empty($value) && !preg_match('/^[6-9]\d{9}$/', $value)) {
            $this->errors[$field] = "Invalid mobile number format";
        }

        return $this;
    }

    /**
     * Validate email
     * @param string $field
     * @param string $value
     */
    public function email($field, $value) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Invalid email format";
        }

        return $this;
    }

    /**
     * Validate minimum length
     * @param string $field
     * @param string $value
     * @param int $min
     */
    public function minLength($field, $value, $min) {
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$field] = ucfirst($field) . " must be at least {$min} characters";
        }

        return $this;
    }

    /**
     * Validate maximum length
     * @param string $field
     * @param string $value
     * @param int $max
     */
    public function maxLength($field, $value, $max) {
        if (!empty($value) && strlen($value) > $max) {
            $this->errors[$field] = ucfirst($field) . " must not exceed {$max} characters";
        }

        return $this;
    }

    /**
     * Validate numeric value
     * @param string $field
     * @param mixed $value
     */
    public function numeric($field, $value) {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = ucfirst($field) . " must be a number";
        }

        return $this;
    }

    /**
     * Validate decimal/float value
     * @param string $field
     * @param mixed $value
     */
    public function decimal($field, $value) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_FLOAT)) {
            $this->errors[$field] = ucfirst($field) . " must be a valid decimal number";
        }

        return $this;
    }

    /**
     * Validate minimum value
     * @param string $field
     * @param numeric $value
     * @param numeric $min
     */
    public function min($field, $value, $min) {
        if (!empty($value) && $value < $min) {
            $this->errors[$field] = ucfirst($field) . " must be at least {$min}";
        }

        return $this;
    }

    /**
     * Validate enum values
     * @param string $field
     * @param mixed $value
     * @param array $allowed
     */
    public function enum($field, $value, $allowed) {
        if (!empty($value) && !in_array($value, $allowed)) {
            $this->errors[$field] = "Invalid value for " . ucfirst($field);
        }

        return $this;
    }

    /**
     * Check if validation passed
     * @return bool
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     * @return bool
     */
    public function fails() {
        return !$this->passes();
    }

    /**
     * Get all errors
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Sanitize string input
     * @param string $value
     * @return string
     */
    public static function sanitizeString($value) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize integer input
     * @param mixed $value
     * @return int
     */
    public static function sanitizeInt($value) {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float input
     * @param mixed $value
     * @return float
     */
    public static function sanitizeFloat($value) {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
?>
