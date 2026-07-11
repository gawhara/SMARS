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
            'schedule_mode' => 'single',
            'schedule_name_ar' => 'دوام الاختبار',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => '1',
        ])->assertRedirect(route('shifts.index'));

        $this->assertDatabaseHas('branches', ['company_id' => $company->id, 'name_en' => 'Riyadh Branch']);
        $this->assertDatabaseHas('departments', ['name_en' => 'Support']);
        $this->assertDatabaseHas('positions', ['name_en' => 'Supervisor']);
        $this->assertDatabaseHas('shifts', ['shift_number' => 1, 'start_time' => '09:00']);
    }

    public function test_global_schedule_can_create_two_non_overlapping_shifts_for_the_same_day(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $company = Company::create([
            'name_ar' => 'شركة الورديات',
            'name_en' => 'Shift Company',
            'code' => 'SHIFTCO',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('shifts.store'), [
            'schedule_mode' => 'double',
            'schedule_name_ar' => 'دوام الفترتين',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'second_start_time' => '14:00',
            'second_end_time' => '22:00',
            'is_active' => '1',
        ])->assertRedirect(route('shifts.index'));

        $this->assertDatabaseHas('shifts', ['shift_number' => 1, 'schedule_name_ar' => 'دوام الفترتين']);
        $this->assertDatabaseHas('shifts', ['shift_number' => 2, 'schedule_name_ar' => 'دوام الفترتين']);
    }

    public function test_two_shift_scenario_rejects_overlapping_hours(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $company = Company::create([
            'name_ar' => 'شركة التداخل',
            'name_en' => 'Overlap Company',
            'code' => 'OVERLAP',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('shifts.store'), [
            'schedule_mode' => 'double',
            'schedule_name_ar' => 'دوام متداخل',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'second_start_time' => '15:00',
            'second_end_time' => '23:00',
            'is_active' => '1',
        ])->assertSessionHasErrors('second_start_time');

        $this->assertDatabaseCount('shifts', 0);
    }

    public function test_deleting_global_shift_removes_the_complete_schedule(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('shifts.store'), [
            'schedule_mode' => 'double',
            'schedule_name_ar' => 'دوام للحذف',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'second_start_time' => '14:00',
            'second_end_time' => '22:00',
            'is_active' => '1',
        ]);

        $shift = \App\Models\Shift::firstOrFail();

        $this->actingAs($user)->delete(route('shifts.destroy', $shift))
            ->assertRedirect(route('shifts.index'));

        $this->assertSame(0, \App\Models\Shift::count());
    }

    public function test_adding_a_schedule_keeps_previously_created_schedules(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach ([
            ['name' => 'الدوام الأول', 'start' => '06:00', 'end' => '14:00'],
            ['name' => 'الدوام الثاني', 'start' => '08:00', 'end' => '16:00'],
        ] as $schedule) {
            $this->actingAs($user)->post(route('shifts.store'), [
                'schedule_mode' => 'single',
                'schedule_name_ar' => $schedule['name'],
                'start_time' => $schedule['start'],
                'end_time' => $schedule['end'],
                'is_active' => '1',
            ])->assertRedirect(route('shifts.index'));
        }

        $this->assertSame(2, \App\Models\Shift::distinct('schedule_id')->count('schedule_id'));
        $this->assertDatabaseHas('shifts', ['schedule_name_ar' => 'الدوام الأول']);
        $this->assertDatabaseHas('shifts', ['schedule_name_ar' => 'الدوام الثاني']);
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
