<!DOCTYPE html>
<html lang="de">
<head>
    <link rel="icon" href="<?php echo e(asset("favicon.svg")); ?>" type="image/svg+xml">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Panel</title>
    <link rel="stylesheet" href="<?php echo e(asset('vendor/tailwind/tailwind.min.css')); ?>">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800">

    <nav class="bg-white border-b border-gray-200 px-4 py-2.5 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-bold flex items-center gap-1.5">
                    <span>⚙️</span> <span>Admin Panel</span>
                </h1>
                <a href="<?php echo e(route('calendar.index')); ?>" class="text-blue-600 hover:underline text-xs font-semibold">← Zurück zum Kalender</a>
            </div>
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-2.5 py-1 rounded text-xs">Abmelden</button>
            </form>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-6">
        <?php echo $__env->make('admin.pending_reports', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php if(session('success')): ?>
            <div class="p-3 bg-green-100 border border-green-300 text-green-800 rounded text-xs font-semibold">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="p-3 bg-red-100 border border-red-300 text-red-800 rounded text-xs font-semibold">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="p-3 bg-red-100 border border-red-300 text-red-800 rounded text-xs">
                <span class="font-bold block mb-1">Bitte prüfe folgende Eingaben:</span>
                <ul class="list-disc list-inside">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- 1. FIRMEN VERWALTUNG -->
        <?php if(auth()->user()->canDo('manage_companies')): ?>
            <div class="bg-white p-4 rounded-xl shadow border border-gray-200 mb-6">
                <h2 class="text-sm font-bold uppercase text-gray-700 mb-3">🏢 Unternehmen</h2>
                <form action="<?php echo e(route('admin.companies.store')); ?>" method="POST" class="space-y-2 mb-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <?php echo csrf_field(); ?>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" name="name" required placeholder="Unternehmenname" class="border rounded p-1.5 text-xs bg-white w-48">
                        <input type="text" name="short_code" required placeholder="Kürzel" maxlength="10" class="border rounded p-1.5 text-xs uppercase bg-white w-24">
                        
                        <div class="flex items-center gap-3 text-xs text-gray-700 bg-white border border-gray-300 rounded px-2.5 py-1.5">
                            <span class="font-semibold text-2xs uppercase text-gray-400 mr-0.5">ANZEIGE:</span>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="name_display_format" value="short" class="text-blue-600">
                                <span>Nur Kürzel</span>
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="name_display_format" value="both" checked class="text-blue-600">
                                <span>[Kürzel] Name</span>
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="name_display_format" value="name" class="text-blue-600">
                                <span>Nur Name</span>
                            </label>
                        </div>

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold px-3 py-1.5 shadow-sm">+ Unternehmen anlegen</button>
                    </div>
                </form>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                    <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="p-2.5 bg-gray-50 border rounded flex justify-between items-center text-xs">
                            <div>
                                <span class="font-bold"><?php echo e($comp->display_name); ?></span>
                                <span class="text-gray-500 block text-2xs"><?php echo e($comp->users_count); ?> Mitarbeiter</span>
                            </div>
                            <div class="flex gap-2 items-center">
                                <button onclick="editCompany(<?php echo e($comp->id); ?>, '<?php echo e(addslashes($comp->name)); ?>', '<?php echo e($comp->short_code); ?>', '<?php echo e($comp->name_display_format ?? 'both'); ?>')" class="text-blue-600 hover:underline">Bearbeiten</button>
                                <form action="<?php echo e(route('admin.companies.destroy', $comp)); ?>" method="POST" onsubmit="return confirm('Unternehmen löschen?');">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2. KUNDEN VERWALTUNG -->
        <?php if(auth()->user()->canDo('manage_clients')): ?>
            <div class="bg-white p-4 rounded-xl shadow border border-gray-200 mb-6">
                <h2 class="text-sm font-bold uppercase text-gray-700 mb-3">👥 Kundenverwaltung</h2>
                <form action="<?php echo e(route('admin.clients.store')); ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 mb-4 bg-gray-50 p-3 rounded border">
                    <?php echo csrf_field(); ?>
                    <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Kundenname</label><input type="text" name="name" required placeholder="z. B. DB Cargo" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                    
                    <!-- Kürzel mit Checkboxen daneben mittig platziert -->
                    <div class="lg:col-span-2 flex flex-col items-center">
                        <div>
                            <label class="block text-2xs font-semibold text-gray-600 mb-0.5">Kürzel & Namenanzeige</label>
                            <div class="flex flex-wrap items-center gap-2">
                            <input type="text" name="short_code" required placeholder="z. B. DBC" class="w-24 border rounded p-1.5 text-xs uppercase bg-white">
                            <div class="flex items-center gap-2.5 text-xs text-gray-700 bg-white border border-gray-300 rounded px-2.5 py-1.5 w-fit">
                                <span class="font-semibold text-2xs uppercase text-gray-400 mr-0.5">ANZEIGE:</span>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="name_display_format" value="short" class="text-blue-600">
                                    <span>Nur Kürzel</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="name_display_format" value="both" checked class="text-blue-600">
                                    <span>[Kürzel] Name</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="name_display_format" value="name" class="text-blue-600">
                                    <span>Nur Name</span>
                                </label>
                            </div>
                        </div>
                        </div>
                    </div>

                    <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Farbe</label><input type="color" name="color" value="#3b82f6" class="w-full h-8 border rounded p-0.5 bg-white cursor-pointer"></div>
                    <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Telefon Dispo</label><input type="text" name="phone_dispo" placeholder="0800 111111" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                    <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Telefon 24h / Ausweich</label><input type="text" name="phone_backup" placeholder="0800 222222" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                    <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">E-Mail Dispo</label><input type="email" name="email_dispo" placeholder="dispo@kunde.de" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                    <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">E-Mail Wagenliste</label><input type="email" name="email_wagenliste" placeholder="wagenliste@kunde.de" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                    <div class="sm:col-span-2"><label class="block text-2xs font-semibold text-gray-600 mb-0.5">E-Mail Schadwagen</label><input type="email" name="email_schadwagen" placeholder="schadwagen@kunde.de" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                    <div class="sm:col-span-2 lg:col-span-2"><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Notizen</label><input type="text" name="notes" placeholder="Notizen" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                    <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                        <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white rounded text-xs font-semibold px-4 py-2 hover:bg-blue-700 shadow-sm">+ Kunde anlegen</button>
                    </div>
                </form>

                <!-- Mobile Karten: Kunden -->
                <div class="block md:hidden space-y-2.5 mt-3">
                    <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="inline-block w-3.5 h-3.5 rounded-full border flex-shrink-0" style="background-color: <?php echo e($c->color); ?>;"></span>
                                    <span class="font-bold text-gray-900 truncate"><?php echo e($c->name); ?></span>
                                </div>
                                <span class="bg-white border px-1.5 py-0.5 rounded font-mono font-bold text-2xs flex-shrink-0"><?php echo e($c->short_code); ?></span>
                            </div>

                            <div class="space-y-1 text-2xs text-gray-600 pt-1 border-t border-gray-200">
                                <div>📞 <span class="font-medium text-gray-800"><?php echo e($c->phone_dispo ?: '—'); ?></span> <?php if($c->phone_backup): ?><span class="text-gray-400">(<?php echo e($c->phone_backup); ?>)</span><?php endif; ?></div>
                                <div>Dispo: <span class="text-blue-600"><?php echo e($c->email_dispo ?: '—'); ?></span></div>
                                <div>Wagen: <span class="text-gray-700"><?php echo e($c->email_wagenliste ?: '—'); ?></span></div>
                                <div>Schaden: <span class="text-red-600"><?php echo e($c->email_schadwagen ?: '—'); ?></span></div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-200 text-xs">
                                <button onclick="editClient(<?php echo e(json_encode($c)); ?>)" class="text-blue-600 font-semibold hover:underline">Bearbeiten</button>
                                <form action="<?php echo e(route('admin.clients.destroy', $c)); ?>" method="POST" class="inline" onsubmit="return confirm('Kunde löschen?');">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="text-red-600 font-semibold hover:underline">Löschen</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-center py-4 text-xs text-gray-400">Keine Kunden hinterlegt.</div>
                    <?php endif; ?>
                </div>

                <!-- Desktop Tabelle: Kunden (immer nur Name in Spalte 'Kunde') -->
                <div class="hidden md:block mt-6 border-t border-gray-100 pt-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="p-2">Kunde</th>
                                    <th class="p-2">Kürzel</th>
                                    <th class="p-2">Farbe</th>
                                    <th class="p-2">Telefon</th>
                                    <th class="p-2">E-Mails</th>
                                    <th class="p-2 text-right">Aktion</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="p-2 font-bold"><?php echo e($c->name); ?></td>
                                        <td class="p-2"><span class="bg-gray-100 px-1.5 py-0.5 rounded font-mono font-bold"><?php echo e($c->short_code); ?></span></td>
                                        <td class="p-2"><span class="inline-block w-4 h-4 rounded-full border" style="background-color: <?php echo e($c->color); ?>;"></span></td>
                                        <td class="p-2">
                                            <div><?php echo e($c->phone_dispo ?: '—'); ?></div>
                                            <div class="text-2xs text-gray-400"><?php echo e($c->phone_backup); ?></div>
                                        </td>
                                        <td class="p-2 text-2xs space-y-0.5">
                                            <div>Dispo: <span class="text-blue-600"><?php echo e($c->email_dispo ?: '—'); ?></span></div>
                                            <div>Wagen: <span class="text-gray-600"><?php echo e($c->email_wagenliste ?: '—'); ?></span></div>
                                            <div>Schaden: <span class="text-red-600"><?php echo e($c->email_schadwagen ?: '—'); ?></span></div>
                                        </td>
                                        <td class="p-2 text-right space-x-2">
                                            <button onclick="editClient(<?php echo e(json_encode($c)); ?>)" class="text-blue-600 hover:underline">Bearbeiten</button>
                                            <form action="<?php echo e(route('admin.clients.destroy', $c)); ?>" method="POST" class="inline" onsubmit="return confirm('Kunde löschen?');">
                                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 3. ROLLEN VERWALTUNG -->
        <?php if(auth()->user()->canDo('manage_roles')): ?>
            <div class="bg-white p-4 rounded-xl shadow border border-gray-200 mb-6">
                <h2 class="text-sm font-bold uppercase text-gray-700 mb-3">🛡️ Rollen & Rechte</h2>
                <form action="<?php echo e(route('admin.roles.store')); ?>" method="POST" class="space-y-3 mb-4 max-w-2xl bg-gray-50 p-3 rounded border">
                    <?php echo csrf_field(); ?>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="name" required placeholder="Systemname (z. B. disponent)" class="border rounded p-1.5 text-xs">
                        <input type="text" name="label" required placeholder="Anzeigename (z. B. Disponent)" class="border rounded p-1.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-2xs font-bold text-gray-600 uppercase mb-1">Berechtigungen dieser Rolle:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="permissions[]" value="access_admin_panel" class="rounded"> ⚙️ Zugriff Admin-Panel</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="permissions[]" value="manage_companies" class="rounded"> 🏢 Unternehmen verwalten</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="permissions[]" value="manage_users" class="rounded"> 👤 Benutzer verwalten</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="permissions[]" value="manage_clients" class="rounded"> 🚆 Kunden verwalten</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="permissions[]" value="manage_roles" class="rounded"> 🛡️ Rollen verwalten</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="permissions[]" value="manage_files" class="rounded"> 📁 Dateien verwalten</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="permissions[]" value="view_other_appointments" class="rounded"> 👥 Dienst Anderer</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="permissions[]" value="edit_appointments" class="rounded"> ✍️ Dienste erstellen</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="permissions[]" value="manage_absences" class="rounded"> ⛔ Urlaub/Krank eintragen</label>
                        </div>
                    </div>
                    <button type="submit" class="bg-purple-600 text-white rounded text-xs font-semibold px-3 py-1.5 hover:bg-purple-700">+ Rolle speichern</button>
                </form>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="p-2.5 bg-gray-50 border rounded text-xs">
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-bold"><?php echo e($r->label); ?></span>
                                <div class="flex gap-2">
                                    <button onclick="editRole(<?php echo e(json_encode($r)); ?>)" class="text-blue-600 hover:underline text-2xs">Bearbeiten</button>
                                    <?php if(!in_array($r->name, ['admin', 'employee'])): ?>
                                        <form action="<?php echo e(route('admin.roles.destroy', $r)); ?>" method="POST" onsubmit="return confirm('Rolle löschen?');">
                                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="text-red-600 hover:underline text-2xs">Löschen</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-2xs text-gray-500">Mitarbeiter mit dieser Rolle: <?php echo e($r->users_count); ?></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 4. BENUTZER VERWALTUNG -->
        <?php if(auth()->user()->canDo('manage_users')): ?>
            <div class="bg-white p-4 rounded-xl shadow border border-gray-200 mb-6">
                <h2 class="text-sm font-bold uppercase text-gray-700 mb-3">👤 Benutzerverwaltung</h2>
                <form action="<?php echo e(route('admin.users.store')); ?>" method="POST" class="space-y-3 mb-4 bg-gray-50 p-3 rounded border">
                    <?php echo csrf_field(); ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                        <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Name *</label><input type="text" name="name" required placeholder="Vorname Nachname" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                        <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Kürzel *</label><input type="text" name="short_code" required placeholder="z. B. DW" class="w-full border rounded p-1.5 text-xs uppercase bg-white"></div>
                        <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">E-Mail *</label><input type="email" name="email" required placeholder="name@firma.de" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                        <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Telefon</label><input type="text" name="phone" placeholder="0171 / 1234567" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                        <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Passwort *</label><input type="password" name="password" required placeholder="Min. 8 Zeichen" class="w-full border rounded p-1.5 text-xs bg-white"></div>
                        <div><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Stamm-Unternehmen *</label><select name="company_id" required class="w-full border rounded p-1.5 text-xs bg-white"><option value="" disabled selected>Unternehmen wählen...</option><?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($comp->id); ?>"><?php echo e($comp->display_name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                        <div class="sm:col-span-2"><label class="block text-2xs font-semibold text-gray-600 mb-0.5">Rolle *</label><select name="role" required class="w-full border rounded p-1.5 text-xs bg-white"><option value="" disabled selected>Rolle wählen...</option><?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($r->name); ?>"><?php echo e($r->label ?? $r->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="w-full sm:w-auto bg-green-600 text-white rounded text-xs font-semibold px-4 py-2 hover:bg-green-700 shadow-sm">+ Benutzer anlegen</button>
                    </div>
                </form>

                <!-- Mobile Karten: Benutzer -->
                <div class="block md:hidden space-y-2.5 mt-3">
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="bg-white border font-mono font-bold px-1.5 py-0.5 rounded text-2xs"><?php echo e($u->short_code ?: '—'); ?></span>
                                    <span class="font-bold text-gray-900 truncate"><?php echo e($u->name); ?></span>
                                </div>
                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-2xs font-semibold flex-shrink-0">
                                    <?php echo e($u->roleModel->label ?? $u->role); ?>

                                </span>
                            </div>

                            <div class="space-y-1 text-2xs text-gray-600 pt-1 border-t border-gray-200">
                                <div>📞 <span class="text-gray-800"><?php echo e($u->phone ?: '—'); ?></span></div>
                                <div>✉️ <span class="text-gray-800"><?php echo e($u->email); ?></span></div>
                                <div>Stamm: <span class="font-bold text-blue-800"><?php echo e($u->company->short_code ?? '—'); ?></span></div>
                                <div>Verwaltbar: 
                                    <?php
                                        $managed = $u->managedCompanies->pluck('short_code')->toArray();
                                        echo !empty($managed) ? '<span class="font-medium text-gray-800">' . implode(', ', $managed) . '</span>' : '<span class="text-gray-400 italic">Nur Stamm</span>';
                                    ?>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-200 text-xs">
                                <button onclick="editUser(<?php echo e(json_encode($u)); ?>, <?php echo e(json_encode($u->managedCompanies->pluck('id'))); ?>)" class="text-blue-600 font-semibold hover:underline">Bearbeiten</button>
                                <?php if($u->id !== auth()->id()): ?>
                                    <form action="<?php echo e(route('admin.users.destroy', $u)); ?>" method="POST" class="inline" onsubmit="return confirm('Benutzer löschen?');">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-red-600 font-semibold hover:underline">Löschen</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-center py-4 text-xs text-gray-400">Keine Benutzer hinterlegt.</div>
                    <?php endif; ?>
                </div>

                <!-- Desktop Tabelle: Benutzer -->
                <div class="hidden md:block mt-6 border-t border-gray-100 pt-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="p-2">Kürzel</th>
                                    <th class="p-2">Name</th>
                                    <th class="p-2">Telefon & E-Mail</th>
                                    <th class="p-2">Stamm</th>
                                    <th class="p-2">Verwaltbare Unternehmen</th>
                                    <th class="p-2">Rolle</th>
                                    <th class="p-2 text-right">Aktion</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="p-2"><span class="bg-gray-100 font-mono font-bold px-1.5 py-0.5 rounded"><?php echo e($u->short_code ?: '—'); ?></span></td>
                                        <td class="p-2 font-bold"><?php echo e($u->name); ?></td>
                                        <td class="p-2 text-2xs">
                                            <div>📞 <?php echo e($u->phone ?: '—'); ?></div>
                                            <div class="text-gray-500">✉️ <?php echo e($u->email); ?></div>
                                        </td>
                                        <td class="p-2 font-bold text-blue-800"><?php echo e($u->company->short_code ?? '—'); ?></td>
                                        <td class="p-2">
                                            <?php
                                                $managed = $u->managedCompanies->pluck('short_code')->toArray();
                                                echo !empty($managed) ? implode(', ', $managed) : '<span class="text-gray-400 italic">Nur Stamm</span>';
                                            ?>
                                        </td>
                                        <td class="p-2"><span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-2xs font-semibold"><?php echo e($u->roleModel->label ?? $u->role); ?></span></td>
                                        <td class="p-2 text-right space-x-2">
                                            <button onclick="editUser(<?php echo e(json_encode($u)); ?>, <?php echo e(json_encode($u->managedCompanies->pluck('id'))); ?>)" class="text-blue-600 hover:underline">Bearbeiten</button>
                                            <?php if($u->id !== auth()->id()): ?>
                                                <form action="<?php echo e(route('admin.users.destroy', $u)); ?>" method="POST" class="inline" onsubmit="return confirm('Benutzer löschen?');">
                                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- MODAL: Unternehmen bearbeiten -->
    <div id="modalEditCompany" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl p-5 max-w-lg w-full">
            <h3 class="font-bold text-sm mb-3">Unternehmen bearbeiten</h3>
            <form id="formEditCompany" method="POST" class="space-y-3">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <div>
                    <label class="block text-2xs font-semibold text-gray-600 mb-0.5">Unternehmenname</label>
                    <input type="text" name="name" id="editCompName" required class="w-full border rounded p-1.5 text-xs">
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <div class="w-28 shrink-0">
                        <label class="block text-2xs font-semibold text-gray-600 mb-0.5">Kürzel</label>
                        <input type="text" name="short_code" id="editCompCode" required maxlength="10" placeholder="Kürzel" class="w-full border rounded p-1.5 text-xs uppercase">
                    </div>
                    <div class="flex-grow">
                        <label class="block text-2xs font-bold text-gray-500 uppercase mb-0.5">NAMENANZEIGE</label>
                        <div class="flex flex-wrap items-center gap-2.5 bg-gray-50 p-1.5 rounded border border-gray-200 text-xs">
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="name_display_format" value="short" id="editFormatShort" class="text-blue-600">
                                <span>Nur Kürzel</span>
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="name_display_format" value="both" id="editFormatBoth" class="text-blue-600">
                                <span>[Kürzel] Name</span>
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="name_display_format" value="name" id="editFormatName" class="text-blue-600">
                                <span>Nur Name</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t">
                    <button type="button" onclick="document.getElementById('modalEditCompany').classList.add('hidden')" class="px-3 py-1 bg-gray-100 rounded text-xs">Abbrechen</button>
                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-xs font-semibold">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Kunde bearbeiten -->
    <div id="modalEditClient" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-3 overflow-y-auto">
        <div class="bg-white rounded-xl p-5 max-w-lg w-full">
            <h3 class="font-bold text-sm mb-3">Kunde bearbeiten</h3>
            <form id="formEditClient" method="POST" class="grid grid-cols-2 gap-2 text-xs">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <div class="col-span-2">
                    <label class="block font-semibold mb-0.5">Name</label>
                    <input type="text" name="name" id="editClName" required placeholder="z.B. DB Cargo" class="w-full border rounded p-1.5">
                </div>
                
                <!-- Kürzel und Namenanzeige nebeneinander im Edit-Modal -->
                <div class="col-span-2">
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="w-24 shrink-0">
                            <label class="block font-semibold mb-0.5">Kürzel</label>
                            <input type="text" name="short_code" id="editClCode" required maxlength="8" class="w-full border rounded p-1.5 uppercase">
                        </div>
                        <div class="flex-grow">
                            <label class="block font-bold text-gray-500 uppercase mb-0.5 text-2xs">NAMENANZEIGE</label>
                            <div class="flex flex-wrap items-center gap-2.5 bg-gray-50 p-1.5 rounded border border-gray-200 text-xs">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="name_display_format" value="short" id="editClFormatShort" class="text-blue-600">
                                    <span>Nur Kürzel</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="name_display_format" value="both" id="editClFormatBoth" class="text-blue-600">
                                    <span>[Kürzel] Name</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="name_display_format" value="name" id="editClFormatName" class="text-blue-600">
                                    <span>Nur Name</span>
                                </label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold mb-0.5">Farbe</label>
                    <input type="color" name="color" id="editClColor" class="w-full h-8 p-0 border rounded">
                </div>
                <div>
                    <label class="block font-semibold mb-0.5">Telefon Dispo</label>
                    <input type="text" name="phone_dispo" id="editClPhoneDispo" class="w-full border rounded p-1.5">
                </div>
                <div>
                    <label class="block font-semibold mb-0.5">Telefon 24h</label>
                    <input type="text" name="phone_backup" id="editClPhoneBackup" class="w-full border rounded p-1.5">
                </div>
                <div>
                    <label class="block font-semibold mb-0.5">E-Mail Dispo</label>
                    <input type="email" name="email_dispo" id="editClMailDispo" class="w-full border rounded p-1.5">
                </div>
                <div>
                    <label class="block font-semibold mb-0.5">E-Mail Wagenliste</label>
                    <input type="email" name="email_wagenliste" id="editClMailWagen" class="w-full border rounded p-1.5">
                </div>
                <div class="col-span-2">
                    <label class="block font-semibold mb-0.5">E-Mail Schadwagen</label>
                    <input type="email" name="email_schadwagen" id="editClMailSchad" class="w-full border rounded p-1.5">
                </div>
                <div class="col-span-2">
                    <label class="block font-semibold mb-0.5">Notizen</label>
                    <input type="text" name="notes" id="editClNotes" placeholder="Notizen" class="w-full border rounded p-1.5">
                </div>
                <div class="col-span-2 flex justify-end gap-2 pt-2 border-t mt-2">
                    <button type="button" onclick="document.getElementById('modalEditClient').classList.add('hidden')" class="px-3 py-1 bg-gray-100 rounded">Abbrechen</button>
                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded font-semibold">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Rolle bearbeiten -->
    <div id="modalEditRole" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-3">
        <div class="bg-white rounded-xl p-5 max-w-md w-full">
            <h3 class="font-bold text-sm mb-3">Rolle bearbeiten</h3>
            <form id="formEditRole" method="POST" class="space-y-3 text-xs">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <div>
                    <label class="block font-semibold mb-0.5">Anzeigename</label>
                    <input type="text" name="label" id="editRoleLabel" required class="w-full border rounded p-1.5">
                </div>
                <div>
                    <label class="block font-bold uppercase mb-1">Berechtigungen:</label>
                    <div class="grid grid-cols-2 gap-1.5">
                        <label class="flex items-center gap-1"><input type="checkbox" name="permissions[]" value="access_admin_panel" id="perm_admin_panel" class="rounded"> ⚙️ Admin-Panel</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="permissions[]" value="manage_companies" id="perm_comp" class="rounded"> 🏢 Unternehmen</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="permissions[]" value="manage_users" id="perm_user" class="rounded"> 👤 Benutzer</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="permissions[]" value="manage_clients" id="perm_client" class="rounded"> 🚆 Kunden</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="permissions[]" value="manage_roles" id="perm_role" class="rounded"> 🛡️ Rollen</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="permissions[]" value="manage_files" id="perm_files" class="rounded"> 📁 Dateien</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="permissions[]" value="view_other_appointments" id="perm_other_apt" class="rounded"> 👥 Dienst Anderer</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="permissions[]" value="edit_appointments" id="perm_apt" class="rounded"> ✍️ Dienste erstellen</label>
                        <label class="flex items-center gap-1"><input type="checkbox" name="permissions[]" value="manage_absences" id="perm_abs" class="rounded"> ⛔ Urlaub/Krank</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t">
                    <button type="button" onclick="document.getElementById('modalEditRole').classList.add('hidden')" class="px-3 py-1 bg-gray-100 rounded">Abbrechen</button>
                    <button type="submit" class="px-3 py-1 bg-purple-600 text-white rounded font-semibold">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Benutzer bearbeiten -->
    <div id="modalEditUser" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-3 overflow-y-auto">
        <div class="bg-white rounded-xl p-5 max-w-lg w-full">
            <h3 class="font-bold text-sm mb-3">Benutzer bearbeiten</h3>
            <form id="formEditUser" method="POST" class="space-y-3 text-xs">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold mb-0.5">Name</label>
                        <input type="text" name="name" id="editUserName" required class="w-full border rounded p-1.5">
                    </div>
                    <div>
                        <label class="block font-semibold mb-0.5">Mitarbeiterkürzel</label>
                        <input type="text" name="short_code" id="editUserShortCode" required maxlength="10" class="w-full border rounded p-1.5 uppercase">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold mb-0.5">E-Mail</label>
                        <input type="email" name="email" id="editUserMail" required class="w-full border rounded p-1.5">
                    </div>
                    <div>
                        <label class="block font-semibold mb-0.5">Telefon</label>
                        <input type="text" name="phone" id="editUserPhone" class="w-full border rounded p-1.5">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold mb-0.5">Passwort (nur ausfüllen zum Ändern)</label>
                    <input type="password" name="password" placeholder="Neues Passwort oder leer lassen" class="w-full border rounded p-1.5">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold mb-0.5">Stamm</label>
                        <select name="company_id" id="editUserCompany" required class="w-full border rounded p-1.5 bg-white">
                            <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($comp->id); ?>"><?php echo e($comp->display_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-0.5">Rolle</label>
                        <select name="role_id" id="editUserRole" required class="w-full border rounded p-1.5 bg-white">
                            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($r->id); ?>"><?php echo e($r->label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>

                <?php if(auth()->user()->isAdmin()): ?>
                    <div>
                        <label class="block font-bold uppercase mb-1">Verwaltbare Unternehmen:</label>
                        <div class="flex flex-wrap items-center gap-3 bg-gray-50 p-2 rounded border">
                            <label class="flex items-center gap-1 cursor-pointer font-bold text-blue-700">
                                <input type="checkbox" id="selectAllCompaniesEdit" onchange="toggleAllCompanies(this, '.edit-managed-comp')" class="rounded">
                                <span>[ Alle Unternehmen ]</span>
                            </label>
                            <span class="text-gray-300">|</span>
                            <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" name="managed_companies[]" value="<?php echo e($comp->id); ?>" class="edit-managed-comp rounded text-blue-600">
                                    <span><?php echo e($comp->display_name); ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex justify-end gap-2 pt-2 border-t">
                    <button type="button" onclick="document.getElementById('modalEditUser').classList.add('hidden')" class="px-3 py-1 bg-gray-100 rounded">Abbrechen</button>
                    <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded font-semibold">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleAllCompanies(sourceCheckbox, targetSelector) {
            document.querySelectorAll(targetSelector).forEach(chk => {
                chk.checked = sourceCheckbox.checked;
            });
        }

        function editCompany(id, name, code, format) {
            document.getElementById('formEditCompany').action = '/admin/companies/' + id;
            document.getElementById('editCompName').value = name;
            document.getElementById('editCompCode').value = code;

            format = format || 'both';
            document.getElementById('editFormatShort').checked = (format === 'short');
            document.getElementById('editFormatBoth').checked = (format === 'both');
            document.getElementById('editFormatName').checked = (format === 'name');

            document.getElementById('modalEditCompany').classList.remove('hidden');
        }

        function editClient(c) {
            document.getElementById('formEditClient').action = '/admin/clients/' + c.id;
            document.getElementById('editClName').value = c.name;
            document.getElementById('editClCode').value = c.short_code;
            document.getElementById('editClColor').value = c.color;
            document.getElementById('editClPhoneDispo').value = c.phone_dispo || '';
            document.getElementById('editClPhoneBackup').value = c.phone_backup || '';
            document.getElementById('editClMailDispo').value = c.email_dispo || '';
            document.getElementById('editClMailWagen').value = c.email_wagenliste || '';
            document.getElementById('editClMailSchad').value = c.email_schadwagen || '';
            document.getElementById('editClNotes').value = c.notes || '';

            const format = c.name_display_format || 'both';
            document.getElementById('editClFormatShort').checked = (format === 'short');
            document.getElementById('editClFormatBoth').checked = (format === 'both');
            document.getElementById('editClFormatName').checked = (format === 'name');

            document.getElementById('modalEditClient').classList.remove('hidden');
        }

        function editRole(r) {
            document.getElementById('formEditRole').action = '/admin/roles/' + r.id;
            document.getElementById('editRoleLabel').value = r.label;
            const perms = r.permissions || [];
            document.getElementById('perm_admin_panel').checked = perms.includes('access_admin_panel');
            document.getElementById('perm_comp').checked = perms.includes('manage_companies');
            document.getElementById('perm_user').checked = perms.includes('manage_users');
            document.getElementById('perm_client').checked = perms.includes('manage_clients');
            document.getElementById('perm_role').checked = perms.includes('manage_roles');
            document.getElementById('perm_files').checked = perms.includes('manage_files');
            document.getElementById('perm_other_apt').checked = perms.includes('view_other_appointments');
            document.getElementById('perm_apt').checked = perms.includes('edit_appointments');
            document.getElementById('perm_abs').checked = perms.includes('manage_absences');
            document.getElementById('modalEditRole').classList.remove('hidden');
        }

        function editUser(u, managedIds) {
            document.getElementById('formEditUser').action = '/admin/users/' + u.id;
            document.getElementById('editUserName').value = u.name;
            document.getElementById('editUserShortCode').value = u.short_code || '';
            document.getElementById('editUserMail').value = u.email;
            document.getElementById('editUserPhone').value = u.phone || '';
            document.getElementById('editUserCompany').value = u.company_id;
            document.getElementById('editUserRole').value = u.role_id;

            const chks = document.querySelectorAll('.edit-managed-comp');
            let allChecked = chks.length > 0;
            chks.forEach(chk => {
                const isChecked = managedIds.includes(parseInt(chk.value));
                chk.checked = isChecked;
                if (!isChecked) allChecked = false;
            });

            const selAll = document.getElementById('selectAllCompaniesEdit');
            if (selAll) selAll.checked = allChecked;

            document.getElementById('modalEditUser').classList.remove('hidden');
        }
    </script>
</body>
</html><?php /**PATH /var/www/vhosts/hosting233863.ae9ed.netcup.net/ucp/resources/views/admin/index.blade.php ENDPATH**/ ?>