<?php
$f = 'resources/views/appointments/calendar.blade.php';
$c = file_get_contents($f);

$fnStart = <<<'JS'
function openPendingReportsModal() {
    var list = document.getElementById('pendingReportsList');
    var changes = window.pendingChangeRequestsData || [];
    var modal = document.getElementById('pendingReportsModal');
    if (modal) modal.classList.remove('hidden');
    if (!list) return;

    var changeHtml = '';
    if (changes.length > 0) {
        changeHtml = '<li class="p-2 bg-amber-50 border border-amber-200 rounded font-bold text-amber-900 text-2xs uppercase">⚠️ Offene Korrekturanträge (' + changes.length + ')</li>' +
            changes.map(function(item) {
                return '<li class="p-2.5 bg-white border border-amber-200 rounded-lg shadow-sm flex items-center justify-between gap-2">' +
                       '<div><div class="font-bold text-gray-800">#' + item.appointment_id + ' - ' + item.location + '</div>' +
                       '<div class="text-gray-500 text-2xs">' + item.user_name + ' &bull; <span class="italic text-gray-700">' + item.reason + '</span></div></div>' +
                       '<button type="button" onclick="loadAndOpenShift(' + item.appointment_id + ')" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded text-2xs shadow-sm">Prüfen</button>' +
                       '</li>';
            }).join('');
    }

    list.innerHTML = (changeHtml ? changeHtml + '<li class="mt-3 mb-1 border-t pt-2 font-bold text-gray-500 text-2xs uppercase">📋 Eigene offene Dienste</li>' : '') +
                     '<li id="shiftsLoadingIndicator" class="p-3 text-center text-gray-400">Lade offene Dienste...</li>';

    /* PART_TWO */
EOF;

$regex = '/function openPendingReportsModal\(\)\s*\{.*?(?=document\.addEventListener\(\x27DOMContentLoaded\x27,\s*updatePendingReportsCounter\);)/s';
$c = preg_replace($regex, $fnStart . "\n", $c, 1);
file_put_contents($f, $c);
echo "Teil 1 gesetzt!\n";
