<?php

namespace App\Traits;

trait PostgresBooleanCast
{
    /**
     * Override setAttribute untuk handle PostgreSQL boolean dengan PDO emulate prepares
     * 
     * Karena PDO::ATTR_EMULATE_PREPARES => true, boolean di-convert jadi integer.
     * PostgreSQL strict typing tidak menerima integer untuk kolom boolean.
     * Trait ini convert boolean ke string 'true'/'false' yang PostgreSQL terima.
     */
    public function setAttribute($key, $value)
    {
        // Get list of boolean fields from casts
        $booleanFields = collect($this->getCasts())
            ->filter(fn($cast) => in_array($cast, ['boolean', 'bool']))
            ->keys()
            ->toArray();

        // Convert boolean to PostgreSQL-compatible string
        if (in_array($key, $booleanFields)) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === 1 || $value === '1') {
                $value = 'true';
            } elseif ($value === 0 || $value === '0' || $value === null) {
                $value = 'false';
            }
        }
        
        return parent::setAttribute($key, $value);
    }
}
