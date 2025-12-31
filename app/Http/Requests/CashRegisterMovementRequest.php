<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CashRegisterMovementRequest extends FormRequest
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
        return [
            'type' => 'required|in:ingreso,retiro',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'type.required' => 'El tipo de movimiento es requerido',
            'type.in' => 'El tipo debe ser "ingreso" o "retiro"',
            'amount.required' => 'El monto es requerido',
            'amount.numeric' => 'El monto debe ser un número válido',
            'amount.min' => 'El monto debe ser mayor a 0',
            'reason.required' => 'La razón del movimiento es requerida',
            'reason.max' => 'La razón no puede exceder 255 caracteres',
            'notes.max' => 'Las notas no pueden exceder 500 caracteres',
        ];
    }
}
