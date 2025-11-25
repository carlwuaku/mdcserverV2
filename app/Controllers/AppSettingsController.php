<?php

namespace App\Controllers;

use App\Helpers\Utils;
use App\Models\AppSettingsOverridesModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * App Settings Controller
 * Manages runtime overrides of app-settings.json values
 */
class AppSettingsController extends ResourceController
{
    protected $modelName = 'App\Models\AppSettingsOverridesModel';
    protected $format = 'json';

    /**
     * Get all app settings (base + overrides)
     * GET /api/app-settings
     */
    public function index()
    {
        try {
            // Get file-based settings
            $fileSettings = json_decode(file_get_contents(Utils::getAppSettingsFileName()), true);

            // Get all overrides
            $model = new AppSettingsOverridesModel();
            $overrides = $model->findAll();

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'fileSettings' => $fileSettings,
                    'overrides' => $overrides,
                ],
            ]);
        } catch (\Throwable $th) {
            log_message('error', 'Error fetching app settings: ' . $th->getMessage());
            return $this->failServerError('Failed to fetch app settings');
        }
    }

    /**
     * Get a specific setting by key
     * GET /api/app-settings/:key
     */
    public function show($key = null)
    {
        try {
            if (!$key) {
                return $this->failValidationErrors('Setting key is required');
            }

            // Get current effective value
            $effectiveValue = Utils::getAppSettings($key);

            // Get file value
            $fileSettings = json_decode(file_get_contents(Utils::getAppSettingsFileName()), true);
            $fileValue = $fileSettings[$key] ?? null;

            // Get override if exists
            $model = new AppSettingsOverridesModel();
            $override = $model->where('setting_key', $key)->first();

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'key' => $key,
                    'effectiveValue' => $effectiveValue,
                    'fileValue' => $fileValue,
                    'override' => $override,
                ],
            ]);
        } catch (\Throwable $th) {
            log_message('error', 'Error fetching setting: ' . $th->getMessage());
            return $this->failServerError('Failed to fetch setting');
        }
    }

    /**
     * Create or update a setting override
     * POST /api/app-settings
     */
    public function create()
    {
        try {
            $rules = [
                'setting_key' => 'required|max_length[255]',
                'setting_value' => 'required',
                'value_type' => 'required|in_list[string,number,boolean,array,object]',
                'description' => 'permit_empty|string',
            ];

            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $userId = auth('tokens')->id();

            $model = new AppSettingsOverridesModel();

            // Encode value if it's not a string
            $value = $data['setting_value'];
            if ($data['value_type'] !== 'string' && !is_string($value)) {
                $value = json_encode($value);
            }

            $result = $model->setOverride(
                $data['setting_key'],
                $value,
                $data['value_type'],
                $data['description'] ?? null,
                $userId
            );

            if ($result) {
                // Clear all app settings cache
                $this->clearSettingsCache();

                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'Setting override created successfully',
                    'data' => $model->where('setting_key', $data['setting_key'])->first(),
                ]);
            }

            return $this->failServerError('Failed to create setting override');
        } catch (\Throwable $th) {
            log_message('error', 'Error creating setting override: ' . $th->getMessage());
            return $this->failServerError('Failed to create setting override: ' . $th->getMessage());
        }
    }

    /**
     * Update a setting override
     * PUT /api/app-settings/:id
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('Setting ID is required');
            }

            $model = new AppSettingsOverridesModel();
            $existing = $model->find($id);

            if (!$existing) {
                return $this->failNotFound('Setting override not found');
            }

            $rules = [
                'setting_value' => 'permit_empty',
                'value_type' => 'permit_empty|in_list[string,number,boolean,array,object]',
                'description' => 'permit_empty|string',
                'is_active' => 'permit_empty|in_list[0,1]',
            ];

            if (!$this->validate($rules)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $data = $this->request->getJSON(true);
            $userId = auth('tokens')->id();
            $data['updated_by'] = $userId;

            // Encode value if needed
            if (isset($data['setting_value']) && isset($data['value_type']) && $data['value_type'] !== 'string') {
                if (!is_string($data['setting_value'])) {
                    $data['setting_value'] = json_encode($data['setting_value']);
                }
            }

            if ($model->update($id, $data)) {
                // Clear all app settings cache
                $this->clearSettingsCache();

                return $this->respond([
                    'status' => 'success',
                    'message' => 'Setting override updated successfully',
                    'data' => $model->find($id),
                ]);
            }

            return $this->failServerError('Failed to update setting override');
        } catch (\Throwable $th) {
            log_message('error', 'Error updating setting override: ' . $th->getMessage());
            return $this->failServerError('Failed to update setting override: ' . $th->getMessage());
        }
    }

    /**
     * Delete/deactivate a setting override
     * DELETE /api/app-settings/:id
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('Setting ID is required');
            }

            $model = new AppSettingsOverridesModel();
            $existing = $model->find($id);

            if (!$existing) {
                return $this->failNotFound('Setting override not found');
            }

            // Deactivate instead of deleting
            if ($model->update($id, ['is_active' => 0])) {
                // Clear all app settings cache
                $this->clearSettingsCache();

                return $this->respondDeleted([
                    'status' => 'success',
                    'message' => 'Setting override removed successfully',
                ]);
            }

            return $this->failServerError('Failed to remove setting override');
        } catch (\Throwable $th) {
            log_message('error', 'Error deleting setting override: ' . $th->getMessage());
            return $this->failServerError('Failed to remove setting override: ' . $th->getMessage());
        }
    }

    /**
     * Get all available keys from app-settings.json
     * GET /api/app-settings/keys
     */
    public function getAvailableKeys()
    {
        try {
            $fileSettings = json_decode(file_get_contents(Utils::getAppSettingsFileName()), true);
            $keys = $this->flattenKeys($fileSettings);

            return $this->respond([
                'status' => 'success',
                'data' => $keys,
            ]);
        } catch (\Throwable $th) {
            log_message('error', 'Error fetching available keys: ' . $th->getMessage());
            return $this->failServerError('Failed to fetch available keys');
        }
    }

    /**
     * Clear all settings cache
     * POST /api/app-settings/clear-cache
     */
    public function clearCache()
    {
        try {
            $this->clearSettingsCache();

            return $this->respond([
                'status' => 'success',
                'message' => 'Settings cache cleared successfully',
            ]);
        } catch (\Throwable $th) {
            log_message('error', 'Error clearing cache: ' . $th->getMessage());
            return $this->failServerError('Failed to clear cache');
        }
    }

    /**
     * Helper to flatten nested array keys
     */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value) && !empty($value)) {
                // Add the parent key
                $result[] = [
                    'key' => $fullKey,
                    'type' => $this->detectType($value),
                    'hasChildren' => true,
                ];

                // Recursively add child keys
                $childKeys = $this->flattenKeys($value, $fullKey);
                $result = array_merge($result, $childKeys);
            } else {
                $result[] = [
                    'key' => $fullKey,
                    'type' => $this->detectType($value),
                    'hasChildren' => false,
                ];
            }
        }

        return $result;
    }

    /**
     * Detect the type of a value
     */
    private function detectType($value): string
    {
        if (is_array($value)) {
            return empty($value) || array_keys($value) === range(0, count($value) - 1) ? 'array' : 'object';
        }
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_numeric($value)) {
            return 'number';
        }
        return 'string';
    }

    /**
     * Clear all settings-related caches
     */
    private function clearSettingsCache(): void
    {
        $cache = cache();

        // Clear main caches
        $cache->delete('app_settings_all_with_overrides');
        $cache->delete('app_settings_overrides_all');

        // Clear individual setting caches
        $fileSettings = json_decode(file_get_contents(Utils::getAppSettingsFileName()), true);
        foreach (array_keys($fileSettings) as $key) {
            $cache->delete('app_setting_' . $key);
            $cache->delete('app_setting_override_' . $key);
        }

        // Clear auth-related app settings caches
        $cache->delete('app_settings_guest');
        // We can't clear user-specific caches without knowing all user IDs,
        // but the 1-hour TTL will handle it
    }
}
