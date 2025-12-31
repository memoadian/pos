<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username,' . $userId,
            'email' => 'required|email|max:255|unique:users,email,' . $userId,
            'password' => $this->isMethod('post')
                ? 'required|string|min:8|confirmed'
                : 'nullable|string|min:8|confirmed',
            'branch_id' => 'nullable|exists:branches,id',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom error messages for validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',

            'username.required' => 'El nombre de usuario es obligatorio',
            'username.max' => 'El nombre de usuario no puede tener más de 50 caracteres',
            'username.unique' => 'Ya existe un usuario con este nombre de usuario',

            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'El correo electrónico debe ser una dirección válida',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres',
            'email.unique' => 'Ya existe un usuario con este correo electrónico',

            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'La confirmación de contraseña no coincide',

            'branch_id.exists' => 'La sucursal seleccionada no existe',

            'roles.required' => 'Debe asignar al menos un rol al usuario',
            'roles.array' => 'Los roles deben ser un arreglo válido',
            'roles.min' => 'Debe asignar al menos un rol al usuario',
            'roles.*.exists' => 'Uno o más roles seleccionados no existen',

            'is_active.boolean' => 'El estado del usuario debe ser verdadero o falso',
        ];
    }
}
