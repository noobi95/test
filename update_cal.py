import re

with open('resources/views/appointments/calendar.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Externe CDNs durch lokale Pfade ersetzen
content = re.sub(
    r'<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/[^"]+">',
    '<link rel="stylesheet" href="{{ asset(\'vendor/tailwind/tailwind.min.css\') }}">',
    content
)
content = re.sub(
    r'<script src="https://cdn.jsdelivr.net/npm/fullcalendar@[^"]+"></script>',
    '<script src="{{ asset(\'vendor/fullcalendar/index.global.min.js\') }}"></script>',
    content
)
content = re.sub(
    r'<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@[^"]+"></script>',
    '<script src="{{ asset(\'vendor/fullcalendar/de.global.min.js\') }}"></script>',
    content
)

# Smartphone-Header umbruchsicher machen
nav_old = '<nav class="bg-white border-b border-gray-200 px-4 py-2 sticky top-0 z-30 shadow-sm">'
nav_new = '<nav class="bg-white border-b border-gray-200 px-2 sm:px-4 py-2 sticky top-0 z-30 shadow-sm">'
content = content.replace(nav_old, nav_new)

# Max-Breite und Umbrüche
content = content.replace(
    '<div class="flex items-center gap-2">',
    '<div class="flex flex-wrap items-center gap-1.5 sm:gap-2">'
)
content = content.replace(
    '<div class="flex items-center space-x-2">',
    '<div class="flex flex-wrap items-center gap-1.5 sm:gap-2">'
)

with open('resources/views/appointments/calendar.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("calendar.blade.php erfolgreich aktualisiert!")
