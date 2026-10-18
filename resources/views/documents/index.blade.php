<!DOCTYPE html>
<html lang="de">
<head>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumente & Richtlinien</title>
    <link rel="stylesheet" href="{{ asset('vendor/tailwind/tailwind.min.css') }}">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800">

    <!-- Navigation (mobil optimiert) -->
    <nav class="bg-white border-b border-gray-200 px-3 py-2.5 sm:px-4 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-3 min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-gray-900 truncate flex items-center gap-1.5">
                    <span>📁</span> <span>Dokumente & Richtlinien</span>
                </h1>
                <a href="{{ route('calendar.index') }}" class="text-blue-600 hover:underline text-2xs sm:text-xs font-semibold">
                    ← Zurück zum Kalender
                </a>
            </div>

            <!-- Konto Menü -->
            <div class="relative flex-shrink-0">
                <button type="button" onclick="document.getElementById('userMenuDropdown').classList.toggle('hidden')" class="flex items-center gap-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-300 text-gray-700 px-2.5 py-1.5 rounded-lg text-xs font-semibold shadow-sm transition">
                    <span>👤</span>
                    <span class="hidden sm:inline">Konto</span>
                    <span class="text-2xs">▼</span>
                </button>

                <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-xl shadow-xl py-1 z-40 text-xs">
                    <div class="px-4 py-2 border-b border-gray-100 text-gray-500 font-medium truncate">
                        {{ auth()->user()->name }}
                    </div>
                    @php
                        $u = auth()->user();
                        $isAdmin = false;
                        if ($u) {
                            if (method_exists($u, "canDo") && $u->canDo("manage_all_companies")) {
                                $isAdmin = true;
                            } elseif (is_string($u->role) && $u->role === "admin") {
                                $isAdmin = true;
                            } elseif (is_object($u->role) && (($u->role->slug ?? null) === "admin" || ($u->role->name ?? null) === "admin")) {
                                $isAdmin = true;
                            } elseif ($u->relationLoaded("roles") && $u->roles->contains(fn($r) => ($r->slug ?? $r->name) === "admin")) {
                                $isAdmin = true;
                            }
                        }
                    @endphp
                    @if($isAdmin)
                        <a href="{{ url('/admin') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Admin-Bereich</a>
                    @endif
                    <a href="{{ url('/password/change') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Passwort ändern</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 font-semibold">Abmelden</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-3 sm:p-6 space-y-3 sm:space-y-4">

        @if(session('success'))
            <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded-xl text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if(isset($errors) &&$errors->any())
            <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-xs font-semibold">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter- und Uploadleiste -->
        <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
            <form method="GET" action="{{ route('documents.index') }}" class="flex items-center gap-2 flex-1">
                @if(request('company_id'))
                    <input type="hidden" name="company_id" value="{{ request('company_id') }}">
                @endif
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Suchen..." class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-2 text-xs bg-white focus:outline-none focus:border-blue-500">
                    <span class="absolute left-2.5 top-2.5 text-gray-400 text-xs">🔍</span>
                </div>
                <button type="submit" class="px-3.5 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg text-xs font-semibold transition">Suchen</button>
            </form>

            <div class="flex items-center gap-2">
                <button type="button" onclick="document.getElementById('docFilterModal').classList.remove('hidden')" class="flex-1 sm:flex-none justify-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                    <span>🔍</span> <span>Filter</span>
                    @if(request('company_id') || request('category') || request('search'))
                        <span class="w-2 h-2 rounded-full bg-blue-600 inline-block"></span>
                    @endif
                </button>
                <button type="button" onclick="document.getElementById('docUploadModal').classList.remove('hidden')" class="flex-1 sm:flex-none justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <span>➕</span> <span>Hochladen</span>
                </button>
            </div>
        </div>

        @if(request('company_id') || request('category') || request('search'))
            <div class="flex flex-wrap items-center gap-1.5 text-xs px-1">
                <span class="text-gray-500 font-semibold">Filter:</span>
                @if(request('company_id'))
                    @php $actComp =$companies->firstWhere('id', request('company_id')); @endphp
                    <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded text-2xs font-bold">
                        {{ $actComp ? $actComp->short_code : request('company_id') }}
                    </span>
                @endif
                @if(request('category'))
                    <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded text-2xs font-bold">
                        {{ request('category') }}
                    </span>
                @endif
                @if(request('search'))
                    <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded text-2xs font-bold">
                        "{{ request('search') }}"
                    </span>
                @endif
                <a href="{{ route('documents.index') }}" class="text-red-600 hover:underline text-2xs ml-1 font-semibold">Zurücksetzen</a>
            </div>
        @endif

        <!-- MOBILE KARTENANSICHT (nur auf Smartphones: block md:hidden) -->
        <div class="block md:hidden space-y-2.5">
            @forelse($documents as $doc)
                <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-sm space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-bold text-sm text-gray-900 truncate">{{ $doc->title }}</div>
                            <div class="text-2xs text-gray-400 truncate mt-0.5">{{ $doc->original_filename }}</div>
                        </div>
                        @if($doc->company)
                            <span class="px-2 py-0.5 rounded text-2xs font-bold bg-blue-50 text-blue-700 border border-blue-200 flex-shrink-0">
                                {{ $doc->company->short_code }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 text-2xs text-gray-500">
                        <span class="px-2 py-0.5 rounded font-medium bg-gray-100 text-gray-700 border border-gray-200">
                            {{ $doc->category }}
                        </span>
                        <span>•</span>
                        <span>{{ number_format($doc->file_size / 1024, 1, ',', '.') }} KB</span>
                        <span>•</span>
                        <span>{{ $doc->created_at ? $doc->created_at->format('d.m.Y') : '—' }}</span>
                    </div>

                    <div class="pt-2 border-t border-gray-100 flex items-center justify-end gap-2">
                        <a href="{{ route('documents.download', $doc) }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-semibold text-xs transition">
                            Herunterladen
                        </a>
                        <button type="button" onclick="openEditModal({{ json_encode([
                            'id' => $doc->id,
                            'title' => $doc->title,
                            'category' => $doc->category,
                            'company_id' => $doc->company_id,
                            'visible_ids' => $doc->companies->pluck('id')->toArray(),
                            'update_url' => route('documents.update', $doc),
                            'destroy_url' => route('documents.destroy', $doc)
                        ]) }})" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg font-semibold text-xs transition">
                            Bearbeiten
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 p-6 text-center text-xs text-gray-500 shadow-sm">
                    Keine Dokumente gefunden.
                </div>
            @endforelse
        </div>

        <!-- DESKTOP TABELLENANSICHT (ab Tablet / PC: hidden md:block) -->
        <div class="hidden md:block bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-2xs font-bold">
                        <tr>
                            <th class="px-4 py-3 text-left">Titel & Datei</th>
                            <th class="px-4 py-3 text-left">Kategorie</th>
                            <th class="px-4 py-3 text-left">Unternehmen</th>
                            <th class="px-4 py-3 text-left">Größe</th>
                            <th class="px-4 py-3 text-left">Datum</th>
                            <th class="px-4 py-3 text-right">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($documents as $doc)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">📄</span>
                                        <div>
                                            <div>{{ $doc->title }}</div>
                                            <div class="text-2xs text-gray-400 font-normal">{{ $doc->original_filename }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-2xs font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ $doc->category }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($doc->company)
                                        <span class="px-2 py-0.5 rounded text-2xs font-bold bg-blue-50 text-blue-700 border border-blue-200" title="{{ $doc->company->name }}">
                                            {{ $doc->company->short_code }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                    {{ number_format($doc->file_size / 1024, 1, ',', '.') }} KB
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                    {{ $doc->created_at ? $doc->created_at->format('d.m.Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right space-x-1.5">
                                    <a href="{{ route('documents.download', $doc) }}" class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded font-semibold text-2xs transition">
                                        Herunterladen
                                    </a>
                                    <button type="button" onclick="openEditModal({{ json_encode([
                                        'id' => $doc->id,
                                        'title' => $doc->title,
                                        'category' => $doc->category,
                                        'company_id' => $doc->company_id,
                                        'visible_ids' => $doc->companies->pluck('id')->toArray(),
                                        'update_url' => route('documents.update', $doc),
                                        'destroy_url' => route('documents.destroy', $doc)
                                    ]) }})" class="inline-flex items-center px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded font-semibold text-2xs transition">
                                        Bearbeiten
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    Keine Dokumente gefunden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($documents->hasPages())
            <div class="p-3 bg-white rounded-xl border border-gray-200 shadow-sm">
                {{ $documents->links() }}
            </div>
        @endif

    </div>

    <!-- Filter Modal -->
    <div id="docFilterModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-4 sm:p-5 relative border border-gray-200">
            <div class="flex justify-between items-center pb-2 border-b">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                    <span>🔍</span> <span>Dokumente filtern</span>
                </h3>
                <button type="button" onclick="document.getElementById('docFilterModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form method="GET" action="{{ route('documents.index') }}" class="space-y-3 mt-3">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Unternehmen</label>
                    <select name="company_id" class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                        <option value="">Alle meine Unternehmen</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>
                                [{{ $c->short_code }}] {{$c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Kategorie</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                        <option value="">Alle Kategorien</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t mt-4">
                    <a href="{{ route('documents.index') }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-xs">Zurücksetzen</a>
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow">Filter anwenden</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="docUploadModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-4 sm:p-5 relative border border-gray-200">
            <div class="flex justify-between items-center pb-2 border-b">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                    <span>➕</span> <span>Neues Dokument hochladen</span>
                </h3>
                <button type="button" onclick="document.getElementById('docUploadModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3 mt-3">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Titel *</label>
                    <input type="text" name="title" required placeholder="z. B. Gleisplan Bahnhof Nord" class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Kategorie *</label>
                    <select name="category" required class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                        <option value="" disabled selected>Kategorie auswählen...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                @if($companies->count() === 1)
                    <input type="hidden" name="company_id" value="{{ $companies->first()->id }}">
                @else
                    <div>
                        <label class="text-xs font-semibold text-gray-600 block mb-1">Unternehmen *</label>
                        <select name="company_id" id="uploadMainCompany" required class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white" onchange="syncVisibleCompanyUpload(this.value)">
                            <option value="" disabled selected>Unternehmen zuweisen...</option>
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}">[{{ $c->short_code }}] {{$c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="pt-1">
                    <button type="button" onclick="document.getElementById('visibilityBoxUpload').classList.toggle('hidden')" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 transition flex items-center justify-between w-full">
                        <span>Sichtbar für Unternehmen</span>
                        <span class="text-2xs text-gray-500 font-normal">(Mehrfachauswahl) ▼</span>
                    </button>

                    <div id="visibilityBoxUpload" class="hidden mt-2 p-2.5 bg-gray-50 border border-gray-200 rounded-lg space-y-1.5 text-xs">
                        <div class="flex justify-between items-center pb-1 border-b border-gray-200">
                            <span class="text-2xs text-gray-500 font-semibold uppercase">Auswählen:</span>
                            <button type="button" onclick="toggleAllVisibleUpload()" class="text-blue-600 hover:underline text-2xs font-semibold">Alle an/abwählen</button>
                        </div>
                        <div class="max-h-32 overflow-y-auto space-y-1 pt-1">
                            @foreach($companies as $c)
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-1 rounded transition">
                                    <input type="checkbox" name="visible_company_ids[]" value="{{ $c->id }}" class="upload-company-checkbox rounded text-blue-600 focus:ring-0" {{ $companies->count() === 1 ? 'checked' : '' }}>
                                    <span class="text-gray-800 font-medium">[{{ $c->short_code }}] {{$c->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Datei auswählen (max. 20 MB) *</label>
                    <input type="file" name="file" required class="w-full border border-gray-300 rounded-lg p-1.5 text-xs bg-white">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t mt-4">
                    <button type="button" onclick="document.getElementById('docUploadModal').classList.add('hidden')" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-xs">Abbrechen</button>
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow">Hochladen</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bearbeiten & Löschen Modal -->
    <div id="docEditModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-4 sm:p-5 relative border border-gray-200">
            <div class="flex justify-between items-center pb-2 border-b">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                    <span>✏️</span> <span>Dokument bearbeiten</span>
                </h3>
                <button type="button" onclick="document.getElementById('docEditModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form id="editDocForm" method="POST" action="" class="space-y-3 mt-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Titel *</label>
                    <input type="text" name="title" id="edit_title" required class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Kategorie *</label>
                    <select name="category" id="edit_category" required class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Unternehmen (Hauptzuweisung) *</label>
                    <select name="company_id" id="edit_company_id" required class="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white" onchange="syncVisibleCompanyEdit(this.value)">
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}">[{{ $c->short_code }}] {{$c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-1">
                    <button type="button" onclick="document.getElementById('visibilityBoxEdit').classList.toggle('hidden')" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 transition flex items-center justify-between w-full">
                        <span>Sichtbar für Unternehmen</span>
                        <span class="text-2xs text-gray-500 font-normal">(Mehrfachauswahl) ▼</span>
                    </button>

                    <div id="visibilityBoxEdit" class="hidden mt-2 p-2.5 bg-gray-50 border border-gray-200 rounded-lg space-y-1.5 text-xs">
                        <div class="flex justify-between items-center pb-1 border-b border-gray-200">
                            <span class="text-2xs text-gray-500 font-semibold uppercase">Auswählen:</span>
                            <button type="button" onclick="toggleAllVisibleEdit()" class="text-blue-600 hover:underline text-2xs font-semibold">Alle an/abwählen</button>
                        </div>
                        <div class="max-h-32 overflow-y-auto space-y-1 pt-1">
                            @foreach($companies as $c)
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-1 rounded transition">
                                    <input type="checkbox" name="visible_company_ids[]" value="{{ $c->id }}" class="edit-company-checkbox rounded text-blue-600 focus:ring-0">
                                    <span class="text-gray-800 font-medium">[{{ $c->short_code }}] {{$c->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t mt-4">
                    <button type="button" onclick="submitDeleteDoc()" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 font-semibold rounded-lg text-xs transition">
                        Dokument löschen
                    </button>

                    <div class="flex gap-2">
                        <button type="button" onclick="document.getElementById('docEditModal').classList.add('hidden')" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-xs">Abbrechen</button>
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow">Speichern</button>
                    </div>
                </div>
            </form>

            <form id="deleteDocForm" method="POST" action="" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <script>
        function syncVisibleCompanyUpload(companyId) {
            const cb = document.querySelector(`.upload-company-checkbox[value="${companyId}"]`);
            if (cb) { cb.checked = true; }
        }

        function toggleAllVisibleUpload() {
            const boxes = document.querySelectorAll('.upload-company-checkbox');
            const anyUnchecked = Array.from(boxes).some(b => !b.checked);
            boxes.forEach(b => b.checked = anyUnchecked);
        }

        function syncVisibleCompanyEdit(companyId) {
            const cb = document.querySelector(`.edit-company-checkbox[value="${companyId}"]`);
            if (cb) { cb.checked = true; }
        }

        function toggleAllVisibleEdit() {
            const boxes = document.querySelectorAll('.edit-company-checkbox');
            const anyUnchecked = Array.from(boxes).some(b => !b.checked);
            boxes.forEach(b => b.checked = anyUnchecked);
        }

        let currentDestroyUrl = '';

        function openEditModal(data) {
            document.getElementById('editDocForm').action = data.update_url;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_company_id').value = data.company_id;

            currentDestroyUrl = data.destroy_url;

            document.querySelectorAll('.edit-company-checkbox').forEach(cb => {
                cb.checked = data.visible_ids.includes(parseInt(cb.value));
            });

            document.getElementById('docEditModal').classList.remove('hidden');
        }

        function submitDeleteDoc() {
            if (confirm('Dokument wirklich unwiderruflich löschen?')) {
                const delForm = document.getElementById('deleteDocForm');
                delForm.action = currentDestroyUrl;
                delForm.submit();
            }
        }

        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userMenuDropdown');
            if (dropdown && !dropdown.contains(e.target) && !e.target.closest('button[onclick*="userMenuDropdown"]')) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
</body>
</html>