<?php
class Validator
{
    protected array $customMessages = [];
    protected array $errors = [];

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
                        if (!is_string($value)) {
                            $this->addError($errorKey, $field, 'The :attribute must be a string.');
                        }
                        break;

                    case 'integer':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $this->addError($errorKey, $field, 'The :attribute must be an integer.');
                        }
                        break;

                    case 'min':
                        if (strlen($value) < (int)$param) {
                            $this->addError($errorKey, $field, 'The :attribute must be at least :min characters.', ['min' => $param]);
                        }
                        break;

                    case 'max':
                        if (strlen($value) > (int)$param) {
                            $this->addError($errorKey, $field, 'The :attribute may not be greater than :max characters.', ['max' => $param]);
                        }
                        break;

                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($errorKey, $field, 'The :attribute must be a valid email address.');
                        }
                        break;

                        // Add more validation rules as needed
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Add an error message.
     *
     * @param string $key The error key.
     * @param string $field The field name.
     * @param string $default The default error message.
     * @param array $params Parameters to replace in the message.
     */
    protected function addError(string $key, string $field, string $default, array $params = []): void
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
}
