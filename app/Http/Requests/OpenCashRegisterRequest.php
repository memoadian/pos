<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashRegisterRequest extends FormRequest
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
        $rules = [
            'opening_amount' => 'required|numeric|min:0',
            'opening_notes' => 'nullable|string|max:500',
        ];

        // Si el usuario es admin sin sucursal asignada, debe seleccionar una
        if (!auth()->user()->branch_id && auth()->user()->hasRole(['Admin', 'Admin'])) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        return $rules;
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'opening_amount.required' => 'El monto inicial es requerido',
            'opening_amount.numeric' => 'El monto inicial debe ser un número',
            'opening_amount.min' => 'El monto inicial no puede ser negativo',
            'branch_id.required' => 'Debes seleccionar una sucursal',
            'branch_id.exists' => 'La sucursal seleccionada no existe',
            'opening_notes.max' => 'Las notas no pueden exceder 500 caracteres',
        ];
    }
}
