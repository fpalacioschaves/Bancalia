<?php
declare(strict_types=1);

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Valida que el campo no esté vacío.
     */
    public function required(string $field, string $label): self
    {
        $value = $this->getValue($field);
        if ($value === null || trim((string) $value) === '') {
            $this->addError($field, "$label es obligatorio.");
        }
        return $this;
    }

    /**
     * Valida formato de email.
     */
    public function email(string $field, string $label): self
    {
        $value = $this->getValue($field);
        if ($value !== null && trim((string) $value) !== '') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, "$label no tiene un formato válido.");
            }
        }
        return $this;
    }

    /**
     * Valida que sea numérico.
     */
    public function numeric(string $field, string $label): self
    {
        $value = $this->getValue($field);
        if ($value !== null && trim((string) $value) !== '') {
            if (!is_numeric($value)) {
                $this->addError($field, "$label debe ser numérico.");
            }
        }
        return $this;
    }

    /**
     * Regla personalizada.
     * @param callable $rule Función que retorna true si pasa, false si falla.
     */
    public function custom(string $field, callable $rule, string $message): self
    {
        // Si ya hay error en este campo, saltamos (para no acumular msg redundantes)
        // Ojo: a veces queremos acumular. Por simplicidad, chequeamos si la regla falla.
        if (!$rule($this->getValue($field), $this->data)) {
            $this->addError($field, $message);
        }
        return $this;
    }

    // --- Helpers ---

    private function getValue(string $field)
    {
        return $this->data[$field] ?? null;
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        foreach ($this->errors as $msgs) {
            return $msgs[0] ?? null;
        }
        return null;
    }
}
