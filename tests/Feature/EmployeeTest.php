<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Company;
use App\Models\Country;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function seedReferences(): void
    {
        Country::create(['iso2' => 'SA', 'name_en' => 'Saudi Arabia', 'name_ar' => 'السعودية', 'priority' => 99]);
        Country::create(['iso2' => 'EG', 'name_en' => 'Egypt', 'name_ar' => 'مصر', 'priority' => 100]);
        Bank::create(['code' => 'RAJHI', 'iban_code' => '80', 'name_en' => 'Al Rajhi Bank', 'name_ar' => 'الراجحي', 'is_active' => true]);
        Bank::create(['code' => 'RIYAD', 'iban_code' => '20', 'name_en' => 'Riyad Bank', 'name_ar' => 'الرياض', 'is_active' => true]);
    }

    private function company(): Company
    {
        return Company::firstOrCreate(
            ['code' => 'CO1'],
            ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true],
        );
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company()->id,
            'name_ar' => 'اسم',
            'name_en' => 'Name',
            'employee_code' => 'EMP-1',
            'hr_employee_id' => 'HR-1',
            'national_id' => '1234567890',
            'nationality' => 'SA',
            'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P1234567',
            'status' => 'active',
        ], $overrides);
    }

    public function test_index_page_loads(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->get(route('employees.index'))->assertOk();
    }

    public function test_create_form_renders(): void
    {
        $this->seedReferences();
        $this->company();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('employees.create'))
            ->assertOk()
            ->assertSee(__('app.emp.national_id'))
            ->assertSee(__('app.emp.section_salary'));
    }

    public function test_show_and_edit_pages_render(): void
    {
        $this->seedReferences();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('employees.store'), $this->validPayload());
        $employee = Employee::firstOrFail();

        $this->actingAs($user)->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee(__('app.emp.tab_overview'))
            ->assertSee($employee->name_en);

        $this->actingAs($user)->get(route('employees.edit', $employee))->assertOk();
    }

    public function test_can_create_saudi_employee_with_auto_classification(): void
    {
        $this->seedReferences();
        $user = User::factory()->create(['is_active' => true]);

        // national_id starts with 1 => Saudi + nationality SA are enforced server-side.
        $response = $this->actingAs($user)->post(route('employees.store'), $this->validPayload([
            'saudi_non_saudi' => 'non_saudi', // should be overridden to saudi
            'nationality' => 'EG',            // should be overridden to SA
            'phone' => '0512345678',          // should normalize to +9665...
        ]));

        $employee = Employee::firstOrFail();
        $response->assertRedirect(route('employees.show', $employee));

        $this->assertSame('saudi', $employee->saudi_non_saudi);
        $this->assertSame('SA', $employee->nationality);
        $this->assertSame('+966512345678', $employee->phone);
        $this->assertSame($user->id, $employee->created_by);
    }

    public function test_rejects_national_id_with_invalid_prefix(): void
    {
        $this->seedReferences();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('employees.store'), $this->validPayload([
            'national_id' => '3234567890',
        ]))->assertSessionHasErrors('national_id');

        $this->assertSame(0, Employee::count());
    }

    public function test_rejects_past_expiry_dates(): void
    {
        $this->seedReferences();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('employees.store'), $this->validPayload([
            'iqama_expiry' => now()->subDay()->toDateString(),
        ]))->assertSessionHasErrors('iqama_expiry');
    }

    public function test_valid_saudi_iban_is_accepted_and_mismatch_rejected(): void
    {
        $this->seedReferences();
        $user = User::factory()->create(['is_active' => true]);

        // Al Rajhi IBAN (bank code 80) with the wrong bank selected => rejected.
        $this->actingAs($user)->post(route('employees.store'), $this->validPayload([
            'bank' => 'RIYAD',
            'iban' => 'SA0380000000608010167519',
        ]))->assertSessionHasErrors('iban');

        // Correct bank => accepted.
        $this->actingAs($user)->post(route('employees.store'), $this->validPayload([
            'bank' => 'RAJHI',
            'iban' => 'SA03 8000 0000 6080 1016 7519',
        ]));

        $this->assertDatabaseHas('employees', ['iban' => 'SA0380000000608010167519', 'bank' => 'RAJHI']);
    }

    public function test_passport_id_is_globally_unique(): void
    {
        $this->seedReferences();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('employees.store'), $this->validPayload());

        // Second employee, different identifiers but same passport => rejected.
        $this->actingAs($user)->post(route('employees.store'), $this->validPayload([
            'employee_code' => 'EMP-2',
            'hr_employee_id' => 'HR-2',
            'national_id' => '1999999999',
        ]))->assertSessionHasErrors('passport_id');

        $this->assertSame(1, Employee::count());
    }
}
