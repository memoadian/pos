<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
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
        $branchId = $this->route('branch')?->id;

        return [
            'name' => 'required|string|max:255|unique:branches,name,' . $branchId,
            'address' => 'nullable|string|max:500',
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
            'name.required' => 'El nombre de la sucursal es obligatorio',
            'name.max' => 'El nombre de la sucursal no puede tener más de 255 caracteres',
            'name.unique' => 'Ya existe una sucursal con este nombre',

            'address.max' => 'La dirección no puede tener más de 500 caracteres',

            'is_active.boolean' => 'El estado de la sucursal debe ser verdadero o falso',
        ];
    }
}
