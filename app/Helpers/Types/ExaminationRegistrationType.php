<?php
namespace App\Helpers\Types;
class ExaminationRegistrationScoreType
{
    public function __construct(
        public string $title,
        public string $score
    ) {

    }
}
class ExaminationRegistrationType
{


    public function __construct(
        public string $examId,
        public ?string $uuid,
        public string $internCode,
        public string $indexNumber,
        public ?string $result,
        public array $scores,
        public ?string $registrationLetter,
        public ?string $resultLetter,
        public ?string $publishResultDate,
        public ?string $createdAt,
    ) {

    }

    public function toArray(): array
    {
        return [
            'exam_id' => $this->examId,
            'uuid' => $this->uuid,
            'intern_code' => $this->internCode,
            'index_number' => $this->indexNumber,
            'result' => $this->result,
            'scores' => $this->scores,
            'registration_letter' => $this->registrationLetter,
            'result_letter' => $this->resultLetter,
            'publish_result_date' => $this->publishResultDate,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * create a registration object with its scores from a typical request object
     * @param object{exam_id:string, intern_code:string, index_number:string, result:string, scores:object{title:string, score:string}[], registration_letter: string, result_letter: string, publish_result_date: string} $object
     * @return ExaminationRegistrationType
     */
    public function createFromRequest($object)
    {

        $this->examId = $object->exam_id;
        $this->internCode = $object->intern_code;
        $this->indexNumber = $object->index_number;
        $this->result = $object->result;
        $this->scores = $object->scores;
        $this->registrationLetter = $object->registration_letter;
        $this->resultLetter = $object->result_letter;
        $this->publishResultDate = $object->publish_result_date;
        $this->createdAt = date('Y-m-d H:i:s'); // Default to current time if not provided

        if (isset($object->scores) && is_array($object->scores)) {
            foreach ($object->scores as $score) {
                $this->scores[] = new ExaminationRegistrationScoreType(
                    title: $score->title, // Placeholder, will be set later
                    score: $score->score
                );
            }
        }
        if (empty($this->exam_id)) {
            throw new \InvalidArgumentException('Exam id cannot be empty');
        }
        if (empty($this->intern_code)) {
            throw new \InvalidArgumentException('Intern code cannot be empty');
        }
        if (empty($this->index_number)) {
            throw new \InvalidArgumentException('Index number cannot be empty');
        }
        return $this;

    }

}