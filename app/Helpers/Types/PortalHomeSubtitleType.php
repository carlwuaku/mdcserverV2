<?php
namespace App\Helpers\Types;
class PortalHomeSubtitleType
{
    public string $field;
    public string $label;

    /**
     * a list of criteria which must all be met for the subtitle to be shown
     * @var CriteriaType[]
     */
    public array $criteria;

    public string $template;

    public function __construct(
        string $field = '',
        string $label = '',
        string $template = '',
        array $criteria = []
    ) {
        $this->field = $field;
        $this->label = $label;
        $this->template = $template;
        $this->criteria = $criteria;
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'label' => $this->label,
            'template' => $this->template,
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
            $data['field'] ?? '',
            $data['label'] ?? '',
            $data['template'] ?? '',
            $alertCriteria
        );
    }
}