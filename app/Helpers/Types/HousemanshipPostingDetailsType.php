<?php
namespace App\Helpers\Types;

class HousemanshipPostingDetailsType
{
    public $posting_uuid;
    public $facility_name;
    public $facility_region;
    public $facility_details;
    public $discipline;
    public $start_date;
    public $end_date;

    public function __construct(
        string $posting_uuid,
        string $facility_name,
        string $facility_region,
        string $facility_details,
        string $discipline,
        ?string $start_date,
        ?string $end_date
    ) {
        $this->posting_uuid = $posting_uuid;
        $this->facility_name = $facility_name;
        $this->facility_region = $facility_region;
        $this->facility_details = $facility_details;
        $this->discipline = $discipline;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }
}