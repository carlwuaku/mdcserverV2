<?php
namespace App\Helpers\Types;
class ExaminationLetterType
{
    /**
     * @param ExaminationLetterCriteriaType[] $criteria
     */

    public function __construct(
        public ?string $examId,
        public string $name,
        public string $type,
        public string $content,
        public ?string $createdAt,
        public ?array $criteria = []
    ) {
        foreach ($this->criteria as $criterion) {

            if (!$criterion instanceof ExaminationLetterCriteriaType) {
                throw new \InvalidArgumentException('All criteria must be instances of ExaminationLetterCriteriaType');
            }
        }
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'exam_id' => $this->examId,
            'type' => $this->type,
            'content' => $this->content,
            'created_at' => $this->createdAt,
            'criteria' => array_map(fn($criterion) => $criterion->toArray(), $this->criteria)
        ];
    }

    /**
     * create a letter object with its criteria from a typical request object
     * @param object{name:string, type:string, content:string, criteria:array} $object
     * @return ExaminationLetterType
     */
    public function createFromRequest($object)
    {
        $this->name = $object->name;
        $this->type = $object->type;
        $this->content = $object->content;
        $this->examId = $object->exam_id ?? null; // Optional exam_id
        $this->createdAt = date('Y-m-d H:i:s'); // Default to current time if not provided

        if (isset($object->criteria) && is_array($object->criteria)) {
            foreach ($object->criteria as $criterion) {
                $this->criteria[] = new ExaminationLetterCriteriaType(
                    letterId: 0, // Placeholder, will be set later
                    field: $criterion->field,
                    value: $criterion->value,
                    createdAt: date('Y-m-d H:i:s') // Default to current time if not provided
                );
            }
        }
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        if (empty($this->type)) {
            throw new \InvalidArgumentException('Type cannot be empty');
        }
        if (empty($this->content)) {
            throw new \InvalidArgumentException('Content cannot be empty');
        }
        return $this;

    }

}