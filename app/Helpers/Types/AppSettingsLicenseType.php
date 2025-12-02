<?php
namespace App\Helpers\Types;

class AppSettingsLicenseType
{
    public string $table;
    public string $uniqueKeyField;
    public array $selectionFields;
    public array $displayColumns;
    public array $fields;
    public array $onCreateValidation;
    public array $onUpdateValidation;
    public array $renewalFields;
    public array $implicitRenewalFields;
    public string $renewalTable;
    public array $renewalStages;
    public array $fieldsToUpdateOnRenewal;
    public array $basicStatisticsFields;
    public array $canApplyForRenewalCriteria;
    public array $shouldApplyForRenewalCriteria;
    public bool $mustBeInGoodStandingToRenew;
    public float $renewalCpdCategory1Cutoff;
    public float $renewalCpdCategory2Cutoff;
    public float $renewalCpdCategory3Cutoff;
    public float $renewalCpdTotalCutoff;
    public int $daysFromRenewalExpiryToOpenApplication;
    public int $revalidationPeriodInYears;
    public string $revalidationMessage;
    public array $basicStatisticsFilterFields;
    public array $advancedStatisticsFields;
    public array $renewalFilterFields;
    public array $renewalBasicStatisticsFields;
    public array $renewalSearchFields;
    public array $gazetteTableColumns;
    public array $renewalJsonFields;
    /**
     * fields that the portal should show in the renewal form. this is different from the 'renewalFields' array, which is used for the renewal form in the admin side
     * @var FormFieldType[]
     */
    public array $portalRenewalFields;
    /**
     * an array of fields that should be prepopulated using existing data. this can be used together with the 'readonly' type on a field that should be prepopulated but not editable
     * @var string[]
     */
    public array $portalRenewalFieldsPrePopulate;

    public array $searchFields;
    /**
     * a list of actions a user can perform from the portal
     * @var string[]
     */
    public array $userActions;

    public array $licenseNumberFormat;
    public array $validRenewalStatuses;

    public function __construct(
        string $table = '',
        string $uniqueKeyField = '',
        array $selectionFields = [],
        array $displayColumns = [],
        array $fields = [],
        array $onCreateValidation = [],
        array $onUpdateValidation = [],
        array $renewalFields = [],
        array $implicitRenewalFields = [],
        string $renewalTable = '',
        array $renewalStages = [],
        array $fieldsToUpdateOnRenewal = [],
        array $basicStatisticsFields = [],
        array $canApplyForRenewalCriteria = [],
        array $shouldApplyForRenewalCriteria = [],
        bool $mustBeInGoodStandingToRenew = false,
        float $renewalCpdCategory1Cutoff = 0.0,
        float $renewalCpdCategory2Cutoff = 0.0,
        float $renewalCpdCategory3Cutoff = 0.0,
        float $renewalCpdTotalCutoff = 0.0,
        int $daysFromRenewalExpiryToOpenApplication = 0,
        int $revalidationPeriodInYears = 0,
        string $revalidationMessage = '',
        array $basicStatisticsFilterFields = [],
        array $advancedStatisticsFields = [],
        array $renewalFilterFields = [],
        array $renewalBasicStatisticsFields = [],
        array $renewalSearchFields = [],
        array $gazetteTableColumns = [],
        array $renewalJsonFields = [],
        array $userActions = [],
        array $searchFields = [],
        array $portalRenewalFields = [],
        array $portalRenewalFieldsPrePopulate = [],
        array $validRenewalStatuses = []
    ) {
        $this->table = $table;
        $this->uniqueKeyField = $uniqueKeyField;
        $this->selectionFields = $selectionFields;
        $this->displayColumns = $displayColumns;
        $this->fields = $fields;
        $this->onCreateValidation = $onCreateValidation;
        $this->onUpdateValidation = $onUpdateValidation;
        $this->renewalFields = $renewalFields;
        $this->implicitRenewalFields = $implicitRenewalFields;
        $this->renewalTable = $renewalTable;
        $this->renewalStages = $renewalStages ?? [];
        $this->fieldsToUpdateOnRenewal = $fieldsToUpdateOnRenewal;
        $this->basicStatisticsFields = $basicStatisticsFields;
        $this->canApplyForRenewalCriteria = $canApplyForRenewalCriteria;
        $this->shouldApplyForRenewalCriteria = $shouldApplyForRenewalCriteria;
        $this->mustBeInGoodStandingToRenew = $mustBeInGoodStandingToRenew;
        $this->renewalCpdCategory1Cutoff = $renewalCpdCategory1Cutoff;
        $this->renewalCpdCategory2Cutoff = $renewalCpdCategory2Cutoff;
        $this->renewalCpdCategory3Cutoff = $renewalCpdCategory3Cutoff;
        $this->renewalCpdTotalCutoff = $renewalCpdTotalCutoff;
        $this->daysFromRenewalExpiryToOpenApplication = $daysFromRenewalExpiryToOpenApplication;
        $this->revalidationPeriodInYears = $revalidationPeriodInYears;
        $this->revalidationMessage = $revalidationMessage;
        $this->basicStatisticsFilterFields = $basicStatisticsFilterFields;
        $this->advancedStatisticsFields = $advancedStatisticsFields;
        $this->renewalFilterFields = $renewalFilterFields;
        $this->renewalBasicStatisticsFields = $renewalBasicStatisticsFields;
        $this->renewalSearchFields = $renewalSearchFields;
        $this->gazetteTableColumns = $gazetteTableColumns;
        $this->renewalJsonFields = $renewalJsonFields;
        $this->userActions = $userActions;
        $this->searchFields = $searchFields;
        $this->portalRenewalFields = $portalRenewalFields;
        $this->portalRenewalFieldsPrePopulate = $portalRenewalFieldsPrePopulate;
        $this->validRenewalStatuses = $validRenewalStatuses;
    }

    /**
     * Create instance from array
     */
    public static function fromArray(array $data): self
    {
        $portalRenewalFields = [];
        if (isset($data['portalRenewalFields']) && is_array($data['portalRenewalFields'])) {
            foreach ($data['portalRenewalFields'] as $portalRenewalField) {
                $portalRenewalFields[] = FormFieldType::fromArray($portalRenewalField);
            }
        }
        return new self(
            $data['table'] ?? '',
            $data['uniqueKeyField'] ?? '',
            $data['selectionFields'] ?? [],
            $data['displayColumns'] ?? [],
            $data['fields'] ?? [],
            $data['onCreateValidation'] ?? [],
            $data['onUpdateValidation'] ?? [],
            $data['renewalFields'] ?? [],
            $data['implicitRenewalFields'] ?? [],
            $data['renewalTable'] ?? '',
            isset($data['renewalStages']) ? $data['renewalStages'] : [],
            $data['fieldsToUpdateOnRenewal'] ?? [],
            $data['basicStatisticsFields'] ?? [],
            $data['canApplyForRenewalCriteria'] ?? [],
            $data['shouldApplyForRenewalCriteria'] ?? [],
            $data['mustBeInGoodStandingToRenew'] ?? false,
            (float) ($data['renewalCpdCategory1Cutoff'] ?? 0.0),
            (float) ($data['renewalCpdCategory2Cutoff'] ?? 0.0),
            (float) ($data['renewalCpdCategory3Cutoff'] ?? 0.0),
            (float) ($data['renewalCpdTotalCutoff'] ?? 0.0),
            (int) ($data['daysFromRenewalExpiryToOpenApplication'] ?? 0),
            (int) ($data['revalidationPeriodInYears'] ?? 0),
            $data['revalidationMessage'] ?? '',
            $data['basicStatisticsFilterFields'] ?? [],
            $data['advancedStatisticsFields'] ?? [],
            $data['renewalFilterFields'] ?? [],
            $data['renewalBasicStatisticsFields'] ?? [],
            $data['renewalSearchFields'] ?? [],
            $data['gazetteTableColumns'] ?? [],
            $data['renewalJsonFields'] ?? [],
            $data['userActions'] ?? [],
            $data['searchFields'] ?? [],
            $portalRenewalFields ?? [],
            $data['portalRenewalFieldsPrePopulate'] ?? [],
            $data['validRenewalStatuses'] ?? []
        );
    }

    /**
     * Create instance from JSON string
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return self::fromArray($data);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'uniqueKeyField' => $this->uniqueKeyField,
            'selectionFields' => $this->selectionFields,
            'displayColumns' => $this->displayColumns,
            'fields' => $this->fields,
            'onCreateValidation' => $this->onCreateValidation,
            'onUpdateValidation' => $this->onUpdateValidation,
            'renewalFields' => $this->renewalFields,
            'implicitRenewalFields' => $this->implicitRenewalFields,
            'renewalTable' => $this->renewalTable,
            'renewalStages' => $this->renewalStages,
            'fieldsToUpdateOnRenewal' => $this->fieldsToUpdateOnRenewal,
            'basicStatisticsFields' => $this->basicStatisticsFields,
            'canApplyForRenewalCriteria' => $this->canApplyForRenewalCriteria,
            'shouldApplyForRenewalCriteria' => $this->shouldApplyForRenewalCriteria,
            'mustBeInGoodStandingToRenew' => $this->mustBeInGoodStandingToRenew,
            'renewalCpdCategory1Cutoff' => $this->renewalCpdCategory1Cutoff,
            'renewalCpdCategory2Cutoff' => $this->renewalCpdCategory2Cutoff,
            'renewalCpdCategory3Cutoff' => $this->renewalCpdCategory3Cutoff,
            'renewalCpdTotalCutoff' => $this->renewalCpdTotalCutoff,
            'daysFromRenewalExpiryToOpenApplication' => $this->daysFromRenewalExpiryToOpenApplication,
            'revalidationPeriodInYears' => $this->revalidationPeriodInYears,
            'revalidationMessage' => $this->revalidationMessage,
            'basicStatisticsFilterFields' => $this->basicStatisticsFilterFields,
            'advancedStatisticsFields' => $this->advancedStatisticsFields,
            'renewalFilterFields' => $this->renewalFilterFields,
            'renewalBasicStatisticsFields' => $this->renewalBasicStatisticsFields,
            'renewalSearchFields' => $this->renewalSearchFields,
            'gazetteTableColumns' => $this->gazetteTableColumns,
            'renewalJsonFields' => $this->renewalJsonFields,
            'userActions' => $this->userActions,
            'searchFields' => $this->searchFields,
            'portalRenewalFields' => $this->portalRenewalFields,
            'portalRenewalFieldsPrePopulate' => $this->portalRenewalFieldsPrePopulate,
            'validRenewalStatuses' => $this->validRenewalStatuses
        ];
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $flags = 0): string
    {
        $json = json_encode($this->toArray(), $flags);

        if ($json === false) {
            throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Convert to pretty formatted JSON
     */
    public function toPrettyJson(): string
    {
        return $this->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    // Getter methods
    public function getTable(): string
    {
        return $this->table;
    }
    public function getUniqueKeyField(): string
    {
        return $this->uniqueKeyField;
    }
    public function getSelectionFields(): array
    {
        return $this->selectionFields;
    }
    public function getDisplayColumns(): array
    {
        return $this->displayColumns;
    }
    public function getFields(): array
    {
        return $this->fields;
    }
    public function getOnCreateValidation(): array
    {
        return $this->onCreateValidation;
    }
    public function getOnUpdateValidation(): array
    {
        return $this->onUpdateValidation;
    }
    public function getRenewalFields(): array
    {
        return $this->renewalFields;
    }
    public function getImplicitRenewalFields(): array
    {
        return $this->implicitRenewalFields;
    }
    public function getRenewalTable(): string
    {
        return $this->renewalTable;
    }
    public function getRenewalStages(): array
    {
        return $this->renewalStages;
    }
    public function getFieldsToUpdateOnRenewal(): array
    {
        return $this->fieldsToUpdateOnRenewal;
    }
    public function getBasicStatisticsFields(): array
    {
        return $this->basicStatisticsFields;
    }
    public function getCanApplyForRenewalCriteria(): array
    {
        return $this->canApplyForRenewalCriteria;
    }
    public function getShouldApplyForRenewalCriteria(): array
    {
        return $this->shouldApplyForRenewalCriteria;
    }
    public function getMustBeInGoodStandingToRenew(): bool
    {
        return $this->mustBeInGoodStandingToRenew;
    }
    public function getRenewalCpdCategory1Cutoff(): float
    {
        return $this->renewalCpdCategory1Cutoff;
    }
    public function getRenewalCpdCategory2Cutoff(): float
    {
        return $this->renewalCpdCategory2Cutoff;
    }
    public function getRenewalCpdCategory3Cutoff(): float
    {
        return $this->renewalCpdCategory3Cutoff;
    }
    public function getRenewalCpdTotalCutoff(): float
    {
        return $this->renewalCpdTotalCutoff;
    }
    public function getDaysFromRenewalExpiryToOpenApplication(): int
    {
        return $this->daysFromRenewalExpiryToOpenApplication;
    }
    public function getRevalidationPeriodInYears(): int
    {
        return $this->revalidationPeriodInYears;
    }
    public function getRevalidationMessage(): string
    {
        return $this->revalidationMessage;
    }
    public function getBasicStatisticsFilterFields(): array
    {
        return $this->basicStatisticsFilterFields;
    }
    public function getAdvancedStatisticsFields(): array
    {
        return $this->advancedStatisticsFields;
    }
    public function getRenewalFilterFields(): array
    {
        return $this->renewalFilterFields;
    }
    public function getRenewalBasicStatisticsFields(): array
    {
        return $this->renewalBasicStatisticsFields;
    }
    public function getRenewalSearchFields(): array
    {
        return $this->renewalSearchFields;
    }
    public function getGazetteTableColumns(): array
    {
        return $this->gazetteTableColumns;
    }
    public function getRenewalJsonFields(): array
    {
        return $this->renewalJsonFields;
    }

    // Setter methods
    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }
    public function setUniqueKeyField(string $uniqueKeyField): self
    {
        $this->uniqueKeyField = $uniqueKeyField;
        return $this;
    }
    public function setSelectionFields(array $selectionFields): self
    {
        $this->selectionFields = $selectionFields;
        return $this;
    }
    public function setDisplayColumns(array $displayColumns): self
    {
        $this->displayColumns = $displayColumns;
        return $this;
    }
    public function setFields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }
    public function setOnCreateValidation(array $onCreateValidation): self
    {
        $this->onCreateValidation = $onCreateValidation;
        return $this;
    }
    public function setOnUpdateValidation(array $onUpdateValidation): self
    {
        $this->onUpdateValidation = $onUpdateValidation;
        return $this;
    }
    public function setRenewalFields(array $renewalFields): self
    {
        $this->renewalFields = $renewalFields;
        return $this;
    }
    public function setImplicitRenewalFields(array $implicitRenewalFields): self
    {
        $this->implicitRenewalFields = $implicitRenewalFields;
        return $this;
    }
    public function setRenewalTable(string $renewalTable): self
    {
        $this->renewalTable = $renewalTable;
        return $this;
    }
    public function setRenewalStages(array $renewalStages): self
    {
        $this->renewalStages = $renewalStages;
        return $this;
    }
    public function setFieldsToUpdateOnRenewal(array $fieldsToUpdateOnRenewal): self
    {
        $this->fieldsToUpdateOnRenewal = $fieldsToUpdateOnRenewal;
        return $this;
    }
    public function setBasicStatisticsFields(array $basicStatisticsFields): self
    {
        $this->basicStatisticsFields = $basicStatisticsFields;
        return $this;
    }
    public function setCanApplyForRenewalCriteria(array $canApplyForRenewalCriteria): self
    {
        $this->canApplyForRenewalCriteria = $canApplyForRenewalCriteria;
        return $this;
    }
    public function setShouldApplyForRenewalCriteria(array $shouldApplyForRenewalCriteria): self
    {
        $this->shouldApplyForRenewalCriteria = $shouldApplyForRenewalCriteria;
        return $this;
    }
    public function setMustBeInGoodStandingToRenew(bool $mustBeInGoodStandingToRenew): self
    {
        $this->mustBeInGoodStandingToRenew = $mustBeInGoodStandingToRenew;
        return $this;
    }
    public function setRenewalCpdCategory1Cutoff(float $renewalCpdCategory1Cutoff): self
    {
        $this->renewalCpdCategory1Cutoff = $renewalCpdCategory1Cutoff;
        return $this;
    }
    public function setRenewalCpdCategory2Cutoff(float $renewalCpdCategory2Cutoff): self
    {
        $this->renewalCpdCategory2Cutoff = $renewalCpdCategory2Cutoff;
        return $this;
    }
    public function setRenewalCpdCategory3Cutoff(float $renewalCpdCategory3Cutoff): self
    {
        $this->renewalCpdCategory3Cutoff = $renewalCpdCategory3Cutoff;
        return $this;
    }
    public function setRenewalCpdTotalCutoff(float $renewalCpdTotalCutoff): self
    {
        $this->renewalCpdTotalCutoff = $renewalCpdTotalCutoff;
        return $this;
    }
    public function setDaysFromRenewalExpiryToOpenApplication(int $daysFromRenewalExpiryToOpenApplication): self
    {
        $this->daysFromRenewalExpiryToOpenApplication = $daysFromRenewalExpiryToOpenApplication;
        return $this;
    }
    public function setRevalidationPeriodInYears(int $revalidationPeriodInYears): self
    {
        $this->revalidationPeriodInYears = $revalidationPeriodInYears;
        return $this;
    }
    public function setRevalidationMessage(string $revalidationMessage): self
    {
        $this->revalidationMessage = $revalidationMessage;
        return $this;
    }
    public function setBasicStatisticsFilterFields(array $basicStatisticsFilterFields): self
    {
        $this->basicStatisticsFilterFields = $basicStatisticsFilterFields;
        return $this;
    }
    public function setAdvancedStatisticsFields(array $advancedStatisticsFields): self
    {
        $this->advancedStatisticsFields = $advancedStatisticsFields;
        return $this;
    }
    public function setRenewalFilterFields(array $renewalFilterFields): self
    {
        $this->renewalFilterFields = $renewalFilterFields;
        return $this;
    }
    public function setRenewalBasicStatisticsFields(array $renewalBasicStatisticsFields): self
    {
        $this->renewalBasicStatisticsFields = $renewalBasicStatisticsFields;
        return $this;
    }
    public function setRenewalSearchFields(array $renewalSearchFields): self
    {
        $this->renewalSearchFields = $renewalSearchFields;
        return $this;
    }
    public function setGazetteTableColumns(array $gazetteTableColumns): self
    {
        $this->gazetteTableColumns = $gazetteTableColumns;
        return $this;
    }
    public function setRenewalJsonFields(array $renewalJsonFields): self
    {
        $this->renewalJsonFields = $renewalJsonFields;
        return $this;
    }

    /**
     * Magic method to handle dynamic property acdecess
     */
    public function __get(string $property)
    {
        $method = 'get' . ucfirst($property);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \InvalidArgumentException("Property '{$property}' does not exist");
    }

    /**
     * Magic method to handle dynamic property setting
     */
    public function __set(string $property, $value): void
    {
        $method = 'set' . ucfirst($property);
        if (method_exists($this, $method)) {
            $this->$method($value);
            return;
        }

        throw new \InvalidArgumentException("Property '{$property}' does not exist");
    }

    /**
     * Check if a property exists
     */
    public function __isset(string $property): bool
    {
        $method = 'get' . ucfirst($property);
        return method_exists($this, $method);
    }

    /**
     * Validate the configuration
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->table)) {
            $errors[] = 'Table name is required';
        }

        if (empty($this->uniqueKeyField)) {
            $errors[] = 'Unique key field is required';
        }

        // Add more validation rules as needed

        return $errors;
    }

    /**
     * Check if the configuration is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }


}