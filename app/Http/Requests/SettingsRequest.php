<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            // Formato hex estricto: es lo unico que ColorRamp sabe interpretar.
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:50'],
            'business_tax_id' => ['nullable', 'string', 'max:50'],
            'ticket_footer' => ['nullable', 'string', 'max:255'],
            'currency_symbol' => ['nullable', 'string', 'max:5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site_name.required' => 'El nombre del sitio es obligatorio',
            'primary_color.regex' => 'El color debe ser un hexadecimal valido (ej. #0891b2)',
            'logo.image' => 'El logo debe ser una imagen',
            'logo.mimes' => 'El logo debe ser PNG, JPG, WEBP o SVG',
            'logo.max' => 'El logo no puede pesar mas de 2MB',
        ];
    }
}
