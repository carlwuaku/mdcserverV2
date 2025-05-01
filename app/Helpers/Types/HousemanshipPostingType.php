<?php
namespace App\Helpers\Types;
class HousemanshipPostingType
{
    public string $license_number;
    public string $type;
    public string $category;
    public string $session;
    public string $year;
    public string $letter_template;
    public string $tags;

    public array $details;

    public string $practitioner_details;

    public function __construct(
        string $license_number,
        string $type,
        string $category,
        string $session,
        string $year,
        string $letter_template,
        string $tags,
        string $practitioner_details,
        array $details
    ) {
        $this->license_number = $license_number;
        $this->type = $type;
        $this->category = $category;
        $this->session = $session;
        $this->year = $year;
        $this->letter_template = $letter_template;
        $this->tags = $tags;
        $this->practitioner_details = $practitioner_details;
        $this->details = $details;
    }

}