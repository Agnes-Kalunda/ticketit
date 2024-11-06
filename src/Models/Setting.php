<?php

namespace Ticket\Ticketit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    protected $table = 'ticketit_settings';
    protected $fillable = ['lang', 'slug', 'value', 'default'];

    /**
     * Get setting value by slug
     */
    public static function grab($slug, $default = null)
    {
        try {
            Log::info("Getting setting for slug: $slug");
            
            return Cache::remember("ticketit::settings.$slug", 60, function () use ($slug, $default) {
                $setting = static::where('slug', $slug)->first();
                
                if (!$setting) {
                    Log::warning("Setting not found for slug: $slug");
                    return $default;
                }

                // If we have a language key, translate it
                if ($setting->lang) {
                    return trans($setting->lang);
                }

                // If the value is serialized, unserialize it
                if (static::isSerialized($setting->value)) {
                    return unserialize($setting->value);
                }

                Log::info("Setting found", [
                    'slug' => $slug,
                    'value' => $setting->value
                ]);

                return $setting->value;
            });

        } catch (\Exception $e) {
            Log::error("Error getting setting for slug: $slug", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $default;
        }
    }

    /**
     * Check if a value is serialized
     */
    public static function isSerialized($data)
    {
        if (!is_string($data)) {
            return false;
        }

        $data = trim($data);

        if ('N;' === $data) {
            return true;
        }

        if (strlen($data) < 4) {
            return false;
        }

        if (':' !== $data[1]) {
            return false;
        }

        $lastchar = substr($data, -1);
        if (';' !== $lastchar && '}' !== $lastchar) {
            return false;
        }

        $token = $data[0];
        switch ($token) {
            case 's':
                if ('"' !== substr($data, -2, 1)) {
                    return false;
                }
                // Fall through
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                return (bool) preg_match("/^{$token}:[0-9.E-]+;$/", $data);
        }

        return false;
    }

    /**
     * Clear setting cache
     */
    public static function clearCache($slug = null)
    {
        if ($slug) {
            Cache::forget("ticketit::settings.$slug");
        } else {
            $settings = static::all();
            foreach ($settings as $setting) {
                Cache::forget("ticketit::settings.{$setting->slug}");
            }
        }
    }

    /**
     * Set a setting value
     */
    public static function set($slug, $value, $default = null)
    {
        try {
            $setting = static::where('slug', $slug)->first();
            
            if (!$setting) {
                $setting = static::create([
                    'slug' => $slug,
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'default' => is_array($default) ? json_encode($default) : ($default ?? $value)
                ]);
            } else {
                $setting->value = is_array($value) ? json_encode($value) : $value;
                $setting->save();
            }

            static::clearCache($slug);
            
            return $setting;

        } catch (\Exception $e) {
            Log::error("Error setting value for slug: $slug", [
                'error' => $e->getMessage(),
                'value' => $value
            ]);
            return false;
        }
    }
}