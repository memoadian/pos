<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id;

        $rules = [
            'department_id' => 'required|exists:departments,id',
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $productId,
            'name' => 'required|string|max:255',
            'sale_type_ids' => 'required|array|min:1',
            'sale_type_ids.*' => 'exists:sale_types,id',
            'default_sale_type_id' => ['required', 'exists:sale_types,id', Rule::in((array) $this->input('sale_type_ids', []))],
            'min_stock' => 'nullable|numeric|min:0',
            'price_retail' => 'required|numeric|min:0',
            'price_wholesale' => 'required|numeric|min:0',
            'price_super_wholesale' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'min_wholesale_qty' => 'nullable|integer|min:1',
            'min_super_wholesale_qty' => 'nullable|integer|min:1|gt:min_wholesale_qty',
            'is_active' => 'boolean',
            // Apodos con los que el mostrador busca el producto ("dogo", "chupiral").
            'aliases' => 'nullable|array|max:20',
            'aliases.*' => 'nullable|string|max:100',
        ];

        // El tipo principal usa los precios y umbrales del producto; cada tipo
        // adicional marcado trae los suyos y su factor de conversion.
        foreach ((array) $this->input('sale_type_ids', []) as $saleTypeId) {
            if ((string) $saleTypeId === (string) $this->input('default_sale_type_id')) {
                continue;
            }

            $prefix = "sale_types.{$saleTypeId}.";
            $rules[$prefix . 'conversion_factor'] = 'required|numeric|min:0.0001';
            $rules[$prefix . 'price_retail'] = 'required|numeric|min:0';
            $rules[$prefix . 'price_wholesale'] = 'required|numeric|min:0';
            $rules[$prefix . 'price_super_wholesale'] = 'required|numeric|min:0';
            $rules[$prefix . 'min_wholesale_qty'] = 'nullable|integer|min:1';
            $rules[$prefix . 'min_super_wholesale_qty'] = 'nullable|integer|min:1|gt:' . $prefix . 'min_wholesale_qty';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'department_id.required' => 'El departamento es obligatorio',
            'department_id.exists' => 'El departamento seleccionado no existe',

            'barcode.unique' => 'Ya existe un producto con este código de barras',
            'barcode.max' => 'El código de barras no puede tener más de 255 caracteres',

            'name.required' => 'El nombre del producto es obligatorio',
            'name.max' => 'El nombre del producto no puede tener más de 255 caracteres',

            'sale_type_ids.required' => 'Debes marcar al menos un tipo de venta',
            'sale_type_ids.min' => 'Debes marcar al menos un tipo de venta',
            'sale_type_ids.*.exists' => 'Uno de los tipos de venta seleccionados no existe',

            'default_sale_type_id.required' => 'Debes elegir el tipo de venta principal',
            'default_sale_type_id.exists' => 'El tipo de venta principal no existe',
            'default_sale_type_id.in' => 'El tipo de venta principal debe ser uno de los marcados',

            'sale_types.*.conversion_factor.required' => 'La equivalencia del tipo de venta es obligatoria',
            'sale_types.*.conversion_factor.numeric' => 'La equivalencia debe ser un número',
            'sale_types.*.conversion_factor.min' => 'La equivalencia debe ser mayor a cero',
            'sale_types.*.price_retail.required' => 'El precio al menudeo del tipo de venta es obligatorio',
            'sale_types.*.price_retail.numeric' => 'El precio al menudeo debe ser un número',
            'sale_types.*.price_retail.min' => 'El precio al menudeo no puede ser negativo',
            'sale_types.*.price_wholesale.required' => 'El precio al mayoreo del tipo de venta es obligatorio',
            'sale_types.*.price_wholesale.numeric' => 'El precio al mayoreo debe ser un número',
            'sale_types.*.price_wholesale.min' => 'El precio al mayoreo no puede ser negativo',
            'sale_types.*.price_super_wholesale.required' => 'El precio super mayoreo del tipo de venta es obligatorio',
            'sale_types.*.price_super_wholesale.numeric' => 'El precio super mayoreo debe ser un número',
            'sale_types.*.price_super_wholesale.min' => 'El precio super mayoreo no puede ser negativo',
            'sale_types.*.min_wholesale_qty.integer' => 'La cantidad mínima para mayoreo debe ser un número entero',
            'sale_types.*.min_wholesale_qty.min' => 'La cantidad mínima para mayoreo debe ser al menos 1',
            'sale_types.*.min_super_wholesale_qty.integer' => 'La cantidad mínima para super mayoreo debe ser un número entero',
            'sale_types.*.min_super_wholesale_qty.min' => 'La cantidad mínima para super mayoreo debe ser al menos 1',
            'sale_types.*.min_super_wholesale_qty.gt' => 'La cantidad mínima para super mayoreo debe ser mayor que la de mayoreo',

            'min_stock.numeric' => 'El stock mínimo debe ser un número',
            'min_stock.min' => 'El stock mínimo no puede ser negativo',

            'price_retail.required' => 'El precio al menudeo es obligatorio',
            'price_retail.numeric' => 'El precio al menudeo debe ser un número',
            'price_retail.min' => 'El precio al menudeo no puede ser negativo',

            'price_wholesale.required' => 'El precio al mayoreo es obligatorio',
            'price_wholesale.numeric' => 'El precio al mayoreo debe ser un número',
            'price_wholesale.min' => 'El precio al mayoreo no puede ser negativo',

            'price_super_wholesale.required' => 'El precio super mayoreo es obligatorio',
            'price_super_wholesale.numeric' => 'El precio super mayoreo debe ser un número',
            'price_super_wholesale.min' => 'El precio super mayoreo no puede ser negativo',

            'cost.required' => 'El costo es obligatorio',
            'cost.numeric' => 'El costo debe ser un número',
            'cost.min' => 'El costo no puede ser negativo',

            'min_wholesale_qty.integer' => 'La cantidad mínima para mayoreo debe ser un número entero',
            'min_wholesale_qty.min' => 'La cantidad mínima para mayoreo debe ser al menos 1',

            'min_super_wholesale_qty.integer' => 'La cantidad mínima para super mayoreo debe ser un número entero',
            'min_super_wholesale_qty.min' => 'La cantidad mínima para super mayoreo debe ser al menos 1',
            'min_super_wholesale_qty.gt' => 'La cantidad mínima para super mayoreo debe ser mayor que la de mayoreo',

            'is_active.boolean' => 'El estado del producto debe ser verdadero o falso',
        ];
    }
}
