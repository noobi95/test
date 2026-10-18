<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public const CATEGORIES = [
        'Richtlinien',
        'Betriebsregelwerke',
        'Gleispläne',
        'Dienstanweisungen',
        'Vorlagen',
        'Sonstige',
    ];

    protected function getAllowedCompanies($user)
    {
        $isAdmin = false;
        if (method_exists($user, 'canDo') && $user->canDo('manage_all_companies')) {
            $isAdmin = true;
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $isAdmin = true;
        } elseif (($user->role ?? null) === 'admin') {
            $isAdmin = true;
        }

        if ($isAdmin) {
            return Company::orderBy('name')->get();
        }

        // Relation companies() oder Fallback auf Einzelunternehmen company_id
        if (method_exists($user, 'companies') && $user->companies()->exists()) {
            $companies = $user->companies()->orderBy('name')->get();
        } elseif (!empty($user->company_id)) {
            $companies = Company::where('id', $user->company_id)->get();
        } else {
            $companies = collect();
        }

        return $companies;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = false;
        if (method_exists($user, 'canDo') && $user->canDo('manage_all_companies')) {
            $isAdmin = true;
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $isAdmin = true;
        } elseif (($user->role ?? null) === 'admin') {
            $isAdmin = true;
        }

        $companies = $this->getAllowedCompanies($user);
        $allowedCompanyIds = $companies->pluck('id')->toArray();

        $query = Document::with(['companies', 'company']);

        if (!$isAdmin) {
            $query->where(function ($q) use ($allowedCompanyIds) {
                $q->whereHas('companies', function ($sq) use ($allowedCompanyIds) {
                    $sq->whereIn('companies.id', $allowedCompanyIds);
                })->orWhereIn('company_id', $allowedCompanyIds);
            });
        }

        if ($request->filled('company_id')) {
            $filterCompanyId = $request->company_id;
            if (in_array($filterCompanyId, $allowedCompanyIds) || $isAdmin) {
                $query->where(function ($q) use ($filterCompanyId) {
                    $q->whereHas('companies', function ($sq) use ($filterCompanyId) {
                        $sq->where('companies.id', $filterCompanyId);
                    })->orWhere('company_id', $filterCompanyId);
                });
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('original_filename', 'like', "%{$search}%");
            });
        }

        $documents = $query->latest()->paginate(25)->withQueryString();
        $categories = self::CATEGORIES;

        return view('documents.index', compact('documents', 'companies', 'categories'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $allowedCompanies = $this->getAllowedCompanies($user);
        $allowedCompanyIds = $allowedCompanies->pluck('id')->toArray();

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', self::CATEGORIES),
            'company_id' => 'required|in:' . implode(',', $allowedCompanyIds),
            'visible_company_ids' => 'nullable|array',
            'visible_company_ids.*' => 'in:' . implode(',', $allowedCompanyIds),
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'title' => $request->title,
            'category' => $request->category,
            'company_id' => $request->company_id,
            'original_filename' => $originalName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'uploaded_by' => $user->id,
        ]);

        // Sichtbarkeits-Unternehmen verknüpfen (Hauptunternehmen ist immer dabei)
        $visibleIds = $request->input('visible_company_ids', []);
        if (!in_array($request->company_id, $visibleIds)) {
            $visibleIds[] = (int) $request->company_id;
        }

        if (method_exists($document, 'companies')) {
            $document->companies()->sync($visibleIds);
        }

        return redirect()->route('documents.index')->with('success', 'Dokument erfolgreich hochgeladen.');
    }

    public function download(Document $document)
    {
        $user = auth()->user();
        $allowedCompanies = $this->getAllowedCompanies($user);
        $allowedCompanyIds = $allowedCompanies->pluck('id')->toArray();

        $docCompanyIds = method_exists($document, 'companies') ? $document->companies()->pluck('companies.id')->toArray() : [];
        if ($document->company_id) {
            $docCompanyIds[] = $document->company_id;
        }

        $isAdmin = false;
        if (method_exists($user, 'canDo') && $user->canDo('manage_all_companies')) {
            $isAdmin = true;
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $isAdmin = true;
        } elseif (($user->role ?? null) === 'admin') {
            $isAdmin = true;
        }

        if (!$isAdmin && empty(array_intersect($docCompanyIds, $allowedCompanyIds))) {
            abort(403, 'Zugriff verweigert.');
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Datei nicht gefunden.');
        }

        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }

    public function destroy(Document $document)
    {
        $user = auth()->user();
        $allowedCompanies = $this->getAllowedCompanies($user);
        $allowedCompanyIds = $allowedCompanies->pluck('id')->toArray();

        $docCompanyIds = method_exists($document, 'companies') ? $document->companies()->pluck('companies.id')->toArray() : [];
        if ($document->company_id) {
            $docCompanyIds[] = $document->company_id;
        }

        $isAdmin = false;
        if (method_exists($user, 'canDo') && $user->canDo('manage_all_companies')) {
            $isAdmin = true;
        } elseif (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $isAdmin = true;
        } elseif (($user->role ?? null) === 'admin') {
            $isAdmin = true;
        }

        if (!$isAdmin && empty(array_intersect($docCompanyIds, $allowedCompanyIds))) {
            abort(403, 'Zugriff verweigert.');
        }

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        if (method_exists($document, 'companies')) {
            $document->companies()->detach();
        }
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Dokument gelöscht.');
    }

    public function update(Request $request, Document $document)
    {
        $user = auth()->user();
        $allowedCompanies = $this->getAllowedCompanies($user);
        $allowedCompanyIds = $allowedCompanies->pluck("id")->toArray();

        $docCompanyIds = method_exists($document, "companies") ? $document->companies()->pluck("companies.id")->toArray() : [];
        if ($document->company_id) {
            $docCompanyIds[] = $document->company_id;
        }

        $isAdmin = false;
        if (method_exists($user, "canDo") && $user->canDo("manage_all_companies")) {
            $isAdmin = true;
        } elseif (method_exists($user, "hasRole") && $user->hasRole("admin")) {
            $isAdmin = true;
        } elseif (($user->role ?? null) === "admin") {
            $isAdmin = true;
        }

        if (!$isAdmin && empty(array_intersect($docCompanyIds, $allowedCompanyIds))) {
            abort(403, "Zugriff verweigert.");
        }

        $request->validate([
            "title" => "required|string|max:255",
            "category" => "required|string|in:" . implode(",", self::CATEGORIES),
            "company_id" => "required|in:" . implode(",", $allowedCompanyIds),
            "visible_company_ids" => "nullable|array",
            "visible_company_ids.*" => "in:" . implode(",", $allowedCompanyIds),
        ]);

        $document->update([
            "title" => $request->title,
            "category" => $request->category,
            "company_id" => $request->company_id,
        ]);

        $visibleIds = $request->input("visible_company_ids", []);
        if (!in_array($request->company_id, $visibleIds)) {
            $visibleIds[] = (int) $request->company_id;
        }

        if (method_exists($document, "companies")) {
            $document->companies()->sync($visibleIds);
        }

        return redirect()->route("documents.index")->with("success", "Dokument erfolgreich aktualisiert.");
    }

}
