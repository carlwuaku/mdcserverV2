<?php

namespace App\Traits;

use App\Helpers\CacheHelper;

trait CacheInvalidatorTrait
{
    /**
     * Invalidate cache for a specific prefix
     *
     * @param string $prefix The cache key prefix to invalidate
     * @return int Number of cache entries deleted
     */
    protected function invalidateCache(string $prefix): int
    {
        try {
            $deletedCount = 0;

            // Get all cache keys
            $cache = \Config\Services::cache();
            $keys = $cache->getCacheInfo();

            // If we have keys and they're in an array
            if (is_array($keys)) {
                foreach ($keys as $key => $value) {
                    // If the key starts with our prefix, delete it
                    if (strpos($key, $prefix) === 0) {
                        if (CacheHelper::delete($key)) {
                            $deletedCount++;
                        }
                    }
                }

                // Log cache invalidation
                if ($deletedCount > 0) {
                    log_message('info', "Cache invalidated: {$deletedCount} entries with prefix '{$prefix}' deleted");
                }
            } else {
                // Some cache backends might not support getCacheInfo()
                // In that case, try clearing with a wildcard pattern
                log_message('warning', "Cache backend does not support getCacheInfo(). Unable to invalidate cache with prefix '{$prefix}'");
            }

            return $deletedCount;
        } catch (\Throwable $e) {
            log_message('error', "Error invalidating cache with prefix '{$prefix}': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Invalidate all cache entries
     *
     * @return bool True if successful, false otherwise
     */
    protected function invalidateAllCache(): bool
    {
        try {
            $result = CacheHelper::clear();
            log_message('info', 'All cache entries cleared');
            return $result;
        } catch (\Throwable $e) {
            log_message('error', 'Error clearing all cache: ' . $e->getMessage());
            return false;
        }
    }
} 