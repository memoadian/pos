<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use App\Services\BranchContextService;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
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
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:efectivo,tarjeta,transferencia',
            'client_id' => 'nullable|exists:clients,id',
            'idempotency_key' => 'nullable|string|max:64',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->has('items')) {
                return;
            }

            $branchId = app(BranchContextService::class)->currentId();

            foreach ($this->input('items') as $index => $item) {
                $inventory = Inventory::where('product_id', $item['product_id'])
                    ->where('branch_id', $branchId)
                    ->first();

                if (!$inventory) {
                    $validator->errors()->add(
                        "items.{$index}.product_id",
                        'Este producto no tiene inventario en tu sucursal'
                    );
                    continue;
                }

                if ($inventory->stock_quantity < $item['quantity']) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Stock insuficiente. Disponible: {$inventory->stock_quantity}"
                    );
                }
            }
        });
    }

    /**
     * Get custom error messages for validator.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Debes agregar al menos un producto',
            'items.min' => 'Debes agregar al menos un producto',
            'items.*.product_id.required' => 'El producto es requerido',
            'items.*.product_id.exists' => 'El producto no existe',
            'items.*.quantity.required' => 'La cantidad es requerida',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0',
            'items.*.quantity.numeric' => 'La cantidad debe ser un número',
            'items.*.unit_price.required' => 'El precio es requerido',
            'items.*.unit_price.numeric' => 'El precio debe ser un número',
            'items.*.unit_price.min' => 'El precio no puede ser negativo',
            'payment_method.required' => 'El método de pago es requerido',
            'payment_method.in' => 'Método de pago inválido',
            'client_id.exists' => 'El cliente seleccionado no existe',
        ];
    }
}
