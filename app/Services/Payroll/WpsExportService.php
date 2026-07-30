<?php

namespace App\Services\Payroll;

use App\Models\Bank;
use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds a Wage Protection System (WPS / Mudad) Salary Information File (SIF)
 * for a company and salary month.
 *
 * The file is the standard two-record layout: one EDR (Employer Detail Record)
 * header followed by an SDR (Salary Detail Record) per employee. Amounts come
 * from the employee's stored salary fields (total earnings, total deductions,
 * net/remaining salary). Bank routing uses each employee's IBAN (the embedded
 * 2-digit SARIE bank code) with the Bank table as a fallback.
 */
class WpsExportService
{
    /**
     * @return array{header: array<string, mixed>, records: Collection<int, array<string, mixed>>}
     */
    public function build(Company $company, Carbon $month): array
    {
        $ibanCodes = Bank::whereNotNull('iban_code')->pluck('iban_code', 'code');

        $employees = Employee::where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('employee_code')
            ->get();

        $records = $employees->map(function (Employee $e) use ($ibanCodes): array {
            $iban = strtoupper(str_replace(' ', '', (string) $e->iban));
            $bankCode = $iban !== '' && preg_match('/^SA\d{22}$/', $iban)
                ? substr($iban, 4, 2)
                : (string) ($ibanCodes[$e->bank] ?? '');

            $basic = (float) $e->basic_salary;
            $housing = (float) $e->housing_allowance;
            $gross = (float) $e->total;
            $deductions = (float) $e->total_deductions;
            $net = (float) $e->remaining_salary;
            $other = round(max(0.0, $gross - $basic - $housing), 2);

            return [
                'record_type' => 'SDR',
                'national_id' => (string) $e->national_id,
                'reference' => (string) ($e->hr_employee_id ?: $e->employee_code),
                'name' => $e->name_en ?: $e->name_ar,
                'bank_code' => $bankCode,
                'iban' => $iban,
                'basic' => round($basic, 2),
                'housing' => round($housing, 2),
                'other' => $other,
                'deductions' => round($deductions, 2),
                'net' => round($net, 2),
                'employee' => $e,
            ];
        })->values();

        $header = [
            'record_type' => 'EDR',
            'establishment_id' => (string) $company->wps_establishment_id,
            'employer_bank_code' => (string) $company->employer_bank_code,
            'employer_iban' => strtoupper(str_replace(' ', '', (string) $company->employer_iban)),
            'month' => $month->format('Ym'),
            'file_date' => Carbon::today()->format('Ymd'),
            'record_count' => $records->count(),
            'total_net' => round((float) $records->sum('net'), 2),
            'currency' => 'SAR',
        ];

        return ['header' => $header, 'records' => $records];
    }

    /** Rows the employer must fill in before the file can be submitted. */
    public function missing(Company $company, Collection $records): array
    {
        $issues = [];
        if (! $company->wps_establishment_id) {
            $issues[] = 'establishment_id';
        }
        if (! $company->employer_iban) {
            $issues[] = 'employer_iban';
        }
        if ($records->contains(fn ($r) => $r['iban'] === '' || $r['bank_code'] === '')) {
            $issues[] = 'employee_iban';
        }

        return $issues;
    }
}
