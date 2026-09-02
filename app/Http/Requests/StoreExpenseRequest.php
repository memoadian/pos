<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(array_keys(Expense::CATEGORIES))],
            'description' => 'required|string|min:2|max:255',
            'amount' => 'required|numeric|min:0.01|max:9999999.99',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'category.required' => 'La categoría es requerida',
            'category.in' => 'La categoría seleccionada no es válida',
            'description.required' => 'La descripción del gasto es requerida',
            'description.min' => 'La descripción es demasiado corta',
            'description.max' => 'La descripción no puede exceder 255 caracteres',
            'amount.required' => 'El monto es requerido',
            'amount.numeric' => 'El monto debe ser un número válido',
            'amount.min' => 'El monto debe ser mayor a 0',
            'amount.max' => 'El monto es demasiado grande',
        ];
    }
}
