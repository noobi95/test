with open("resources/views/appointments/calendar.blade.php", "w", encoding="utf-8") as f:
    f.write('''<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>{{ config('app.name', 'Terminplaner') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/de.global.min.js"></script>
    <style>
        .fc-event { cursor: pointer; }
        .fc-timegrid-slot { height: 2.2rem !important; }
        .fc-toolbar { flex-wrap: wrap; gap: 0.5rem; }
        .fc-week-number { color: #4B5563 !important; font-weight: bold !important; font-size: 0.75rem !important; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800">

    <!-- Header Navigation -->
    <nav class="bg-white border-b border-gray-200 px-4 py-2 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-4">
                <span class="text-lg font-bold text-gray-900 flex items-center gap-1.5">
                    <span>🚂</span> <span>{{ config('app.name', 'Terminplaner') }}</span>
                </span>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('documents.index') }}" class="h-8 inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 rounded-lg text-xs font-semibold transition">
                        📁 Dateien
                    </a>
                    @if(auth()->user()->canDo('access_admin_panel'))
                        <a href="{{ route('admin.index') }}" class="h-8 inline-flex items-center justify-center bg-purple-600 hover:bg-purple-700 text-white px-3 rounded-lg text-xs font-semibold shadow-sm transition">
                            ⚙️ Admin Panel
                        </a>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('calendar.export') }}" class="h-8 inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 rounded-lg text-xs font-semibold shadow-sm transition gap-1">
                    <span>📄</span> <span>Export / Druck</span>
                </a>

                @if(auth()->user()->canDo('manage_absences'))
                    <button type="button" onclick="openAbsenceModal()" class="h-8 inline-flex items-center justify-center bg-yellow-600 hover:bg-yellow-700 text-white px-3 rounded-lg text-xs font-semibold shadow-sm transition gap-1">
                        <span>⛔</span> <span>Abwesenheit</span>
                    </button>
                @endif

                @if(auth()->user()->canDo('edit_appointments'))
                    <button type="button" onclick="document.getElementById('newAppointmentModal').classList.remove('hidden')" class="h-8 inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-3 rounded-lg text-xs font-semibold shadow-sm transition gap-1">
                        <span>+</span> <span>Neuer Dienst</span>
                    </button>
                @endif

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="h-8 inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 rounded-lg text-xs font-semibold transition">
                        Abmelden
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-3 md:p-6 space-y-3">
        @if(session('success'))
            <div class="p-3 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm font-semibold">
                {{ session('error') }}
            </div>
        @endif

        <!-- Filter-Leiste -->
        <div class="bg-white p-3 rounded-xl shadow border border-gray-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 items-end">
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-0.5">1. Firma</label>
                    <select id="filterCompany" class="w-full border border-gray-300 rounded p-1.5 text-xs bg-white">
                        @if(count($companies) > 1)
                            <option value="">Alle meine Firmen</option>
                        @endif
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}">[{{ $comp->short_code }}] {{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-0.5">2. Mitarbeiter</label>
                    <select id="filterEmployee" class="w-full border border-gray-300 rounded p-1.5 text-xs bg-white">
                        @if(auth()->user()->canDo('view_other_appointments'))
                            <option value="">Alle Mitarbeiter</option>
                        @endif
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" data-company="{{ $emp->company_id }}">{{ $emp->name }} ({{ $emp->short_code ?: '—' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-0.5">3. Kunde</label>
                    <select id="filterClient" class="w-full border border-gray-300 rounded p-1.5 text-xs bg-white">
                        <option value="">Alle Kunden</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">[{{ $c->short_code }}] {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-0.5">Zu Tag springen</label>
                    <div class="flex gap-1.5">
                        <input type="date" id="jumpDate" class="w-full border border-gray-300 rounded p-1 text-xs">
                        <button id="resetFilter" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-semibold">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kalender-Fläche -->
        <div class="bg-white p-3 md:p-5 rounded-xl shadow border border-gray-200 overflow-x-auto">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal: Neuer Dienst -->
    @if(auth()->user()->canDo('edit_appointments'))
        <div id="newAppointmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
            <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-5 relative">
                <div class="flex justify-between items-center pb-2 border-b">
                    <h3 class="text-base font-bold text-gray-900">Neuen Bahndienst anlegen</h3>
                    <button type="button" onclick="document.getElementById('newAppointmentModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('appointments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3 mt-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Leistungsart</label>
                        <div class="grid grid-cols-4 gap-2 bg-gray-50 p-2 rounded border text-center">
                            <label class="flex items-center justify-center gap-1 text-xs font-bold cursor-pointer">
                                <input type="checkbox" name="services[]" value="Az" class="rounded text-blue-600"> Az
                            </label>
                            <label class="flex items-center justify-center gap-1 text-xs font-bold cursor-pointer">
                                <input type="checkbox" name="services[]" value="Rd" class="rounded text-blue-600"> Rd
                            </label>
                            <label class="flex items-center justify-center gap-1 text-xs font-bold cursor-pointer">
                                <input type="checkbox" name="services[]" value="Wu" class="rounded text-blue-600"> Wu
                            </label>
                            <label class="flex items-center justify-center gap-1 text-xs font-bold cursor-pointer">
                                <input type="checkbox" name="services[]" value="Sonstiges" onchange="document.getElementById('otherInput').classList.toggle('hidden', !this.checked)" class="rounded text-blue-600"> Sonstiges
                            </label>
                        </div>
                        <div id="otherInput" class="hidden mt-1.5">
                            <input type="text" name="service_other" placeholder="Genaue Bezeichnung für Sonstiges" class="w-full border rounded p-1.5 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Ort / Bahnhof</label>
                        <input type="text" name="location" required placeholder="z. B. Duisburg Hbf Gl. 4 / Bbf Oberhausen" class="w-full border rounded p-1.5 text-xs">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Firma</label>
                            <select id="modalCompany" name="company_id" required class="w-full border rounded p-1.5 text-xs bg-white">
                                @if(count($companies) > 1)
                                    <option value="">-- Firma wählen --</option>
                                @endif
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}">[{{ $comp->short_code }}] {{ $comp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Kunde</label>
                            <select name="client_id" required class="w-full border rounded p-1.5 text-xs bg-white">
                                <option value="">-- Kunde wählen --</option>
                                @foreach($clients as $cl)
                                    <option value="{{ $cl->id }}">[{{ $cl->short_code }}] {{ $cl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Mitarbeiter</label>
                        <select id="modalEmployee" name="user_id" required class="w-full border rounded p-1.5 text-xs bg-white">
                            @if(count($companies) === 1 || !auth()->user()->canDo('view_other_appointments'))
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->short_code ?: '—' }})</option>
                                @endforeach
                            @else
                                <option value="">Zuerst Firma auswählen</option>
                            @endif
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Beginn</label>
                            <input type="datetime-local" name="start_time" required class="w-full border rounded p-1.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Ende</label>
                            <input type="datetime-local" name="end_time" required class="w-full border rounded p-1.5 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Bemerkungen</label>
                        <textarea name="description" rows="2" class="w-full border rounded p-1.5 text-xs" placeholder="Loknummer, Schichtdetails etc."></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Dateianhang</label>
                        <input type="file" name="attachment" class="w-full text-xs text-gray-500">
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" onclick="document.getElementById('newAppointmentModal').classList.add('hidden')" class="px-3 py-1.5 border rounded text-xs text-gray-600 bg-gray-50">Abbrechen</button>
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white font-semibold rounded text-xs hover:bg-blue-700">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal: Termin Details & Bearbeiten -->
    <div id="detailModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-5 relative">
            <div class="flex justify-between items-center pb-2 border-b">
                <h3 class="text-base font-bold text-gray-900">
                    {{ auth()->user()->canDo('edit_appointments') ? 'Dienst Details & Bearbeiten' : 'Dienst Details' }}
                </h3>
                <button type="button" onclick="document.getElementById('detailModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form id="editAppointmentForm" method="POST" class="space-y-3 mt-3">
                @csrf
                @method('PUT')

                @if(auth()->user()->canDo('edit_appointments'))
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Leistungsart</label>
                        <div class="grid grid-cols-4 gap-2 bg-gray-50 p-2 rounded border text-center">
                            <label class="flex items-center justify-center gap-1 text-xs font-bold cursor-pointer">
                                <input type="checkbox" name="services[]" value="Az" id="editServiceAz" class="rounded text-blue-600"> Az
                            </label>
                            <label class="flex items-center justify-center gap-1 text-xs font-bold cursor-pointer">
                                <input type="checkbox" name="services[]" value="Rd" id="editServiceRd" class="rounded text-blue-600"> Rd
                            </label>
                            <label class="flex items-center justify-center gap-1 text-xs font-bold cursor-pointer">
                                <input type="checkbox" name="services[]" value="Wu" id="editServiceWu" class="rounded text-blue-600"> Wu
                            </label>
                            <label class="flex items-center justify-center gap-1 text-xs font-bold cursor-pointer">
                                <input type="checkbox" name="services[]" value="Sonstiges" id="editServiceOtherChk" class="rounded text-blue-600" onchange="document.getElementById('editOtherInput').classList.toggle('hidden', !this.checked)"> Sonstiges
                            </label>
                        </div>
                        <div id="editOtherInput" class="hidden mt-1.5">
                            <input type="text" name="service_other" id="editServiceOther" placeholder="Bezeichnung für Sonstiges" class="w-full border rounded p-1.5 text-xs">
                        </div>
                    </div>
                @else
                    <div>
                        <span class="text-xs font-semibold text-gray-500 block">Leistung:</span>
                        <span id="readOnlyServices" class="font-bold text-sm text-gray-900"></span>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Ort / Bahnhof</label>
                    <input type="text" name="location" id="editLocation" required {{ !auth()->user()->canDo('edit_appointments') ? 'readonly' : '' }} class="w-full border rounded p-1.5 text-xs {{ !auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : '' }}">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Firma</label>
                        <select name="company_id" id="editCompanyId" required {{ !auth()->user()->canDo('edit_appointments') ? 'disabled' : '' }} class="w-full border rounded p-1.5 text-xs bg-white">
                            @foreach($companies as$comp)
                                <option value="{{ $comp->id }}">[{{ $comp->short_code }}] {{$comp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Kunde</label>
                        <div class="flex gap-1">
                            <select name="client_id" id="editClientId" required {{ !auth()->user()->canDo('edit_appointments') ? 'disabled' : '' }} class="w-full border rounded p-1.5 text-xs bg-white">
                                @foreach($clients as$cl)
                                    <option value="{{ $cl->id }}">[{{ $cl->short_code }}] {{$cl->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" onclick="openClientModal(document.getElementById('editClientId').value)" class="bg-blue-50 text-blue-600 px-2 rounded font-bold text-xs" title="Kunden-Infos anzeigen">ℹ️</button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Mitarbeiter</label>
                    <select name="user_id" id="editUserId" required {{ !auth()->user()->canDo('edit_appointments') ? 'disabled' : '' }} class="w-full border rounded p-1.5 text-xs bg-white">
                        @foreach($employees as$emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{$emp->short_code ?: '—' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Beginn</label>
                        <input type="datetime-local" name="start_time" id="editStartTime" required {{ !auth()->user()->canDo('edit_appointments') ? 'readonly' : '' }} class="w-full border rounded p-1.5 text-xs {{ !auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : '' }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Ende</label>
                        <input type="datetime-local" name="end_time" id="editEndTime" required {{ !auth()->user()->canDo('edit_appointments') ? 'readonly' : '' }} class="w-full border rounded p-1.5 text-xs {{ !auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : '' }}">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Beschreibung / Notizen</label>
                    <textarea name="description" id="editDescription" rows="2" {{ !auth()->user()->canDo('edit_appointments') ? 'readonly' : '' }} class="w-full border rounded p-1.5 text-xs {{ !auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : '' }}"></textarea>
                </div>

                @if(auth()->user()->canDo('edit_appointments'))
                    <div class="flex justify-between items-center pt-2 border-t">
                        <button type="button" onclick="deleteCurrentAppointment()" class="text-red-600 hover:underline font-bold text-xs">🗑️ Dienst löschen</button>
                        <div class="flex gap-2">
                            <button type="button" onclick="document.getElementById('detailModal').classList.add('hidden')" class="px-3 py-1.5 border rounded text-xs bg-gray-50">Abbrechen</button>
                            <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white font-semibold rounded text-xs hover:bg-blue-700">Änderungen speichern</button>
                        </div>
                    </div>
                @else
                    <div class="flex justify-end pt-2 border-t">
                        <button type="button" onclick="document.getElementById('detailModal').classList.add('hidden')" class="px-4 py-1.5 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200">Schließen</button>
                    </div>
                @endif
            </form>

            @if(auth()->user()->canDo('edit_appointments'))
                <form id="deleteAppointmentForm" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endif

            <div class="border-t pt-3 mt-3">
                <span class="font-bold text-xs text-gray-700 block mb-1">Angehängte Dokumente</span>
                <ul id="detAttachments" class="space-y-1 mb-2 max-h-28 overflow-y-auto"></ul>
                @if(auth()->user()->canDo('manage_files') || auth()->user()->canDo('edit_appointments'))
                    <form id="detUploadForm" method="POST" enctype="multipart/form-data" class="flex gap-2">
                        @csrf
                        <input type="file" name="attachment" required class="text-xs">
                        <button type="submit" class="bg-gray-800 text-white px-2 py-1 rounded text-xs">Hochladen</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal: Abwesenheit eintragen -->
    @if(auth()->user()->canDo('manage_absences'))
        <div id="absenceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-5 relative">
                <div class="flex justify-between items-center pb-2 border-b">
                    <h3 class="text-base font-bold text-gray-900">⛔ Abwesenheit erfassen</h3>
                    <button type="button" onclick="document.getElementById('absenceModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>
                <form action="{{ route('absences.store') }}" method="POST" class="space-y-3 mt-3">
                    @csrf

                    @if(count($companies) > 1 && auth()->user()->canDo('view_other_appointments'))
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Firma auswählen</label>
                            <select id="absenceCompanySelect" class="w-full border rounded p-1.5 text-xs bg-white">
                                <option value="">-- Alle meine Firmen --</option>
                                @foreach($companies as$comp)
                                    <option value="{{ $comp->id }}">[{{ $comp->short_code }}] {{$comp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Mitarbeiter</label>
                        <select name="user_id" id="absenceUserSelect" required class="w-full border rounded p-1.5 text-xs bg-white">
                            @foreach($employees as$emp)
                                <option value="{{ $emp->id }}" data-company="{{ $emp->company_id }}">{{ $emp->name }} ({{$emp->short_code ?: '—' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Art</label>
                        <select name="type" required class="w-full border rounded p-1.5 text-xs bg-white">
                            <option value="Urlaub">🏖️ Urlaub</option>
                            <option value="Krank">🤒 Krank / AU</option>
                            <option value="Schulung">📚 Fortbildung / Prüfung</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Von</label>
                            <input type="datetime-local" name="start_time" required class="w-full border rounded p-1.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Bis</label>
                            <input type="datetime-local" name="end_time" required class="w-full border rounded p-1.5 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Notiz</label>
                        <input type="text" name="notes" placeholder="z. B. Vertretung angefordert" class="w-full border rounded p-1.5 text-xs">
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" onclick="document.getElementById('absenceModal').classList.add('hidden')" class="px-3 py-1.5 border rounded text-xs text-gray-600 bg-gray-50">Abbrechen</button>
                        <button type="submit" class="px-3 py-1.5 bg-amber-600 text-white font-semibold rounded text-xs hover:bg-amber-700">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal: Kunden Kontaktdaten Popup -->
    <div id="clientModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-5 relative">
            <div class="flex justify-between items-center pb-2 border-b">
                <h3 id="clModalName" class="text-base font-bold text-gray-900">Kunden-Kontaktdaten</h3>
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>
            <div class="space-y-3 mt-3 text-xs">
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                    <span class="text-xs font-bold text-blue-900 block mb-1">📞 Telefonnummern:</span>
                    <div class="space-y-1">
                        <div>Dispo: <a id="clDispoPhone" href="#" class="font-bold text-blue-600 hover:underline"></a></div>
                        <div>Ausweich / 24h: <a id="clBackupPhone" href="#" class="font-bold text-blue-600 hover:underline"></a></div>
                    </div>
                </div>

                <div class="bg-gray-50 p-3 rounded-lg border">
                    <span class="text-xs font-bold text-gray-800 block mb-1">✉️ E-Mail-Adressen:</span>
                    <div class="space-y-1">
                        <div>Dispo: <a id="clDispoMail" href="#" class="text-blue-600 hover:underline font-medium"></a></div>
                        <div>Wagenliste: <a id="clWagenMail" href="#" class="text-blue-600 hover:underline font-medium"></a></div>
                        <div>Schadwagen: <a id="clSchadMail" href="#" class="text-blue-600 hover:underline font-medium"></a></div>
                    </div>
                </div>

                <div>
                    <span class="font-bold text-gray-700 block mb-0.5">Notizen:</span>
                    <p id="clNotes" class="bg-gray-50 p-2 rounded border text-gray-600"></p>
                </div>
            </div>
            <div class="flex justify-end pt-3 border-t mt-3">
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')" class="px-4 py-1.5 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200">Schließen</button>
            </div>
        </div>
    </div>

    <script>
        let calendar;
        const canEditAppointments = {{ auth()->user()->canDo('edit_appointments') ? 'true' : 'false' }};
        const canManageAbsences = {{ auth()->user()->canDo('manage_absences') ? 'true' : 'false' }};

        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const compFilter = document.getElementById('filterCompany');
            const empFilter = document.getElementById('filterEmployee');
            const clientFilter = document.getElementById('filterClient');
            const jumpDate = document.getElementById('jumpDate');

            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'de',
                initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                weekNumbers: true,
                weekNumberCalculation: 'ISO',
                weekText: 'KW ',

                slotMinTime: '00:00:00',
                slotMaxTime: '24:00:00',
                slotDuration: '00:30:00',
                slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                allDaySlot: false,
                nowIndicator: true,
                slotEventOverlap: true,

                events: function(info, successCallback, failureCallback) {
                    const params = new URLSearchParams({
                        company_id: compFilter ? compFilter.value : '',
                        employee_id: empFilter ? empFilter.value : '',
                        client_id: clientFilter ? clientFilter.value : ''
                    });

                    fetch('{{ route('calendar.events') }}?' + params.toString())
                        .then(r => r.json())
                        .then(data => successCallback(data))
                        .catch(err => failureCallback(err));
                },

                eventClick: function(info) {
                    const evt = info.event;
                    if (evt.extendedProps.type === 'absence') {
                        if (canManageAbsences) {
                            if (confirm(evt.title + '\nStart: ' + evt.start.toLocaleString('de-DE') + '\nEnde: ' + evt.end.toLocaleString('de-DE') + '\n\nMöchtest du diese Abwesenheit löschen?')) {
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = '/absences/' + evt.extendedProps.real_id;
                                form.innerHTML = '@csrf @method("DELETE")';
                                document.body.appendChild(form);
                                form.submit();
                            }
                        } else {
                            alert(evt.title + '\nStart: ' + evt.start.toLocaleString('de-DE') + '\nEnde: ' + evt.end.toLocaleString('de-DE'));
                        }
                        return;
                    }

                    fetch('/appointments/' + evt.extendedProps.real_id)
                        .then(r => r.json())
                        .then(data => {
                            if (canEditAppointments) {
                                document.getElementById('editAppointmentForm').action = '/appointments/' + data.id;
                                const delForm = document.getElementById('deleteAppointmentForm');
                                if (delForm) delForm.action = '/appointments/' + data.id;

                                const srv = data.services || [];
                                document.getElementById('editServiceAz').checked = srv.includes('Az');
                                document.getElementById('editServiceRd').checked = srv.includes('Rd');
                                document.getElementById('editServiceWu').checked = srv.includes('Wu');
                                const hasOther = srv.includes('Sonstiges');
                                document.getElementById('editServiceOtherChk').checked = hasOther;
                                document.getElementById('editOtherInput').classList.toggle('hidden', !hasOther);
                                document.getElementById('editServiceOther').value = data.service_other || '';
                            } else {
                                const readOnlyEl = document.getElementById('readOnlyServices');
                                if (readOnlyEl) readOnlyEl.textContent = (data.services || []).join(', ') + (data.service_other ? ' (' + data.service_other + ')' : '');
                            }

                            document.getElementById('editLocation').value = data.location || '';
                            document.getElementById('editCompanyId').value = data.company_id;
                            document.getElementById('editClientId').value = data.client_id;
                            document.getElementById('editUserId').value = data.user_id;
                            document.getElementById('editDescription').value = data.description || '';

                            const fmtDate = d => new Date(d).toISOString().slice(0, 16);
                            document.getElementById('editStartTime').value = fmtDate(data.start_time);
                            document.getElementById('editEndTime').value = fmtDate(data.end_time);

                            const uploadForm = document.getElementById('detUploadForm');
                            if (uploadForm) uploadForm.action = '/appointments/' + data.id + '/upload';

                            const attachList = document.getElementById('detAttachments');
                            attachList.innerHTML = '';
                            if (data.attachments && data.attachments.length > 0) {
                                data.attachments.forEach(att => {
                                    const li = document.createElement('li');
                                    li.className = 'flex justify-between items-center text-xs bg-gray-50 p-1.5 rounded border';
                                    li.innerHTML = `
                                        <span class="truncate pr-2">📄 ${att.original_filename} <span class="text-gray-400">(${Math.round(att.file_size / 1024)} KB)</span></span>
                                        <a href="/attachments/${att.id}/download" class="text-blue-600 font-semibold hover:underline">Download</a>
                                    `;
                                    attachList.appendChild(li);
                                });
                            } else {
                                attachList.innerHTML = '<li class="text-xs text-gray-400 italic">Keine Anhänge vorhanden.</li>';
                            }

                            document.getElementById('detailModal').classList.remove('hidden');
                        });
                }
            });

            calendar.render();

            if (compFilter) {
                compFilter.addEventListener('change', function () {
                    const compId = this.value;
                    if (empFilter) {
                        empFilter.value = '';
                        Array.from(empFilter.options).forEach(opt => {
                            if (!opt.value) return;
                            opt.hidden = (compId && opt.dataset.company !== compId);
                        });
                    }
                    calendar.refetchEvents();
                });
            }

            if (empFilter) empFilter.addEventListener('change', () => calendar.refetchEvents());
            if (clientFilter) clientFilter.addEventListener('change', () => calendar.refetchEvents());

            if (jumpDate) {
                jumpDate.addEventListener('change', function () {
                    if (this.value) {
                        calendar.gotoDate(this.value);
                        calendar.changeView('timeGridDay');
                    }
                });
            }

            const resetBtn = document.getElementById('resetFilter');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    if (compFilter) compFilter.value = '';
                    if (empFilter) {
                        empFilter.value = '';
                        Array.from(empFilter.options).forEach(opt => opt.hidden = false);
                    }
                    if (clientFilter) clientFilter.value = '';
                    if (jumpDate) jumpDate.value = '';
                    calendar.refetchEvents();
                });
            }

            const modalComp = document.getElementById('modalCompany');
            if (modalComp) {
                modalComp.addEventListener('change', function () {
                    const empSelect = document.getElementById('modalEmployee');
                    empSelect.innerHTML = '<option value="">Laden...</option>';
                    if (!this.value) {
                        empSelect.innerHTML = '<option value="">Zuerst Firma auswählen</option>';
                        return;
                    }
                    fetch('/calendar/company-data?company_id=' + this.value)
                        .then(r => r.json())
                        .then(data => {
                            empSelect.innerHTML = '<option value="">-- Mitarbeiter wählen --</option>';
                            data.employees.forEach(e => {
                                empSelect.innerHTML += `<option value="${e.id}">${e.name}</option>`;
                            });
                        });
                });
            }

            const absCompSelect = document.getElementById('absenceCompanySelect');
            if (absCompSelect) {
                absCompSelect.addEventListener('change', function () {
                    const compId = this.value;
                    const userSelect = document.getElementById('absenceUserSelect');
                    userSelect.value = '';
                    Array.from(userSelect.options).forEach(opt => {
                        if (!opt.value) return;
                        opt.hidden = (compId && opt.dataset.company !== compId);
                    });
                });
            }
        });

        function deleteCurrentAppointment() {
            if (confirm('Möchtest du diesen Dienst wirklich unwiderruflich löschen?')) {
                document.getElementById('deleteAppointmentForm').submit();
            }
        }

        function openAbsenceModal() {
            const m = document.getElementById('absenceModal');
            if (m) m.classList.remove('hidden');
        }

        function openClientModal(clientId) {
            if (!clientId) return;
            fetch('/clients/' + clientId)
                .then(r => r.json())
                .then(c => {
                    document.getElementById('clModalName').textContent = `🏢 ${c.name} [${c.short_code || '—'}]`;

                    const setPhone = (elId, phone) => {
                        const el = document.getElementById(elId);
                        if (phone) {
                            el.textContent = phone;
                            el.href = 'tel:' + phone.replace(/\s+/g, '');
                            el.classList.remove('text-gray-400');
                        } else {
                            el.textContent = '—';
                            el.removeAttribute('href');
                            el.classList.add('text-gray-400');
                        }
                    };

                    const setMail = (elId, mail, subject) => {
                        const el = document.getElementById(elId);
                        if (mail) {
                            el.textContent = mail;
                            el.href = `mailto:${mail}?subject=${encodeURIComponent(subject)}`;
                            el.classList.remove('text-gray-400');
                        } else {
                            el.textContent = '—';
                            el.removeAttribute('href');
                            el.classList.add('text-gray-400');
                        }
                    };

                    setPhone('clDispoPhone', c.phone_dispo);
                    setPhone('clBackupPhone', c.phone_backup);

                    setMail('clDispoMail', c.email_dispo, 'Dispo Anfrage');
                    setMail('clWagenMail', c.email_wagenliste, 'Wagenliste');
                    setMail('clSchadMail', c.email_schadwagen, 'Schadwagen-Meldung');

                    document.getElementById('clNotes').textContent = c.notes || 'Keine Besonderheiten hinterlegt.';
                    document.getElementById('clientModal').classList.remove('hidden');
                });
        }
    </script>
</body>
</html>
''')
