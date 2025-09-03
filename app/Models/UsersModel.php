<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Shield\Models\UserModel;
use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Database\BaseBuilder;

class UsersModel extends UserModel implements TableDisplayInterface
{
    public $tableName = "users";
    public $role_name;
    public $region;
    public $position;
    public $picture;
    public $phone;
    public $email_address;

    public $status;
    public $username;

    public $uuid;

    public $user_type;

    public $two_fa_deadline;

    public $profile_table;
    public $profile_table_uuid;

    /**
     * data from the profile table for non-admin
     * @var object
     */
    public $profile_data;

    public function getDisplayColumns(): array
    {
        return [
            'username',
            'display_name',
            'status',
            'status_message',
            'user_type',
            'active',
            'last_active',
            'deleted_at',
            'role_name',
            'regionId',
            'position',
            'picture',
            'phone',
            'email_address',
            'google_authenticator_setup'
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    protected $allowedFields = [
        'username',
        'display_name',
        'status',
        'status_message',
        'active',
        'last_active',
        'deleted_at',
        'role_name',
        'regionId',
        'position',
        'picture',
        'phone',
        'two_fa_verification_token',
        'two_fa_setup_token',
        'google_auth_secret',
        'user_type',
        'two_fa_deadline',
        'profile_table',
        'profile_table_uuid',
        'email_address'
    ];

    protected $defaultProfileSelect = [
        'display_name',
        'role_name',
        'regionId',
        'position',
        'picture',
        'phone',
        'user_type',
        'two_fa_deadline',
        'profile_table',
        'profile_table_uuid',
        'email_address'
    ];

    protected $searchFields = [
        'display_name',
        'username',
        'position',
        'phone',
        'email_address'
    ];


    public function getUserProfile(string $userId)
    {

    }

    public $validationRules = [
        // "username" => "required|is_unique[users.username, id, {id}]",
        // "password" => "required",
        // "email" => "required|valid_email|is_unique[auth_identities.secret]",
        // "id" => "is_unique[users.id]"
    ];
    public function getPagination(?int $perPage = null): array
    {
        $this->builder()
            ->select('news.*, category.name')
            ->join('category', 'news.category_id = category.id');

        return [
            'news' => $this->paginate($perPage),
            'pager' => $this->pager,
        ];
    }

    /**
     * Retrieves distinct values from a given column in the database.
     *
     * @param string $column The name of the column to retrieve distinct values from.
     * @return array An array of distinct values from the specified column.
     */
    public function getDistinctValues(string $column): array
    {
        $query = $this->builder()->distinct()->select($column);
        return $query->get()->getResultArray();
    }

    public function getDistinctValuesAsKeyValuePairs(string $column): array
    {
        $results = $this->getDistinctValues($column); //[[$column=>"value1"], [$column=>"value2"]]
        $oneDimensionalArray = [];
        foreach ($results as $result) {
            $oneDimensionalArray[] = $result[$column];
        }
        //convert the results to a one-dimensional array of key-value pairs
        return $this->prepResultsAsValuesArray($oneDimensionalArray);
    }

    /**
     * A function that prepares the given results as an array of values.
     *
     * @param array $results The array of results to be prepared.
     * @return array The array of key-value pairs prepared from the results.
     */
    public function prepResultsAsValuesArray(array $results): array
    {
        $keyValuePairs = [];
        foreach ($results as $value) {
            $keyValuePairs[] = ["key" => $value, "value" => $value];
        }
        return $keyValuePairs;
    }

    public function getDisplayColumnFilters(): array
    {
        return [
            [
                "label" => "Search",
                "name" => "param",
                "type" => "text",
                "hint" => "Search names, emails, phone numbers, usernames",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Role",
                "name" => "role_name",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('role_name'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('status'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "User type",
                "name" => "user_type",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('user_type'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "2 factor authentication enabled",
                "name" => "google_auth_secret",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "key" => "Yes",
                        "value" => "--Not Null--"
                    ],
                    [
                        "key" => "No",
                        "value" => "--Null Or Empty--"
                    ],
                ],
                "value" => "",
                "required" => false,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ],
        ];
    }

    public function createArrayFromAllowedFields(array $data, bool $fillNull = false): array
    {
        $array = [];
        foreach ($this->allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($data[$field] === null && !$fillNull) {
                    continue;
                }
                $array[$field] = $data[$field] ?? null;
            }
        }
        return $array;
    }

    public function getNonAdminUserProfile(string $table, string $uuid)
    {
        $this->builder($table)
            ->select($this->defaultProfileSelect)
            ->where('uuid', $uuid);
        return $this->first();
    }

    public function search(string $searchString): BaseBuilder
    {
        try {
            // Sanitize the search string once
            $searchString = trim($searchString);
            $commaWords = array_filter(array_map('trim', explode(',', $searchString)));

            $builder = $this->db->table($this->table);

            // Prepare fields array
            $fields = [];
            $originalFields = $this->searchFields;
            foreach ($originalFields as $originalField) {
                $fields[] = str_contains($originalField, ".") ? $originalField : "$this->table.$originalField";
            }



            // Build search conditions
            if (!empty($commaWords)) {
                $builder->groupStart(); // Start main group (outermost)

                foreach ($commaWords as $commaIndex => $commaWord) {
                    // For the first comma-separated value, start directly
                    // For subsequent values, OR them with the previous ones
                    if ($commaIndex > 0) {
                        $builder->orGroupStart();
                    } else {
                        $builder->groupStart();
                    }

                    $spaceWords = array_filter(array_map('trim', explode(' ', $commaWord)));

                    foreach ($spaceWords as $spaceIndex => $spaceWord) {
                        // For first word, create a field OR group
                        // For subsequent words, AND them with the previous group
                        if ($spaceIndex === 0) {
                            // Create first term group
                            $builder->groupStart(); // Start the field OR group

                            foreach ($fields as $fieldIndex => $field) {
                                $escapedWord = $this->db->escapeLikeString($spaceWord);

                                if ($fieldIndex === 0) {
                                    $builder->like("LOWER($field)", strtolower($escapedWord), 'both', true, true);
                                } else {
                                    $builder->orLike("LOWER($field)", strtolower($escapedWord), 'both', true, true);
                                }
                            }

                            $builder->groupEnd(); // Close field OR group
                        } else {
                            // For subsequent words, AND a new field OR group
                            $builder->groupStart(); // Start the AND group

                            foreach ($fields as $fieldIndex => $field) {
                                $escapedWord = $this->db->escapeLikeString($spaceWord);

                                if ($fieldIndex === 0) {
                                    $builder->like("LOWER($field)", strtolower($escapedWord), 'both', true, true);
                                } else {
                                    $builder->orLike("LOWER($field)", strtolower($escapedWord), 'both', true, true);
                                }
                            }

                            $builder->groupEnd(); // Close the AND group
                        }
                    }

                    $builder->groupEnd(); // Close the group for this comma-separated term
                }

                $builder->groupEnd(); // Close the main search group
            }

            return $builder;
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            log_message("error", $th->getTraceAsString());
            throw $th;
        }
    }
}
