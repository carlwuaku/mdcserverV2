<?php
namespace App\Helpers\Types;
class ExaminationLetterCriteriaType
{


    public function __construct(
        public int $letterId,
        public string $field,
        public array $value,
        public string $createdAt

    ) {
        if (!is_array($this->value)) {
            throw new \InvalidArgumentException('Value must be an array');
        }
        if (empty($this->field)) {
            throw new \InvalidArgumentException('Field cannot be empty');
        }
        if (empty($this->createdAt)) {
            $this->createdAt = date('Y-m-d H:i:s'); // Default to current time if not provided
        }
    }

    public function toArray(): array
    {
        return [
            'letter_id' => $this->letterId,
            'field' => $this->field,
            'value' => $this->value,
            'created_at' => $this->createdAt
        ];
    }

}