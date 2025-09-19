<?php
namespace App\Helpers\Types;
use DateTime;

class ApplicationFormTemplateType
{
    public ?string $uuid;
    public string $form_name;
    public ?string $description;
    public string $guidelines;
    public string $header;
    public string $footer;
    /**
     * List of form fields
     * @var FormFieldType[]
     */
    public array $data;
    public string $open_date;
    public string $close_date;
    public ?string $on_submit_email;
    public ?string $on_submit_message;
    public ?string $deleted_at;
    public ?string $updated_at;
    public ?string $created_on;
    public ?string $modified_on;
    public ?string $approve_url;
    public ?string $deny_url;
    /**
     * Summary of stages
     * @var ApplicationStageType[]
     */
    public array $stages;
    public string $initialStage;
    public string $finalStage;
    public ?string $picture;
    /**
     * Summary of restrictions
     * @var CriteriaType[]
     */
    public array $restrictions;
    public bool $available_externally;

    public function __construct(
        string $form_name,
        string $guidelines,
        string $header,
        string $footer,
        array $data,
        string $open_date,
        string $close_date,
        ?string $approve_url,
        ?string $deny_url,
        array $stages,
        string $initialStage,
        string $finalStage,
        ?string $picture,
        array $restrictions,
        bool $available_externally,
        ?string $uuid = null,
        ?string $description = null,
        ?string $on_submit_email = null,
        ?string $on_submit_message = null,
        ?string $deleted_at = null,
        ?string $updated_at = null,
        ?string $created_on = null,
        ?string $modified_on = null
    ) {
        $this->uuid = $uuid;
        $this->form_name = $form_name;
        $this->description = $description;
        $this->guidelines = $guidelines;
        $this->header = $header;
        $this->footer = $footer;
        $this->data = $data;
        $this->open_date = $open_date;
        $this->close_date = $close_date;
        $this->on_submit_email = $on_submit_email;
        $this->on_submit_message = $on_submit_message;
        $this->deleted_at = $deleted_at;
        $this->updated_at = $updated_at;
        $this->created_on = $created_on;
        $this->modified_on = $modified_on;
        $this->approve_url = $approve_url;
        $this->deny_url = $deny_url;
        $this->stages = $stages;
        $this->initialStage = $initialStage;
        $this->finalStage = $finalStage;
        $this->picture = $picture;
        $this->restrictions = $restrictions;
        $this->available_externally = $available_externally;
    }

    /**
     * Create an ApplicationFormTemplateType instance from an array
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            form_name: $data['form_name'],
            guidelines: $data['guidelines'],
            header: $data['header'],
            footer: $data['footer'],
            data: $data['data'] ?? [],
            open_date: $data['open_date'],
            close_date: $data['close_date'],
            approve_url: $data['approve_url'],
            deny_url: $data['deny_url'],
            stages: $data['stages'] ?? [],
            initialStage: $data['initialStage'],
            finalStage: $data['finalStage'],
            picture: $data['picture'],
            restrictions: $data['restrictions'] ?? [],
            available_externally: $data['available_externally'] ?? false,
            uuid: $data['uuid'] ?? null,
            description: $data['description'] ?? null,
            on_submit_email: $data['on_submit_email'] ?? null,
            on_submit_message: $data['on_submit_message'] ?? null,
            deleted_at: $data['deleted_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
            created_on: $data['created_on'] ?? null,
            modified_on: $data['modified_on'] ?? null
        );
    }

    /**
     * Convert the ApplicationFormTemplateType instance to an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'form_name' => $this->form_name,
            'description' => $this->description,
            'guidelines' => $this->guidelines,
            'header' => $this->header,
            'footer' => $this->footer,
            'data' => $this->data,
            'open_date' => $this->open_date,
            'close_date' => $this->close_date,
            'on_submit_email' => $this->on_submit_email,
            'on_submit_message' => $this->on_submit_message,
            'deleted_at' => $this->deleted_at,
            'updated_at' => $this->updated_at,
            'created_on' => $this->created_on,
            'modified_on' => $this->modified_on,
            'approve_url' => $this->approve_url,
            'deny_url' => $this->deny_url,
            'stages' => $this->stages,
            'initialStage' => $this->initialStage,
            'finalStage' => $this->finalStage,
            'picture' => $this->picture,
            'restrictions' => $this->restrictions,
            'available_externally' => $this->available_externally,
        ];
    }

    /**
     * Create an ApplicationFormTemplateType instance from JSON string
     * 
     * @param string $json
     * @return self
     * @throws JsonException
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }

    /**
     * Convert the ApplicationFormTemplateType instance to JSON string
     * 
     * @param int $flags JSON encode flags (default: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
     * @return string
     * @throws JsonException
     */
    public function toJson(int $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * Get form fields as objects if they are FormFieldType instances
     * 
     * @return FormFieldType[]
     */
    public function getFormFields(): array
    {
        return array_map(function ($field) {
            return is_array($field) && class_exists('FormFieldType')
                ? FormFieldType::fromArray($field)
                : $field;
        }, $this->data);
    }

    /**
     * Get stages as objects if they are ApplicationStageType instances
     * 
     * @return ApplicationStageType[]
     */
    public function getStages(): array
    {
        return array_map(function ($stage) {
            return is_array($stage) && class_exists('ApplicationStageType')
                ? ApplicationStageType::fromArray($stage)
                : $stage;
        }, $this->stages);
    }

    /**
     * Get restrictions as objects if they are CriteriaType instances
     * 
     * @return CriteriaType[]
     */
    public function getRestrictions(): array
    {
        return array_map(function ($restriction) {
            return is_array($restriction) && class_exists('CriteriaType')
                ? CriteriaType::fromArray($restriction)
                : $restriction;
        }, $this->restrictions);
    }

    /**
     * Check if the form is currently open for submissions
     * 
     * @return bool
     */
    public function isOpen(): bool
    {
        $now = new DateTime();
        $openDate = new DateTime($this->open_date);
        $closeDate = new DateTime($this->close_date);

        return $now >= $openDate && $now <= $closeDate && $this->deleted_at === null;
    }

    /**
     * Check if the form has been deleted
     * 
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    /**
     * Add a form field to the data array
     * 
     * @param FormFieldType|array $field
     * @return void
     */
    public function addFormField($field): void
    {
        $this->data[] = $field;
    }

    /**
     * Add a stage to the stages array
     * 
     * @param ApplicationStageType|array $stage
     * @return void
     */
    public function addStage($stage): void
    {
        $this->stages[] = $stage;
    }

    /**
     * Add a restriction to the restrictions array
     * 
     * @param CriteriaType|array $restriction
     * @return void
     */
    public function addRestriction($restriction): void
    {
        $this->restrictions[] = $restriction;
    }
}