<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TiktokCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TiktokSettingController extends Controller
{
    public function edit(): View
    {
        $cred = TiktokCredential::current();
        return view('admin.tiktok.edit', compact('cred'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['mock', 'live'])],
            'app_key' => ['nullable', 'string', 'max:255'],
            'app_secret' => ['nullable', 'string', 'max:1024'],
            'shop_cipher' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string', 'max:2048'],
            'refresh_token' => ['nullable', 'string', 'max:2048'],
        ]);

        $cred = TiktokCredential::current();

        // Isi hanya yang dikirim non-empty, biar tidak nuke existing value
        foreach (['mode', 'app_key', 'shop_cipher'] as $plain) {
            if (!empty($data[$plain]) || $plain === 'mode') {
                $cred->{$plain} = $data[$plain];
            }
        }
        foreach (['app_secret', 'access_token', 'refresh_token'] as $secret) {
            if (!empty($data[$secret])) {
                $cred->{$secret} = $data[$secret];
            }
        }
        $cred->save();

        return redirect()->route('admin.tiktok.edit')->with('success', 'Kredensial TikTok disimpan.');
    }
}
