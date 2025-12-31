<!-- Nombre -->
<div>
    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
        Nombre <span class="text-red-500">*</span>
    </label>
    <input type="text"
           id="name"
           name="name"
           value="{{ old('name', $user->name ?? '') }}"
           required
           class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('name') border-red-400 @enderror">
    @error('name')
        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i>
            {{ $message }}
        </p>
    @enderror
</div>

<!-- Usuario -->
<div>
    <label for="username" class="block text-sm font-medium text-slate-700 mb-1.5">
        Usuario <span class="text-red-500">*</span>
    </label>
    <input type="text"
           id="username"
           name="username"
           value="{{ old('username', $user->username ?? '') }}"
           required
           class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('username') border-red-400 @enderror">
    <p class="mt-1 text-xs text-slate-500">Máximo 50 caracteres</p>
    @error('username')
        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i>
            {{ $message }}
        </p>
    @enderror
</div>

<!-- Email -->
<div>
    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
        Correo Electrónico <span class="text-red-500">*</span>
    </label>
    <input type="email"
           id="email"
           name="email"
           value="{{ old('email', $user->email ?? '') }}"
           required
           class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('email') border-red-400 @enderror">
    @error('email')
        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i>
            {{ $message }}
        </p>
    @enderror
</div>

<!-- Contraseña -->
<div>
    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
        Contraseña
        @if(isset($user))
            <span class="text-slate-400 font-normal">(dejar en blanco para mantener actual)</span>
        @else
            <span class="text-red-500">*</span>
        @endif
    </label>
    <div class="relative">
        <input type="password"
               id="password"
               name="password"
               @if(!isset($user)) required @endif
               class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('password') border-red-400 @enderror">
        <button type="button"
                onclick="togglePassword('password')"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
            <i class="bi bi-eye" id="password-icon"></i>
        </button>
    </div>
    <p class="mt-1 text-xs text-slate-500">Mínimo 8 caracteres</p>
    @error('password')
        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i>
            {{ $message }}
        </p>
    @enderror
</div>

<!-- Confirmar Contraseña -->
<div>
    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">
        Confirmar Contraseña
        @if(isset($user))
            <span class="text-slate-400 font-normal">(si cambió la contraseña)</span>
        @else
            <span class="text-red-500">*</span>
        @endif
    </label>
    <div class="relative">
        <input type="password"
               id="password_confirmation"
               name="password_confirmation"
               @if(!isset($user)) required @endif
               class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
        <button type="button"
                onclick="togglePassword('password_confirmation')"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
            <i class="bi bi-eye" id="password_confirmation-icon"></i>
        </button>
    </div>
</div>

<!-- Sucursal -->
<div>
    <label for="branch_id" class="block text-sm font-medium text-slate-700 mb-1.5">
        Sucursal
    </label>
    <select id="branch_id"
            name="branch_id"
            class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('branch_id') border-red-400 @enderror">
        <option value="">Sin sucursal asignada</option>
        @foreach($branches as $branch)
        <option value="{{ $branch->id }}"
                {{ old('branch_id', $user->branch_id ?? '') == $branch->id ? 'selected' : '' }}>
            {{ $branch->name }}
        </option>
        @endforeach
    </select>
    @error('branch_id')
        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i>
            {{ $message }}
        </p>
    @enderror
</div>

<!-- Roles -->
<div>
    <label class="block text-sm font-medium text-slate-700 mb-3">
        Roles <span class="text-red-500">*</span>
    </label>
    <div class="border border-slate-200 rounded-lg p-4 @error('roles') border-red-400 @enderror">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            @foreach($roles as $role)
            <label class="flex items-start gap-2 p-2 hover:bg-slate-50 rounded cursor-pointer">
                <input type="checkbox"
                       name="roles[]"
                       value="{{ $role->id }}"
                       {{ (is_array(old('roles')) && in_array($role->id, old('roles'))) ||
                          (isset($user) && $user->hasRole($role->name)) ? 'checked' : '' }}
                       class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-cyan-500 mt-0.5">
                <div class="flex-1">
                    <span class="text-sm text-slate-900">{{ $role->name }}</span>
                    @if($role->description)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $role->description }}</p>
                    @endif
                </div>
            </label>
            @endforeach
        </div>
    </div>
    <p class="mt-1 text-xs text-slate-500">Selecciona al menos un rol para el usuario</p>
    @error('roles')
        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i>
            {{ $message }}
        </p>
    @enderror
</div>

<!-- Estado Activo -->
<div>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox"
               name="is_active"
               value="1"
               {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
               class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-cyan-500">
        <span class="text-sm font-medium text-slate-700">Usuario activo</span>
    </label>
    <p class="mt-1 text-xs text-slate-500 ml-6">Los usuarios inactivos no pueden iniciar sesión</p>
    @error('is_active')
        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
            <i class="bi bi-exclamation-circle"></i>
            {{ $message }}
        </p>
    @enderror
</div>

@push('scripts')
<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
@endpush
