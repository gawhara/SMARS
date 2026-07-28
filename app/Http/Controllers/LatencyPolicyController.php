<?php

namespace App\Http\Controllers;

use App\Http\Requests\LatencyPolicyRequest;
use App\Models\LatencyPolicy;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LatencyPolicyController extends Controller
{
    public function index(): View
    {
        return view('latency.policies.index', [
            'policies' => LatencyPolicy::withCount('employees')->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function store(LatencyPolicyRequest $request): RedirectResponse
    {
        $policy = DB::transaction(function () use ($request): LatencyPolicy {
            $policy = LatencyPolicy::create($request->validated());
            $this->syncDefault($policy);

            return $policy;
        });

        AuditLogger::record('latency_policy.created', $policy, $policy->name);

        return redirect()->route('latency.policies.index')->with('status', __('app.saved_successfully'));
    }

    public function update(LatencyPolicyRequest $request, LatencyPolicy $latencyPolicy): RedirectResponse
    {
        DB::transaction(function () use ($request, $latencyPolicy): void {
            $latencyPolicy->update($request->validated());
            $this->syncDefault($latencyPolicy);
        });

        AuditLogger::record('latency_policy.updated', $latencyPolicy, $latencyPolicy->name);

        return redirect()->route('latency.policies.index')->with('status', __('app.saved_successfully'));
    }

    public function destroy(LatencyPolicy $latencyPolicy): RedirectResponse
    {
        $name = $latencyPolicy->name;
        $latencyPolicy->delete();

        AuditLogger::record('latency_policy.deleted', null, $name);

        return redirect()->route('latency.policies.index')->with('status', __('app.deleted_successfully'));
    }

    /** Ensure at most one active default policy exists. */
    private function syncDefault(LatencyPolicy $policy): void
    {
        if ($policy->is_default) {
            LatencyPolicy::where('id', '!=', $policy->id)->where('is_default', true)->update(['is_default' => false]);
        }
    }
}
