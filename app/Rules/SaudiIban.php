<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a Saudi IBAN (CODEX §17):
 *  - Structure: SA + 2 check digits + 2 bank code digits + 18 account digits (24 chars).
 *  - ISO 7064 mod-97 checksum.
 *  - When an expected bank code is supplied, the IBAN's embedded bank code must match.
 *
 * The value is expected to be already normalized (uppercase, no spaces) by the caller.
 */
class SaudiIban implements ValidationRule
{
    public function __construct(private ?string $expectedBankCode = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $iban = strtoupper(str_replace(' ', '', (string) $value));

        if (! preg_match('/^SA\d{22}$/', $iban)) {
            $fail(__('app.emp.iban_invalid'));

            return;
        }

        if (! $this->passesChecksum($iban)) {
            $fail(__('app.emp.iban_invalid'));

            return;
        }

        $ibanBankCode = substr($iban, 4, 2);

        if ($this->expectedBankCode !== null && $ibanBankCode !== $this->expectedBankCode) {
            $fail(__('app.emp.iban_bank_mismatch'));
        }
    }

    /**
     * ISO 7064 mod-97-10: move first four chars to the end, convert letters to
     * numbers (A=10 … Z=35), the resulting integer mod 97 must equal 1.
     */
    private function passesChecksum(string $iban): bool
    {
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        $numeric = '';
        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }

        // bcmod-free modulo for a long numeric string.
        $remainder = 0;
        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }
}
