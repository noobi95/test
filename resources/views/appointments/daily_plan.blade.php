@php 
    $hasMultiComp = !isset($selectedCompany) || !$selectedCompany;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <link rel="icon" href="{{ asset("favicon.svg") }}" type="image/svg+xml">
    <meta charset="UTF-8">
    <title>Tagesdienstplan - {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/tailwind/tailwind.min.css') }}">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 9pt; background: white; margin: 0; }
            @page { size: portrait; margin: 7mm; }
            body { font-size: 8.5pt; background: white; margin: 0; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 p-3">

    <div class="max-w-7xl mx-auto space-y-3">
        <!-- Kopfzeile mit Druck-Button -->
        <div class="flex flex-wrap justify-between items-center border-b pb-2 no-print gap-2">
            <div>
                <a href="{{ route('calendar.export') }}" class="text-xs text-blue-600 hover:underline">← Zurück zur Auswahl</a>
                <h1 class="text-lg font-bold mt-0.5">📄 Tagesdienstplan (Druck & PDF)</h1>
            </div>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded text-xs shadow">
                🖨️ Drucken / Als PDF speichern
            </button>
        </div>

        <!-- Titel für Ausdruck -->
        <div class="text-center my-1.5 border-b pb-1.5">
            <h2 class="text-xl font-black uppercase tracking-wider">TAGESDIENSTPLAN</h2>
            <p class="text-xs text-gray-600 font-semibold mt-0.5">
                Datum: <span class="text-gray-700 font-normal">{{ \Carbon\Carbon::parse($date)->locale('de')->isoFormat('dddd, DD. MMMM YYYY') }}</span>
                @if($selectedCompany) | Unternehmen: {{ $selectedCompany->short_code }} @endif
                @if($selectedEmployee) | Mitarbeiter: <span class="text-blue-700 font-bold">{{ $selectedEmployee->name }}</span> @endif
            </p>
        </div>

        <!-- 1. Block: Abwesenheiten ganz oben -->
        @if(isset($absences) && $absences->count() > 0)
            <div class="bg-white rounded border overflow-hidden">
                <div class="bg-gray-100 px-2.5 py-0.5 font-bold text-xs flex justify-between items-center border-b">
                    <span class="flex items-center gap-1"><span>⛔</span> <span class="uppercase tracking-wider">Abwesenheit</span></span>
                    <span class="text-2xs text-gray-500">{{ $absences->count() === 1 ? 'Abwesenheit' : 'Abwesenheiten' }} ({{ $absences->count() }})</span>
                </div>
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-gray-50 border-b text-2xs uppercase text-gray-500">
                        <tr>
                            <th class="p-1 border w-24">Von</th>
                            <th class="p-1 border w-24">Bis</th>
                            @if($hasMultiComp)
                                <th class="p-1 border w-24">Unternehmen</th>
                            @endif
                            <th class="p-1 border w-28">Status / Grund</th>
                            @if(!$selectedEmployee)
                                <th class="p-1 border w-44">Mitarbeiter</th>
                            @endif
                            <th class="p-1 border">Details / Notizen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($absences as $ab)
                            <tr class="hover:bg-gray-50">
                                <td class="p-1 border font-bold whitespace-nowrap">{{ $ab->start_time->format('d.m.Y') }}</td>
                                <td class="p-1 border font-bold whitespace-nowrap">{{ $ab->end_time->format('d.m.Y') }}</td>
                                @if($hasMultiComp)
                                    <td class="p-1 border font-semibold">{{ $ab->user->company->short_code ?? '—' }}</td>
                                @endif
                                <td class="p-1 border font-bold text-red-700">⛔ {{ $ab->type }}</td>
                                @if(!$selectedEmployee)
                                    <td class="p-1 border font-bold">{{ $ab->user->name }}</td>
                                @endif
                                <td class="p-1 border italic text-gray-600">{{ $ab->notes ?: 'Keine Notiz' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @php
            $totalHours = 0;
        @endphp

        <!-- 2. Block: Dienste -->
        <div class="bg-white rounded border overflow-hidden">
            <div class="bg-gray-100 px-2.5 py-0.5 font-bold text-xs flex justify-between items-center border-b">
                <span>Dienste</span>
                <span class="text-2xs text-gray-500">Dienste ({{ $appointments->count() }})</span>
            </div>
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-gray-50 border-b text-2xs uppercase text-gray-500">
                    <tr>
                        <th class="p-1 border w-24">Uhrzeit</th>
                        <th class="p-1 border w-16">Dauer</th>
                        @if($hasMultiComp)
                            <th class="p-1 border w-16">Unternehmen</th>
                        @endif
                        <th class="p-1 border w-20">Kunde</th>
                        <th class="p-1 border w-28">Leistung</th>
                        <th class="p-1 border">Ort / Bahnhof</th>
                        @if(!$selectedEmployee)
                            <th class="p-1 border w-44">Mitarbeiter</th>
                        @endif
                        <th class="p-1 border">Details / Notizen</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($appointments as $apt)
                        @php
                            $durationMinutes = $apt->start_time->diffInMinutes($apt->end_time);
                            $durationHours = round($durationMinutes / 60, 2);
                            $totalHours += $durationHours;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="p-1 border font-bold whitespace-nowrap">
                                {{ $apt->start_time->format('H:i') }} - {{ $apt->end_time->format('H:i') }}
                            </td>
                            <td class="p-1 border font-mono whitespace-nowrap text-blue-700 font-bold">
                                {{ number_format($durationHours, 2, ',', '.') }} Std.
                            </td>
                            @if($hasMultiComp)
                                <td class="p-1 border font-semibold">{{ $apt->company->short_code ?? ($apt->employee->company->short_code ?? '—') }}</td>
                            @endif
                            <td class="p-1 border font-semibold" style="color: {{ $apt->client->color ?? '#000' }};">
                                {{ $apt->client->short_code ?? '—' }}
                            </td>
                            <td class="p-1 border font-bold">{{ $apt->title }}</td>
                            <td class="p-1 border">{{ $apt->location ?? '—' }}</td>
                            @if(!$selectedEmployee)
                                <td class="p-1 border font-semibold">{{ $apt->employee->name ?? '—' }}</td>
                            @endif
                            <td class="p-1 border text-gray-600">{{ $apt->description ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $selectedEmployee ? ($hasMultiComp ? 7 : 6) : ($hasMultiComp ? 8 : 7) }}" class="p-3 text-center text-gray-400 italic">Keine Dienste an diesem Tag geplant.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($selectedEmployee && $appointments->count() > 0)
                    <tfoot class="bg-gray-100 font-bold text-xs border-t-2 border-gray-300">
                        <tr>
                            <td class="p-1 border text-right">Gesamtstunden:</td>
                            <td class="p-1 border font-mono text-blue-900 font-black">
                                {{ number_format($totalHours, 2, ',', '.') }} Std.
                            </td>
                            <td colspan="{{ $hasMultiComp ? 5 : 4 }}" class="p-1 border text-gray-500 italic">
                                Summe geplanter Schichtzeiten
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- Wichtige Kontaktdaten -->
        <div class="border-t border-gray-300 pt-2 mt-3 space-y-3">
            <h3 class="font-bold text-2xs uppercase tracking-wider text-gray-700">📞 Wichtige Kontaktdaten für diesen Tag</h3>

            <div class="bg-white p-2.5 rounded border border-gray-200">
                <span class="font-bold text-blue-900 block text-2xs uppercase tracking-wider mb-2">🏢 AUFTRAGGEBER / EVU KONTAKTE:</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-2xs text-gray-700">
                    @forelse($involvedClients as $cl)
                        <div class="p-2 bg-gray-50 rounded border border-gray-100 flex flex-col justify-between">
                            <div>
                                <div class="font-bold text-xs mb-1">
                                    <span style="color: {{ $cl->color ?? '#111827' }}">{{ $cl->short_code }}</span>
                                    @if(($cl->name_display_format ?? 'both') !== 'short')
                                        <span class="font-normal text-gray-600">({{ $cl->name }})</span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-2xs mb-1">
                                    @if($cl->phone_dispo)
                                        <span>Dispo: <strong class="text-black">{{ $cl->phone_dispo }}</strong></span>
                                    @endif
                                    @if($cl->phone_backup)
                                        @if($cl->phone_dispo)<span class="text-gray-300">|</span>@endif
                                        <span>24h: <strong class="text-black">{{ $cl->phone_backup }}</strong></span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-2xs font-mono text-blue-700 break-all">
                                    @if($cl->email_dispo)
                                        <span>Dispo: <a href="mailto:{{ $cl->email_dispo }}" class="hover:underline">{{ $cl->email_dispo }}</a></span>
                                    @endif
                                    @if($cl->email_wagenliste)
                                        @if($cl->email_dispo)<span class="text-gray-300">|</span>@endif
                                        <span>WL: <a href="mailto:{{ $cl->email_wagenliste }}" class="hover:underline">{{ $cl->email_wagenliste }}</a></span>
                                    @endif
                                    @if($cl->email_schadwagen)
                                        @if($cl->email_dispo || $cl->email_wagenliste)<span class="text-gray-300">|</span>@endif
                                        <span>Schadw.: <a href="mailto:{{ $cl->email_schadwagen }}" class="hover:underline">{{ $cl->email_schadwagen }}</a></span>
                                    @endif
                                    @if(!$cl->email_dispo && !$cl->email_wagenliste && !$cl->email_schadwagen && $cl->email)
                                        <span><a href="mailto:{{ $cl->email }}" class="hover:underline">{{ $cl->email }}</a></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <span class="text-gray-400 italic">Keine Auftraggeber beteiligt.</span>
                    @endforelse
                </div>
            </div>

            <div class="bg-white p-2.5 rounded border border-gray-200">
                <span class="font-bold text-blue-900 block text-2xs uppercase tracking-wider mb-2">👤 Eingeteilte Mitarbeiter & Personal:</span>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-2xs text-gray-700">
                    @forelse($involvedEmployees as $emp)
                        <div class="p-2 bg-gray-50 rounded border border-gray-100 flex flex-col justify-between">
                            <div class="font-bold text-gray-900 text-xs mb-0.5">
                                @if($hasMultiComp && isset($emp->company->short_code))[{{ $emp->company->short_code }}] @endif{{ $emp->name }}
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 text-2xs">
                                <span>Tel: <strong class="text-black">{{ $emp->phone ?? "-" }}</strong></span>
                                <span>Mail: <a href="mailto:{{ $emp->email }}" class="text-blue-700 font-mono hover:underline">{{ $emp->email ?? "-" }}</a></span>
                            </div>
                        </div>
                    @empty
                        <span class="text-gray-400 italic">Keine Mitarbeiter eingeteilt.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</body>
</html>