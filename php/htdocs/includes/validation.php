<?php
/** Server-side validation & sanitization of every input. */
defined('APP') or exit;

class ValidationError extends RuntimeException
{
    public function __construct(public array $fields)
    {
        parent::__construct('Validation failed');
    }
}

/** Remove control characters and angle brackets, collapse spaces. */
function sanitize_text(string $s, bool $multiline = false): string
{
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = preg_replace($multiline ? '/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u', '', $s) ?? '';
    $s = str_replace(['<', '>'], '', $s);
    $s = preg_replace('/[ \t]+/u', ' ', $s) ?? '';
    return trim($s);
}

final class Validator
{
    private array $errors = [];
    private array $out = [];

    /** @param array<string,mixed> $in */
    public function __construct(private array $in, array $allowedKeys)
    {
        foreach (array_keys($in) as $k) {
            if (!in_array($k, $allowedKeys, true)) $this->errors['_'] = 'Неизвестное поле';
        }
    }

    private function raw(string $k)
    {
        return $this->in[$k] ?? null;
    }

    public function name(string $k = 'name'): self
    {
        $v = $this->raw($k);
        if (!is_string($v)) return $this->fail($k, 'Укажите имя');
        $v = sanitize_text($v);
        $len = mb_strlen($v);
        if ($len < 2) return $this->fail($k, 'Имя слишком короткое');
        if ($len > 60) return $this->fail($k, 'Имя слишком длинное');
        if (!preg_match("/^[\\p{L}\\p{M}\\s'’.\\-]+$/u", $v)) return $this->fail($k, 'Имя может содержать только буквы');
        $this->out[$k] = $v;
        return $this;
    }

    public function phone(string $k = 'phone'): self
    {
        $v = $this->raw($k);
        $p = is_string($v) && strlen($v) <= 32 ? normalize_phone($v) : null;
        if (!$p) return $this->fail($k, 'Телефон в формате +996 XXX XXX XXX');
        $this->out[$k] = $p;
        return $this;
    }

    public function tableId(string $k = 'tableId'): self
    {
        $v = $this->raw($k);
        if (!is_string($v) || !isset(TABLES[$v])) return $this->fail($k, 'Выберите стол');
        $this->out[$k] = $v;
        return $this;
    }

    public function date(string $k = 'date', bool $optional = false): self
    {
        $v = $this->raw($k);
        if ($v === null && $optional) return $this;
        if (!is_string($v) || !is_valid_date($v)) return $this->fail($k, 'Некорректная дата');
        $this->out[$k] = $v;
        return $this;
    }

    public function int(string $k, int $min, int $max, string $msg, int $step = 1, bool $optional = false): self
    {
        $v = $this->raw($k);
        if ($v === null && $optional) return $this;
        if (is_string($v) && preg_match('/^-?\d+$/', $v)) $v = (int) $v;
        if (!is_int($v) || $v < $min || $v > $max || $v % $step !== 0) return $this->fail($k, $msg);
        $this->out[$k] = $v;
        return $this;
    }

    public function number(string $k, float $min, float $max, float $step, string $msg): self
    {
        $v = $this->raw($k);
        if (is_string($v) && is_numeric($v)) $v = (float) $v;
        if (!is_int($v) && !is_float($v)) return $this->fail($k, $msg);
        $v = (float) $v;
        if ($v < $min || $v > $max || fmod($v / $step, 1.0) !== 0.0) return $this->fail($k, $msg);
        $this->out[$k] = $v;
        return $this;
    }

    public function text(string $k, int $max, bool $required = false, int $min = 0, string $msg = 'Некорректный текст'): self
    {
        $v = $this->raw($k);
        if ($v === null || $v === '') {
            if ($required) return $this->fail($k, $msg);
            $this->out[$k] = null;
            return $this;
        }
        if (!is_string($v) || mb_strlen($v) > $max * 2) return $this->fail($k, $msg);
        $v = sanitize_text($v, true);
        if (mb_strlen($v) > $max) return $this->fail($k, "Не более {$max} символов");
        if ($required && mb_strlen($v) < $min) return $this->fail($k, $msg);
        $this->out[$k] = $v === '' ? null : $v;
        return $this;
    }

    public function bool(string $k): self
    {
        $v = $this->raw($k);
        if ($v !== null && !is_bool($v)) return $this->fail($k, 'Некорректное значение');
        $this->out[$k] = (bool) $v;
        return $this;
    }

    public function honeypot(string $k = 'website'): self
    {
        $v = $this->raw($k);
        $this->out[$k] = is_string($v) ? trim($v) : '';
        return $this;
    }

    private function fail(string $k, string $msg): self
    {
        $this->errors[$k] ??= $msg;
        return $this;
    }

    /** @return array<string,mixed> */
    public function result(): array
    {
        if ($this->errors) throw new ValidationError($this->errors);
        return $this->out;
    }
}
