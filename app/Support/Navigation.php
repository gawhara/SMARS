<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Renders the sidebar from config/navigation.php: visibility, active state,
 * and route resolution. Keeps the Blade partial free of business logic.
 */
class Navigation
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function items(): array
    {
        return array_values(array_map(
            static fn ($item) => self::expand($item),
            array_filter(
                config('navigation.items', []),
                static fn ($item) => self::isVisible($item),
            ),
        ));
    }

    /**
     * Resolve dynamic children (e.g. the four companies under Employees).
     */
    private static function expand(array $item): array
    {
        if (($item['children_source'] ?? null) === 'employees_by_company') {
            $current = (string) request('company_id');
            $onEmployees = request()->routeIs('employees.*');

            $children = [[
                'label' => __('app.emp.all'),
                'route' => 'employees.index',
                'is_active' => $onEmployees && $current === '',
            ]];

            foreach (Company::orderBy('name_en')->get() as $company) {
                $children[] = [
                    'label' => $company->localizedName(),
                    'route' => 'employees.index',
                    'params' => ['company_id' => $company->id],
                    'is_active' => $onEmployees && $current === (string) $company->id,
                ];
            }

            $item['children'] = $children;
        }

        return $item;
    }

    /**
     * Permission gate. Reserved for RBAC — until a matching ability is defined
     * the item stays visible, so the menu works before policies land.
     */
    public static function isVisible(array $item): bool
    {
        $permission = $item['permission'] ?? null;

        if (empty($permission) || ! Gate::has($permission)) {
            return true;
        }

        return Gate::allows($permission);
    }

    /**
     * Is this specific item (a leaf/link) the current page?
     */
    public static function isActive(array $item): bool
    {
        // Dynamically-built items carry a precomputed active flag.
        if (array_key_exists('is_active', $item)) {
            return (bool) $item['is_active'];
        }

        if (! empty($item['active'])) {
            return Route::currentRouteName() !== null && request()->routeIs($item['active']);
        }

        $route = $item['route'] ?? null;

        if (empty($route)) {
            return false;
        }

        if (request()->routeIs($route)) {
            return true;
        }

        // Match sibling routes of a resource, e.g. branches.index -> branches.*
        if (Str::contains($route, '.')) {
            return request()->routeIs(Str::beforeLast($route, '.').'.*');
        }

        return false;
    }

    /**
     * A group is active/open when it or any of its children is the current page.
     */
    public static function isGroupActive(array $item): bool
    {
        if (self::isActive($item)) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            if (self::isActive($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve an item's href (or null when it is a group/placeholder).
     */
    public static function href(array $item): ?string
    {
        $route = $item['route'] ?? null;

        return $route ? route($route, $item['params'] ?? []) : null;
    }
}
