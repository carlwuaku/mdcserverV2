<?php

namespace App\Models\ApiIntegration;

use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Interfaces\TableDisplayInterface;
use App\Models\MyBaseModel;
use CodeIgniter\Database\BaseBuilder;

class ApiKeyModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'api_keys';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'institution_id',
        'name',
        'key_id',
        'key_secret_hash',
        'hmac_secret',
        'last_4_secret',
        'status',
        'expires_at',
        'last_used_at',
        'last_used_ip',
        'rate_limit_per_minute',
        'rate_limit_per_day',
        'scopes',
        'allowed_endpoints',
        'metadata',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'created_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'scopes' => 'json',
        'allowed_endpoints' => 'json',
        'metadata' => 'json',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'institution_id' => 'required|max_length[36]',
        'name' => 'required|max_length[255]',
        'key_id' => 'required|max_length[64]|is_unique[api_keys.key_id,id,{id}]',
        'key_secret_hash' => 'required|max_length[255]',
        'last_4_secret' => 'required|exact_length[4]',
        'status' => 'permit_empty|in_list[active,revoked,expired]',
        'rate_limit_per_minute' => 'permit_empty|integer',
        'rate_limit_per_day' => 'permit_empty|integer',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    public $searchFields = [
        'name',
        'key_id',
        'last_4_secret',
    ];

    public function getDisplayColumns(): array
    {
        return [
            'institution_name',
            'name',
            'key_id',
            'last_4_secret',
            'status',
            'rate_limit_per_minute',
            'rate_limit_per_day',
            'last_used_at',
            'expires_at',
            'created_at',
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [
            'institution_name' => 'Institution',
            'name' => 'Key Name',
            'key_id' => 'Key ID',
            'last_4_secret' => 'Secret (Last 4)',
            'rate_limit_per_minute' => 'Rate Limit (Per Min)',
            'rate_limit_per_day' => 'Rate Limit (Per Day)',
            'last_used_at' => 'Last Used',
            'expires_at' => 'Expires',
            'created_at' => 'Created',
        ];
    }

    public function getDisplayColumnFilters(): array
    {
        return [
            [
                "label" => "Institution",
                "name" => "institution_id",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "api-integration/institutions",
                "apiKeyProperty" => "id",
                "apiLabelProperty" => "name",
                "apiType" => "search",
            ],
            [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "",
                "options" => [
                    ["key" => "Active", "value" => "active"],
                    ["key" => "Revoked", "value" => "revoked"],
                    ["key" => "Expired", "value" => "expired"],
                ],
                "value" => "",
                "required" => false,
            ],
        ];
    }

    public function getFormFields(): array
    {
        return [
            [
                "label" => "Institution",
                "name" => "institution_id",
                "type" => "api",
                "hint" => "Select the institution for this API key",
                "options" => [],
                "value" => "",
                "required" => true,
                "api_url" => "api-integration/institutions",
                "apiKeyProperty" => "id",
                "apiLabelProperty" => "name",
                "apiType" => "search",
                "showOnly" => false,
            ],
            [
                "label" => "Key Name",
                "name" => "name",
                "type" => "text",
                "hint" => "Friendly name for this API key (e.g., 'Production Key', 'Test Environment')",
                "options" => [],
                "value" => "",
                "required" => true,
                "showOnly" => false,
            ],
            [
                "label" => "Rate Limit (Per Minute)",
                "name" => "rate_limit_per_minute",
                "type" => "number",
                "hint" => "Maximum requests per minute",
                "options" => [],
                "value" => "60",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Rate Limit (Per Day)",
                "name" => "rate_limit_per_day",
                "type" => "number",
                "hint" => "Maximum requests per day",
                "options" => [],
                "value" => "10000",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Expires At",
                "name" => "expires_at",
                "type" => "datetime-local",
                "hint" => "Optional expiration date for this key",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Scopes/Permissions",
                "name" => "scopes",
                "type" => "json",
                "hint" => "JSON array of allowed permissions. Leave empty to configure later.",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Allowed Endpoints",
                "name" => "allowed_endpoints",
                "type" => "json",
                "hint" => "JSON array of allowed endpoint patterns. Leave empty to allow all endpoints.",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Metadata",
                "name" => "metadata",
                "type" => "json",
                "hint" => "Optional JSON metadata (environment, purpose, etc.)",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
        ];
    }

    /**
     * Add custom fields with institution name
     */
    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        $institutionModel = new InstitutionModel();
        $builder->select("{$this->table}.*, {$institutionModel->table}.name as institution_name")
            ->join($institutionModel->table, "{$this->table}.institution_id = {$institutionModel->table}.id", "left");
        return $builder;
    }

    /**
     * Find API key by key_id
     */
    public function findByKeyId(string $keyId)
    {
        return $this->where('key_id', $keyId)
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->first();
    }

    /**
     * Revoke an API key
     */
    public function revokeKey(string $id, string $reason, ?int $revokedBy = null): bool
    {
        $data = [
            'status' => 'revoked',
            'revoked_at' => date('Y-m-d H:i:s'),
            'revocation_reason' => $reason,
        ];

        if ($revokedBy) {
            $data['revoked_by'] = $revokedBy;
        }

        return $this->update($id, $data);
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(string $id, string $ipAddress): bool
    {
        return $this->update($id, [
            'last_used_at' => date('Y-m-d H:i:s'),
            'last_used_ip' => $ipAddress,
        ]);
    }

    /**
     * Check if key is expired
     */
    public function isExpired(array $apiKey): bool
    {
        if (empty($apiKey['expires_at'])) {
            return false;
        }

        return strtotime($apiKey['expires_at']) < time();
    }

    /**
     * Get API keys for an institution
     */
    public function getByInstitution(string $institutionId)
    {
        return $this->where('institution_id', $institutionId)
            ->where('deleted_at', null)
            ->findAll();
    }
}
