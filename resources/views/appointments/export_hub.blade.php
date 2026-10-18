<!DOCTYPE html>
<html lang="de">
<head>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dienstplan Export & Druck</title>
    <link rel="stylesheet" href="{{ asset('vendor/tailwind/tailwind.min.css') }}">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800">

    <nav class="bg-white border-b border-gray-200 px-4 py-2.5 sticky top-0 z-30 shadow-sm">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-bold flex items-center gap-1.5">
                    <span>📄</span> <span>Dienstplan Export & Druck</span>
                </h1>
                <a href="{{ route('calendar.index') }}" class="text-blue-600 hover:underline text-xs font-semibold">← Zurück zum Kalender</a>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-2.5 py-1 rounded text-xs font-semibold">Abmelden</button>
            </form>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-4 md:p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- 1. Tagesdienstplan -->
            <div class="bg-white p-6 rounded-xl shadow border border-gray-200 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-2xl">📋</span>
                        <h2 class="text-base font-bold text-gray-900">Tagesdienstplan</h2>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Erzeugt eine druckoptimierte DIN-A4 Tagesübersicht mit automatischer Stundenberechnung.</p>

                    <form action="{{ route('calendar.dailyPlan') }}" method="GET" target="_blank" class="space-y-3">
                        <div>
                            <label class="block text-2xs font-bold uppercase text-gray-600 mb-1">Tag auswählen</label>
                            <div class="relative flex items-center cursor-pointer border border-gray-300 rounded bg-white hover:border-gray-500 transition" >
                                <input type="text" id="dailyInput" name="date" value="{{ now()->format('d.m.Y') }}" placeholder="TT.MM.JJJJ" required class="w-full py-2 pl-3 pr-9 text-xs font-medium text-gray-800 rounded outline-none" oninput="syncInputToPicker(this.value)">
                                <button type="button" class="absolute right-2.5 text-xs text-gray-400 hover:text-gray-700 cursor-pointer" onclick="document.getElementById('dailyPickerHidden').showPicker ? document.getElementById('dailyPickerHidden').showPicker() : document.getElementById('dailyPickerHidden').click()">📅</button>
                                <input type="date" id="dailyPickerHidden" class="sr-only" onchange="if(this.value){let p=this.value.split('-');document.getElementById('dailyInput').value=p[2]+'.'+p[1]+'.'+p[0];}">
                            </div>
                        </div>
                        <div>
                            <label class="block text-2xs font-bold uppercase text-gray-600 mb-1">Unternehmen filtern</label>
                            <select name="company_id" id="dailyCompanySelect" class="w-full border rounded p-2 text-xs bg-white">
                                <option value="">Alle meine Unternehmen</option>
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}">{{ $comp->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-2xs font-bold uppercase text-gray-600 mb-1">Mitarbeiter filtern (optional)</label>
                            <select name="employee_id" id="dailyEmployeeSelect" class="w-full border rounded p-2 text-xs bg-white">
                                <option value="">Alle Mitarbeiter</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" data-company="{{ $emp->company_id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pt-2">
                            <button type="submit" style="background-color: #2563eb !important; color: #ffffff !important;" class="w-full font-semibold py-2 rounded text-xs shadow hover:opacity-90 transition">
                                🖨️ Tagesplan öffnen / Drucken
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. Wochendienstplan -->
            <div class="bg-white p-6 rounded-xl shadow border border-gray-200 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-2xl">📅</span>
                        <h2 class="text-base font-bold text-gray-900">Wochendienstplan</h2>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Erzeugt eine 7-Tage-Übersicht im Querformat inkl. Gesamtsumme der Wochenstunden.</p>

                    <form action="{{ route('calendar.weeklyPlan') }}" method="GET" target="_blank" class="space-y-3">
                        <div>
                            <label class="block text-2xs font-bold uppercase text-gray-600 mb-1">Woche auswählen</label>
                            <div class="relative flex items-center cursor-pointer border rounded bg-white hover:border-gray-500 transition" onclick="triggerPicker('weekPicker')">
                                <span id="weekDisplay" class="py-2 px-3 text-xs font-medium text-gray-800 select-none">KW {{ now()->isoWeek() }} / {{ now()->year }}</span>
                                <span class="ml-auto pr-3 text-gray-400 text-xs pointer-events-none">📅</span>
                                <input type="date" id="weekPicker" name="date" value="{{ now()->format('Y-m-d') }}" required class="sr-only" onchange="syncWeek(this.value)">
                            </div>
                        </div>
                        <div>
                            <label class="block text-2xs font-bold uppercase text-gray-600 mb-1">Unternehmen filtern</label>
                            <select name="company_id" id="weeklyCompanySelect" class="w-full border rounded p-2 text-xs bg-white">
                                <option value="">Alle meine Unternehmen</option>
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}">{{ $comp->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-2xs font-bold uppercase text-gray-600 mb-1">Mitarbeiter filtern (optional)</label>
                            <select name="employee_id" id="weeklyEmployeeSelect" class="w-full border rounded p-2 text-xs bg-white">
                                <option value="">Alle Mitarbeiter</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" data-company="{{ $emp->company_id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pt-2">
                            <button type="submit" style="background-color: #7c3aed !important; color: #ffffff !important;" class="w-full font-semibold py-2 rounded text-xs shadow hover:opacity-90 transition">
                                🖨️ Wochenplan öffnen / Drucken
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. Monatsdienstplan -->
            <div class="bg-white p-6 rounded-xl shadow border border-gray-200 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-2xl">🗓️</span>
                        <h2 class="text-base font-bold text-gray-900">Monatsdienstplan</h2>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Erzeugt eine druckoptimierte Monatsübersicht im Querformat inkl. Gesamtsumme der Monatsstunden.</p>

                    <form action="{{ route('calendar.monthlyPlan') }}" method="GET" target="_blank" class="space-y-3">
                        <div>
                            <label class="block text-2xs font-bold uppercase text-gray-600 mb-1">Monat auswählen</label>
                            <div class="relative flex items-center cursor-pointer border rounded bg-white hover:border-gray-500 transition" onclick="triggerPicker('monthPicker')">
                                <span id="monthDisplay" class="py-2 px-3 text-xs font-medium text-gray-800 select-none">{{ now()->format('m.Y') }}</span>
                                <span class="ml-auto pr-3 text-gray-400 text-xs pointer-events-none">📅</span>
                                <input type="date" id="monthPicker" name="month" value="{{ now()->format('Y-m-d') }}" required class="sr-only" onchange="syncMonth(this.value)">
                            </div>
                        </div>
                        <div>
                            <label class="block text-2xs font-bold uppercase text-gray-600 mb-1">Unternehmen filtern</label>
                            <select name="company_id" id="monthlyCompanySelect" class="w-full border rounded p-2 text-xs bg-white">
                                <option value="">Alle meine Unternehmen</option>
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}">{{ $comp->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-2xs font-bold uppercase text-gray-600 mb-1">Mitarbeiter filtern (optional)</label>
                            <select name="employee_id" id="monthlyEmployeeSelect" class="w-full border rounded p-2 text-xs bg-white">
                                <option value="">Alle Mitarbeiter</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" data-company="{{ $emp->company_id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pt-2">
                            <button type="submit" style="background-color: #059669 !important; color: #ffffff !important;" class="w-full font-semibold py-2 rounded text-xs shadow hover:opacity-90 transition">
                                🖨️ Monatsplan öffnen / Drucken
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
                function syncDay(val) {
            if (!val) return;
            const parts = val.split('-');
            const disp = document.getElementById('dailyDisplay');
            if (disp && parts.length === 3) {
                disp.textContent = parts[2] + '.' + parts[1] + '.' + parts[0];
            }
        }

        function triggerPicker(id) {
            const input = document.getElementById(id);
            if (!input) return;
            try {
                if (typeof input.showPicker === 'function') {
                    input.showPicker();
                } else {
                    input.focus();
                    input.click();
                }
            } catch (err) {
                input.focus();
                input.click();
            }
        }

        function getWeekNumber(dateStr) {
            const d = new Date(dateStr + 'T00:00:00');
            d.setDate(d.getDate() + 4 - (d.getDay() || 7));
            const yearStart = new Date(d.getFullYear(), 0, 1);
            const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
            return { week: weekNo, year: d.getFullYear() };
        }

        function syncWeek(val) {
            if (!val) return;
            const res = getWeekNumber(val);
            const disp = document.getElementById('weekDisplay');
            if (disp) disp.textContent = 'KW ' + res.week + ' / ' + res.year;
        }

        function syncMonth(val) {
            if (!val) return;
            const parts = val.split('-');
            const disp = document.getElementById('monthDisplay');
            if (disp && parts.length >= 2) {
                disp.textContent = parts[1] + '.' + parts[0];
            }
        }

        function bindCompanyFilter(companySelectId, employeeSelectId) {
            const compSelect = document.getElementById(companySelectId);
            const empSelect = document.getElementById(employeeSelectId);
            if (!compSelect || !empSelect) return;

            compSelect.addEventListener('change', function() {
                const compId = this.value;
                empSelect.value = '';
                Array.from(empSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    opt.hidden = (compId && opt.dataset.company !== compId);
                });
            });
        }

        bindCompanyFilter('dailyCompanySelect', 'dailyEmployeeSelect');
        bindCompanyFilter('weeklyCompanySelect', 'weeklyEmployeeSelect');
        bindCompanyFilter('monthlyCompanySelect', 'monthlyEmployeeSelect');
    </script>
</body>
</html>
