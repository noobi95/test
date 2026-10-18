<!DOCTYPE html>
<html lang="de">
<head>
    <link rel="icon" href="{{ asset("favicon.svg") }}" type="image/svg+xml">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung | {{ config('app.name', 'Terminplaner') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/tailwind/tailwind.min.css') }}">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans antialiased text-gray-800">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <span class="text-4xl">🚂</span>
            <h2 class="mt-2 text-2xl font-black tracking-tight text-gray-900">
                {{ config('app.name', 'Terminplaner') }}
            </h2>
            <p class="mt-1 text-xs text-gray-600 font-medium">Dienstplanung & Disposition</p>
        </div>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl border border-gray-200 sm:px-10">
            @if(session('status'))
                <div class="mb-4 text-xs font-semibold text-green-700 bg-green-50 p-2.5 rounded border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

        @if($errors->any())
            <div class="mb-4 text-xs font-semibold text-red-700 bg-red-50 p-3 rounded-lg border border-red-200 space-y-1">
                @foreach($errors->all() as $error)
                    <p class="flex items-center gap-1.5"><span>⚠️</span> {{ $error }}</p>
                @endforeach
            </div>
        @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">E-Mail-Adresse</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Passwort</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-gray-600 font-medium">Angemeldet bleiben</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="font-semibold text-blue-600 hover:text-blue-500 hover:underline">
                            Passwort vergessen?
                        </a>
                    @endif
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-md text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        Anmelden
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
