<?php
namespace App\Helpers\Types;



class HousemanshipApplicationFormTagsType
{
    public string $name;
    public string $value;
    public bool $implicit;
    public string $description;

    /**
     * a list of criteria which must all be met for the alert to be shown
     * @var CriteriaType[]
     */
    public array $criteria;

    public function __construct(
        string $name = '',
        string $value = '',
        bool $implicit = false,
        string $description = '',
        array $criteria = []
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->implicit = $implicit;
        $this->description = $description;
        $this->criteria = $criteria;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'implicit' => $this->implicit,
            'description' => $this->description,
            'criteria' => $this->criteria
        ];
    }

    public static function fromArray(array $data): self
    {
        $alertCriteria = [];
        if (isset($data['criteria']) && is_array($data['criteria'])) {
            foreach ($data['criteria'] as $criteriaData) {
                $alertCriteria[] = CriteriaType::fromArray($criteriaData);
            }
        }
        return new self(
            $data['name'] ?? '',
            $data['value'] ?? '',
            $data['implicit'] ?? false,
            $data['description'] ?? '',
            $alertCriteria
        );
    }
}

