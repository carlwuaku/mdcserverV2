<?php
namespace App\Helpers\Types;
class HousemanshipPostingApplicationRequestType
{
    public string $license_number;
    public string $application_uuid;
    public string $session;
    public string $year;
    public string $letter_template;
    public string $tags;
    /**
     * 
     * @var array<HousemanshipPostingDetailsType>
     */
    public array $details;

    public function __construct(
        string $license_number,
        string $session,
        string $year,
        string $letter_template,
        string $tags,
        array $details,
        string $application_uuid
    ) {
        $this->license_number = $license_number;

        $this->session = $session;
        $this->year = $year;
        $this->letter_template = $letter_template;
        $this->tags = $tags;
        $this->details = $details;
        $this->application_uuid = $application_uuid;
    }
}