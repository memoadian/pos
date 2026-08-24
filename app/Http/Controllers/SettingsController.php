<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $settings) {}

    public function edit()
    {
        return view('settings.edit', [
            'settings' => $this->settings->all(),
            'logoUrl' => $this->settings->logoUrl(),
        ]);
    }

    public function update(SettingsRequest $request)
    {
        $values = $request->safe()->only([
            'site_name',
            'primary_color',
            'business_name',
            'business_address',
            'business_phone',
            'business_tax_id',
            'ticket_footer',
            'currency_symbol',
        ]);

        if ($request->boolean('remove_logo')) {
            $this->deleteStoredLogo();
            $values['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            $this->deleteStoredLogo();
            $values['logo_path'] = $request->file('logo')->store('settings', 'public');
        }

        $this->settings->setMany($values);

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Configuración guardada exitosamente');
    }

    /**
     * El logo anterior no se deja huerfano en disco: se borra antes de
     * guardar uno nuevo o al quitarlo, para no acumular archivos sin uso.
     */
    private function deleteStoredLogo(): void
    {
        $current = $this->settings->get('logo_path');

        if ($current) {
            Storage::disk('public')->delete($current);
        }
    }
}
