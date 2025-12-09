<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class PostgresBooleanCast implements CastsAttributes
{
    /**
     * Cast the given value (dari database ke aplikasi)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return bool
     */
    public function get($model, string $key, $value, array $attributes)
    {
        // PostgreSQL mengembalikan 't', 'f', 'true', 'false', 1, 0
        if (is_string($value)) {
            return in_array(strtolower($value), ['t', 'true', '1', 'yes']);
        }
        
        return (bool) $value;
    }

    /**
     * Prepare the given value for storage (dari aplikasi ke database)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, string $key, $value, array $attributes)
    {
        // Convert ke string 'true'/'false' untuk PostgreSQL dengan emulate prepares
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif ($value === 1 || $value === '1' || $value === 'true' || $value === 't') {
            return 'true';
        } else {
            return 'false';
        }
    }
}
