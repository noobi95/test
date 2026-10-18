<?php
$file = 'resources/views/appointments/calendar.blade.php';
$content = file_get_contents($file);

// 1. Zuerst das versehentlich in detailModal geratene Bruchstück entfernen
// Falls noch Fragmente wie id="abs_start_date" im oberen Bereich stehen:
// Wir stellen die Datei am besten via git checkout wieder her
