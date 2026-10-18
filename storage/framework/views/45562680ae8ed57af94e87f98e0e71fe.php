<?php
    $user = auth()->user();
    $canManage = $user && $user->canDo('edit_appointments');
    $allowedCompanyIds = $canManage ? $user->getAllowedCompanyIds() : [];

    if ($canManage) {
        $changeRequests = \App\Models\AppointmentChangeRequest::with(['appointment.user', 'appointment.company', 'user'])
            ->where('status', 'pending')
            ->whereHas('appointment', function ($q) use ($allowedCompanyIds) {
                if (!empty($allowedCompanyIds)) {
                    $q->whereIn('company_id', $allowedCompanyIds);
                }
            })
            ->latest()
            ->get();
    } else {
        $changeRequests = collect();
    }
?>

<?php if($canManage && $changeRequests->isNotEmpty()): ?>
    <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-sm mb-6">
        <div class="flex items-center justify-between mb-3 border-b border-amber-200 pb-2">
            <h2 class="text-sm font-bold uppercase text-amber-900 flex items-center gap-1.5">
                <span>⚠️</span> <span>Offene Korrekturanträge Dienstmeldungen (<?php echo e($changeRequests->count()); ?>)</span>
            </h2>
        </div>

        <!-- 1. MOBILE KARTEN (block md:hidden) -->
        <div class="block md:hidden space-y-3">
            <?php $__currentLoopData = $changeRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $apt = $req->appointment;
                    $fmtDate = fn($d) => !empty($d) ? \Carbon\Carbon::parse($d)->format('d.m. H:i') : '—';
                    $fmtTime = fn($d) => !empty($d) ? \Carbon\Carbon::parse($d)->format('H:i') : '—';
                    $reasonText = $req->report_notes ?: ($req->reason ?: null);
                ?>
                <div class="bg-white border border-amber-200 rounded-lg p-3 text-xs shadow-sm space-y-2.5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-1.5">
                        <span class="font-bold text-gray-800">#<?php echo e($apt->id ?? '—'); ?> - <?php echo e($apt->location ?? 'Ohne Ort'); ?></span>
                        <span class="text-2xs text-gray-500 font-medium"><?php echo e($apt->user->name ?? ($req->user->name ?? '—')); ?></span>
                    </div>

                    <!-- Soll (Planung) -->
                    <div class="bg-gray-50 rounded p-2 text-2xs text-gray-600">
                        <span class="font-bold text-gray-700 block uppercase mb-0.5">Soll (Planung):</span>
                        <span class="font-mono"><?php echo e($fmtDate($apt->start_time ?? null)); ?> bis <?php echo e($fmtDate($apt->end_time ?? null)); ?></span>
                    </div>

                    <!-- Vorher vs. Nachher -->
                    <div class="grid grid-cols-2 gap-2 text-2xs">
                        <div class="bg-gray-50 border border-gray-200 rounded p-2">
                            <span class="font-bold text-gray-600 block uppercase mb-1">Meldung (Alt):</span>
                            <div><span class="font-semibold">Schicht:</span> <?php echo e($fmtDate($apt->actual_start ?? null)); ?> - <?php echo e($fmtDate($apt->actual_end ?? null)); ?></div>
                            <div class="text-gray-500 mt-0.5">Hin: <?php echo e($fmtTime($apt->travel_out_start ?? null)); ?> | Rück: <?php echo e($fmtTime($apt->travel_back_start ?? null)); ?></div>
                            <div class="text-gray-500">Pause: <?php echo e($apt->pause_minutes ?? 0); ?> Min.</div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded p-2 text-blue-950">
                            <span class="font-bold text-blue-800 block uppercase mb-1">Korrektur (Neu):</span>
                            <div><span class="font-semibold">Schicht:</span> <?php echo e($fmtDate($req->actual_start ?? null)); ?> - <?php echo e($fmtDate($req->actual_end ?? null)); ?></div>
                            <div class="text-blue-800 mt-0.5">Hin: <?php echo e($fmtTime($req->travel_out_start ?? null)); ?> | Rück: <?php echo e($fmtTime($req->travel_back_start ?? null)); ?></div>
                            <div class="text-blue-800">Pause: <?php echo e($req->pause_minutes ?? 0); ?> Min.</div>
                        </div>
                    </div>

                    <!-- Begründung -->
                    <div class="text-2xs <?php echo e($reasonText ? 'text-gray-700 bg-amber-50/60 border-amber-100' : 'text-gray-400 bg-gray-50 border-gray-200'); ?> p-2 rounded border">
                        <span class="font-bold text-amber-900 block uppercase text-3xs mb-0.5">Begründung:</span>
                        <span class="italic"><?php echo e($reasonText ?: 'Keine Begründung angegeben'); ?></span>
                    </div>

                    <!-- Aktionsbuttons -->
                    <?php if($apt): ?>
                        <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-100">
                            <form action="<?php echo e(route('appointments.shift_report', $apt->id)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action_type" value="approve_change">
                                <input type="hidden" name="change_request_id" value="<?php echo e($req->id); ?>">
                                <button type="submit" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-bold shadow-sm text-center">
                                    ✓ Genehmigen
                                </button>
                            </form>
                            <form action="<?php echo e(route('appointments.shift_report', $apt->id)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action_type" value="reject_change">
                                <input type="hidden" name="change_request_id" value="<?php echo e($req->id); ?>">
                                <button type="submit" class="w-full py-2 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-bold shadow-sm text-center">
                                    ✕ Ablehnen
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <!-- 2. DESKTOP TABELLE (hidden md:block) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs bg-white rounded-lg border divide-y">
                <thead class="bg-amber-100/70 text-gray-700 font-bold uppercase text-2xs tracking-wider">
                    <tr>
                        <th class="p-2.5">Dienst & MA</th>
                        <th class="p-2.5">Soll (Planung)</th>
                        <th class="p-2.5">Ursprüngliche Meldung (Alt)</th>
                        <th class="p-2.5">Beantragte Korrektur (Neu)</th>
                        <th class="p-2.5">Begründung</th>
                        <th class="p-2.5 text-right">Aktion</th>
                    </tr>
                </thead>
                <tbody class="divide-y text-gray-800">
                    <?php $__currentLoopData = $changeRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $apt = $req->appointment;
                            $fmtDate = fn($d) => !empty($d) ? \Carbon\Carbon::parse($d)->format('d.m. H:i') : '—';
                            $fmtTime = fn($d) => !empty($d) ? \Carbon\Carbon::parse($d)->format('H:i') : '—';
                            $reasonText = $req->report_notes ?: ($req->reason ?: null);
                        ?>
                        <tr class="hover:bg-amber-50/40 transition">
                            <td class="p-2.5 font-bold">
                                #<?php echo e($apt->id ?? '—'); ?> - <?php echo e($apt->location ?? 'Ohne Ort'); ?><br>
                                <span class="font-normal text-gray-500"><?php echo e($apt->user->name ?? ($req->user->name ?? '—')); ?></span>
                            </td>
                            <td class="p-2.5 text-2xs text-gray-600 font-mono">
                                <?php echo e($fmtDate($apt->start_time ?? null)); ?> bis<br><?php echo e($fmtDate($apt->end_time ?? null)); ?>

                            </td>
                            <td class="p-2.5 text-2xs text-gray-700">
                                <div class="font-bold">Schicht:</div>
                                <?php echo e($fmtDate($apt->actual_start ?? null)); ?> bis <?php echo e($fmtDate($apt->actual_end ?? null)); ?><br>
                                <span class="text-gray-400">Hin:</span> <?php echo e($fmtTime($apt->travel_out_start ?? null)); ?> |
                                <span class="text-gray-400">Rück:</span> <?php echo e($fmtTime($apt->travel_back_start ?? null)); ?><br>
                                <span class="text-gray-400">Pause:</span> <?php echo e($apt->pause_minutes ?? 0); ?> Min.
                            </td>
                            <td class="p-2.5 text-2xs text-blue-900 font-semibold bg-blue-50/40">
                                <div class="font-bold">Schicht:</div>
                                <?php echo e($fmtDate($req->actual_start ?? null)); ?> bis <?php echo e($fmtDate($req->actual_end ?? null)); ?><br>
                                <span class="text-gray-500">Hin:</span> <?php echo e($fmtTime($req->travel_out_start ?? null)); ?> |
                                <span class="text-gray-500">Rück:</span> <?php echo e($fmtTime($req->travel_back_start ?? null)); ?><br>
                                <span class="text-gray-500">Pause:</span> <?php echo e($req->pause_minutes ?? 0); ?> Min.
                            </td>
                            <td class="p-2.5 italic text-gray-700 max-w-xs break-words">
                                <?php echo e($reasonText ?: 'Keine Begründung angegeben'); ?>

                            </td>
                            <td class="p-2.5 text-right whitespace-nowrap">
                                <?php if($apt): ?>
                                    <form action="<?php echo e(route('appointments.shift_report', $apt->id)); ?>" method="POST" class="inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action_type" value="approve_change">
                                        <input type="hidden" name="change_request_id" value="<?php echo e($req->id); ?>">
                                        <button type="submit" class="px-2.5 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-bold shadow-sm">
                                            ✓ Genehmigen
                                        </button>
                                    </form>
                                    <form action="<?php echo e(route('appointments.shift_report', $apt->id)); ?>" method="POST" class="inline ml-1">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action_type" value="reject_change">
                                        <input type="hidden" name="change_request_id" value="<?php echo e($req->id); ?>">
                                        <button type="submit" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-bold shadow-sm">
                                            ✕ Ablehnen
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH /var/www/vhosts/hosting233863.ae9ed.netcup.net/ucp/resources/views/admin/pending_reports.blade.php ENDPATH**/ ?>