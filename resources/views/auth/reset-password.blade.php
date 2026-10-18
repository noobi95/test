<!DOCTYPE html>
<html lang="de">
<head>
    <link rel="icon" href="{{ asset("favicon.svg") }}" type="image/svg+xml">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neues Passwort vergeben | {{ config('app.name', 'Terminplaner') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/tailwind/tailwind.min.css') }}">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans antialiased text-gray-800">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <span class="text-4xl">🔐</span>
            <h2 class="mt-2 text-2xl font-black tracking-tight text-gray-900">
                Neues Passwort festlegen
            </h2>
            <p class="mt-1 text-xs text-gray-600 font-medium">Vergib ein neues, sicheres Passwort für deinen Account.</p>
        </div>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl border border-gray-200 sm:px-10">
            @if($errors->any())
                <div class="mb-4 text-xs font-semibold text-red-700 bg-red-50 p-2.5 rounded border border-red-200">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">E-Mail-Adresse</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Neues Passwort</label>
                    <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"
                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Passwort bestätigen</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-md text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        Passwort speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
