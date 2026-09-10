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
        return [
            'opening_amount' => 'required|numeric|min:0',
            'opening_notes' => 'nullable|string|max:500',
            // Permite registrar cajas de días pasados; nunca a futuro.
            'opened_at' => 'nullable|date|before_or_equal:today',
        ];
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
            'opening_notes.max' => 'Las notas no pueden exceder 500 caracteres',
            'opened_at.date' => 'La fecha de apertura no es válida',
            'opened_at.before_or_equal' => 'La fecha de apertura no puede ser futura',
        ];
    }
}
