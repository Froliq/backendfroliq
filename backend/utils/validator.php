<?php
/**
 * Input Validation Utility
 * Provides validation methods for user inputs
 */

class Validator {
    
    private $data;
    private $errors = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    /**
     * Validate required fields
     * 
     * @param string $field Field name
     * @param string $message Optional custom error message
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field][] = $message ?: "The {$field} field is required.";
        }
        return $this;
    }
    
    /**
     * Validate email format
     * 
     * @param string $field Field name
     * @param string $message Optional custom error message
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?: "The {$field} must be a valid email address.";
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $field Field name
     * @param int $min Minimum length
     * @param string $message Optional custom error message
     */
    public function min($field, $min, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field][] = $message ?: "The {$field} must be at least {$min} characters.";
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $field Field name
     * @param int $max Maximum length
     * @param string $message Optional custom error message
     */
    public function max($field, $max, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field][] = $message ?: "The {$field} must not exceed {$max} characters.";
        }
        return $this;
    }
    
    /**
     * Validate numeric value
     * 
     * @param string $field Field name
     * @param string $message Optional custom error message
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = $message ?: "The {$field} must be a number.";
        }
        return $this;
    }
    
    /**
     * Validate integer value
     * 
     * @param string $field Field name
     * @param string $message Optional custom error message
     */
    public function integer($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = $message ?: "The {$field} must be an integer.";
        }
        return $this;
    }
    
    /**
     * Validate phone number format
     * 
     * @param string $field Field name
     * @param string $message Optional custom error message
     */
    public function phone($field, $message = null) {
        if (isset($this->data[$field])) {
            $phone = preg_replace('/[^0-9]/', '', $this->data[$field]);
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                $this->errors[$field][] = $message ?: "The {$field} must be a valid phone number.";
            }
        }
        return $this;
    }
    
    /**
     * Validate date format
     * 
     * @param string $field Field name
     * @param string $format Date format (default: Y-m-d)
     * @param string $message Optional custom error message
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field][] = $message ?: "The {$field} must be a valid date in format {$format}.";
            }
        }
        return $this;
    }
    
    /**
     * Validate that value is in array
     * 
     * @param string $field Field name
     * @param array $values Allowed values
     * @param string $message Optional custom error message
     */
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field][] = $message ?: "The {$field} must be one of: " . implode(', ', $values);
        }
        return $this;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $field Field name
     * @param string $message Optional custom error message
     */
    public function password($field, $message = null) {
        if (isset($this->data[$field])) {
            $password = $this->data[$field];
            $errors = [];
            
            if (strlen($password) < 8) {
                $errors[] = 'at least 8 characters';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'at least one uppercase letter';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'at least one lowercase letter';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'at least one number';
            }
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'at least one special character';
            }
            
            if (!empty($errors)) {
                $this->errors[$field][] = $message ?: "The {$field} must contain " . implode(', ', $errors) . '.';
            }
        }
        return $this;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $field Field name
     * @param string $message Optional custom error message
     */
    public function url($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = $message ?: "The {$field} must be a valid URL.";
        }
        return $this;
    }
    
    /**
     * Validate minimum value
     * 
     * @param string $field Field name
     * @param int|float $min Minimum value
     * @param string $message Optional custom error message
     */
    public function minValue($field, $min, $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] < $min) {
            $this->errors[$field][] = $message ?: "The {$field} must be at least {$min}.";
        }
        return $this;
    }
    
    /**
     * Validate maximum value
     * 
     * @param string $field Field name
     * @param int|float $max Maximum value
     * @param string $message Optional custom error message
     */
    public function maxValue($field, $max, $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] > $max) {
            $this->errors[$field][] = $message ?: "The {$field} must not exceed {$max}.";
        }
        return $this;
    }
    
    /**
     * Custom validation rule
     * 
     * @param string $field Field name
     * @param callable $callback Validation callback
     * @param string $message Error message
     */
    public function custom($field, $callback, $message) {
        if (isset($this->data[$field])) {
            $result = call_user_func($callback, $this->data[$field]);
            if (!$result) {
                $this->errors[$field][] = $message;
            }
        }
        return $this;
    }
    
    /**
     * Check if validation has failed
     * 
     * @return bool
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Get the first error for a field
     * 
     * @param string $field Field name
     * @return string|null
     */
    public function first($field) {
        return isset($this->errors[$field]) ? $this->errors[$field][0] : null;
    }
    
    /**
     * Sanitize input data
     * 
     * @param array $data Input data
     * @return array Sanitized data
     */
    public static function sanitize($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    /**
     * Validate file upload
     * 
     * @param string $field Field name
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @param string $message Optional custom error message
     */
    public function file($field, $allowedTypes = [], $maxSize = 5242880, $message = null) {
        if (isset($_FILES[$field])) {
            $file = $_FILES[$field];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors[$field][] = $message ?: "File upload failed for {$field}.";
                return $this;
            }
            
            if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
                $this->errors[$field][] = $message ?: "The {$field} must be one of: " . implode(', ', $allowedTypes);
            }
            
            if ($file['size'] > $maxSize) {
                $maxSizeMB = round($maxSize / 1024 / 1024, 2);
                $this->errors[$field][] = $message ?: "The {$field} must not exceed {$maxSizeMB}MB.";
            }
        }
        return $this;
    }
}

// Helper function to create validator instance
function validate($data) {
    return new Validator($data);
}
?>