<?php

class Validator
{
    protected array $customMessages = [];
    protected array $errors = [];
    protected array $database = []; // Mock database for `unique` validation

    /**
     * Set custom error messages.
     *
     * @param array $messages Custom messages in the format ['field.rule' => 'Custom message'].
     */
    public function setCustomMessages(array $messages): void
    {
        $this->customMessages = $messages;
    }

    /**
     * Mock a database for unique validation (for demonstration purposes).
     *
     * @param array $database Mock database as an associative array.
     */
    public function setDatabase(array $database): void
    {
        $this->database = $database;
    }

    /**
     * Validate the given data against the given rules.
     *
     * @param array $data The data to validate.
     * @param array $rules The validation rules.
     * @return bool True if validation passes, false otherwise.
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);
            $value = $data[$field] ?? null;

            foreach ($rulesArray as $rule) {
                $params = explode(':', $rule);
                $ruleName = $params[0];
                $param = $params[1] ?? null;

                $errorKey = "{$field}.{$ruleName}";

                switch ($ruleName) {
                    case 'required':
                        if (is_null($value) || trim($value) === '') {
                            $this->addError($errorKey, $field, 'The :attribute field is required.');
                        }
                        break;

                    case 'string':
                        if (!is_null($value) && $value !== '') {
                            if (!is_string($value)) {
                                $this->addError($errorKey, $field, 'The :attribute must be a string.');
                            }
                        }
                        break;

                    case 'integer':
                        if (!is_null($value) && $value !== '') {
                            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                                $this->addError($errorKey, $field, 'The :attribute must be an integer.');
                            }
                        }
                        break;

                    case 'min':
                        if (!is_null($value) && $value !== '') {
                            if (strlen($value) < (int)$param) {
                                $this->addError($errorKey, $field, 'The :attribute must be at least :min characters.', ['min' => $param]);
                            }
                        }
                        break;

                    case 'max':
                        if (!is_null($value) && $value !== '') {
                            if (strlen($value) > (int)$param) {
                                $this->addError($errorKey, $field, 'The :attribute may not be greater than :max characters.', ['max' => $param]);
                            }
                        }
                        break;

                    case 'email':
                        if (!is_null($value) && $value !== '') {
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $this->addError($errorKey, $field, 'The :attribute must be a valid email address.');
                            }
                        }
                        break;

                    case 'regex':
                        if (!is_null($value) && $value !== '') {
                            if (!preg_match('/' . $param . '/', $value)) {
                                $this->addError($errorKey, $field, 'The :attribute format is invalid.');
                            }
                        }
                        break;

                    case 'unique':

                        // if (!is_null($value) && $value !== '') {
                        //     $tableAndField = explode(',', $param);
                        //     $table = $tableAndField[0] ?? null;
                        //     $fieldInDb = $tableAndField[1] ?? $field;

                        //     if (isset($this->database[$table])) {
                        //         foreach ($this->database[$table] as $record) {
                        //             if (isset($record[$fieldInDb]) && $record[$fieldInDb] === $value) {
                        //                 $this->addError($errorKey, $field, 'The :attribute must be unique.');
                        //             }
                        //         }
                        //     }
                        // }

                        if (!is_null($value) && $value !== '') {
                            $tableAndField = explode(',', $param);
                            $table = $tableAndField[0] ?? null;
                            $fieldInDb = $tableAndField[1] ?? $field;

                            if ($this->checkUnique($table, $fieldInDb, $value)) {
                                $this->addError($errorKey, $field, 'The :attribute must be unique.');
                            }
                        }
                        break;

                    case 'date':
                        if (!is_null($value) && $value !== '') {
                            $format = $param ?: 'Y-m-d';
                            $dateTime = \DateTime::createFromFormat($format, $value);
                            if (!$dateTime || $dateTime->format($format) !== $value) {
                                $this->addError($errorKey, $field, 'The :attribute must be a valid date in the format :format.', ['format' => $format]);
                            }
                        }
                        break;

                        // Add more validation rules as needed
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Check if the value is unique in the database.
     *
     * @param string $table The table name.
     * @param string $field The field name.
     * @param string $value The value to check.
     * @return bool True if the value is not unique, false otherwise.
     */
    protected function checkUnique(string $table, string $field, string $value): bool
    {
        $this->CI = &get_instance();
        $query = $this->CI->db->where($field, $value)->get($table);

        // If there's a record with the same value, it's not unique.
        return $query->num_rows() > 0;
    }

    /**
     * Add an error message.
     *
     * @param string $key The error key.
     * @param string $field The field name.
     * @param string $default The default error message.
     * @param array $params Parameters to replace in the message.
     */
    public function addError(string $key, string $field, string $default, array $params = []): void
    {
        $message = $this->customMessages[$key] ?? $default;
        $message = str_replace(':attribute', $field, $message);

        foreach ($params as $paramKey => $paramValue) {
            $message = str_replace(':' . $paramKey, $paramValue, $message);
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get all validation errors.
     *
     * @return array The errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }



    /**
     * Example
     * 
     * $data = [
     *'username' => 'john_doe',
     *'email' => 'new@example.com',
     *'password' => 'abc123',
     *];

     *$rules = [
     *'username' => 'required|string|unique:users,username',
     *'email' => 'required|email|unique:users,email',
     *'password' => 'required|regex:/^[a-zA-Z0-9]{6,}$/',
     *];

     *$customMessages = [
     *'username.unique' => 'The username is already taken.',
     *'email.unique' => 'The email is already registered.',
     *'password.regex' => 'The password must contain only letters and numbers, and be at least 6 characters long.',
     *];
     */
}
