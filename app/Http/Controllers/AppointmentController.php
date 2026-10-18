<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Attachment;
use App\Models\Absence;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use App\Models\AppointmentChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppointmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();

        $companies = Company::whereIn('id', $allowedCompanyIds)->orderBy('name')->get();
        
        if ($user->canDo('view_other_appointments')) {
            $employees = User::whereIn('company_id', $allowedCompanyIds)->whereIn('role', ['admin', 'employee'])->orderBy('name')->get();
        } else {
            $employees = collect([$user]);
        }

        $clients = Client::orderBy('name')->get();

        return view('appointments.calendar', compact('companies', 'employees', 'clients'));
    }

    public function events(Request $request)
    {
        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();
        $userCompanyId = $user->company_id;

        $query = Appointment::with(['company', 'employee', 'client'])
            ->whereIn('company_id', $allowedCompanyIds);

        if (!$user->canDo('view_other_appointments')) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('employee_id')) {
            $query->where('user_id', $request->employee_id);
        }

        if ($request->filled('company_id') && in_array($request->company_id, $allowedCompanyIds)) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $fmtIso = function ($d) {
            if (empty($d)) return null;
            return $d instanceof \DateTimeInterface 
                ? $d->format('Y-m-d\TH:i:s') 
                : \Carbon\Carbon::parse($d)->format('Y-m-d\TH:i:s');
        };

        $events = collect();

        foreach ($query->get() as $apt) {
            $empCode = $apt->employee->short_code ?? ($apt->employee->name ?? '—');
            $compCode = $apt->company->short_code ?? ($apt->company->name ?? '—');
            $clientCode = $apt->client->short_code ?? ($apt->client->name ?? '—');

            // Firmenkürzel nur anzeigen, wenn der Mitarbeiter NICHT zum Stamm gehört
            $isForeignCompany = $apt->company_id && ($apt->company_id != $userCompanyId);
            $compBadge = $isForeignCompany ? " [{$compCode}]" : "";

            $title = "{$empCode}{$compBadge} | {$clientCode}";

            $events->push([
                'id' => 'apt_' . $apt->id,
                'real_id' => $apt->id,
                'type' => 'appointment',
                'title' => $title,
                'start' => $fmtIso($apt->start_time),
                'end' => $fmtIso($apt->end_time),
                'backgroundColor' => $apt->client->color ?? '#2563EB',
                'borderColor' => $apt->client->color ?? '#2563EB',
                'textColor' => '#ffffff',
                'user_id' => $apt->user_id,
                'employee_id' => $apt->user_id,
                'employee_name' => $apt->employee->name ?? null,
                'client_name' => $apt->client->name ?? null,
                'location' => $apt->location,
                'report_status' => $apt->report_status ?? 'pending',
                'actual_start' => $fmtIso($apt->actual_start),
                'actual_end' => $fmtIso($apt->actual_end),
                'travel_out_start' => $fmtIso($apt->travel_out_start),
                'travel_out_end' => $fmtIso($apt->travel_out_end),
                'travel_back_start' => $fmtIso($apt->travel_back_start),
                'travel_back_end' => $fmtIso($apt->travel_back_end),
                'pause_minutes' => $apt->pause_minutes ?? 0,
                'report_notes' => $apt->report_notes,
            ]);
        }

        $absQuery = Absence::with('user')
            ->whereHas('user', function ($q) use ($allowedCompanyIds) {
                $q->whereIn('company_id', $allowedCompanyIds);
            });

        if (!$user->canDo('view_other_appointments')) {
            $absQuery->where('user_id', $user->id);
        } elseif ($request->filled('employee_id')) {
            $absQuery->where('user_id', $request->employee_id);
        }

        foreach ($absQuery->get() as $abs) {
            $isKrank = in_array(strtolower($abs->type), ['krank', 'krankheit']);
            $color = $isKrank ? '#DC2626' : '#D97706';
            $typeName = ucfirst($abs->type);
            $empCode = $abs->user->short_code ?: $abs->user->name;

            $events->push([
                'id' => 'abs_' . $abs->id,
                'real_id' => $abs->id,
                'type' => 'absence',
                'title' => "⛔ {$typeName}: {$empCode}",
                'start' => $fmtIso($abs->start_time),
                'end' => $fmtIso($abs->end_time),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'user_id' => $abs->user_id,
                'employee_id' => $abs->user_id,
                'absence_type' => $abs->type,
                'employee_name' => $abs->user->name ?? null,
                'notes' => $abs->notes,
            ]);
        }

        return response()->json($events);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->canDo('edit_appointments')) {
            abort(403, 'Keine Berechtigung zum Erstellen von Diensten.');
        }

        $allowedCompanyIds = auth()->user()->getAllowedCompanyIds();

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'user_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:clients,id',
            'services' => 'nullable|array',
            'service_other' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|max:20480',
        ]);

        if (!in_array($validated['company_id'], $allowedCompanyIds)) {
            abort(403, 'Für dieses Unternehmen dürfen keine Dienste erstellt werden.');
        }

        if (!auth()->user()->canDo('view_other_appointments') && (int)$validated['user_id'] !== auth()->id()) {
            abort(403, 'Sie dürfen Dienste nur für sich selbst eintragen.');
        }

        $collision = Absence::where('user_id', $validated['user_id'])
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                  ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                  ->orWhere(function ($sub) use ($validated) {
                      $sub->where('start_time', '<=', $validated['start_time'])
                          ->where('end_time', '>=', $validated['end_time']);
                  });
            })->first();

        if ($collision) {
            return back()->withInput()->with('error', "Kollision! Der Mitarbeiter hat im Zeitraum {$collision->type} eingetragen.");
        }

        $services = $request->input('services', []);
        $title = implode(', ', $services) ?: 'Dienst';
        if ($request->filled('service_other')) {
            $title .= ' (' . $request->service_other . ')';
        }

        $apt = Appointment::create([
            'company_id' => $validated['company_id'],
            'user_id' => $validated['user_id'],
            'client_id' => $validated['client_id'],
            'title' => $title,
            'services' => $services,
            'service_other' => $validated['service_other'] ?? null,
            'location' => $validated['location'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'description' => $validated['description'] ?? null,
            'status' => 'scheduled',
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('attachments', 'public');
            Attachment::create([
                'appointment_id' => $apt->id,
                'uploaded_by' => auth()->id(),
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        return redirect()->route('calendar.index')->with('success', 'Dienst erfolgreich angelegt!');
    }

    public function show(Appointment $appointment)
    {
        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();
        if (!in_array($appointment->company_id, $allowedCompanyIds)) {
            abort(403, 'Zugriff verweigert.');
        }

        if (!$user->canDo('view_other_appointments') && $appointment->user_id !== $user->id) {
            abort(403, 'Zugriff auf fremde Dienste verweigert.');
        }

        $appointment->load(['company', 'employee', 'client', 'attachments.uploader']);
        return response()->json($appointment);
    }

    public function update(Request $request, Appointment $appointment)
    {
        if (!$request->has('user_id') && $request->has('employee_id')) {
            $request->merge(['user_id' => $request->input('employee_id')]);
        }
        if (!$request->has('description') && $request->has('notes')) {
            $request->merge(['description' => $request->input('notes')]);
        }

        if (!auth()->user()->canDo('edit_appointments')) {
            abort(403, 'Keine Berechtigung zum Bearbeiten von Diensten.');
        }

        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();
        if (!in_array($appointment->company_id, $allowedCompanyIds)) {
            abort(403, 'Zugriff verweigert.');
        }

        if (!$user->canDo('view_other_appointments') && $appointment->user_id !== $user->id) {
            abort(403, 'Zugriff verweigert.');
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'user_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:clients,id',
            'services' => 'nullable|array',
            'service_other' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'description' => 'nullable|string',
        ]);

        if (!in_array($validated['company_id'], $allowedCompanyIds)) {
            abort(403, 'Für dieses Unternehmen dürfen keine Dienste bearbeitet werden.');
        }

        $appointment->update($validated);

        return back()->with('success', 'Dienst erfolgreich aktualisiert.');
    }

    public function destroy(Appointment $appointment)
    {
        if (!auth()->user()->canDo('edit_appointments')) {
            abort(403, 'Keine Berechtigung zum Löschen.');
        }

        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();
        if (!in_array($appointment->company_id, $allowedCompanyIds)) {
            abort(403, 'Zugriff verweigert.');
        }

        if (!$user->canDo('view_other_appointments') && $appointment->user_id !== $user->id) {
            abort(403, 'Zugriff verweigert.');
        }

        $appointment->delete();
        return redirect()->route('calendar.index')->with('success', 'Dienst gelöscht!');
    }

    public function storeAbsence(Request $request)
    {
        if (!auth()->user()->canDo('manage_absences')) {
            abort(403, 'Keine Berechtigung zum Verwalten von Abwesenheiten.');
        }

        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'notes' => 'nullable|string',
        ]);

        if (!$user->canDo('view_other_appointments') && (int)$validated['user_id'] !== $user->id) {
            abort(403, 'Sie dürfen Abwesenheiten nur für sich selbst erfassen.');
        }

        $targetUser = User::findOrFail($validated['user_id']);
        if (!in_array($targetUser->company_id, $allowedCompanyIds)) {
            abort(403, 'Mitarbeiter gehört nicht zu einem erlaubten Unternehmen.');
        }

        Absence::create($validated);
        return redirect()->route('calendar.index')->with('success', 'Abwesenheit eingetragen!');
    }

    public function destroyAbsence(Absence $absence)
    {
        if (!auth()->user()->canDo('manage_absences')) {
            abort(403, 'Keine Berechtigung zum Löschen von Abwesenheiten.');
        }

        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();
        if (!in_array($absence->user->company_id, $allowedCompanyIds)) {
            abort(403, 'Zugriff verweigert.');
        }

        if (!$user->canDo('view_other_appointments') && $absence->user_id !== $user->id) {
            abort(403, 'Zugriff verweigert.');
        }

        $absence->delete();
        return back()->with('success', 'Abwesenheit gelöscht.');
    }

    public function exportIndex()
    {
        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();
        $companies = Company::whereIn('id', $allowedCompanyIds)->orderBy('name')->get();

        if ($user->canDo('view_other_appointments')) {
            $employees = User::whereIn('company_id', $allowedCompanyIds)->whereIn('role', ['admin', 'employee'])->orderBy('name')->get();
        } else {
            $employees = collect([$user]);
        }

        return view('appointments.export_hub', compact('companies', 'employees'));
    }

    public function dailyPlan(Request $request)
    {
        $user = auth()->user();
        $rawDate = $request->get('date', now()->format('Y-m-d'));
        try {
            $date = \Carbon\Carbon::parse($rawDate)->format('Y-m-d');
        } catch (\Exception $e) {
            $date = now()->format('Y-m-d');
        }
        $allowedCompanyIds = $user->getAllowedCompanyIds();

        if ($request->filled('company_id') && !in_array($request->company_id, $allowedCompanyIds)) {
            abort(403, 'Zugriff nicht gestattet.');
        }

        $effectiveCompanyIds = $request->filled('company_id') ? [$request->company_id] : $allowedCompanyIds;

        $aptQuery = Appointment::with(['company', 'employee', 'client'])
            ->whereIn('company_id', $effectiveCompanyIds)
            ->whereDate('start_time', '<=', $date)
            ->whereDate('end_time', '>=', $date)
            ->orderBy('start_time');

        if (!$user->canDo('view_other_appointments')) {
            $aptQuery->where('user_id', $user->id);
            $selectedEmployee = $user;
        } else {
            if ($request->filled('employee_id')) {
                $checkEmp = User::whereIn('company_id', $effectiveCompanyIds)->find($request->employee_id);
                if (!$checkEmp) abort(403);
                $aptQuery->where('user_id', $request->employee_id);
                $selectedEmployee = $checkEmp;
            } else {
                $selectedEmployee = null;
            }
        }

        $appointments = $aptQuery->get();

        $absQuery = Absence::with('user')
            ->whereHas('user', function ($q) use ($effectiveCompanyIds) {
                $q->whereIn('company_id', $effectiveCompanyIds);
            })
            ->whereDate('start_time', '<=', $date)
            ->whereDate('end_time', '>=', $date);

        if (!$user->canDo('view_other_appointments')) {
            $absQuery->where('user_id', $user->id);
        } elseif ($request->filled('employee_id')) {
            $absQuery->where('user_id', $request->employee_id);
        }

        $absences = $absQuery->get();

        $selectedCompany = $request->filled('company_id') ? Company::find($request->company_id) : null;

        $involvedClients = $appointments->pluck('client')->filter()->unique('id');
        $involvedEmployees = $appointments->pluck('employee')->merge($absences->pluck('user'))->filter()->unique('id');

        return view('appointments.daily_plan', compact('date', 'appointments', 'absences', 'selectedCompany', 'selectedEmployee', 'involvedClients', 'involvedEmployees'));
    }

    public function weeklyPlan(Request $request)
    {
        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();

        if ($request->filled('company_id') && !in_array($request->company_id, $allowedCompanyIds)) {
            abort(403, 'Zugriff nicht gestattet.');
        }

        $effectiveCompanyIds = $request->filled('company_id') ? [$request->company_id] : $allowedCompanyIds;

        $dateStr = $request->get('date', now()->format('Y-m-d'));
        $startOfWeek = \Carbon\Carbon::parse($dateStr)->startOfWeek();
        $endOfWeek = (clone $startOfWeek)->endOfWeek();

        $aptQuery = Appointment::with(['company', 'employee', 'client'])
            ->whereIn('company_id', $effectiveCompanyIds)
            ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
            ->orderBy('start_time');

        if (!$user->canDo('view_other_appointments')) {
            $aptQuery->where('user_id', $user->id);
            $selectedEmployee = $user;
        } else {
            if ($request->filled('employee_id')) {
                $checkEmp = User::whereIn('company_id', $effectiveCompanyIds)->find($request->employee_id);
                if (!$checkEmp) abort(403);
                $aptQuery->where('user_id', $request->employee_id);
                $selectedEmployee = $checkEmp;
            } else {
                $selectedEmployee = null;
            }
        }

        $appointments = $aptQuery->get();

        $absQuery = Absence::with('user')
            ->whereHas('user', function ($q) use ($effectiveCompanyIds) {
                $q->whereIn('company_id', $effectiveCompanyIds);
            })
            ->where(function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                  ->orWhereBetween('end_time', [$startOfWeek, $endOfWeek]);
            });

        if (!$user->canDo('view_other_appointments')) {
            $absQuery->where('user_id', $user->id);
        } elseif ($request->filled('employee_id')) {
            $absQuery->where('user_id', $request->employee_id);
        }

        $absences = $absQuery->get();

        $companies = Company::whereIn('id', $allowedCompanyIds)->get();
        $selectedCompany = $request->filled('company_id') ? Company::find($request->company_id) : null;

        $involvedClients = $appointments->pluck('client')->filter()->unique('id');
        $involvedEmployees = $appointments->pluck('employee')->merge($absences->pluck('user'))->filter()->unique('id');

        return view('appointments.weekly_plan', compact('startOfWeek', 'endOfWeek', 'appointments', 'absences', 'companies', 'selectedCompany', 'selectedEmployee', 'involvedClients', 'involvedEmployees'));
    }

    public function monthlyPlan(Request $request)
    {
        $user = auth()->user();
        $allowedCompanyIds = $user->getAllowedCompanyIds();

        if ($request->filled('company_id') && !in_array($request->company_id, $allowedCompanyIds)) {
            abort(403, 'Zugriff nicht gestattet.');
        }

        $effectiveCompanyIds = $request->filled('company_id') ? [$request->company_id] : $allowedCompanyIds;

        $monthInput = $request->get('month') ?: $request->get('date', now()->format('Y-m-d'));
        $startOfMonth = \Carbon\Carbon::parse($monthInput)->startOfMonth();
        $endOfMonth = (clone $startOfMonth)->endOfMonth();

        $aptQuery = Appointment::with(['company', 'employee', 'client'])
            ->whereIn('company_id', $effectiveCompanyIds)
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->orderBy('start_time');

        if (!$user->canDo('view_other_appointments')) {
            $aptQuery->where('user_id', $user->id);
            $selectedEmployee = $user;
        } else {
            if ($request->filled('employee_id')) {
                $checkEmp = User::whereIn('company_id', $effectiveCompanyIds)->find($request->employee_id);
                if (!$checkEmp) abort(403);
                $aptQuery->where('user_id', $request->employee_id);
                $selectedEmployee = $checkEmp;
            } else {
                $selectedEmployee = null;
            }
        }

        $appointments = $aptQuery->get();

        $absQuery = Absence::with('user')
            ->whereHas('user', function ($q) use ($effectiveCompanyIds) {
                $q->whereIn('company_id', $effectiveCompanyIds);
            })
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start_time', [$startOfMonth, $endOfMonth])
                  ->orWhereBetween('end_time', [$startOfMonth, $endOfMonth]);
            });

        if (!$user->canDo('view_other_appointments')) {
            $absQuery->where('user_id', $user->id);
        } elseif ($request->filled('employee_id')) {
            $absQuery->where('user_id', $request->employee_id);
        }

        $absences = $absQuery->get();

        $companies = Company::whereIn('id', $allowedCompanyIds)->get();
        $selectedCompany = $request->filled('company_id') ? Company::find($request->company_id) : null;

        $involvedClients = $appointments->pluck('client')->filter()->unique('id');
        $involvedEmployees = $appointments->pluck('employee')->merge($absences->pluck('user'))->filter()->unique('id');

        return view('appointments.monthly_plan', compact('startOfMonth', 'endOfMonth', 'appointments', 'absences', 'companies', 'selectedCompany', 'selectedEmployee', 'involvedClients', 'involvedEmployees'));
    }

    public function getCompanyData(Request $request)
    {
        $user = auth()->user();
        $companyId = $request->get('company_id');
        $allowedCompanyIds = $user->getAllowedCompanyIds();

        if (!$user->canDo('view_other_appointments')) {
            return response()->json([
                'employees' => [['id' => $user->id, 'name' => $user->name]]
            ]);
        }

        $empQuery = User::whereIn('company_id', $allowedCompanyIds)->whereIn('role', ['admin', 'employee']);
        if ($companyId && in_array($companyId, $allowedCompanyIds)) {
            $empQuery->where('company_id', $companyId);
        }

        return response()->json([
            'employees' => $empQuery->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function getClient(Client $client)
    {
        return response()->json($client);
    }

    public function uploadAttachment(Request $request, Appointment $appointment)
    {
        if (!auth()->user()->canDo('manage_files') && !auth()->user()->canDo('edit_appointments')) {
            abort(403);
        }

        $request->validate(['attachment' => 'required|file|max:20480']);
        $file = $request->file('attachment');
        $path = $file->store('attachments', 'public');

        Attachment::create([
            'appointment_id' => $appointment->id,
            'uploaded_by' => auth()->id(),
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Datei hinzugefügt!');
    }

    public function downloadAttachment(Attachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->file_path)) abort(404);
        return Storage::disk('public')->download($attachment->file_path, $attachment->original_filename);
    }

    public function deleteAttachment($id)
    {
        $attachment = Attachment::findOrFail($id);
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::delete($attachment->file_path);
        } elseif (Storage::exists($attachment->file_path)) {
            Storage::delete($attachment->file_path);
        }
        $attachment->delete();

        return response()->json(['success' => true]);
    }

    public function updateShiftReport(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $user = auth()->user();

        $canEdit = $user->canDo('edit_appointments');
        $isOwner = ($user->id === $appointment->user_id);

        if (!$canEdit && !$isOwner) {
            abort(403, 'Keine Berechtigung für diesen Dienst.');
        }

        $action = $request->input('action_type', 'save');

        if ($action === 'approve_change' && $canEdit) {
            $changeReq = AppointmentChangeRequest::where('appointment_id', $appointment->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($changeReq) {
                $appointment->update([
                    'actual_start' => $changeReq->actual_start,
                    'actual_end' => $changeReq->actual_end,
                    'travel_out_start' => $changeReq->travel_out_start,
                    'travel_out_end' => $changeReq->travel_out_end,
                    'travel_back_start' => $changeReq->travel_back_start,
                    'travel_back_end' => $changeReq->travel_back_end,
                    'pause_minutes' => $changeReq->pause_minutes,
                    'report_notes' => $changeReq->report_notes,
                    'report_status' => 'completed',
                ]);
                $changeReq->update(['status' => 'approved']);
            } else {
                $appointment->update(['report_status' => 'completed']);
            }
            return back()->with('success', 'Änderungsantrag genehmigt und in den Dienst übernommen.');
        }

        if ($action === 'reject_change' && $canEdit) {
            AppointmentChangeRequest::where('appointment_id', $appointment->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            $appointment->update(['report_status' => 'completed']);
            return back()->with('success', 'Änderungsantrag abgewiesen. Ursprüngliche Meldung bleibt aktiv.');
        }

        if ($action === 'reset' && $canEdit) {
            AppointmentChangeRequest::where('appointment_id', $appointment->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            $appointment->update(['report_status' => 'pending']);
            return back()->with('success', 'Dienstmeldung zurückgesetzt / freigegeben.');
        }

        $validated = $request->validate([
            'actual_start' => 'nullable|date',
            'actual_end'   => 'nullable|date|after_or_equal:actual_start',
            'travel_out_start' => 'nullable|date',
            'travel_out_end'   => 'nullable|date|after_or_equal:travel_out_start',
            'travel_back_start' => 'nullable|date',
            'travel_back_end'   => 'nullable|date|after_or_equal:travel_back_start',
            'pause_minutes' => 'nullable|integer|min:0',
            'report_notes'  => 'nullable|string',
        ], [
            'actual_end.after_or_equal' => 'Das Schichtende darf zeitlich nicht vor dem Beginn liegen.',
            'travel_out_end.after_or_equal' => 'Das Ende der Hinfahrt darf nicht vor dem Beginn liegen.',
            'travel_back_end.after_or_equal' => 'Das Ende der Rückfahrt darf nicht vor dem Beginn liegen.',
        ]);

        if ($action === 'request_change') {
            AppointmentChangeRequest::create([
                'appointment_id' => $appointment->id,
                'user_id' => $user->id,
                'actual_start' => $validated['actual_start'] ?? null,
                'actual_end' => $validated['actual_end'] ?? null,
                'travel_out_start' => $validated['travel_out_start'] ?? null,
                'travel_out_end' => $validated['travel_out_end'] ?? null,
                'travel_back_start' => $validated['travel_back_start'] ?? null,
                'travel_back_end' => $validated['travel_back_end'] ?? null,
                'pause_minutes' => $validated['pause_minutes'] ?? 0,
                'report_notes' => $validated['report_notes'] ?? null,
                'status' => 'pending',
            ]);

            $appointment->update(['report_status' => 'change_requested']);
            return back()->with('success', 'Korrekturantrag eingereicht. Warten auf Prüfung durch die Disposition.');
        }

        if ($action === 'complete') {
            if (new \DateTime($appointment->start_time) > new \DateTime() && !$canEdit) {
                return back()->withErrors(['error' => 'Zukünftige Schichten können noch nicht abgeschlossen werden.']);
            }

            $appointment->update(array_merge($validated, [
                'report_status' => 'completed',
                'reported_at' => now(),
            ]));

            return back()->with('success', 'Dienstmeldung erfolgreich abgeschlossen.');
        }

        $appointment->update($validated);
        return back()->with('success', 'Dienstmeldung zwischengespeichert.');
    }

    public function updateAbsence(\Illuminate\Http\Request $request, \App\Models\Absence $absence)
    {
        if (!auth()->user()->canDo('manage_absences') && !auth()->user()->isAdmin()) abort(403);
        $data = $request->validate([
            'type' => 'required|string|max:50',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'notes' => 'nullable|string',
        ]);
        $absence->update($data);
        return back()->with('success', 'Abwesenheit aktualisiert.');
    }
}