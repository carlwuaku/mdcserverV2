<?php

namespace App\Traits;

use App\Helpers\CacheHelper;

trait CacheInvalidatorTrait
{
    /**
     * Invalidate cache for a specific prefix
     * 
     * @param string $prefix The cache key prefix to invalidate
     */
    protected function invalidateCache(string $prefix): void
    {
        // Get all cache keys
        $cache = \Config\Services::cache();
        $keys = $cache->getCacheInfo();
        
        // If we have keys and they're in an array
        if (is_array($keys)) {
            foreach ($keys as $key => $value) {
                // If the key starts with our prefix, delete it
                if (strpos($key, $prefix) === 0) {
                    CacheHelper::delete($key);
                }
            }
        }
    }

    /**
     * Invalidate all cache entries
     */
    protected function invalidateAllCache(): void
    {
        CacheHelper::clear();
    }
} 