<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
    <link rel="icon" href="{{ asset("favicon.svg") }}" type="image/svg+xml">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TerminPlaner') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Mobile Calendar Optimization (Desktop bleibt unberührt) -->
    <style>
    @media (max-width: 768px) {
        .fc, 
        .fc-view-harness, 
        .fc-scroller-harness,
        .calendar-container {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        .fc-timegrid, 
        .fc-col-header, 
        .fc-timegrid-body, 
        .fc-scrollgrid,
        .fc-scrollgrid-sync-table {
            min-width: 680px !important;
        }

        .fc-col-header-cell-cushion {
            font-size: 0.8rem !important;
            padding: 4px 2px !important;
            display: inline-block !important;
            white-space: normal !important;
            line-height: 1.2 !important;
        }

        .fc-event {
            font-size: 0.72rem !important;
            line-height: 1.15 !important;
            padding: 2px !important;
        }

        .fc-event-main {
            padding: 2px 3px !important;
            word-break: break-word !important;
        }

        .fc-timegrid-slot-label-cushion {
            font-size: 0.75rem !important;
            padding: 0 4px !important;
        }

        .fc-header-toolbar {
            flex-wrap: wrap !important;
            gap: 6px !important;
        }
    }
    </style>
</head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <!-- Capacitor Back-Button Handling -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.App) {
                window.Capacitor.Plugins.App.addListener("backButton", ({ canGoBack }) => {
                    if (window.location.pathname === "/dashboard" || window.location.pathname === "/login") {
                        window.Capacitor.Plugins.App.exitApp();
                    } else if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.Capacitor.Plugins.App.exitApp();
                    }
                });
            }
        });
    </script>
</body>
</html>
