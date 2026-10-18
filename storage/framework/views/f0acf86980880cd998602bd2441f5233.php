<?php
  $canManageChanges = auth()->user() && auth()->user()->canDo('edit_appointments');
  $pendingChangeItems = [];
  if ($canManageChanges) {
      $allowedCompIds = auth()->user()->getAllowedCompanyIds();
      $changeReqs = \App\Models\AppointmentChangeRequest::with(['appointment.user', 'user'])
          ->where('status', 'pending')
          ->whereHas('appointment', function ($q) use ($allowedCompIds) {
              if (!empty($allowedCompIds)) { $q->whereIn('company_id', $allowedCompIds); }
          })->latest()->get();
      $pendingChangeItems = $changeReqs->map(fn($r) => [
          'id' => $r->id,
          'appointment_id' => $r->appointment_id,
          'user_name' => $r->appointment->user->name ?? ($r->user->name ?? 'Mitarbeiter'),
          'location' => $r->appointment->location ?? 'Ohne Ort',
          'reason' => $r->report_notes ?: ($r->reason ?: 'Keine Begründung')
      ])->values()->toArray();
  }
?>
<script>
  window.pendingChangeRequestsData = <?php echo json_encode($pendingChangeItems, 15, 512) ?>;
  window.canManageChanges = <?php echo json_encode($canManageChanges, 15, 512) ?>;
</script>
<!DOCTYPE html>
<html lang="de">
<head>
    <link rel="icon" href="<?php echo e(asset("favicon.svg")); ?>" type="image/svg+xml">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?php echo e(config('app.name', 'Terminplaner')); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset("vendor/tailwind/tailwind.min.css")); ?>">
    <script src="<?php echo e(asset("vendor/fullcalendar/index.global.min.js")); ?>"></script>
    <script src="<?php echo e(asset("vendor/fullcalendar/de.global.min.js")); ?>"></script>
    <style>
        .fc-event { cursor: pointer; }
        .fc-timegrid-slot { height: 2.2rem !important; }
        .fc-toolbar { flex-wrap: wrap; gap: 0.5rem; }
        .fc-week-number { color: #4B5563 !important; font-weight: bold !important; font-size: 0.75rem !important; }

        /* Karten-Layout für Monat & Woche */
        .fc-daygrid-event {
            white-space: normal !important;
            display: block !important;
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 4px !important;
            margin: 2px 3px !important;
            padding: 3px 5px !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
        }
        .fc-daygrid-event .fc-event-main {
            padding: 0 !important;
            color: #1f2937 !important;
        }
        .fc-daygrid-day-frame { min-height: 48px !important; height: auto !important; }
        .fc-scrollgrid-sync-table tr { height: auto !important; }
        .fc-daygrid-day { height: auto !important; }

		/* Kräftig hervorgehobene Abwesenheits-Karten in Monat & Woche */
        .fc-daygrid-event.fc-event-absence,
        .fc-dayGridMonth-view .fc-event-absence,
        .fc-dayGridWeek-view .fc-event-absence {
            background-color: #ffedd5 !important; /* Kräftigerer Orangeton */
            background: #ffedd5 !important;
            border: 1px solid #fdba74 !important;
            border-left: 4px solid #c2410c !important; /* Markanter dunklerer linker Rand */
            border-radius: 4px !important;
            color: #7c2d12 !important; /* Dunkler, gut lesbarer Text */
            display: block !important;
            width: auto !important;
            max-width: 100% !important;
            margin: 2px 3px !important;
            padding: 3px 6px !important;
            box-shadow: 0 1px 3px rgba(194, 65, 12, 0.2) !important;
        }
        .fc-daygrid-event.fc-event-absence .fc-event-main,
        .fc-dayGridMonth-view .fc-event-absence .fc-event-main,
        .fc-dayGridWeek-view .fc-event-absence .fc-event-main {
            color: #7c2d12 !important;
            font-weight: 700 !important;
        }

        /* Durchgehend gelber Hintergrund für den gesamten Tages-Rasterbereich */
        .fc-timeGridDay-view .fc-timegrid-body,
        .fc-timeGridDay-view .fc-timegrid-cols,
        .fc-timeGridDay-view .fc-timegrid-slots,
        .fc-timeGridDay-view .fc-timegrid-slots table,
        .fc-timeGridDay-view .fc-timegrid-slot,
        .fc-timeGridDay-view .fc-timegrid-slot-lane {
            background-color: #fefce8 !important;
        }
        .fc-timeGridDay-view .fc-timegrid-axis {
            background-color: #ffffff !important;
        }

        .fc-timeGridDay-view .fc-timegrid-col { position: relative !important; }
        .fc-timeGridDay-view .fc-timegrid-event {
            width: 125px !important;
            min-width: 125px !important;
            max-width: 125px !important;
            border-radius: 0 !important;
            border: none !important;
            border-right: 2px solid #ffffff !important;
            box-sizing: border-box !important;
            box-shadow: none !important;
        }

        /* Nativer horizontaler Scroll-Container */
        .fc-timeGridDay-view .fc-scroller {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        /* Durchgängige Linien über die gesamte Breite */
        .fc-timeGridDay-view .fc-timegrid-body {
            min-width: 100% !important;
            width: max-content !important;
        }
        .fc-timeGridDay-view .fc-timegrid-slots table,
        .fc-timeGridDay-view .fc-timegrid-cols table {
            width: 100% !important;
            min-width: 100% !important;
        }
        .fc-timeGridDay-view .fc-timegrid-slot-lane {
            width: 100% !important;
            min-width: 100% !important;
        }

        /* Rote Linie für die aktuelle Uhrzeit */
        #customTodayTimeLine {
            position: absolute !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            height: 2px !important;
            background-color: #ef4444 !important;
            z-index: 60 !important;
            pointer-events: none !important;
        }
        #customTodayTimeLine::before {
            content: "" !important;
            position: absolute !important;
            left: -5px !important;
            top: -4px !important;
            width: 10px !important;
            height: 10px !important;
            border-radius: 50% !important;
            background-color: #ef4444 !important;
            box-shadow: 0 0 0 2px #ffffff !important;
        }

        .fc .fc-timegrid-now-indicator-arrow { display: none !important; }

        /* Desktop: Wochentag zentriert, Badge rechts verankert */
        .fc-timeGridDay-view .fc-col-header-cell-cushion {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: relative !important;
            width: 100% !important;
            padding: 4px 10px !important;
            box-sizing: border-box !important;
        }
        #dayAbsenceHeaderBadge {
            position: absolute !important;
            right: 12px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
            background: transparent !important;
            border: none !important;
            color: #111827 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            pointer-events: none !important;
            user-select: none !important;
            white-space: nowrap !important;
        }

        /* Smartphone (<= 768px): Wochentag links, Badge rechts */
        @media (max-width: 768px) {
            .fc-timeGridDay-view .fc-col-header-cell-cushion {
                justify-content: space-between !important;
            }
            #dayAbsenceHeaderBadge {
                position: static !important;
            }
            #calendar { overflow-x: auto !important; -webkit-overflow-scrolling: touch !important; }
            #calendar .fc-view-harness { overflow-x: auto !important; }
            .fc-dayGridWeek-view table, .fc-dayGridMonth-view table { min-width: 720px !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800">

    <nav class="bg-white border-b border-gray-200 px-2 sm:px-4 py-2 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-4">
                <span class="text-lg font-bold text-gray-900 flex items-center gap-1.5">
                    <span>🚂</span> <span><?php echo e(config('app.name', 'Terminplaner')); ?></span>
                </span>
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <button type="button" id="btnOpenPendingReportsModal" onclick="openPendingReportsModal()" class="h-8 min-w-[2.5rem] px-2.5 inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 whitespace-nowrap overflow-hidden rounded-lg text-xs font-semibold transition gap-1.5 border border-gray-200 shadow-sm" style="color: #374151 !important;">
                        🔔 <span id="pendingReportsBadge" class="ml-1 px-1.5 py-0.5 text-3xs font-bold rounded-full bg-red-600 text-white hidden"></span>
                    </button>
                    <a href="<?php echo e(route('documents.index')); ?>" class="h-8 min-w-[2.5rem] px-2.5 inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 whitespace-nowrap overflow-hidden rounded-lg text-xs font-semibold transition">
                        📁 Dateien
                    </a>
                </div>
            </div>

            <div class="flex items-center">
                <div class="relative" id="accountDropdownContainer">
                    <button type="button" onclick="toggleAccountMenu(event)" id="accountDropdownBtn" class="h-8 inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300 px-3 rounded-lg text-xs font-bold transition gap-1.5 shadow-sm">
                        <span>👤</span> <span>Konto</span> <span id="accountDropdownArrow" class="text-xs text-gray-500 font-bold">▼</span>
                    </button>
                    <div id="accountDropdownMenu" class="hidden absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-52 bg-white rounded-xl shadow-2xl border border-gray-200 py-1.5 z-[100] text-xs">
                        <div class="px-3 py-1.5 border-b border-gray-100 text-gray-500 text-2xs truncate">
                            Angemeldet als<br><strong class="text-gray-900 text-xs"><?php echo e(auth()->user()->name); ?></strong>
                        </div>
                        <?php if(auth()->user()->canDo('access_admin_panel')): ?>
                            <a href="<?php echo e(route('admin.index')); ?>" class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-purple-50 hover:text-purple-700 font-semibold transition">
                                <span>⚙️</span> <span>Admin Panel</span>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo e(route('password.change')); ?>" class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-semibold transition">
                            <span>🔑</span> <span>Passwort ändern</span>
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <form method="POST" action="<?php echo e(route('logout')); ?>" class="block w-full m-0">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-red-600 hover:bg-red-50 font-semibold text-left transition">
                                <span>🚪</span> <span>Abmelden</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-3 md:p-6 space-y-3">
        <?php if(session('success')): ?>
            <div class="p-3 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-semibold">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="p-3 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm font-semibold">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="p-4 bg-red-100 border border-red-300 text-red-900 rounded-lg text-sm font-semibold shadow">
                <p class="font-bold flex items-center gap-1 text-red-800">
                    <span>⚠️</span> <span>Der Eintrag konnte nicht gespeichert werden:</span>
                </p>
                <ul class="list-disc pl-5 mt-1.5 text-xs font-medium space-y-0.5">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="flex md:hidden items-center justify-between gap-2">
            <button type="button" onclick="toggleMobileFilterModal()" class="h-8 inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-800 border border-gray-300 px-3 rounded-lg text-xs font-bold transition gap-1.5 shadow-sm">
                <span>🔍</span> <span>Filter</span>
            </button>
            <div class="flex items-center gap-1.5">
                <?php if(auth()->user()->canDo('edit_appointments') || auth()->user()->canDo('manage_absences')): ?>
                    <button type="button" onclick="openCreateEntryModal('appointment')" class="h-8 inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-2.5 rounded-lg text-xs font-semibold shadow-sm transition gap-1">
                        <span>➕</span> <span>Eintrag</span>
                    </button>
                <?php endif; ?>
                <a href="<?php echo e(route('calendar.export')); ?>" class="h-8 inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 rounded-lg text-xs font-semibold shadow-sm transition gap-1">
                    <span>📄</span> <span>Export</span>
                </a>
            </div>
        </div>

        <div class="hidden md:block bg-white p-3 rounded-xl shadow border border-gray-200">
            <div class="flex flex-wrap lg:flex-nowrap items-end justify-between gap-3">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 flex-grow">
                    <div>
                        <label class="text-xs font-semibold text-gray-600 block mb-0.5">1. Unternehmen</label>
                        <select id="filterCompany" class="w-full border border-gray-300 rounded p-1.5 text-xs bg-white">
                            <?php if(count($companies) > 1): ?>
                                <option value="">Alle meine Unternehmen</option>
                            <?php endif; ?>
                            <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($comp->id); ?>"><?php echo e($comp->display_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-600 block mb-0.5">2. Mitarbeiter</label>
                        <select id="filterEmployee" class="w-full border border-gray-300 rounded p-1.5 text-xs bg-white">
                            <?php if(auth()->user()->canDo('view_other_appointments')): ?>
                                <option value="">Alle Mitarbeiter</option>
                            <?php endif; ?>
                            <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($emp->id); ?>" data-company="<?php echo e($emp->company_id); ?>"><?php echo e($emp->name); ?> (<?php echo e($emp->short_code ?: '—'); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-600 block mb-0.5">3. Kunde</label>
                        <select id="filterClient" class="w-full border border-gray-300 rounded p-1.5 text-xs bg-white">
                            <option value="">Alle Kunden</option>
                            <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($c->id); ?>"><?php echo e($c->display_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-600 block mb-0.5">Zu Tag springen</label>
                        <input type="date" id="jumpDate" class="w-full border border-gray-300 rounded p-1.5 text-xs bg-white">
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 pb-0.5">
                    <?php if(auth()->user()->canDo('edit_appointments') || auth()->user()->canDo('manage_absences')): ?>
                        <button type="button" onclick="openCreateEntryModal('appointment')" class="h-8 inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-3 rounded-lg text-xs font-semibold shadow-sm transition gap-1.5">
                            <span>➕</span> <span>Eintrag</span>
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo e(route('calendar.export')); ?>" class="h-8 inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 rounded-lg text-xs font-semibold shadow-sm transition gap-1">
                        <span>📄</span> <span>Export</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white p-3 md:p-5 rounded-xl shadow border border-gray-200 overflow-x-auto">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modale für Erstellung, Details und Dienstmeldungen -->
    <div id="createEntryModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-5 relative">
            <div class="flex justify-between items-center pb-2 border-b">
                <div class="flex gap-2">
                    <?php if(auth()->user()->canDo('edit_appointments')): ?>
                        <button type="button" id="tabBtnApp" onclick="switchCreateTab('appointment')" class="px-3 py-1 text-xs font-bold rounded-lg bg-blue-600 text-white transition">
                            🚂 Neuer Dienst
                        </button>
                    <?php endif; ?>
                    <?php if(auth()->user()->canDo('manage_absences')): ?>
                        <button type="button" id="tabBtnAbs" onclick="switchCreateTab('absence')" class="px-3 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                            ⛔ Abwesenheit
                        </button>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="closeCreateEntryModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <?php if(auth()->user()->canDo('edit_appointments')): ?>
                <div id="tabContentApp">
                    <form action="<?php echo e(route('appointments.store')); ?>" method="POST" enctype="multipart/form-data" class="space-y-3 mt-3">
                        <?php echo csrf_field(); ?>
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
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Von</label>
                                <input type="datetime-local" name="start_time" required class="w-full border rounded p-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Bis</label>
                                <input type="datetime-local" name="end_time" required class="w-full border rounded p-1.5 text-xs">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Unternehmen</label>
                                <select name="company_id" id="newAppointmentCompanySelect" required class="w-full border rounded p-1.5 text-xs bg-white">
                                    <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($comp->id); ?>"><?php echo e($comp->display_name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Auftraggeber (Kunde)</label>
                                <select name="client_id" required class="w-full border rounded p-1.5 text-xs bg-white">
                                    <option value="">-- Bitte wählen --</option>
                                    <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($client->id); ?>"><?php echo e($client->display_name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Mitarbeiter</label>
                            <select name="employee_id" id="newAppointmentEmployeeSelect" required class="w-full border rounded p-1.5 text-xs bg-white">
                                <option value="">-- Bitte wählen --</option>
                                <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($emp->id); ?>" data-company="<?php echo e($emp->company_id); ?>"><?php echo e($emp->name); ?> (<?php echo e($emp->short_code ?: '—'); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Details / Notizen</label>
                            <textarea name="notes" rows="2" placeholder="Zusätzliche Infos zum Dienst..." class="w-full border rounded p-1.5 text-xs"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Anhang / Dokument</label>
                            <input type="file" name="attachment" class="text-xs">
                        </div>
                        <div class="flex justify-end gap-2 pt-3 border-t mt-4">
                            <button type="button" onclick="closeCreateEntryModal()" class="px-4 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold text-gray-800 bg-white hover:bg-gray-100 shadow-sm">Abbrechen</button>
                            <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded text-xs shadow">Dienst anlegen</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if(auth()->user()->canDo('manage_absences')): ?>
                <div id="tabContentAbs" class="hidden">
                    <form action="<?php echo e(route('absences.store')); ?>" method="POST" class="space-y-3 mt-3">
                        <?php echo csrf_field(); ?>
                        <?php if(count($companies) > 1 && auth()->user()->canDo('view_other_appointments')): ?>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Unternehmen auswählen</label>
                                <select id="absenceCompanySelect" class="w-full border rounded p-1.5 text-xs bg-white">
                                    <option value="">-- Alle meine Firmen --</option>
                                    <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($comp->id); ?>"><?php echo e($comp->display_name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Mitarbeiter</label>
                            <select name="user_id" id="absenceUserSelect" required class="w-full border rounded p-1.5 text-xs bg-white">
                                <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($emp->id); ?>" data-company="<?php echo e($emp->company_id); ?>"><?php echo e($emp->name); ?> (<?php echo e($emp->short_code ?: '—'); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Von (Datum)</label>
                                <input type="date" id="abs_start_date" required class="w-full border rounded p-1.5 text-xs">
                                <input type="hidden" name="start_time" id="abs_start_time">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Bis (Datum)</label>
                                <input type="date" id="abs_end_date" required class="w-full border rounded p-1.5 text-xs">
                                <input type="hidden" name="end_time" id="abs_end_time">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Notiz</label>
                            <input type="text" name="notes" placeholder="z. B. Vertretung angefordert" class="w-full border rounded p-1.5 text-xs">
                        </div>
                        <div class="flex justify-end gap-2 pt-3 border-t mt-4 sticky bottom-0 bg-white">
                            <button type="button" onclick="closeCreateEntryModal()" class="px-4 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold text-gray-800 bg-white hover:bg-gray-100 shadow-sm">Abbrechen</button>
                            <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded text-xs shadow">Speichern</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal: Termin Details & Bearbeiten -->
    <div id="detailModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-5 relative">
            <div class="flex justify-between items-center pb-2 border-b">
                <h3 class="text-base font-bold text-gray-900">
                    <?php echo e(auth()->user()->canDo('edit_appointments') ? 'Dienst Details & Bearbeiten' : 'Dienst Details'); ?>

                </h3>
                <button type="button" onclick="document.getElementById('detailModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>
            <div id="detailReportBar" class="p-2.5 rounded-lg border text-xs flex items-center justify-between mt-2">
                <div class="flex items-center gap-1.5">
                    <span id="detailReportStatusIcon">⚪</span>
                    <span id="detailReportStatusText" class="font-semibold text-gray-800">Dienstmeldung: Offen</span>
                </div>
                <button type="button" id="btnOpenShiftReport" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow-sm transition">
                    📝 Dienstmeldung
                </button>
            </div>
            <form id="editAppointmentForm" method="POST" class="space-y-3 mt-3">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <?php if(auth()->user()->canDo('edit_appointments')): ?>
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
                <?php else: ?>
                    <div>
                        <span class="text-xs font-semibold text-gray-500 block">Leistung:</span>
                        <span id="readOnlyServices" class="font-bold text-sm text-gray-900"></span>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Ort / Bahnhof</label>
                    <input type="text" name="location" id="editLocation" required <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'readonly' : ''); ?> class="w-full border rounded p-1.5 text-xs <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : ''); ?>">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Von</label>
                        <input type="datetime-local" name="start_time" id="editStartTime" required <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'readonly' : ''); ?> class="w-full border rounded p-1.5 text-xs <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : ''); ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Bis</label>
                        <input type="datetime-local" name="end_time" id="editEndTime" required <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'readonly' : ''); ?> class="w-full border rounded p-1.5 text-xs <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : ''); ?>">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Unternehmen</label>
                        <select name="company_id" id="editCompanyId" required <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'disabled' : ''); ?> class="w-full border rounded p-1.5 text-xs bg-white <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : ''); ?>">
                            <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($comp->id); ?>"><?php echo e($comp->display_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Auftraggeber (Kunde)</label>
                        <div class="flex items-center gap-1.5">
                            <select name="client_id" id="editClientId" required <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'disabled' : ''); ?> class="w-full border rounded p-1.5 text-xs bg-white <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : ''); ?>">
                                <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($client->id); ?>"><?php echo e($client->display_name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <button type="button" id="btnOpenClientInfo" title="Kundendetails anzeigen" class="p-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 rounded text-xs flex items-center justify-center shrink-0">ℹ️</button>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Mitarbeiter</label>
                    <select name="user_id" id="editEmployeeId" required <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'disabled' : ''); ?> class="w-full border rounded p-1.5 text-xs bg-white <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : ''); ?>">
                        <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($emp->id); ?>" data-company="<?php echo e($emp->company_id); ?>"><?php echo e($emp->name); ?> (<?php echo e($emp->short_code ?: '—'); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Notiz</label>
                    <input type="text" name="description" id="editNotes" placeholder="Zusätzliche Infos" <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'readonly' : ''); ?> class="w-full border rounded p-1.5 text-xs <?php echo e(!auth()->user()->canDo('edit_appointments') ? 'bg-gray-100' : ''); ?>">
                </div>
                <div class="flex justify-between items-center pt-3 border-t mt-4">
                    <div>
                        <?php if(auth()->user()->canDo('delete_appointments')): ?>
                            <button type="button" id="btnDeleteAppointment" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold">Löschen</button>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="document.getElementById('detailModal').classList.add('hidden')" class="px-4 py-1.5 border border-gray-300 rounded text-xs font-semibold text-gray-800 bg-white hover:bg-gray-100 shadow-sm">Schließen</button>
                        <?php if(auth()->user()->canDo('edit_appointments')): ?>
                            <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded text-xs shadow-sm">Speichern</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <div class="border-t pt-3 mt-3">
                <span class="font-bold text-xs text-gray-700 block mb-1">Angehängte Dokumente</span>
                <ul id="detAttachments" class="space-y-1 mb-2 max-h-28 overflow-y-auto"></ul>
                <?php if(auth()->user()->canDo('manage_files') || auth()->user()->canDo('edit_appointments')): ?>
                    <form id="detUploadForm" method="POST" enctype="multipart/form-data" class="flex gap-2">
                        <?php echo csrf_field(); ?>
                        <input type="file" name="attachment" required class="text-xs">
                        <button type="submit" class="bg-gray-800 text-white px-2 py-1 rounded text-xs">Hochladen</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal: Abwesenheit bearbeiten / ansehen -->
    <div id="editAbsenceModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-5 relative">
            <div class="flex justify-between items-center pb-2 border-b">
                <h3 class="text-base font-bold text-gray-900">⛔ Abwesenheit Details</h3>
                <button type="button" onclick="document.getElementById('editAbsenceModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>
            <form id="editAbsenceForm" method="POST" class="space-y-3 mt-3">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Mitarbeiter</label>
                    <input type="text" id="editAbsEmployee" readonly class="w-full border rounded p-1.5 text-xs bg-gray-100 font-semibold text-gray-800">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Art</label>
                    <select name="type" id="editAbsType" required <?php echo e(!auth()->user()->canDo('manage_absences') ? 'disabled' : ''); ?> class="w-full border rounded p-1.5 text-xs bg-white <?php echo e(!auth()->user()->canDo('manage_absences') ? 'bg-gray-100' : ''); ?>">
                        <option value="Urlaub">🏖️ Urlaub</option>
                        <option value="Krank">🤒 Krank / AU</option>
                        <option value="Schulung">📚 Fortbildung / Prüfung</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Von (Datum)</label>
                        <input type="date" id="edit_abs_start_date" required <?php echo e(!auth()->user()->canDo('manage_absences') ? 'readonly' : ''); ?> class="w-full border rounded p-1.5 text-xs <?php echo e(!auth()->user()->canDo('manage_absences') ? 'bg-gray-100' : ''); ?>">
                        <input type="hidden" name="start_time" id="edit_abs_start_time">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Bis (Datum)</label>
                        <input type="date" id="edit_abs_end_date" required <?php echo e(!auth()->user()->canDo('manage_absences') ? 'readonly' : ''); ?> class="w-full border rounded p-1.5 text-xs <?php echo e(!auth()->user()->canDo('manage_absences') ? 'bg-gray-100' : ''); ?>">
                        <input type="hidden" name="end_time" id="edit_abs_end_time">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Notiz</label>
                    <input type="text" name="notes" id="editAbsNotes" placeholder="Keine Notiz vorhanden" <?php echo e(!auth()->user()->canDo('manage_absences') ? 'readonly' : ''); ?> class="w-full border rounded p-1.5 text-xs <?php echo e(!auth()->user()->canDo('manage_absences') ? 'bg-gray-100' : ''); ?>">
                </div>
                <div class="flex justify-between items-center pt-3 border-t mt-4">
                    <div>
                        <?php if(auth()->user()->canDo('manage_absences')): ?>
                            <button type="button" id="btnDeleteAbsence" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold">Löschen</button>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="document.getElementById('editAbsenceModal').classList.add('hidden')" class="px-4 py-1.5 border border-gray-300 rounded text-xs font-semibold text-gray-800 bg-white hover:bg-gray-100 shadow-sm">Schließen</button>
                        <?php if(auth()->user()->canDo('manage_absences')): ?>
                            <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded text-xs shadow-sm">Speichern</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Dienstmeldung erfassen / bearbeiten -->
    <div id="shiftReportModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-60 flex items-center justify-center p-3">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-5 relative border border-gray-100">
            <div class="flex justify-between items-center pb-3 border-b">
                <div>
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-1.5">
                        <span>📝</span> <span>Dienstmeldung</span>
                    </h3>
                    <span id="repModalSubtitle" class="text-xs text-gray-500 font-semibold"></span>
                </div>
                <button type="button" onclick="document.getElementById('shiftReportModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>
            <form id="shiftReportForm" method="POST" class="space-y-3 mt-3">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action_type" id="reportActionType" value="save">
                <div id="repStatusBox" class="p-2.5 rounded-lg text-xs font-semibold flex items-center justify-between">
                    <span id="repStatusText">Status</span>
                    <span id="repStatusBadge" class="px-2 py-0.5 rounded text-2xs font-bold uppercase"></span>
                </div>
                <div class="bg-blue-50/50 p-2.5 rounded-xl border border-blue-100 space-y-2">
                    <label class="block text-xs font-bold text-blue-900 uppercase">⏱️ Tatsächliche Schichtzeit</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-2xs font-semibold text-gray-600 mb-0.5">Beginn</label>
                            <input type="datetime-local" name="actual_start" id="repActualStart" class="w-full border rounded p-1.5 text-xs bg-white">
                        </div>
                        <div>
                            <label class="block text-2xs font-semibold text-gray-600 mb-0.5">Ende</label>
                            <input type="datetime-local" name="actual_end" id="repActualEnd" class="w-full border rounded p-1.5 text-xs bg-white">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div class="bg-gray-50 p-2.5 rounded-xl border space-y-1.5">
                        <label class="block text-xs font-bold text-gray-800 uppercase">🚗 Hinfahrt</label>
                        <div>
                            <label class="block text-2xs text-gray-500">Beginn</label>
                            <input type="datetime-local" name="travel_out_start" id="repTravelOutStart" class="w-full border rounded p-1 text-xs bg-white">
                        </div>
                        <div>
                            <label class="block text-2xs text-gray-500">Ende</label>
                            <input type="datetime-local" name="travel_out_end" id="repTravelOutEnd" class="w-full border rounded p-1 text-xs bg-white">
                        </div>
                    </div>
                    <div class="bg-gray-50 p-2.5 rounded-xl border space-y-1.5">
                        <label class="block text-xs font-bold text-gray-800 uppercase">🚙 Rückfahrt</label>
                        <div>
                            <label class="block text-2xs text-gray-500">Beginn</label>
                            <input type="datetime-local" name="travel_back_start" id="repTravelBackStart" class="w-full border rounded p-1 text-xs bg-white">
                        </div>
                        <div>
                            <label class="block text-2xs text-gray-500">Ende</label>
                            <input type="datetime-local" name="travel_back_end" id="repTravelBackEnd" class="w-full border rounded p-1 text-xs bg-white">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">☕ Pause (Min.)</label>
                        <input type="number" min="0" step="5" name="pause_minutes" id="repPauseMinutes" placeholder="z. B. 45" class="w-full border rounded p-1.5 text-xs bg-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-0.5">Bemerkungen / Grund</label>
                        <input type="text" name="report_notes" id="repNotes" placeholder="Besonderheiten, Verspätungsgrund..." class="w-full border rounded p-1.5 text-xs bg-white">
                    </div>
                </div>
                <div class="flex flex-wrap justify-between items-center pt-3 border-t mt-4 gap-2">
                    <div id="repDispoActions"></div>
                    <div class="flex gap-2">
                        <button type="button" onclick="document.getElementById('shiftReportModal').classList.add('hidden')" class="px-4 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold text-gray-800 bg-white hover:bg-gray-100 shadow-sm">Abbrechen</button>
                        <button type="button" id="btnRepSave" onclick="submitReport('save')" class="px-3 py-1.5 bg-gray-700 hover:bg-gray-800 text-white font-semibold rounded text-xs shadow-sm">Speichern</button>
                        <button type="button" id="btnRepComplete" onclick="submitReport('complete')" class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded text-xs shadow-sm">Abschließen</button>
                        <button type="button" id="btnRepRequestChange" onclick="submitReport('request_change')" style="background-color: #d97706 !important; color: #ffffff !important;" class="px-4 py-1.5 font-bold rounded-lg text-xs shadow hover:opacity-90 transition">Korrektur beantragen</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Offene Dienste Übersicht -->
    <div id="pendingReportsModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-60 flex items-center justify-center p-3">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-5 relative border border-gray-100">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-1.5">
                    <span>Benachrichtigungen</span>
                </h3>
                <button type="button" onclick="document.getElementById('pendingReportsModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>
            <div class="mt-3 overflow-y-auto max-h-96">
                <ul id="pendingReportsList" class="space-y-2 text-xs">
                    <li class="p-3 text-center text-gray-400">Lade offene Dienste...</li>
                </ul>
            </div>
            <div class="flex justify-end pt-3 border-t mt-4">
                <button type="button" onclick="document.getElementById('pendingReportsModal').classList.add('hidden')" class="px-4 py-1.5 border border-gray-300 rounded text-xs font-semibold text-gray-800 bg-white hover:bg-gray-100">Schließen</button>
            </div>
        </div>
    </div>

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

    <!-- FullCalendar Initialisierung -->
    <script>
        let calendar;
        const canEditAppointments = <?php echo e(auth()->user()->canDo('edit_appointments') ? 'true' : 'false'); ?>;
        const canManageAbsences = <?php echo e(auth()->user()->canDo('manage_absences') ? 'true' : 'false'); ?>;
        const currentUserId = <?php echo e(auth()->id()); ?>;

        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const compFilter = document.getElementById('filterCompany');
            const empFilter = document.getElementById('filterEmployee');
            const clientFilter = document.getElementById('filterClient');
            const jumpDate = document.getElementById('jumpDate');

            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'de',
                initialView: window.innerWidth < 768 ? 'timeGridDay' : 'dayGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,timeGridDay'
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
                slotEventOverlap: false,
                height: 'auto',
                contentHeight: 'auto',
                dayMaxEvents: false,

                datesSet: function(dateInfo) {
                    const oldLine = document.getElementById('customTodayTimeLine');
                    if (oldLine) oldLine.remove();
                    const oldBadge = document.getElementById('dayAbsenceHeaderBadge');
                    if (oldBadge) oldBadge.remove();

                    if (dateInfo.view.type === 'timeGridDay') {
                        const dayStr = dateInfo.startStr.slice(0, 10);
                        
                        // 1. Abwesenheiten für den Tag laden und Zähler im Header anzeigen
                        fetch('<?php echo e(route('calendar.events')); ?>?start=' + dayStr + 'T00:00:00&end=' + dayStr + 'T23:59:59')
                            .then(r => r.json())
                            .then(events => {
                                const absList = events.filter(e => {
                                    return e.type === 'absence' || 
                                           (e.extendedProps && e.extendedProps.type === 'absence') || 
                                           String(e.id).startsWith('abs_') ||
                                           (e.title && (e.title.includes('Urlaub') || e.title.includes('Krank')));
                                });

                                if (absList.length > 0) {
                                    const headerCell = document.querySelector('.fc-timeGridDay-view .fc-col-header-cell-cushion');
                                    if (headerCell && !document.getElementById('dayAbsenceHeaderBadge')) {
                                        const badge = document.createElement('span');
                                        badge.id = 'dayAbsenceHeaderBadge';
                                        badge.innerHTML = '⛔ Abwesend (' + absList.length + ')';
                                        headerCell.appendChild(badge);
                                    }
                                }
                            });

                        // 2. Rote Uhrzeit-Linie mit Punkt (nur wenn der gewählte Tag heute ist)
                        const now = new Date();
                        const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
                        
                        if (dayStr === todayStr) {
                            setTimeout(() => {
                                const body = document.querySelector('.fc-timeGridDay-view .fc-timegrid-body');
                                const slotsTable = document.querySelector('.fc-timeGridDay-view .fc-timegrid-slots table');
                                if (body && slotsTable && !document.getElementById('customTodayTimeLine')) {
                                    const minutes = (now.getHours() * 60) + now.getMinutes();
                                    const totalMinutes = 24 * 60;
                                    const topPos = Math.round((minutes / totalMinutes) * slotsTable.offsetHeight);

                                    const line = document.createElement('div');
                                    line.id = 'customTodayTimeLine';
                                    line.style.top = topPos + 'px';
                                    body.appendChild(line);

                                    const scroller = document.querySelector('.fc-timeGridDay-view .fc-scroller');
                                    if (scroller) {
                                        scroller.scrollTop = Math.max(0, topPos - 200);
                                    }
                                }
                            }, 50);
                        }
                    }
                },

                eventContent: function(arg) {
                    const evt = arg.event;
                    const view = arg.view.type;
                    const isAbs = evt.extendedProps.type === 'absence' || !!evt.extendedProps.absence_type || (evt.title && (evt.title.includes('Urlaub') || evt.title.includes('Krank')));

                    if (view !== 'timeGridDay' && isAbs) {
                        const cleanTitle = evt.title.replace(/^⛔\s*/, '').replace(/^Urlaub:\s*/, 'Urlaub: ');
                        return {
                            html: `<div class="px-1.5 py-1 text-[11px] font-semibold flex items-center gap-1.5 truncate">
                                <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 text-white flex items-center justify-center text-[9px] font-bold shrink-0">⛔</span>
                                <span class="truncate">${cleanTitle}</span>
                            </div>`
                        };
                    }

                    const sTime = evt.start ? evt.start.toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'}) : '';
                    const eTime = evt.end ? evt.end.toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'}) : '';
                    const range = (sTime && eTime) ? (sTime + ' - ' + eTime) : sTime;
                    const col = evt.backgroundColor || '#2563EB';

                    return {
                        html: `<div class="px-1 py-0.5 text-left leading-tight overflow-hidden w-full" style="font-size:11px;">
                            <div class="flex items-center gap-1 font-bold truncate">
                                <span class="inline-block w-2 h-2 rounded-full flex-shrink-0" style="background-color:${col};"></span>
                                <span>${range}</span>
                            </div>
                            <div class="font-medium text-gray-800 truncate pl-3" style="font-size:10.5px;">
                                ${evt.title}
                            </div>
                        </div>`
                    };
                },

                eventDidMount: function(info) {
                    if (info.view.type === 'timeGridDay') {
                        const h = info.el.closest('.fc-timegrid-event-harness') || info.el;
                        h.dataset.s = info.event.start ? info.event.start.getTime() : 0;

                        clearTimeout(window._tblTimer);
                        window._tblTimer = setTimeout(() => {
                            const parent = h.parentElement;
                            if (!parent) return;

                            const all = Array.from(parent.querySelectorAll('.fc-timegrid-event-harness')).filter(e => e.style.display !== 'none');
                            all.sort((a, b) => Number(a.dataset.s) - Number(b.dataset.s));
                            all.forEach((e, idx) => {
                                e.style.setProperty('left', (idx * 125) + 'px', 'important');
                                e.style.setProperty('right', 'auto', 'important');
                                e.style.setProperty('width', '125px', 'important');
                            });

                            // Gesamtbreite berechnen und auf die Tabelle anwenden
                            const axisWidth = 55;
                            const eventsWidth = all.length * 125;
                            const requiredWidth = Math.max(eventsWidth + axisWidth, parent.clientWidth || 0);

                            if (all.length > 0) {
                                const dayView = document.querySelector('.fc-timeGridDay-view');
                                if (dayView) {
                                    const scrollTables = dayView.querySelectorAll('.fc-timegrid-body, .fc-timegrid-slots table, .fc-timegrid-cols table');
                                    scrollTables.forEach(el => {
                                        el.style.setProperty('min-width', requiredWidth + 'px', 'important');
                                    });

                                    const col = parent.closest('.fc-timegrid-col');
                                    if (col) {
                                        col.style.setProperty('min-width', eventsWidth + 'px', 'important');
                                    }
                                }
                            }
                        }, 25);
                    }
                },

                events: function(info, successCallback, failureCallback) {
                    const params = new URLSearchParams({
                        company_id: compFilter ? compFilter.value : '',
                        employee_id: empFilter ? empFilter.value : '',
                        client_id: clientFilter ? clientFilter.value : ''
                    });

                    fetch('<?php echo e(route('calendar.events')); ?>?' + params.toString())
                        .then(r => r.json())
                        .then(data => {
                            const isDay = calendar && calendar.view && calendar.view.type === 'timeGridDay';

                            const absList = data.filter(ev => {
                                return ev.type === 'absence' || 
                                       (ev.extendedProps && ev.extendedProps.type === 'absence') || 
                                       String(ev.id).startsWith('abs_') ||
                                       (ev.title && (ev.title.includes('Urlaub') || ev.title.includes('Krank')));
                            });

                            if (isDay) {
                                const shiftsOnly = data.filter(ev => !absList.includes(ev));
                                successCallback(shiftsOnly);
                            } else {
                                const mapped = data.map(ev => {
                                    if (absList.includes(ev)) {
                                        return Object.assign({}, ev, {
                                            allDay: true,
                                            backgroundColor: '#fef3c7',
                                            borderColor: '#fcd34d',
                                            textColor: '#92400e',
                                            classNames: ['fc-event-absence']
                                        });
                                    }
                                    return ev;
                                });
                                successCallback(mapped);
                            }

                            if (typeof refreshPendingCounterFromEvents === 'function') {
                                refreshPendingCounterFromEvents(data);
                            }
                        })
                        .catch(err => failureCallback(err));
                },

                eventClick: function(info) {
                    const evt = info.event;
                    if (evt.extendedProps.type === 'absence') {
                        const realId = evt.extendedProps.real_id;
                        const f = document.getElementById('editAbsenceForm');
                        if (f) f.action = '/absences/' + realId;

                        const emp = document.getElementById('editAbsEmployee');
                        if (emp) emp.value = evt.extendedProps.employee_name || evt.title.replace(/^.*:\s*/, '');

                        const typ = document.getElementById('editAbsType');
                        if (typ) typ.value = evt.extendedProps.absence_type || 'Urlaub';

                        if (evt.start) {
                            const sy = evt.start.getFullYear();
                            const sm = String(evt.start.getMonth() + 1).padStart(2, '0');
                            const sd = String(evt.start.getDate()).padStart(2, '0');
                            const sEl = document.getElementById('edit_abs_start_date');
                            if (sEl) sEl.value = `${sy}-${sm}-${sd}`;
                        }
                        if (evt.end) {
                            const endD = new Date(evt.end);
                            if (endD.getHours() === 0 && endD.getMinutes() === 0 && endD > evt.start) {
                                endD.setDate(endD.getDate() - 1);
                            }
                            const ey = endD.getFullYear();
                            const em = String(endD.getMonth() + 1).padStart(2, '0');
                            const ed = String(endD.getDate()).padStart(2, '0');
                            const eEl = document.getElementById('edit_abs_end_date');
                            if (eEl) eEl.value = `${ey}-${em}-${ed}`;
                        }

                        const nEl = document.getElementById('editAbsNotes');
                        if (nEl) nEl.value = evt.extendedProps.notes || '';

                        const delBtn = document.getElementById('btnDeleteAbsence');
                        if (delBtn) {
                            delBtn.onclick = function() {
                                if (confirm('Möchtest du diese Abwesenheit wirklich löschen?')) {
                                    const dForm = document.createElement('form');
                                    dForm.method = 'POST';
                                    dForm.action = '/absences/' + realId;
                                    dForm.innerHTML = '<?php echo csrf_field(); ?> <?php echo method_field("DELETE"); ?>';
                                    document.body.appendChild(dForm);
                                    dForm.submit();
                                }
                            };
                        }

                        const m = document.getElementById('editAbsenceModal');
                        if (m) m.classList.remove('hidden');
                        return;
                    }

                    fetch('/appointments/' + (evt.extendedProps.real_id || evt.id))
                        .then(r => r.json())
                        .then(data => {
                            if (canEditAppointments) {
                                const editForm = document.getElementById('editAppointmentForm');
                                if (editForm) editForm.action = "/appointments/" + (data.id || data.appointment_id);
                                const delForm = document.getElementById('deleteAppointmentForm');
                                if (delForm) delForm.action = "/appointments/" + (data.id || data.appointment_id);

                                const srv = data.services || [];
                                const elAz = document.getElementById('editServiceAz');
                                if (elAz) elAz.checked = srv.includes('Az');
                                const elRd = document.getElementById('editServiceRd');
                                if (elRd) elRd.checked = srv.includes('Rd');
                                const elWu = document.getElementById('editServiceWu');
                                if (elWu) elWu.checked = srv.includes('Wu');
                                const elOtherChk = document.getElementById('editServiceOtherChk');
                                const hasOther = srv.includes('Sonstiges');
                                if (elOtherChk) {
                                    elOtherChk.checked = hasOther;
                                    const otherBox = document.getElementById('editOtherInput');
                                    if (otherBox) otherBox.classList.toggle('hidden', !hasOther);
                                }
                                const elOther = document.getElementById('editServiceOther');
                                if (elOther) elOther.value = data.service_other || '';
                            } else {
                                const readOnlyEl = document.getElementById('readOnlyServices');
                                if (readOnlyEl) readOnlyEl.textContent = (data.services || []).join(', ') + (data.service_other ? ' (' + data.service_other + ')' : '');
                            }

                            const locEl = document.getElementById('editLocation');
                            if (locEl) locEl.value = data.location || '';
                            const compEl = document.getElementById('editCompanyId');
                            if (compEl && data.company_id) compEl.value = data.company_id;
                            const clientEl = document.getElementById('editClientId');
                            if (clientEl && data.client_id) clientEl.value = data.client_id;
                            const userEl = document.getElementById('editUserId') || document.getElementById('editEmployeeId');
                            if (userEl) userEl.value = data.user_id || data.employee_id || '';
                            const noteEl = document.getElementById('editDescription') || document.getElementById('editNotes');
                            if (noteEl) noteEl.value = data.description || data.notes || '';

                            const fmtDate = d => d ? new Date(d).toISOString().slice(0, 16) : '';
                            const startEl = document.getElementById('editStartTime');
                            if (startEl && data.start_time) startEl.value = fmtDate(data.start_time);
                            const endEl = document.getElementById('editEndTime');
                            if (endEl && data.end_time) endEl.value = fmtDate(data.end_time);

                            const uploadForm = document.getElementById('detUploadForm');
                            if (uploadForm) uploadForm.action = '/appointments/' + data.id + '/upload';

                            const attachList = document.getElementById('detAttachments');
                            if (attachList) {
                                attachList.innerHTML = '';
                                if (data.attachments && data.attachments.length > 0) {
                                    data.attachments.forEach(att => {
                                        const li = document.createElement('li');
                                        li.className = 'flex justify-between items-center text-xs bg-gray-50 p-1.5 rounded border';
                                        const delBtnHtml = canEditAppointments 
                                            ? `<button type="button" onclick="deleteAttachmentItem(${att.id}, this)" class="text-red-600 font-semibold hover:underline text-xs" title="Anhang entfernen">Löschen</button>` 
                                            : '';
                                        li.innerHTML = `
                                            <span class="truncate pr-2">📄 ${att.original_filename} <span class="text-gray-400">(${Math.round(att.file_size / 1024)} KB)</span></span>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <a href="/attachments/${att.id}/download" class="text-blue-600 font-semibold hover:underline text-xs">Download</a>
                                                ${delBtnHtml}
                                            </div>
                                        `;
                                        attachList.appendChild(li);
                                    });
                                } else {
                                    attachList.innerHTML = '<li class="text-xs text-gray-400 italic">Keine Anhänge vorhanden.</li>';
                                }
                            }

                            window.currentAppointmentData = data;
                            const detModal = document.getElementById('detailModal');
                            if (detModal) detModal.classList.remove('hidden');
                            if (typeof updateDetailReportBar === 'function') updateDetailReportBar(data);
                        })
                        .catch(err => console.error("Fehler beim Laden des Dienstes:", err));
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

            const btnCl = document.getElementById("btnOpenClientInfo");
            if (btnCl) {
                btnCl.onclick = function() {
                    const sel = document.getElementById("editClientId");
                    if (sel && sel.value) openClientModal(sel.value);
                    else alert("Bitte wählen Sie zuerst einen Kunden / Auftraggeber aus.");
                };
            }

            // 1. Submit-Handler für Editieren von Abwesenheiten
            const eAf = document.getElementById("editAbsenceForm");
            if (eAf) {
                eAf.addEventListener('submit', function() {
                    const s = document.getElementById("edit_abs_start_date").value;
                    const e = document.getElementById("edit_abs_end_date").value;
                    if (s) document.getElementById("edit_abs_start_time").value = s + " 00:00:00";
                    if (e) document.getElementById("edit_abs_end_time").value = e + " 23:59:59";
                });
            }

            // 2. Submit-Handler für Erstellen von Abwesenheiten
            const createAbsForm = document.querySelector("#tabContentAbs form");
            if (createAbsForm) {
                createAbsForm.addEventListener('submit', function(evt) {
                    const s = document.getElementById("abs_start_date").value;
                    const e = document.getElementById("abs_end_date").value;
                    if (!s || !e) {
                        alert("Bitte Start- und Enddatum für die Abwesenheit auswählen!");
                        evt.preventDefault();
                        return;
                    }
                    document.getElementById("abs_start_time").value = s + " 00:00:00";
                    document.getElementById("abs_end_time").value = e + " 23:59:59";
                });
            }

            // 3. Mitarbeiterfilter im Schicht-Erstellen-Modal bei Firmenwechsel aktualisieren
            const newAppComp = document.getElementById("newAppointmentCompanySelect");
            const newAppEmp = document.getElementById("newAppointmentEmployeeSelect");
            if (newAppComp && newAppEmp) {
                const updateEmployees = () => {
                    const cId = newAppComp.value;
                    let foundValid = false;
                    Array.from(newAppEmp.options).forEach(opt => {
                        if (!opt.value) return;
                        const match = (!cId || opt.dataset.company === cId);
                        opt.hidden = !match;
                        if (match && !foundValid) {
                            newAppEmp.value = opt.value;
                            foundValid = true;
                        }
                    });
                };
                newAppComp.addEventListener("change", updateEmployees);
                updateEmployees();
            }

            // 4. Firmenfilter im Abwesenheits-Modal bei Firmenwechsel aktualisieren
            const absComp = document.getElementById("absenceCompanySelect");
            const absUser = document.getElementById("absenceUserSelect");
            if (absComp && absUser) {
                absComp.addEventListener("change", function() {
                    const cId = this.value;
                    Array.from(absUser.options).forEach(opt => {
                        if (!opt.value) return;
                        opt.hidden = (cId && opt.dataset.company !== cId);
                    });
                });
            }
        });

        function openCreateEntryModal(defaultTab = 'appointment') {
            const m = document.getElementById('createEntryModal');
            if (!m) return;
            switchCreateTab(defaultTab);
            m.classList.remove('hidden');
        }

        function closeCreateEntryModal() {
            const m = document.getElementById('createEntryModal');
            if (m) m.classList.add('hidden');
        }

        function switchCreateTab(tab) {
            const tabApp = document.getElementById('tabContentApp');
            const tabAbs = document.getElementById('tabContentAbs');
            const btnApp = document.getElementById('tabBtnApp');
            const btnAbs = document.getElementById('tabBtnAbs');

            if (tab === 'absence' && tabAbs) {
                if (tabApp) tabApp.classList.add('hidden');
                tabAbs.classList.remove('hidden');
                if (btnApp) btnApp.className = "px-3 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition";
                if (btnAbs) btnAbs.className = "px-3 py-1 text-xs font-bold rounded-lg bg-yellow-600 text-white transition";
            } else if (tabApp) {
                tabApp.classList.remove('hidden');
                if (tabAbs) tabAbs.classList.add('hidden');
                if (btnApp) btnApp.className = "px-3 py-1 text-xs font-bold rounded-lg bg-blue-600 text-white transition";
                if (btnAbs) btnAbs.className = "px-3 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition";
            }
        }

        function toggleAccountMenu(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            const menu = document.getElementById('accountDropdownMenu');
            if (menu) menu.classList.toggle('hidden');
        }

        window.addEventListener('click', function(e) {
            const container = document.getElementById('accountDropdownContainer');
            const menu = document.getElementById('accountDropdownMenu');
            if (menu && !menu.classList.contains('hidden')) {
                if (!container || !container.contains(e.target)) menu.classList.add('hidden');
            }
        });

        function toggleMobileFilterModal() {
            const m = document.getElementById('mobileFilterModal');
            if (m) m.classList.toggle('hidden');
        }

        function openClientModal(clientId) {
            if (!clientId) return;
            fetch('/clients/' + clientId)
                .then(r => r.json())
                .then(c => {
                    document.getElementById('clModalName').textContent = `🏢 ${c.name} [${c.short_code || '—'}]`;
                    const setPhone = (elId, phone) => {
                        const el = document.getElementById(elId);
                        if (phone) { el.textContent = phone; el.href = 'tel:' + phone.replace(/\s+/g, ''); el.classList.remove('text-gray-400'); }
                        else { el.textContent = '—'; el.removeAttribute('href'); el.classList.add('text-gray-400'); }
                    };
                    const setMail = (elId, mail, subject) => {
                        const el = document.getElementById(elId);
                        if (mail) { el.textContent = mail; el.href = `mailto:${mail}?subject=${encodeURIComponent(subject)}`; el.classList.remove('text-gray-400'); }
                        else { el.textContent = '—'; el.removeAttribute('href'); el.classList.add('text-gray-400'); }
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

        function isEventAnAbsence(e) {
            const p = e.extendedProps || {};
            if (p.type === 'absence' || p.absence_type) return true;
            if (e.title && (e.title.includes('Urlaub') || e.title.includes('Krank') || e.title.includes('Abwesend') || e.title.includes('Schule'))) return true;
            return false;
        }

        function refreshPendingCounterFromEvents(events) {
            if (!Array.isArray(events)) return;
            const now = new Date();
            const openEvents = events.filter(e => {
                if (isEventAnAbsence(e)) return false;
                const p = e.extendedProps || {};
                const eventUserId = Number(p.user_id || p.employee_id || e.user_id || 0);
                const isMine = (eventUserId === Number(currentUserId));
                const isPastOrNow = new Date(e.start) <= now;
                const isPending = (p.report_status !== 'completed' || p.report_status === 'change_requested');
                return isMine && isPending && isPastOrNow;
            });

            const btn = document.getElementById('btnOpenPendingReportsModal');
            const badge = document.getElementById('pendingReportsBadge');
            if (!btn) return;
            const changes = window.pendingChangeRequestsData || [];
            const total = openEvents.length + changes.length;

            if (total > 0) {
                btn.className = "h-8 min-w-[2.5rem] px-2.5 inline-flex items-center justify-center bg-amber-400 hover:bg-amber-300 text-slate-950 rounded-lg text-xs font-bold transition gap-1.5 border border-amber-500 shadow-sm cursor-pointer";
                btn.disabled = false;
                if (badge) {
                    badge.textContent = total > 99 ? '99+' : total;
                    badge.classList.remove('hidden');
                }
            } else {
                btn.className = "h-8 min-w-[2.5rem] px-2.5 inline-flex items-center justify-center bg-gray-100 text-gray-500 rounded-lg text-xs font-semibold cursor-default transition gap-1.5 opacity-70 border border-gray-200";
                btn.disabled = true;
                if (badge) badge.classList.add('hidden');
            }
        }

        function openPendingReportsModal() {
            const list = document.getElementById('pendingReportsList');
            const changes = window.pendingChangeRequestsData || [];
            const modal = document.getElementById('pendingReportsModal');
            if (modal) modal.classList.remove('hidden');
            if (!list) return;

            let changeHtml = '';
            if (changes.length > 0) {
                changeHtml = '<li class="mb-1 pt-1 font-bold text-gray-500 text-2xs uppercase">⚠️ Offene Korrekturanträge (' + changes.length + ')</li>' +
                    changes.map(item => `
                        <li class="p-2.5 bg-white border border-amber-200 rounded-lg shadow-sm flex items-center justify-between gap-2">
                            <div>
                                <div class="font-bold text-gray-800">#${item.appointment_id} - ${item.location}</div>
                                <div class="text-gray-500 text-2xs">${item.user_name} &bull; <span class="italic text-gray-700">${item.reason}</span></div>
                            </div>
                            <a href="/admin" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-xs shadow-sm inline-flex items-center gap-1 shrink-0">Admin-Panel &rarr;</a>
                        </li>
                    `).join('');
            }

            list.innerHTML = (changeHtml ? changeHtml + '<li id="myOpenShiftsHeader" class="mt-3 mb-1 border-t pt-2 font-bold text-gray-500 text-2xs uppercase">📋 Eigene offene Dienste</li>' : '') +
                             '<li id="shiftsLoadingIndicator" class="p-3 text-center text-gray-400">Lade offene Dienste...</li>';

            fetch('/calendar/events')
                .then(r => r.json())
                .then(events => {
                    const indicator = document.getElementById('shiftsLoadingIndicator');
                    if (indicator) indicator.remove();
                    const now = new Date();
                    const openEvents = events.filter(e => {
                        if (isEventAnAbsence(e)) return false;
                        const p = e.extendedProps || {};
                        const eventUserId = Number(p.user_id || p.employee_id || e.user_id || 0);
                        const isMine = (eventUserId === Number(currentUserId));
                        const isPastOrNow = new Date(e.start) <= now;
                        const isPending = (p.report_status !== 'completed' || p.report_status === 'change_requested');
                        return isMine && isPending && isPastOrNow;
                    });

                    const ownHdr = document.getElementById('myOpenShiftsHeader');
                    if (ownHdr) ownHdr.textContent = '📋 EIGENE OFFENE DIENSTE (' + openEvents.length + ')';

                    if (openEvents.length === 0) {
                        const emptyLi = document.createElement('li');
                        emptyLi.className = 'p-3 text-center text-gray-500 text-xs italic';
                        emptyLi.textContent = changeHtml ? 'Keine weiteren offenen Dienstmeldungen.' : 'Keine offenen Benachrichtigungen vorhanden.';
                        list.appendChild(emptyLi);
                        return;
                    }

                    openEvents.forEach(e => {
                        const p = e.extendedProps || {};
                        const targetId = p.real_id || e.id;
                        const li = document.createElement('li');
                        li.className = 'p-3 bg-white border border-gray-200 rounded-lg shadow-sm flex items-center justify-between gap-3';
                        li.innerHTML = `
                            <div>
                                <div class="font-bold text-gray-900 flex items-center gap-1.5"><span>📍</span> <span>${e.title || 'Dienst'}</span></div>
                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5"><span>🕒</span> <span>${new Date(e.start).toLocaleDateString('de-DE', {day: '2-digit', month: '2-digit'})}, ${new Date(e.start).toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'})} - ${new Date(e.end).toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'})}</span></div>
                                <span class="inline-block mt-1 px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-semibold text-2xs">⚠️ Meldung ausstehend</span>
                            </div>
                            <button type="button" onclick="loadAndOpenShift('${targetId}')" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-xs shadow-sm">Öffnen →</button>
                        `;
                        list.appendChild(li);
                    });
                });
        }

        function loadAndOpenShift(rawId) {
            const modal = document.getElementById('pendingReportsModal');
            if (modal) modal.classList.add('hidden');
            const cleanId = String(rawId).replace('apt_', '').trim();

            fetch('/calendar/events')
                .then(r => r.json())
                .then(events => {
                    const ev = events.find(e => {
                        const p = e.extendedProps || {};
                        return String(p.real_id) === cleanId || String(e.id) === String(rawId) || String(e.id).replace('apt_', '') === cleanId;
                    });
                    if (ev) {
                        const p = ev.extendedProps || {};
                        openShiftReportModal({
                            id: p.real_id || cleanId,
                            location: p.location || ev.title,
                            user_id: p.user_id || p.employee_id,
                            actual_start: p.actual_start || null,
                            actual_end: p.actual_end || null,
                            travel_out_start: p.travel_out_start || null,
                            travel_out_end: p.travel_out_end || null,
                            travel_back_start: p.travel_back_start || null,
                            travel_back_end: p.travel_back_end || null,
                            pause_minutes: p.pause_minutes ?? 0,
                            report_notes: p.report_notes || '',
                            report_status: p.report_status || 'pending'
                        });
                    }
                });
        }

        function updateDetailReportBar(data) {
            const bar = document.getElementById('detailReportBar');
            const txt = document.getElementById('detailReportStatusText');
            const ico = document.getElementById('detailReportStatusIcon');
            const btn = document.getElementById('btnOpenShiftReport');
            if (!bar || !txt || !ico || !btn) return;

            const isOwner = (Number(data.user_id) === Number(currentUserId));
            const canEdit = Boolean(canEditAppointments);
            if (!isOwner && !canEdit) { bar.classList.add('hidden'); return; }
            bar.classList.remove('hidden');

            const status = data.report_status || 'pending';
            if (status === 'completed') {
                ico.textContent = '🟢';
                txt.textContent = 'Dienstmeldung abgeschlossen' + (data.reported_at ? ' (' + new Date(data.reported_at).toLocaleString('de-DE') + ')' : '');
                bar.className = 'p-2.5 rounded-lg border border-green-200 bg-green-50 text-xs flex items-center justify-between mt-2';
                btn.textContent = (isOwner && !canEdit) ? 'Korrektur beantragen' : '📝 Dienstmeldung';
            } else if (status === 'change_requested') {
                ico.textContent = '🟠';
                txt.textContent = 'Korrektur beantragt (in Prüfung)';
                bar.className = 'p-2.5 rounded-lg border border-amber-200 bg-amber-50 text-xs flex items-center justify-between mt-2';
                btn.textContent = '📝 In Prüfung';
            } else {
                ico.textContent = '🟡';
                txt.textContent = 'Dienstmeldung ausstehend';
                bar.className = 'p-2.5 rounded-lg border border-yellow-200 bg-yellow-50 text-xs flex items-center justify-between mt-2';
                btn.textContent = '📝 Dienstmeldung erfassen';
            }

            btn.onclick = function() { openShiftReportModal(data); };
        }

        function openShiftReportModal(data) {
            const fmt = d => d ? new Date(d).toISOString().slice(0, 16) : '';
            const form = document.getElementById('shiftReportForm');
            form.action = '/appointments/' + data.id + '/shift-report';

            document.getElementById('repModalSubtitle').textContent = `Dienst #${data.id} - ${data.location || 'Ohne Ort'}`;
            document.getElementById('repActualStart').value = fmt(data.actual_start || '');
            document.getElementById('repActualEnd').value = fmt(data.actual_end || '');
            document.getElementById('repTravelOutStart').value = fmt(data.travel_out_start || '');
            document.getElementById('repTravelOutEnd').value = fmt(data.travel_out_end || '');
            document.getElementById('repTravelBackStart').value = fmt(data.travel_back_start || '');
            document.getElementById('repTravelBackEnd').value = fmt(data.travel_back_end || '');
            document.getElementById('repPauseMinutes').value = (data.pause_minutes !== null && data.pause_minutes !== undefined) ? data.pause_minutes : '';
            document.getElementById('repNotes').value = data.report_notes || '';

            const isOwner = (Number(data.user_id) === Number(currentUserId));
            const canEdit = Boolean(canEditAppointments);
            const status = data.report_status || 'pending';

            const box = document.getElementById('repStatusBox');
            const sText = document.getElementById('repStatusText');
            const sBadge = document.getElementById('repStatusBadge');
            const btnSave = document.getElementById('btnRepSave');
            const btnComplete = document.getElementById('btnRepComplete');
            const btnReqChange = document.getElementById('btnRepRequestChange');
            const dispoActions = document.getElementById('repDispoActions');
            dispoActions.innerHTML = '';

            const allInputs = form.querySelectorAll('input:not([type="hidden"])');
            const isPastOrCurrent = new Date(data.start_time || data.actual_start || Date.now()) <= new Date();

            if (status === 'change_requested') {
                box.className = 'p-2.5 rounded-lg text-xs font-semibold flex items-center justify-between bg-amber-50 border border-amber-200 text-amber-900';
                sText.textContent = 'Korrekturantrag eingereicht. Warten auf Prüfung durch die Disposition.';
                sBadge.className = 'px-2 py-0.5 rounded text-2xs font-bold uppercase bg-amber-200 text-amber-800';
                sBadge.textContent = 'In Prüfung';

                if (isOwner && !canEdit) {
                    allInputs.forEach(i => i.setAttribute('readonly', 'readonly'));
                    btnSave.classList.add('hidden');
                    btnComplete.classList.add('hidden');
                    btnReqChange.classList.add('hidden');
                } else {
                    allInputs.forEach(i => i.removeAttribute('readonly'));
                    btnSave.classList.remove('hidden');
                    btnComplete.classList.add('hidden');
                    btnReqChange.classList.add('hidden');
                    dispoActions.innerHTML = `
                        <button type="button" onclick="submitReport('approve_change')" class="px-2.5 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-bold mr-1">✓ Änderung genehmigen</button>
                        <button type="button" onclick="submitReport('reject_change')" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold">✕ Ablehnen</button>
                    `;
                }
            } else if (status === 'completed') {
                box.className = 'p-2.5 rounded-lg text-xs font-semibold flex items-center justify-between bg-green-50 border border-green-200 text-green-900';
                sText.textContent = 'Dienstmeldung ist abgeschlossen.';
                sBadge.className = 'px-2 py-0.5 rounded text-2xs font-bold uppercase bg-green-200 text-green-800';
                sBadge.textContent = 'Abgeschlossen';

                if (isOwner && !canEdit) {
                    allInputs.forEach(i => i.removeAttribute('readonly'));
                    btnSave.classList.add('hidden');
                    btnComplete.classList.add('hidden');
                    btnReqChange.classList.remove('hidden');
                } else {
                    allInputs.forEach(i => i.removeAttribute('readonly'));
                    btnSave.classList.remove('hidden');
                    btnComplete.classList.add('hidden');
                    btnReqChange.classList.add('hidden');
                    dispoActions.innerHTML = `
                        <button type="button" onclick="submitReport('reset')" class="px-2.5 py-1.5 bg-yellow-100 hover:bg-yellow-200 text-yellow-800 rounded text-xs font-semibold border border-yellow-300">🔓 Zurücksetzen / Entsperren</button>
                    `;
                }
            } else {
                box.className = 'p-2.5 rounded-lg text-xs font-semibold flex items-center justify-between bg-yellow-50 border border-yellow-200 text-yellow-900';
                sText.textContent = isPastOrCurrent ? 'Dienstmeldung ist noch nicht abgeschlossen.' : 'Dienst liegt in der Zukunft.';
                sBadge.className = 'px-2 py-0.5 rounded text-2xs font-bold uppercase bg-yellow-200 text-yellow-800';
                sBadge.textContent = 'Offen';

                allInputs.forEach(i => i.removeAttribute('readonly'));
                btnSave.classList.remove('hidden');
                btnReqChange.classList.add('hidden');
                if (isPastOrCurrent || canEdit) {
                    btnComplete.classList.remove('hidden');
                } else {
                    btnComplete.classList.add('hidden');
                }
            }

            document.getElementById('shiftReportModal').classList.remove('hidden');
        }

        function submitReport(action) {
            if (['save', 'complete', 'request_change'].includes(action)) {
                const sVal = document.getElementById('repActualStart').value;
                const eVal = document.getElementById('repActualEnd').value;
                if (sVal && eVal && new Date(eVal) < new Date(sVal)) {
                    alert('Fehler: Das tatsächliche Schichtende darf nicht vor dem Schichtbeginn liegen!');
                    return;
                }
            }
            document.getElementById('reportActionType').value = action;
            document.getElementById('shiftReportForm').submit();
        }

        function deleteAttachmentItem(attId, btnElement) {
            if (!confirm('Möchtest du diesen Anhang wirklich endgültig löschen?')) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?php echo e(csrf_token()); ?>';
            fetch('/attachments/' + attId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const li = btnElement.closest('li');
                    if (li) li.remove();
                } else {
                    alert('Fehler beim Löschen des Anhangs.');
                }
            });
        }
    </script>
</body>
</html><?php /**PATH /var/www/vhosts/hosting233863.ae9ed.netcup.net/ucp/resources/views/appointments/calendar.blade.php ENDPATH**/ ?>