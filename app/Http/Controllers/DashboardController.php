<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'companies' => Company::orderBy('id')->get(),
            'stats' => [
                'companies' => Company::count(),
                'active_companies' => Company::where('is_active', true)->count(),
                'users' => User::count(),
                'roles' => Role::count(),
                'settings' => SystemSetting::count(),
            ],
        ]);
    }
}
