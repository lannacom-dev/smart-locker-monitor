<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.companies.index', [
            'companies' => Company::with('parentCompany')->orderBy('name')->get(),
            'lastSync'  => $this->lastApiSync(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.companies.form', [
            'company' => new Company(),
            'parents' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'code'              => ['required', 'string', 'max:50', 'unique:companies,code'],
            'parent_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'contact_name'      => ['nullable', 'string'],
            'contact_phone'     => ['nullable', 'string'],
            'api_base_url'      => ['nullable', 'string', 'max:255'],
            'api_client_id'     => ['nullable', 'string', 'max:255'],
            'api_client_secret' => ['nullable', 'string', 'max:255'],
            'api_timeout'       => ['nullable', 'integer', 'min:1', 'max:120'],
            'api_enabled'       => ['boolean'],
            'is_active'         => ['boolean'],
        ]);

        $validated['api_base_url'] = $this->normalizeEndpoint($validated['api_base_url'] ?? null);
        $validated['api_timeout'] = (int) ($validated['api_timeout'] ?? 10);

        $company = Company::create($validated);

        $adminUser = $this->provisionAdminUser($company);

        $msg = 'สร้างบริษัทแล้ว';
        if ($adminUser) {
            $msg .= " — Admin user: {$adminUser->email} (password: password)";
        }

        return redirect()->route('admin.companies.index')->with('success', $msg);
    }

    public function edit(Company $company): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.companies.form', [
            'company' => $company,
            'parents' => Company::where('id', '!=', $company->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'code'              => ['required', 'string', 'max:50', 'unique:companies,code,' . $company->id],
            'parent_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'contact_name'      => ['nullable', 'string'],
            'contact_phone'     => ['nullable', 'string'],
            'api_base_url'      => ['nullable', 'string', 'max:255'],
            'api_client_id'     => ['nullable', 'string', 'max:255'],
            'api_client_secret' => ['nullable', 'string', 'max:255'],
            'api_timeout'       => ['nullable', 'integer', 'min:1', 'max:120'],
            'api_enabled'       => ['boolean'],
            'is_active'         => ['boolean'],
        ]);

        $validated['api_base_url'] = $this->normalizeEndpoint($validated['api_base_url'] ?? null);
        $validated['api_timeout'] = (int) ($validated['api_timeout'] ?? 10);

        $company->update($validated);

        return redirect()->route('admin.companies.index')->with('success', '??????????');
    }

    /**
     * Auto-provision a tenant_admin user for a new company.
     * Only runs for depth 0 (no parent) or depth 1 (parent has no parent).
     * Returns the created User, or null if skipped.
     */
    private function provisionAdminUser(Company $company): ?User
    {
        // Calculate depth: 0 = top-level, 1 = child of top-level, 2+ = skip
        $depth = 0;
        if ($company->parent_company_id !== null) {
            $parent = Company::find($company->parent_company_id);
            $depth = $parent?->parent_company_id !== null ? 2 : 1;
        }

        if ($depth >= 2) {
            return null;
        }

        // Derive a unique email from the company code
        $code  = Str::lower(preg_replace('/[^a-z0-9]/i', '', $company->code));
        $email = "admin@{$code}.local";

        // Avoid duplicate emails
        if (User::where('email', $email)->exists()) {
            $email = "admin@{$code}-" . $company->id . '.local';
        }

        $user = User::create([
            'company_id' => $company->id,
            'name'       => $company->name . ' Admin',
            'email'      => $email,
            'password'   => Hash::make('password'),
            'is_active'  => true,
        ]);

        $user->assignRole('tenant_admin');

        return $user;
    }

    private function normalizeEndpoint(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $clean = trim($url);
        $clean = str_replace('/:', ':', $clean);
        $clean = preg_replace('#(?<!:)/{2,}#', '/', $clean) ?? $clean;

        return rtrim($clean, '/');
    }
}
