<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection as BasePostgresConnection;
use PDO;

class PostgresConnection extends BasePostgresConnection
{
    /**
     * Get the PDO connection options for PostgreSQL
     * Override to ensure emulated prepares are always used for Supabase
     */
    protected function getOptions(array $config)
    {
        $options = parent::getOptions($config);
        
        // Force emulated prepares for Supabase connection pooler compatibility
        $options[PDO::ATTR_EMULATE_PREPARES] = true;
        $options[PDO::ATTR_PERSISTENT] = false;
        
        return $options;
    }

    /**
     * Get a new PDO instance
     * Override to ensure proper configuration for Supabase
     */
    public function getPdo()
    {
        $pdo = parent::getPdo();
        
        // Check if PDO is already initialized (not a Closure)
        if ($pdo instanceof \PDO) {
            // Ensure emulated prepares is set (in case parent didn't apply it)
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $pdo->setAttribute(PDO::ATTR_PERSISTENT, false);
        }
        
        return $pdo;
    }

    /**
     * Get a new PDO instance for read operations
     * Override to ensure proper configuration for Supabase
     */
    public function getReadPdo()
    {
        $pdo = parent::getReadPdo();
        
        // Check if PDO is already initialized (not a Closure) and not null
        if ($pdo !== null && $pdo instanceof \PDO) {
            // Ensure emulated prepares is set for read connection too
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $pdo->setAttribute(PDO::ATTR_PERSISTENT, false);
        }
        
        return $pdo;
    }
}

