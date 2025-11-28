<?php
/**
 * JSON Response Utility
 * Standardized API response format
 */

class Response {

    /**
     * Send JSON success response
     * @param mixed $data
     * @param string $message
     * @param int $code
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    /**
     * Send JSON error response
     * @param string $message
     * @param int $code
     * @param array $errors
     */
    public static function error($message = 'Error occurred', $code = 400, $errors = []) {
        http_response_code($code);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    /**
     * Send validation error response
     * @param array $errors
     */
    public static function validationError($errors = []) {
        self::error('Validation failed', 422, $errors);
    }

    /**
     * Send unauthorized response
     * @param string $message
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }

    /**
     * Send not found response
     * @param string $message
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    /**
     * Send forbidden response
     * @param string $message
     */
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 403);
    }
}
?>
