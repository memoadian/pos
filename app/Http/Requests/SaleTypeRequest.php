<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $saleTypeId = $this->route('sale_type')?->id;

        return [
            'name' => 'required|string|max:255|unique:sale_types,name,' . $saleTypeId,
            'base_unit' => 'required|string|max:50',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',
            'name.unique' => 'Ya existe un tipo de venta con este nombre',
            'base_unit.required' => 'La unidad base es obligatoria',
            'base_unit.max' => 'La unidad base no puede tener más de 50 caracteres',
            'is_active.boolean' => 'El estado debe ser verdadero o falso',
        ];
    }
}
