<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_organization_pages(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Company::create([
            'name_ar' => 'شركة اختبار',
            'name_en' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        foreach (['companies.index', 'branches.index', 'departments.index', 'positions.index', 'shifts.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_authenticated_user_can_create_company_branch_department_and_position(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('companies.store'), [
            'name_ar' => 'شركة أمنية',
            'name_en' => 'Security Company',
            'code' => 'SECURITY',
            'is_active' => '1',
        ])->assertRedirect(route('companies.index'));

        $company = Company::where('code', 'SECURITY')->firstOrFail();

        $this->actingAs($user)->post(route('branches.store'), [
            'company_id' => $company->id,
            'name_ar' => 'فرع الرياض',
            'name_en' => 'Riyadh Branch',
            'location' => 'Riyadh',
            'is_active' => '1',
        ])->assertRedirect(route('branches.index'));

        $this->actingAs($user)->post(route('departments.store'), [
            'name_ar' => 'الدعم',
            'name_en' => 'Support',
            'is_active' => '1',
        ])->assertRedirect(route('departments.index'));

        $this->actingAs($user)->post(route('positions.store'), [
            'name_ar' => 'مشرف',
            'name_en' => 'Supervisor',
            'is_active' => '1',
        ])->assertRedirect(route('positions.index'));

        $this->actingAs($user)->post(route('shifts.store'), [
            'name_ar' => 'وردية الاختبار',
            'name_en' => 'Test Shift',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => '1',
        ])->assertRedirect(route('shifts.index'));

        $this->assertDatabaseHas('branches', ['company_id' => $company->id, 'name_en' => 'Riyadh Branch']);
        $this->assertDatabaseHas('departments', ['name_en' => 'Support']);
        $this->assertDatabaseHas('positions', ['name_en' => 'Supervisor']);
        $this->assertDatabaseHas('shifts', ['name_en' => 'Test Shift']);
    }

    public function test_company_show_page_displays_legal_information(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $company = Company::create([
            'name_ar' => 'شركة اختبار',
            'name_en' => 'Test Company',
            'code' => 'TESTCO',
            'cr_number' => '1010101010',
            'vat_number' => '300000000000003',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('companies.show', $company))
            ->assertOk()
            ->assertSee(__('app.company_info.legal_information'))
            ->assertSee('1010101010')
            ->assertSee('300000000000003');
    }

    public function test_company_legal_fields_are_saved(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('companies.store'), [
            'name_ar' => 'شركة',
            'name_en' => 'Legal Co',
            'code' => 'LEGAL',
            'cr_number' => '4030999999',
            'vat_number' => '311111111111113',
            'email' => 'info@legal.test',
            'is_active' => '1',
        ])->assertRedirect(route('companies.index'));

        $this->assertDatabaseHas('companies', [
            'code' => 'LEGAL',
            'cr_number' => '4030999999',
            'vat_number' => '311111111111113',
            'email' => 'info@legal.test',
        ]);
    }
}
