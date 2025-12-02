<?php

namespace App\Models;

use CodeIgniter\Model;

class AppSettingsOverridesModel extends Model
{
    protected $table = 'app_settings_overrides';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'setting_key',
        'setting_value',
        'value_type',
        'merge_strategy',
        'description',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'is_active' => 'boolean',
    ];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'setting_key' => 'required|max_length[255]|is_unique[app_settings_overrides.setting_key,id,{id}]',
        'value_type' => 'required|in_list[string,number,boolean,array,object]',
        'merge_strategy' => 'permit_empty|in_list[replace,merge,append,prepend]',
    ];
    protected $validationMessages = [
        'setting_key' => [
            'required' => 'Setting key is required',
            'is_unique' => 'This setting key already exists',
        ],
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = ['clearCache'];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = ['clearCache'];

    protected $searchFields = ['setting_key', 'description'];

    /**
     * Get an active override by key
     *
     * @param string $key The setting key
     * @return array|null The override data or null if not found
     */
    public function getActiveOverride(string $key): ?array
    {
        return $this->where('setting_key', $key)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Get all active overrides
     *
     * @return array Array of active overrides
     */
    public function getAllActiveOverrides(): array
    {
        return $this->where('is_active', 1)->findAll();
    }

    /**
     * Set or update an override
     *
     * @param string $key The setting key
     * @param mixed $value The value to set
     * @param string $valueType The type of value
     * @param string $mergeStrategy How to merge arrays/objects
     * @param string|null $description Optional description
     * @param string|null $userId User making the change
     * @return bool|int True/ID on success, false on failure
     */
    public function setOverride(string $key, $value, string $valueType, string $mergeStrategy = 'replace', ?string $description = null, ?string $userId = null)
    {
        $encodedValue = is_string($value) ? $value : json_encode($value);

        $existing = $this->where('setting_key', $key)->first();

        $data = [
            'setting_key' => $key,
            'setting_value' => $encodedValue,
            'value_type' => $valueType,
            'merge_strategy' => $mergeStrategy,
            'is_active' => 1,
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        if ($userId !== null) {
            $data[$existing ? 'updated_by' : 'created_by'] = $userId;
        }

        if ($existing) {
            return $this->update($existing['id'], $data);
        }

        return $this->insert($data);
    }

    /**
     * Remove an override (deactivate it)
     *
     * @param string $key The setting key
     * @return bool True on success
     */
    public function removeOverride(string $key): bool
    {
        $existing = $this->where('setting_key', $key)->first();

        if ($existing) {
            return $this->update($existing['id'], ['is_active' => 0]);
        }

        return true;
    }

    /**
     * Clear cache after updates/deletes
     *
     * @param array $data
     * @return array
     */
    protected function clearCache(array $data): array
    {
        cache()->delete('app_settings_overrides_all');

        // If we have the setting_key, delete its specific cache
        if (isset($data['data']['setting_key'])) {
            cache()->delete('app_setting_override_' . $data['data']['setting_key']);
        } elseif (isset($data['id'])) {
            // On delete, we have the ID but need to clear all cache
            $keys = array_column($data, 'setting_key');
            foreach ($keys as $key) {
                cache()->delete('app_setting_override_' . $key);
            }
        }

        return $data;
    }

    /**
     * Get display columns for table views
     *
     * @return array
     */
    public function getDisplayColumns(): array
    {
        return [
            'id',
            'setting_key',
            'setting_value',
            'value_type',
            'merge_strategy',
            'description',
            'is_active',
            'updated_at'
        ];
    }
}
