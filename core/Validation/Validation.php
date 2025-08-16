<?php

declare(strict_types=1);

namespace Plugs\Validation;

use DateTime;
use Countable;
use DateTimeZone;
use DateTimeInterface;
use Plugs\Exceptions\Validation\ValidationException;

class Validation
{
    protected array $data;
    protected array $rules;
    protected array $errors = [];
    protected array $customMessages = [];
    protected static array $customValidators = [];
    
    protected static array $defaultMessages = [
        'required' => ':field is required.',
        'email' => ':field must be a valid email address.',
        'string' => ':field must be a string.',
        'min' => ':field must be at least :value characters.',
        'max' => ':field must not exceed :value characters.',
        'numeric' => ':field must be a number.',
        'integer' => ':field must be an integer.',
        'boolean' => ':field must be true or false.',
        'array' => ':field must be an array.',
        'datetime' => ':field must be a valid datetime.',
        'confirmed' => ':field confirmation does not match.',
        'unique' => ':field must be unique.',
        'in' => ':field must be one of :values.',
        'same' => ':field must match :other.',
        'different' => ':field must be different from :other.',
        'regex' => ':field format is invalid.',
        'url' => ':field must be a valid URL.',
        'ip' => ':field must be a valid IP address.',
        'file' => ':field must be a file.',
        'image' => ':field must be an image.',
        'mimes' => ':field must be a file of type: :values.',
        'max_size' => ':field must not be larger than :value kilobytes.',
        'min_size' => ':field must be at least :value kilobytes.',
        'password' => ':field must contain at least 8 characters, including uppercase, lowercase, number and special character.',
        'date' => ':field must be a valid date.',
        'date_format' => ':field must match the format :format.',
        'alpha' => ':field must contain only letters.',
        'alpha_num' => ':field must contain only letters and numbers.',
        'alpha_dash' => ':field must contain only letters, numbers, dashes and underscores.',
        'json' => ':field must be a valid JSON string.',
        'digits' => ':field must be :value digits.',
        'digits_between' => ':field must be between :min and :max digits.',
        'distinct' => ':field has duplicate values.',
        'filled' => ':field must not be empty when present.',
        'not_in' => ':field must not be one of :values.',
        'present' => ':field must be present.',
        'required_if' => ':field is required when :other is :value.',
        'required_unless' => ':field is required unless :other is in :values.',
        'required_with' => ':field is required when :values is present.',
        'required_with_all' => ':field is required when :values are present.',
        'required_without' => ':field is required when :values is not present.',
        'required_without_all' => ':field is required when none of :values are present.',
        'size' => ':field must be :size.',
        'timezone' => ':field must be a valid timezone.',
    ];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $this->sanitizeInput($data);
        $this->rules = $rules;
        $this->customMessages = $messages;
    }

    /**
     * Sanitize input data to prevent XSS and other injections
     */
    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else if ($value !== null) {
                // Sanitize strings but preserve null values
                $sanitized[$key] = is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            } else {
                $sanitized[$key] = null;
            }
        }
        return $sanitized;
    }

    public function passes(): bool
    {
        foreach ($this->rules as $field => $rules) {
            if (!is_string($rules)) {
                throw new ValidationException("Validation rules for field '{$field}' must be a string.");
            }

            $rules = explode('|', $rules);
            $value = $this->getValue($field);

            // Check if the field is nullable and empty
            $isNullable = in_array('nullable', $rules);
            if ($isNullable && $this->isEmptyValue($value)) {
                continue; // Skip all validation for this field
            }

            foreach ($rules as $rule) {
                $parameters = explode(':', $rule, 2);
                $ruleName = $parameters[0];
                $ruleValue = $parameters[1] ?? null;

                // Skip validation if field is nullable and empty
                if ($ruleName === 'nullable' && $this->isEmptyValue($value)) {
                    continue;
                }

                // Handle conditional rules
                if (str_starts_with($ruleName, 'required_')) {
                    if (!$this->validateConditionalRequired($ruleName, $field, $ruleValue)) {
                        continue;
                    }
                }

                $method = 'validate' . ucfirst($ruleName);

                if (method_exists($this, $method)) {
                    $this->$method($field, $ruleValue);
                } elseif (isset(self::$customValidators[$ruleName])) {
                    call_user_func(self::$customValidators[$ruleName], $this, $field, $ruleValue);
                } else {
                    throw new ValidationException("Validation rule '{$ruleName}' does not exist.");
                }
            }
        }

        return empty($this->errors);
    }

    protected function getValue(string $field)
    {
        return $this->data[$field] ?? null;
    }

    protected function isEmptyValue($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if ($value instanceof Countable && count($value) === 0) {
            return true;
        }

        return false;
    }

    protected function validateConditionalRequired(string $ruleName, string $field, ?string $ruleValue): bool
    {
        $value = $this->getValue($field);
        
        switch ($ruleName) {
            case 'required_if':
                // Format: required_if:other_field,value
                [$otherField, $otherValue] = explode(',', $ruleValue, 2);
                $otherFieldValue = $this->getValue($otherField);
                
                if ((string)$otherFieldValue === (string)$otherValue && $this->isEmptyValue($value)) {
                    $this->addError($field, $this->getMessage('required_if', $field, $otherField, $otherValue));
                    return false;
                }
                break;
                
            case 'required_unless':
                // Format: required_unless:other_field,value1,value2
                $parts = explode(',', $ruleValue);
                $otherField = array_shift($parts);
                $otherFieldValue = $this->getValue($otherField);
                
                if (!in_array((string)$otherFieldValue, $parts) && $this->isEmptyValue($value)) {
                    $this->addError($field, $this->getMessage('required_unless', $field, $otherField, implode(',', $parts)));
                    return false;
                }
                break;
                
            case 'required_with':
                // Format: required_with:field1,field2
                $fields = explode(',', $ruleValue);
                $anyPresent = false;
                
                foreach ($fields as $f) {
                    if (!$this->isEmptyValue($this->getValue($f))) {
                        $anyPresent = true;
                        break;
                    }
                }
                
                if ($anyPresent && $this->isEmptyValue($value)) {
                    $this->addError($field, $this->getMessage('required_with', $field, $ruleValue));
                    return false;
                }
                break;
                
            case 'required_with_all':
                // Format: required_with_all:field1,field2
                $fields = explode(',', $ruleValue);
                $allPresent = true;
                
                foreach ($fields as $f) {
                    if ($this->isEmptyValue($this->getValue($f))) {
                        $allPresent = false;
                        break;
                    }
                }
                
                if ($allPresent && $this->isEmptyValue($value)) {
                    $this->addError($field, $this->getMessage('required_with_all', $field, $ruleValue));
                    return false;
                }
                break;
                
            case 'required_without':
                // Format: required_without:field1,field2
                $fields = explode(',', $ruleValue);
                $anyMissing = false;
                
                foreach ($fields as $f) {
                    if ($this->isEmptyValue($this->getValue($f))) {
                        $anyMissing = true;
                        break;
                    }
                }
                
                if ($anyMissing && $this->isEmptyValue($value)) {
                    $this->addError($field, $this->getMessage('required_without', $field, $ruleValue));
                    return false;
                }
                break;
                
            case 'required_without_all':
                // Format: required_without_all:field1,field2
                $fields = explode(',', $ruleValue);
                $allMissing = true;
                
                foreach ($fields as $f) {
                    if (!$this->isEmptyValue($this->getValue($f))) {
                        $allMissing = false;
                        break;
                    }
                }
                
                if ($allMissing && $this->isEmptyValue($value)) {
                    $this->addError($field, $this->getMessage('required_without_all', $field, $ruleValue));
                    return false;
                }
                break;
        }
        
        return true;
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public static function addCustomValidator(string $name, callable $callback): void
    {
        self::$customValidators[$name] = $callback;
    }

    protected function getMessage(string $rule, string $field, ...$params): string
    {
        $customKey = "{$field}.{$rule}";

        if (isset($this->customMessages[$customKey])) {
            $message = $this->customMessages[$customKey];
        } elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        } else {
            $message = self::$defaultMessages[$rule] ?? ":field validation failed for rule {$rule}.";
        }

        $message = str_replace(':field', $this->formatFieldName($field), $message);

        foreach ($params as $i => $param) {
            $message = str_replace([':value', ':other', ':values', ':format', ':size', ':min', ':max'][$i] ?? ":param{$i}", $param, $message);
        }

        return $message;
    }

    protected function formatFieldName(string $field): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $field));
    }

    protected function validateRequired(string $field): void
    {
        $value = $this->getValue($field);

        if ($this->isEmptyValue($value)) {
            $this->addError($field, $this->getMessage('required', $field));
        }
    }

    protected function validateFilled(string $field): void
    {
        if (array_key_exists($field, $this->data) && $this->isEmptyValue($this->data[$field])) {
            $this->addError($field, $this->getMessage('filled', $field));
        }
    }

    protected function validatePresent(string $field): void
    {
        if (!array_key_exists($field, $this->data)) {
            $this->addError($field, $this->getMessage('present', $field));
        }
    }

    protected function validateEmail(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $this->getMessage('email', $field));
        }
    }

    protected function validateString(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!is_string($value)) {
            $this->addError($field, $this->getMessage('string', $field));
        }
    }

    protected function validateMin(string $field, string $value): void
    {
        $val = $this->getValue($field);
        if ($val === null) return;

        if (is_string($val)) {
            if (mb_strlen($val) < $value) {
                $this->addError($field, $this->getMessage('min', $field, $value));
            }
        } elseif (is_array($val)) {
            if (count($val) < $value) {
                $this->addError($field, $this->getMessage('min', $field, $value));
            }
        } elseif (is_numeric($val)) {
            if ($val < $value) {
                $this->addError($field, $this->getMessage('min', $field, $value));
            }
        }
    }

    protected function validateMax(string $field, string $value): void
    {
        $val = $this->getValue($field);
        if ($val === null) return;

        if (is_string($val)) {
            if (mb_strlen($val) > $value) {
                $this->addError($field, $this->getMessage('max', $field, $value));
            }
        } elseif (is_array($val)) {
            if (count($val) > $value) {
                $this->addError($field, $this->getMessage('max', $field, $value));
            }
        } elseif (is_numeric($val)) {
            if ($val > $value) {
                $this->addError($field, $this->getMessage('max', $field, $value));
            }
        }
    }

    protected function validateSize(string $field, string $size): void
    {
        $val = $this->getValue($field);
        if ($val === null) return;

        if (is_string($val)) {
            if (mb_strlen($val) != $size) {
                $this->addError($field, $this->getMessage('size', $field, $size));
            }
        } elseif (is_array($val)) {
            if (count($val) != $size) {
                $this->addError($field, $this->getMessage('size', $field, $size));
            }
        } elseif (is_numeric($val)) {
            if ($val != $size) {
                $this->addError($field, $this->getMessage('size', $field, $size));
            }
        }
    }

    protected function validateNumeric(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!is_numeric($value)) {
            $this->addError($field, $this->getMessage('numeric', $field));
        }
    }

    protected function validateInteger(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, $this->getMessage('integer', $field));
        }
    }

    protected function validateDigits(string $field, string $value): void
    {
        $val = $this->getValue($field);
        if ($val === null) return;

        if (!ctype_digit((string)$val) || strlen((string)$val) != $value) {
            $this->addError($field, $this->getMessage('digits', $field, $value));
        }
    }

    protected function validateDigitsBetween(string $field, string $ruleValue): void
    {
        $val = $this->getValue($field);
        if ($val === null) return;

        [$min, $max] = explode(',', $ruleValue);
        $length = strlen((string)$val);

        if (!ctype_digit((string)$val) || $length < $min || $length > $max) {
            $this->addError($field, $this->getMessage('digits_between', $field, $min, $max));
        }
    }

    protected function validateBoolean(string $field): void
    {
        $val = $this->getValue($field);
        if ($val === null) return;

        if (!filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null && $val !== false) {
            $this->addError($field, $this->getMessage('boolean', $field));
        }
    }

    protected function validateArray(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!is_array($value)) {
            $this->addError($field, $this->getMessage('array', $field));
        }
    }

    protected function validateDistinct(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null || !is_array($value)) return;

        if (count($value) !== count(array_unique($value))) {
            $this->addError($field, $this->getMessage('distinct', $field));
        }
    }

    protected function validateDate(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!$this->isValidDate($value)) {
            $this->addError($field, $this->getMessage('date', $field));
        }
    }

    protected function validateDateFormat(string $field, string $format): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        $date = DateTime::createFromFormat($format, $value);
        if (!$date || $date->format($format) !== $value) {
            $this->addError($field, $this->getMessage('date_format', $field, $format));
        }
    }

    protected function isValidDate($value): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if (strtotime($value) === false) {
            return false;
        }

        try {
            new DateTime($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function validateDatetime(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!$this->isValidDate($value)) {
            $this->addError($field, $this->getMessage('datetime', $field));
        }
    }

    protected function validateTimezone(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        try {
            new DateTimeZone($value);
        } catch (\Exception $e) {
            $this->addError($field, $this->getMessage('timezone', $field));
        }
    }

    protected function validateConfirmed(string $field): void
    {
        $confirmationField = "{$field}_confirmation";
        $value = $this->getValue($field);

        if (!isset($this->data[$confirmationField])) {
            $this->addError($field, $this->getMessage('confirmed', $field));
            return;
        }

        if ($value !== $this->data[$confirmationField]) {
            $this->addError($field, $this->getMessage('confirmed', $field));
        }
    }

    protected function validateUnique(string $field, ?string $tableColumnCondition = null): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if ($tableColumnCondition === null || empty($tableColumnCondition)) {
            throw new ValidationException("The 'unique' validation rule for field '{$field}' requires a table.column parameter.");
        }

        $parts = explode(',', $tableColumnCondition);
        $tableColumn = array_shift($parts);

        if (substr_count($tableColumn, '.') !== 1) {
            throw new ValidationException("Invalid format for unique validation. Expected 'table.column'.");
        }

        [$table, $column] = explode('.', $tableColumn);

        // Use dependency injection for database would be better
        $db = Database::getInstance();
        if (!$db) {
            throw new ValidationException("Database connection not established");
        }

        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $params = [$value];

        if (!empty($parts)) {
            $additionalCondition = trim($parts[0]);

            if (preg_match('/(\w+)\s*(=|!=|<|>|<=|>=)\s*(.+)/', $additionalCondition, $matches)) {
                $conditionField = $matches[1];
                $conditionOperator = $matches[2];
                $conditionValue = $matches[3];

                $query .= " AND {$conditionField} {$conditionOperator} ?";
                $params[] = is_numeric($conditionValue) ? $conditionValue : trim($conditionValue, "'");
            } else {
                throw new ValidationException("Invalid condition format in unique validation.");
            }
        }

        try {
            $result = $db->query($query, $params);

            if ($result === false) {
                throw new ValidationException("Database query failed: " . $db->getLastError());
            }

            if (!empty($result) && $result[0]['count'] > 0) {
                $this->addError($field, $this->getMessage('unique', $field));
            }
        } catch (\Exception $e) {
            throw new ValidationException("Database error during unique validation: " . $e->getMessage());
        }
    }

    protected function validateIn(string $field, string $values): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        $allowedValues = explode(',', $values);

        if (!in_array($value, $allowedValues)) {
            $this->addError($field, $this->getMessage('in', $field, $values));
        }
    }

    protected function validateNotIn(string $field, string $values): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        $disallowedValues = explode(',', $values);

        if (in_array($value, $disallowedValues)) {
            $this->addError($field, $this->getMessage('not_in', $field, $values));
        }
    }

    protected function validateSame(string $field, string $otherField): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        $otherValue = $this->getValue($otherField);

        if ($otherValue === null) {
            $this->addError($field, str_replace(':other', $this->formatFieldName($otherField),
                $this->getMessage('same', $field)));
            return;
        }

        if ($value !== $otherValue) {
            $this->addError($field, str_replace(':other', $this->formatFieldName($otherField),
                $this->getMessage('same', $field)));
        }
    }

    protected function validateDifferent(string $field, string $otherField): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        $otherValue = $this->getValue($otherField);
        if ($otherValue === null) return;

        if ($value === $otherValue) {
            $this->addError($field, str_replace(':other', $this->formatFieldName($otherField),
                $this->getMessage('different', $field)));
        }
    }

    protected function validateRegex(string $field, string $pattern): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!preg_match($pattern, $value)) {
            $this->addError($field, $this->getMessage('regex', $field));
        }
    }

    protected function validateAlpha(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!ctype_alpha($value)) {
            $this->addError($field, $this->getMessage('alpha', $field));
        }
    }

    protected function validateAlphaNum(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!ctype_alnum($value)) {
            $this->addError($field, $this->getMessage('alpha_num', $field));
        }
    }

    protected function validateAlphaDash(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            $this->addError($field, $this->getMessage('alpha_dash', $field));
        }
    }

    protected function validateJson(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!is_string($value) || !json_decode($value) || json_last_error() !== JSON_ERROR_NONE) {
            $this->addError($field, $this->getMessage('json', $field));
        }
    }

    protected function validateUrl(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, $this->getMessage('url', $field));
        }
    }

    protected function validateIp(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            $this->addError($field, $this->getMessage('ip', $field));
        }
    }

    protected function validatePassword(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null) return;

        if (!$this->isValidPassword($value)) {
            $this->addError($field, $this->getMessage('password', $field));
        }
    }

    protected function isValidPassword(string $password): bool
    {
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/\d/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }

    protected function validateFile(string $field): void
    {
        $file = $this->getValue($field);
        if ($file === null) return;

        if (!is_array($file) || !isset($file['tmp_name']) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->addError($field, $this->getMessage('file', $field));
            return;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            $this->addError($field, $this->getMessage('file', $field));
        }
    }

    protected function validateImage(string $field): void
    {
        $file = $this->getValue($field);
        if ($file === null) return;

        if (!is_array($file) || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $this->addError($field, $this->getMessage('image', $field));
        }
    }

    protected function validateMimes(string $field, string $allowedTypes): void
    {
        $file = $this->getValue($field);
        if ($file === null) return;

        if (!is_array($file) || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $allowedMimes = explode(',', $allowedTypes);
        $fileMime = mime_content_type($file['tmp_name']);

        if (!in_array($fileMime, $allowedMimes)) {
            $this->addError($field, str_replace(':values', $allowedTypes, $this->getMessage('mimes', $field)));
        }
    }

    protected function validateMaxSize(string $field, string $maxSizeKB): void
    {
        $file = $this->getValue($field);
        if ($file === null) return;

        if (!is_array($file) || !isset($file['size']) || $file['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $maxSizeBytes = $maxSizeKB * 1024;
        if ($file['size'] > $maxSizeBytes) {
            $this->addError($field, str_replace(':value', $maxSizeKB, $this->getMessage('max_size', $field)));
        }
    }

    protected function validateMinSize(string $field, string $minSizeKB): void
    {
        $file = $this->getValue($field);
        if ($file === null) return;

        if (!is_array($file) || !isset($file['size']) || $file['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $minSizeBytes = $minSizeKB * 1024;
        if ($file['size'] < $minSizeBytes) {
            $this->addError($field, str_replace(':value', $minSizeKB, $this->getMessage('min_size', $field)));
        }
    }

    protected function validateNullable(string $field): void
    {
        // Handled in the passes() method
    }
}