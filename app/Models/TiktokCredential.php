<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TiktokCredential extends Model
{
    protected $table = 'tiktok_credentials';

    protected $fillable = [
        'app_key',
        'app_secret',
        'shop_cipher',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'last_polled_at',
        'mode',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_polled_at' => 'datetime',
    ];

    /**
     * Ambil row tunggal (singleton). Buat kalau belum ada.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['mode' => config('tiktok.mode', 'mock')]);
    }

    // ---- Encrypted accessors/mutators ----
    public function setAppSecretAttribute(?string $value): void
    {
        $this->attributes['app_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAppSecretAttribute(?string $value): ?string
    {
        return $value ? $this->safeDecrypt($value) : null;
    }

    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute(?string $value): ?string
    {
        return $value ? $this->safeDecrypt($value) : null;
    }

    public function setRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute(?string $value): ?string
    {
        return $value ? $this->safeDecrypt($value) : null;
    }

    private function safeDecrypt(string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
