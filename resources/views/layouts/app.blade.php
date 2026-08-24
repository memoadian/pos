<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'POS') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">
    {{-- Impersonation Banner --}}
    @impersonating($guard = null)
    <div class="bg-amber-500 text-white px-4 py-2 flex items-center justify-between sticky top-0 z-[60]">
        <div class="flex items-center gap-2">
            <i class="bi bi-person-badge text-lg"></i>
            <span class="text-sm font-medium">
                Estás impersonando a <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }})
            </span>
        </div>
        <a href="{{ route('impersonate.leave') }}"
           class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-box-arrow-left"></i>
            <span>Dejar de impersonar</span>
        </a>
    </div>
    @endImpersonating

    @php $isPos = request()->routeIs('pos.*'); @endphp
    <div class="flex h-screen" id="app">
        <!-- Overlay -->
        <div id="overlay" class="fixed inset-0 bg-black/50 z-40 hidden {{ $isPos ? '' : 'md:hidden' }}"></div>

        <!-- Sidebar -->
        <aside
            id="sidebar"
            class="fixed {{ $isPos ? '' : 'md:static' }} inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 transform -translate-x-full {{ $isPos ? '' : 'md:translate-x-0' }} transition-transform duration-200"
        >
            @include('layouts.sidebar')
        </aside>

        <!-- Main -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Header -->
            <header class="relative z-[45] h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-6 flex-shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    {{-- En el POS el sidebar queda oculto por defecto (mas espacio para vender);
                         este boton lo saca como panel flotante si hace falta navegar a otro lado. --}}
                    <button
                        id="menuBtn"
                        class="{{ $isPos ? '' : 'md:hidden' }} shrink-0 -ml-2 p-2 text-slate-600 hover:bg-slate-100 rounded-lg"
                    >
                        <i class="bi bi-list text-xl"></i>
                    </button>
                    @include('layouts.partials.branch-switcher')
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-600">{{ auth()->user()->name }}</span>
                    <div class="w-8 h-8 bg-cyan-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-auto p-4 md:p-6">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Loader -->
    <div id="loader" class="fixed inset-0 bg-white/80 flex items-center justify-center z-[100] hidden">
        <div class="flex flex-col items-center gap-3">
            <i class="bi bi-arrow-repeat text-3xl text-cyan-600 animate-spin"></i>
            <span class="text-sm text-slate-600">Cargando...</span>
        </div>
    </div>

    @include('components.confirm-modal')

    <script>
        const isPosScreen = @json($isPos);
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function openMenu() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeMenu() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        menuBtn?.addEventListener('click', openMenu);
        overlay?.addEventListener('click', closeMenu);

        // Close on link click (mobile, o siempre en el POS ya que ahi el sidebar es flotante)
        sidebar?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                if (isPosScreen || window.innerWidth < 768) closeMenu();
            });
        });

        // Active link
        document.querySelectorAll('[data-nav]').forEach(link => {
            if (link.getAttribute('href') === window.location.pathname) {
                link.classList.add('bg-cyan-50', 'text-cyan-700', 'border-l-2', 'border-cyan-600');
                link.classList.remove('text-slate-600', 'hover:bg-slate-50');
            }
        });

        const branchSwitcherBtn = document.getElementById('branchSwitcherBtn');
        const branchSwitcherMenu = document.getElementById('branchSwitcherMenu');

        branchSwitcherBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            branchSwitcherMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (branchSwitcherMenu && !branchSwitcherMenu.classList.contains('hidden') && !branchSwitcherMenu.contains(e.target)) {
                branchSwitcherMenu.classList.add('hidden');
            }
        });

        function showLoader() {
            document.getElementById('loader')?.classList.remove('hidden');
        }

        function hideLoader() {
            document.getElementById('loader')?.classList.add('hidden');
        }
    </script>

    @yield('scripts')
</body>
</html>
