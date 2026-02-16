<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Helper Statique pour récupérer une valeur n'importe où dans le code.
     * Exemple : Setting::get('pause_forfaitaire', 30);
     */
    public static function get(string $key, $default = null)
    {
        // On utilise le cache pour éviter de taper la BDD à chaque calcul de pointage
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Helper pour mettre à jour et vider le cache
     */
    public static function set(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }
}