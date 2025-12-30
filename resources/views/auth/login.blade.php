<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión - {{ config('app.name', 'POS') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-cyan-600 rounded-xl mb-4">
                <i class="bi bi-shop text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Sistema POS</h1>
            <p class="text-slate-500 text-sm mt-1">Productos de Limpieza</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Iniciar Sesión</h2>
                <p class="text-slate-500 text-sm mt-1">Ingrese sus credenciales</p>
            </div>

            <form method="POST" action="{{ route('login.post') }}" class="p-6 space-y-5" id="loginForm">
                @csrf

                <!-- Login Field -->
                <div>
                    <label for="login" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Usuario o Email
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="bi bi-person text-lg"></i>
                        </span>
                        <input
                            type="text"
                            id="login"
                            name="login"
                            value="{{ old('login') }}"
                            class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('login') border-red-400 @enderror"
                            placeholder="usuario@ejemplo.com"
                            required
                            autofocus
                        >
                    </div>
                    @error('login')
                        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="bi bi-lock text-lg"></i>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full pl-10 pr-10 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('password') border-red-400 @enderror"
                            placeholder="••••••••"
                            required
                        >
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                        >
                            <i id="eyeIcon" class="bi bi-eye text-lg"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                            <i class="bi bi-exclamation-circle"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Remember -->
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-cyan-500"
                    >
                    <label for="remember" class="ml-2 text-sm text-slate-600 select-none cursor-pointer">
                        Recordar sesión
                    </label>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    class="w-full py-2.5 px-4 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    id="submitBtn"
                >
                    <span id="btnText">Iniciar Sesión</span>
                    <i id="btnLoader" class="bi bi-arrow-repeat animate-spin hidden"></i>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-slate-500 mt-6">
            © {{ date('Y') }} Sistema POS
        </p>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const loader = document.getElementById('btnLoader');
            btn.disabled = true;
            text.textContent = 'Ingresando...';
            loader.classList.remove('hidden');
        });
    </script>
</body>
</html>
