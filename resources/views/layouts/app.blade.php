<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'POS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #06b6d4 100%);
            min-height: 100vh;
        }

        .sidebar-link {
            @apply flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 hover:bg-cyan-50 hover:text-cyan-700 transition-colors cursor-pointer;
        }

        .sidebar-link-active {
            @apply bg-gradient-to-r from-cyan-100 to-sky-100 text-cyan-700 font-semibold;
        }

        .sidebar-link i {
            min-width: 20px;
            text-align: center;
        }

        .sidebar.collapsed .sidebar-link span {
            @apply hidden;
        }

        .sidebar.collapsed .sidebar-logo {
            @apply h-10 w-auto;
        }

        .sidebar.collapsed {
            @apply w-20;
        }

        .sidebar.collapsed .user-footer > div:not(:first-child) {
            @apply hidden;
        }

        .sidebar.collapsed .sidebar-label {
            @apply hidden;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-sky-50 to-cyan-50">
    <div class="flex h-screen" id="app">
        <!-- Hamburger Menu - Solo visible en móvil -->
        <button class="md:hidden fixed top-4 left-4 z-[1001] p-2 rounded-lg bg-cyan-600 text-white hover:bg-cyan-700 transition-colors"
            id="hamburger-toggle">
            <i class="bi bi-list text-xl"></i>
        </button>

        <!-- Overlay para móvil -->
        <div class="fixed inset-0 bg-black bg-opacity-50 hidden md:hidden z-40" id="sidebar-overlay"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed left-0 top-0 h-screen md:relative md:translate-x-0 -translate-x-full transition-all duration-300 z-50 md:z-0 w-64 bg-white flex flex-col border-r border-slate-200"
            id="sidebar">
            @include('layouts.sidebar')
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col w-full overflow-hidden">
            <!-- Header superior -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-8 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sucursal:</span>
                    <span class="text-sm font-semibold text-slate-900">{{ auth()->user()->branch->name ?? 'N/A' }}</span>
                </div>
                <h1 class="text-lg md:text-xl font-semibold text-slate-900">
                    Hola <span class="text-cyan-700">{{ auth()->user()->name }}</span>
                </h1>
            </header>

            <!-- Contenido scrollable -->
            <section class="flex-1 overflow-y-auto p-4 md:p-8">
                <div class="max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </section>
        </main>

        <!-- Global Loader -->
        <div id="globalLoader" class="hidden fixed inset-0 bg-white bg-opacity-75 flex items-center justify-center z-[9999]">
            <div class="flex flex-col items-center gap-4">
                <svg class="animate-spin h-12 w-12 text-cyan-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-slate-600 font-medium">Cargando...</p>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        const hamburgerToggle = document.getElementById('hamburger-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        }

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        }

        // Toggle sidebar on hamburger click
        if (hamburgerToggle) {
            hamburgerToggle.addEventListener('click', () => {
                if (sidebar.classList.contains('-translate-x-full')) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            });
        }

        // Close sidebar when overlay is clicked
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        // Close sidebar when a link is clicked
        if (sidebar) {
            const links = sidebar.querySelectorAll('a');
            links.forEach(link => {
                // Skip the collapse toggle
                if (!link.id.includes('collapse')) {
                    link.addEventListener('click', (e) => {
                        // Only close on mobile
                        if (window.innerWidth < 768) {
                            closeSidebar();
                        }
                    });
                }
            });
        }

        // Sidebar collapse functionality
        let isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

        function toggleSidebarCollapse(e) {
            if (e) e.preventDefault();
            isCollapsed = !isCollapsed;
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            applySidebarCollapseState();
        }

        function applySidebarCollapseState() {
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        }

        // Apply collapse state on load
        applySidebarCollapseState();

        // Global loader functions
        function showLoader() {
            const loader = document.getElementById('globalLoader');
            if (loader) {
                loader.classList.remove('hidden');
            }
        }

        function hideLoader() {
            const loader = document.getElementById('globalLoader');
            if (loader) {
                loader.classList.add('hidden');
            }
        }

        // Set active sidebar link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const sidebarLinks = document.querySelectorAll('.sidebar-link-item');

            sidebarLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('sidebar-link-active');
                } else {
                    link.classList.remove('sidebar-link-active');
                }
            });
        });
    </script>
</body>
</html>
