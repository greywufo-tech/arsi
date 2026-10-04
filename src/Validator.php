<?php
declare(strict_types=1);

/**
 * Validator sederhana berbasis aturan:
 *   $v->validate($data, ['email' => 'required|email', 'stock' => 'required|integer|min:0']);
 *
 * Aturan yang didukung:
 *   required, email, numeric, integer, confirmed, same (bandingkan dengan field lain),
 *   date, after_or_equal,
 *   min/max  -> panjang string (huruf)
 *   gte/lte  -> perbandingan angka (untuk harga, stok, dll)
 */
final class Validator
{
    private array $errors = [];
    private array $labels = [];

    public function __construct(array $labels = [])
    {
        $this->labels = $labels;
    }

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $rulesList = explode('|', $ruleString);

            // Aturan "required" menentukan boleh tidaknya field kosong.
            $isRequired = in_array('required', $rulesList, true);
            $isEmpty = $value === null || (is_string($value) && trim($value) === '');

            if ($isRequired && $isEmpty) {
                $this->addError($field, "{$this->label($field)} wajib diisi.");
                continue;
            }
            if ($isEmpty) {
                continue;
            }

            foreach ($rulesList as $rule) {
                if ($rule === 'required') {
                    continue;
                }
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($name, $param, $field, $value, $data);

                // Berhenti pada error pertama agar pesan yang tampil paling spesifik.
                if (isset($this->errors[$field])) {
                    break;
                }
            }
        }

        return $this->errors === [];
    }

    private function applyRule(string $name, ?string $param, string $field, $value, array $data): void
    {
        $label = $this->label($field);

        switch ($name) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Format {$label} tidak valid.");
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "{$label} harus berupa angka.");
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "{$label} harus berupa bilangan bulat.");
                }
                break;

            case 'min':
                // min = panjang minimum (huruf/karakter)
                if (mb_strlen((string) $value) < (float) $param) {
                    $this->addError($field, "{$label} minimal {$param} karakter.");
                }
                break;

            case 'max':
                if (mb_strlen((string) $value) > (float) $param) {
                    $this->addError($field, "{$label} maksimal {$param} karakter.");
                }
                break;

            case 'gte':
                // gte = perbandingan angka (bukan panjang string)
                if (!is_numeric($value) || (float) $value < (float) $param) {
                    $this->addError($field, "{$label} minimal {$param}.");
                }
                break;

            case 'lte':
                if (!is_numeric($value) || (float) $value > (float) $param) {
                    $this->addError($field, "{$label} maksimal {$param}.");
                }
                break;

            case 'confirmed':
                if ($value !== ($data[$field . '_confirmation'] ?? null)) {
                    $this->addError($field, "Konfirmasi {$label} tidak sama.");
                }
                break;

            case 'same':
                if ($value !== ($data[$param] ?? null)) {
                    $this->addError($field, "{$label} harus sama dengan {$this->label($param)}.");
                }
                break;

            case 'date':
                if (strtotime((string) $value) === false) {
                    $this->addError($field, "{$label} harus berupa tanggal yang valid.");
                }
                break;

            case 'after_or_equal':
                if (strtotime((string) $value) < strtotime((string) ($data[$param] ?? ''))) {
                    $this->addError($field, "{$label} harus sama dengan atau lebih besar dari {$this->label($param)}.");
                }
                break;
        }
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : reset($this->errors);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }
}