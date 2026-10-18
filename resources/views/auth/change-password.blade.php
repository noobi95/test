<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort ändern - {{ config('app.name', 'Terminplaner') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/tailwind/tailwind.min.css') }}">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 min-h-screen flex flex-col justify-center items-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-gray-200 p-6 sm:p-8">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
            <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <span>🔑</span> <span>Passwort ändern</span>
            </h1>
            <a href="{{ route('calendar.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 transition">
                ✕ Zurück zum Kalender
            </a>
        </div>

        @if(session('status') === 'password-updated')
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                <span>✓</span> <span>Dein Passwort wurde erfolgreich aktualisiert!</span>
            </div>
        @endif

        @if($errors->updatePassword->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs space-y-1">
                @foreach($errors->updatePassword->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-xs font-bold text-gray-700 uppercase mb-1">Aktuelles Passwort</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                       class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-gray-700 uppercase mb-1">Neues Passwort</label>
                <input type="password" id="password" name="password" required autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-gray-700 uppercase mb-1">Neues Passwort bestätigen</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="pt-4 flex items-center justify-between border-t border-gray-100">
                <a href="{{ route('calendar.index') }}" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                    Abbrechen
                </a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow transition">
                    Passwort speichern
                </button>
            </div>
        </form>
    </div>
</body>
</html>