<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Client;
use App\Models\Role;
use App\Models\AppointmentChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();

        $companies = Company::whereIn('id', $allowedCompanyIds)->withCount(['users', 'appointments'])->orderBy('name')->get();
        $users = User::whereIn('company_id', $allowedCompanyIds)->with(['company', 'roleModel', 'managedCompanies'])->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        if ($user->isAdmin()) {
            $roles = Role::withCount('users')->orderBy('label')->get();
        } else {
            $myPerms = $user->getPermissions();
            $roles = Role::withCount('users')->get()->filter(function ($role) use ($myPerms) {
                $rolePerms = $role->permissions ?? [];
                return empty(array_diff($rolePerms, $myPerms));
            });
        }

        // Korrekturanträge nur laden, wenn der Nutzer Dienste bearbeiten darf
        // und nur für Dienste aus Firmen, die er verwalten darf
        if ($user->canDo('edit_appointments')) {
            $pendingReports = AppointmentChangeRequest::with(['appointment.user', 'appointment.company', 'user'])
                ->where('status', 'pending')
                ->whereHas('appointment', function ($q) use ($allowedCompanyIds) {
                    $q->whereIn('company_id', $allowedCompanyIds);
                })
                ->latest()
                ->get();
        } else {
            $pendingReports = collect();
        }

        return view('admin.index', compact('companies', 'users', 'clients', 'roles', 'pendingReports'));
    }

    // 1. FIRMEN
    public function storeCompany(Request $request)
    {
        if (!auth()->user()->canDo('manage_companies')) abort(403);
        $request->validate([
            'name' => 'required|string|max:255',
            'name_display_format' => 'nullable|in:short,both,name', 'short_code' => 'required|string|max:10', 'name_display_format' => 'nullable|in:short,both,name'
        ]);
        Company::create($request->only('name', 'short_code', 'name_display_format'));
        return back()->with('success', 'Firma angelegt.');
    }

    public function updateCompany(Request $request, Company $company)
    {
        if (!auth()->user()->canDo('manage_companies')) abort(403);
        $request->validate([
            'name' => 'required|string|max:255',
            'name_display_format' => 'nullable|in:short,both,name', 'short_code' => 'required|string|max:10', 'name_display_format' => 'nullable|in:short,both,name'
        ]);
        $company->update($request->only('name', 'short_code', 'name_display_format'));
        return back()->with('success', 'Firma aktualisiert.');
    }

    public function destroyCompany(Company $company)
    {
        if (!auth()->user()->canDo('manage_companies')) abort(403);
        $company->delete();
        return back()->with('success', 'Firma gelöscht.');
    }

    // 2. KUNDEN
    public function storeClient(Request $request)
    {
        if (!auth()->user()->canDo('manage_clients')) abort(403);
        $request->validate([
            'name' => 'required|string|max:255',
            'name_display_format' => 'nullable|in:short,both,name', 'short_code' => 'required|string|max:10', 'name_display_format' => 'nullable|in:short,both,name',
            'color' => 'required|string',
            'phone_dispo' => 'nullable|string',
            'phone_backup' => 'nullable|string',
            'email_dispo' => 'nullable|email',
            'email_wagenliste' => 'nullable|email',
            'email_schadwagen' => 'nullable|email',
            'notes' => 'nullable|string',
        ]);
        Client::create($request->all());
        return back()->with('success', 'Kunde angelegt.');
    }

    public function updateClient(Request $request, Client $client)
    {
        if (!auth()->user()->canDo('manage_clients')) abort(403);
        $client->update($request->all());
        return back()->with('success', 'Kunde aktualisiert.');
    }

    public function destroyClient(Client $client)
    {
        if (!auth()->user()->canDo('manage_clients')) abort(403);
        $client->delete();
        return back()->with('success', 'Kunde gelöscht.');
    }

    // 3. ROLLEN
    public function storeRole(Request $request)
    {
        if (!auth()->user()->canDo('manage_roles')) abort(403);

        $request->validate(['name' => 'required|string|unique:roles,name', 'label' => 'required|string']);

        $requestedPerms = $request->input('permissions', []);
        $myPerms = auth()->user()->getPermissions();

        if (!auth()->user()->isAdmin()) {
            foreach ($requestedPerms as $perm) {
                if (!in_array($perm, $myPerms)) {
                    return back()->with('error', "Du kannst der Rolle nicht das Recht '{$perm}' geben, da du es selbst nicht besitzt.");
                }
            }
        }

        Role::create([
            'name' => strtolower($request->name),
            'label' => $request->label,
            'permissions' => $requestedPerms,
        ]);
        return back()->with('success', 'Rolle erstellt.');
    }

    public function updateRole(Request $request, Role $role)
    {
        if (!auth()->user()->canDo('manage_roles')) abort(403);

        $request->validate(['label' => 'required|string']);

        $requestedPerms = $request->input('permissions', []);
        $myPerms = auth()->user()->getPermissions();

        if (!auth()->user()->isAdmin()) {
            foreach ($requestedPerms as $perm) {
                if (!in_array($perm, $myPerms)) {
                    return back()->with('error', "Du kannst keine Rechte vergeben, die du selbst nicht besitzt.");
                }
            }
        }

        $role->update([
            'label' => $request->label,
            'permissions' => $requestedPerms,
        ]);
        return back()->with('success', 'Rolle aktualisiert.');
    }

    public function destroyRole(Role $role)
    {
        if (!auth()->user()->canDo('manage_roles')) abort(403);
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Rolle ist noch Benutzern zugewiesen.');
        }
        $role->delete();
        return back()->with('success', 'Rolle gelöscht.');
    }

    // 4. BENUTZER MIT KÜRZEL, TELEFON UND MAIL
    public function storeUser(Request $request)
    {
        if (!auth()->user()->canDo('manage_users')) abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'name_display_format' => 'nullable|in:short,both,name', 'short_code' => 'required|string|max:10', 'name_display_format' => 'nullable|in:short,both,name',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'company_id' => 'required|exists:companies,id',
            'managed_companies' => 'nullable|array',
        ]);

        $role = Role::findOrFail($request->role_id);

        if (!auth()->user()->isAdmin()) {
            $myPerms = auth()->user()->getPermissions();
            $rolePerms = $role->permissions ?? [];
            if (!empty(array_diff($rolePerms, $myPerms))) {
                return back()->withInput()->with('error', 'Du kannst keinen Benutzer mit einer Rolle anlegen, die höhere Rechte besitzt als du.');
            }
        }

        $user = User::create([
            'name' => $request->name,
            'short_code' => strtoupper($request->short_code),
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'company_id' => $request->company_id,
            'role_id' => $role->id,
            'role' => $role->name === 'admin' ? 'admin' : 'employee',
        ]);

        if (auth()->user()->isAdmin() && $request->filled('managed_companies')) {
            $user->managedCompanies()->sync($request->managed_companies);
        }

        return back()->with('success', 'Benutzer ' . $user->name . ' (' . $user->short_code . ') angelegt.');
    }

    public function updateUser(Request $request, User $user)
    {
        if (!auth()->user()->canDo('manage_users')) abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'name_display_format' => 'nullable|in:short,both,name', 'short_code' => 'required|string|max:10', 'name_display_format' => 'nullable|in:short,both,name',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'role_id' => 'required|exists:roles,id',
            'company_id' => 'required|exists:companies,id',
            'managed_companies' => 'nullable|array',
        ]);

        $role = Role::findOrFail($request->role_id);

        if (!auth()->user()->isAdmin()) {
            $myPerms = auth()->user()->getPermissions();
            $rolePerms = $role->permissions ?? [];
            if (!empty(array_diff($rolePerms, $myPerms))) {
                return back()->withInput()->with('error', 'Du kannst keine Rolle zuweisen, die mehr Rechte besitzt als du.');
            }
        }

        $data = [
            'name' => $request->name,
            'short_code' => strtoupper($request->short_code),
            'email' => $request->email,
            'phone' => $request->phone,
            'company_id' => $request->company_id,
            'role_id' => $role->id,
            'role' => $role->name === 'admin' ? 'admin' : 'employee',
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if (auth()->user()->isAdmin()) {
            $user->managedCompanies()->sync($request->input('managed_companies', []));
        }

        return back()->with('success', 'Benutzer aktualisiert.');
    }

    public function destroyUser(User $user)
    {
        if (!auth()->user()->canDo('manage_users')) abort(403);
        if ($user->id === auth()->id()) return back()->with('error', 'Eigenen Account kann man nicht löschen.');
        $user->delete();
        return back()->with('success', 'Benutzer gelöscht.');
    }
}