<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpException;

final class Validator
{
    private array $errors = [];

    public function __construct(private readonly array $data)
    {
    }

    public function required(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? null;
            if ($value === null || (is_string($value) && trim($value) === '')) {
                $this->errors[$field] = 'Este campo es obligatorio.';
            }
        }
        return $this;
    }

    public function number(string $field, float $min = 0, ?float $max = null): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '' || isset($this->errors[$field])) {
            return $this;
        }
        if (!is_numeric($value) || $value < $min || ($max !== null && $value > $max)) {
            $this->errors[$field] = $max === null
                ? "Debe ser un número mayor o igual a $min."
                : "Debe ser un número entre $min y $max.";
        }
        return $this;
    }

    public function in(string $field, array $options): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !in_array($value, $options, true)) {
            $this->errors[$field] = 'Valor inválido. Opciones: ' . implode(', ', $options) . '.';
        }
        return $this;
    }

    public function email(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Email inválido.';
        }
        return $this;
    }

    public function validate(): void
    {
        if ($this->errors) {
            throw new HttpException(422, 'Revisá los datos ingresados.', $this->errors);
        }
    }

    /** Convierte strings vacíos en null y recorta espacios. */
    public static function clean(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? null : $value;
        }
        return $value;
    }
}
