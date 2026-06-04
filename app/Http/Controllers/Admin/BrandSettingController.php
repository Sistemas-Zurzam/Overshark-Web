<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.brand.edit', [
            'brand' => BrandSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['nullable', 'required_without:icon', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
            'icon' => ['nullable', 'required_without:logo', 'file', 'mimes:png,ico,svg', 'max:2048'],
        ]);

        $brand = BrandSetting::current();

        foreach (['logo', 'icon'] as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $pathField = "{$field}_path";
            $newPath = $request->file($field)->store('branding', 'public');

            if ($brand->{$pathField}) {
                Storage::disk('public')->delete($brand->{$pathField});
            }

            $brand->{$pathField} = $newPath;
        }

        $brand->save();

        return back()->with('status', 'Identidad visual actualizada correctamente.');
    }
}
