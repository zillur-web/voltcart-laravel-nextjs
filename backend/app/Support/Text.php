<?php

namespace App\Support;

class Text
{
    public static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

        return trim($value);
    }

    public static function like(string $value): string
    {
        return '%'.addcslashes($value, '%_\\').'%';
    }
}
