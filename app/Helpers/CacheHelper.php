<?php

namespace App\Helpers;

use CodeIgniter\Cache\CacheInterface;
use Config\Services;

class CacheHelper
{
    private static ?CacheInterface $cache = null;
    
    /**
     * Default cache duration in seconds (1 hour)
     */
    private static int $defaultDuration = 3600;

    /**
     * Get the cache instance
     */
    private static function getCache(): CacheInterface
    {
        if (self::$cache === null) {
            self::$cache = Services::cache();
        }
        return self::$cache;
    }

    /**
     * Get an item from cache
     * 
     * @param string $key Cache key
     * @return mixed The cached value or null if not found
     */
    public static function get(string $key)
    {
        return self::getCache()->get($key);
    }

    /**
     * Save an item to cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $duration Time to live in seconds (null for default duration)
     * @return bool True on success, false on failure
     */
    public static function save(string $key, $value, ?int $duration = null): bool
    {
        return self::getCache()->save(
            $key,
            $value,
            $duration ?? self::$defaultDuration
        );
    }

    /**
     * Delete an item from cache
     * 
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public static function delete(string $key): bool
    {
        return self::getCache()->delete($key);
    }

    /**
     * Clear all items from cache
     * 
     * @return bool True on success, false on failure
     */
    public static function clear(): bool
    {
        return self::getCache()->clean();
    }

    /**
     * Get and cache value if not exists
     * 
     * @param string $key Cache key
     * @param callable $callback Function to generate value if not cached
     * @param int|null $duration Time to live in seconds (null for default duration)
     * @return mixed The cached or generated value
     */
    public static function remember(string $key, callable $callback, ?int $duration = null)
    {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::save($key, $value, $duration);
        
        return $value;
    }

    /**
     * Set default cache duration
     * 
     * @param int $seconds Duration in seconds
     */
    public static function setDefaultDuration(int $seconds): void
    {
        self::$defaultDuration = $seconds;
    }
} 