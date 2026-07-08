<?php

namespace App\Support;

/**
 * Normalizes and validates Saudi mobile numbers (CODEX §15).
 * Canonical storage format: +9665XXXXXXXX
 */
class SaudiPhone
{
    /**
     * Convert accepted inputs (05XXXXXXXX, 9665XXXXXXXX, +9665XXXXXXXX, 0096659...)
     * into the canonical +9665XXXXXXXX form. Returns the original string when it
     * cannot be confidently normalized, so validation can then reject it.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/[\s\-()]/', '', trim($value)) ?? '';

        if ($digits === '') {
            return null;
        }

        // Drop a leading +, 00 (international prefix) so we work with digits only.
        $digits = preg_replace('/^\+/', '', $digits);
        $digits = preg_replace('/^00/', '', $digits);

        // 05XXXXXXXX  -> 9665XXXXXXXX
        if (preg_match('/^05(\d{8})$/', $digits, $m)) {
            return '+9665'.$m[1];
        }

        // 9665XXXXXXXX
        if (preg_match('/^9665(\d{8})$/', $digits, $m)) {
            return '+9665'.$m[1];
        }

        // 5XXXXXXXX (bare mobile without leading 0)
        if (preg_match('/^5(\d{8})$/', $digits, $m)) {
            return '+9665'.$m[1];
        }

        return $value;
    }

    public static function isValid(?string $value): bool
    {
        return is_string($value) && preg_match('/^\+9665\d{8}$/', $value) === 1;
    }
}
