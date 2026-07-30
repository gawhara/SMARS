<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Payroll\WpsExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WpsExportTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        return Company::firstOrCreate(['code' => 'WPS'], [
            'name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true,
            'wps_establishment_id' => '1-2345678', 'employer_bank_code' => '80',
            'employer_iban' => 'SA0380000000608010167519',
        ]);
    }

    private function employee(Company $c, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'company_id' => $c->id, 'name_ar' => 'م', 'name_en' => 'Worker One',
            'employee_code' => 'WPS-1', 'hr_employee_id' => 'HR-WPS-1', 'national_id' => '1800000001',
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'WPS-P', 'status' => 'active',
            'bank' => 'RJHISARI', 'iban' => 'SA0380000000608010167519',
            'total' => 12000, 'basic_salary' => 8000, 'housing_allowance' => 2000,
            'total_deductions' => 500, 'remaining_salary' => 11500,
        ], $overrides));
    }

    public function test_build_produces_header_and_records_from_stored_fields(): void
    {
        $c = $this->company();
        $this->employee($c);

        $data = app(WpsExportService::class)->build($c, Carbon::parse('2026-05-01'));

        $this->assertSame('EDR', $data['header']['record_type']);
        $this->assertSame('1-2345678', $data['header']['establishment_id']);
        $this->assertSame('202605', $data['header']['month']);
        $this->assertSame(1, $data['header']['record_count']);
        $this->assertSame(11500.0, $data['header']['total_net']);

        $rec = $data['records']->first();
        $this->assertSame('SDR', $rec['record_type']);
        $this->assertSame('1800000001', $rec['national_id']);
        $this->assertSame('80', $rec['bank_code']); // Al Rajhi SARIE code (IBAN chars 4–5, after the check digits)
        $this->assertSame(8000.0, $rec['basic']);
        $this->assertSame(2000.0, $rec['housing']);
        $this->assertSame(2000.0, $rec['other']); // 12000 - 8000 - 2000
        $this->assertSame(11500.0, $rec['net']);
    }

    public function test_only_active_employees_included(): void
    {
        $c = $this->company();
        $this->employee($c);
        $this->employee($c, ['employee_code' => 'WPS-2', 'hr_employee_id' => 'HR-WPS-2', 'national_id' => '1800000002', 'passport_id' => 'WPS-P2', 'status' => 'inactive']);

        $data = app(WpsExportService::class)->build($c, Carbon::parse('2026-05-01'));
        $this->assertSame(1, $data['header']['record_count']);
    }

    public function test_wps_route_streams_sif(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $c = $this->company();
        $this->employee($c);
        $period = PayrollPeriod::create(['company_id' => $c->id, 'period_month' => '2026-05-01', 'status' => 'open']);

        $res = $this->get(route('payroll.periods.wps', $period));
        $res->assertOk();
        $this->assertStringContainsString('.sif.csv', $res->headers->get('content-disposition'));
        $body = $res->streamedContent();
        $this->assertStringContainsString('EDR', $body);
        $this->assertStringContainsString('SDR', $body);
        $this->assertStringContainsString('1800000001', $body);
    }
}
