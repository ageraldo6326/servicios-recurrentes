<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanySettingController extends Controller
{
    public function edit()
    {
        $settings = CompanySetting::firstOrCreate(['id' => 1]);

        return view('settings.company', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = CompanySetting::firstOrCreate(['id' => 1]);
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
        ]);

        unset($data['logo']);
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company', 'public');
        }

        $settings->update($data);

        return back()->with('success', 'Configuración de empresa guardada.');
    }

    public function logo()
    {
        $settings = CompanySetting::query()->firstOrFail();
        abort_unless($settings->logo_path && Storage::disk('public')->exists($settings->logo_path), 404);

        return response()->file(Storage::disk('public')->path($settings->logo_path));
    }
}
