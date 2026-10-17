<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashRegisterRequest extends FormRequest
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
        // La fecha de cierre solo la puede ajustar un administrador. Para
        // cualquier otro usuario el campo se rechaza si llega en el request.
        $canEditClosedAt = $this->user()?->hasRole('Admin') ?? false;

        return [
            'closing_amount' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string|max:500',
            'closed_at' => [
                $canEditClosedAt ? 'nullable' : 'prohibited',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'closing_amount.required' => 'El monto final es requerido',
            'closing_amount.numeric' => 'El monto final debe ser un número',
            'closing_amount.min' => 'El monto final no puede ser negativo',
            'closing_notes.max' => 'Las notas no pueden exceder 500 caracteres',
            'closed_at.date' => 'La fecha de cierre no es válida',
            'closed_at.before_or_equal' => 'La fecha de cierre no puede ser futura',
            'closed_at.prohibited' => 'Solo un administrador puede cambiar la fecha de cierre',
        ];
    }
}
