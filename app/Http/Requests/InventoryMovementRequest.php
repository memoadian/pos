<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryMovementRequest extends FormRequest
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
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'type' => 'required|in:IN,OUT,ADJUST',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
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
            'product_id.required' => 'El producto es obligatorio',
            'product_id.exists' => 'El producto seleccionado no existe',

            'branch_id.required' => 'La sucursal es obligatoria',
            'branch_id.exists' => 'La sucursal seleccionada no existe',

            'type.required' => 'El tipo de movimiento es obligatorio',
            'type.in' => 'El tipo de movimiento debe ser: IN (entrada), OUT (salida) o ADJUST (ajuste)',

            'quantity.required' => 'La cantidad es obligatoria',
            'quantity.numeric' => 'La cantidad debe ser un número',
            'quantity.min' => 'La cantidad debe ser mayor a 0',

            'reason.required' => 'El motivo es obligatorio',
            'reason.max' => 'El motivo no puede tener más de 500 caracteres',
        ];
    }
}
