<?php

namespace App\Models\Practitioners;

use CodeIgniter\Database\BaseBuilder;
use App\Helpers\Interfaces\TableDisplayInterface;
use App\Models\MyBaseModel;
class PractitionerModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'practitioners';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        "first_name",
        "middle_name",
        "last_name",
        "email",
        "maiden_name",
        "registration_number",
        "sex",
        "registration_date",
        "title",
        "marital_status",
        "nationality",
        "postal_address",
        "residential_address",
        "hometown",
        "picture",
        "status",
        "date_of_birth",
        "provisional_number",
        "register_type",
        "specialty",
        "category",
        "place_of_birth",
        "qualification_at_registration",
        "training_institution",
        "qualification_date",
        "phone",
        "year_of_permanent",
        "year_of_provisional",
        "mailing_city",
        "mailing_region",
        "crime_details",
        "referee1_name",
        "referee1_phone",
        "referee1_email",
        "referee2_name",
        "referee2_phone",
        "referee2_email",
        "subspecialty",
        "region",
        "institution_type",
        "district",
        "town",
        "place_of_work",
        "portal_access",
        "portal_access_message",
        "college_membership"
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    public $renewalDate = "";
    public function setRenewalDate(string $date)
    {
        $this->renewalDate = $date;
    }


    public $searchFields = [
        "first_name",
        "last_name",
        "middle_name",
        "registration_number",
        "maiden_name",
        "email",
        "provisional_number"
    ];

    public function getDisplayColumns(): array
    {
        return [
            "picture",
            "registration_number",
            "first_name",
            "middle_name",
            "last_name",
            "in_good_standing",
            "status",
            "email",
            "phone",
            "maiden_name",
            "sex",
            "register_type",
            "category",
            "registration_date",
            "nationality",
            "date_of_birth",
            "provisional_number",
            "specialty",
            "subspecialty",
            "qualification_at_registration",
            "training_institution",
            "qualification_date",
            "year_of_permanent",
            "year_of_provisional",
            "college_membership",
            "deleted_at",
            "last_renewal_start",
            "last_renewal_expiry",
            "last_renewal_status",
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [
            "sex" => "Gender"
        ];
    }

    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        if($this->renewalDate === "") {
            $this->renewalDate = date("Y-m-d");
        }
        $practitionersTable = $this->table;

        $filteredColumns = [
            "registration_number", "first_name", "middle_name", "last_name", "email", "phone", "maiden_name",
            "sex", "register_type", "category", "registration_date", "nationality",
             "date_of_birth", "provisional_number", "specialty", "subspecialty",
            "qualification_at_registration", "training_institution", "qualification_date",
            "year_of_permanent", "year_of_provisional", "college_membership", "deleted_at","uuid",
            "practitioner_type","place_of_work","institution_type","region","district","title","postal_address",
            "status","last_renewal_start",
            "last_renewal_expiry",
            "last_renewal_status"
        ];
        $builder
        // ->join($renewalTable, "$practitionersTable.uuid = $renewalTable.practitioner_uuid", "left")
            ->select(implode(', ', array_map(function ($col) {
                return 'practitioners.' . $col;
            }, $filteredColumns)))
            ->select("(CASE  
            when last_renewal_status = 'Approved' and '$this->renewalDate' BETWEEN last_renewal_start AND last_renewal_expiry THEN 'In Good Standing'
            when last_renewal_status = 'Pending Payment' and '$this->renewalDate' BETWEEN last_renewal_start AND last_renewal_expiry THEN 'Pending Payment'
            when last_renewal_status = 'Pending Approval' and '$this->renewalDate' BETWEEN last_renewal_start AND last_renewal_expiry THEN 'Pending Approval'
             ELSE 'Not In Good Standing'
             END) as in_good_standing")
            
            ->select("CONCAT('" . base_url("file-server/image-render") . "','/practitioners_images/'," . "$practitionersTable.picture) as picture");
            // ->groupBy("$practitionersTable.uuid");
        return $builder;
    }

    /** this query works faster than the one above, but is buggy. if the year is not the current year it incorrectly 
     * returns "Not In Good Standing". this is because it does the query for each row in the renewal table. and 
     * since we limit it to 1 it only returns what the last renewal row says. so if that last renewal happens to contain 
     * the date that's being specified, it appears to work. however once you go back a year, and that year is not the 
     * last renewal, it returns "Not In Good Standing".
     */
    // public function addCustomFields(BaseBuilder $builder): BaseBuilder
    // {
    //     if ($this->renewalDate === "") {
    //         $this->renewalDate = date("Y-m-d");
    //     }
    //     $renewalTable = "practitioner_renewal";
    //     $practitionersTable = $this->table;

    //     $filteredColumns = [
    //         "registration_number",
    //         "first_name",
    //         "middle_name",
    //         "last_name",
    //         "email",
    //         "phone",
    //         "maiden_name",
    //         "sex",
    //         "register_type",
    //         "category",
    //         "registration_date",
    //         "nationality",
    //         "date_of_birth",
    //         "provisional_number",
    //         "specialty",
    //         "subspecialty",
    //         "qualification_at_registration",
    //         "training_institution",
    //         "qualification_date",
    //         "year_of_permanent",
    //         "year_of_provisional",
    //         "college_membership",
    //         "deleted_at",
    //         "uuid",
    //         "practitioner_type",
    //         "place_of_work",
    //         "institution_type",
    //         "region",
    //         "district",
    //         "title",
    //         "postal_address"
    //     ];
    //     $builder->join($renewalTable, "$practitionersTable.uuid = $renewalTable.practitioner_uuid", "left")
    //         ->select(implode(', ', array_map(function ($col) {
    //             return 'practitioners.' . $col;
    //         }, $filteredColumns)))
    //         ->select("COALESCE((SELECT 
    //         CASE 
    //             WHEN '$this->renewalDate' BETWEEN $renewalTable.year AND $renewalTable.expiry AND $renewalTable.status = 'Approved' THEN 'In Good Standing'
    //             WHEN '$this->renewalDate' BETWEEN $renewalTable.year AND $renewalTable.expiry AND $renewalTable.status = 'Pending Payment' THEN 'Pending Payment'
    //             WHEN '$this->renewalDate' BETWEEN $renewalTable.year AND $renewalTable.expiry AND $renewalTable.status = 'Pending Approval' THEN 'Pending Approval'
    //             ELSE 'Not In Good Standing'
    //         END
    //         FROM $renewalTable 
    //         WHERE $renewalTable.practitioner_uuid = $practitionersTable.uuid
    //          ORDER BY $renewalTable.year DESC LIMIT 1), 'Not In Good Standing') AS in_good_standing")
    //         //  ->select("(SELECT $renewalTable.uuid 
    //         // FROM $renewalTable 
    //         // WHERE $renewalTable.practitioner_uuid = $practitionersTable.uuid AND '$this->renewalDate' BETWEEN $renewalTable.year AND $renewalTable.expiry 
    //         // LIMIT 1) AS last_renewal_uuid")
    //         ->select("(CASE $practitionersTable.status when 1 THEN 'Alive'
    //         ELSE 'Deceased'
    //         END) as status")
    //         ->select("CONCAT('" . base_url("file-server/image-render") . "','/practitioners_images/'," . "$practitionersTable.picture) as picture")
    //         ->groupBy("$practitionersTable.uuid");
    //     return $builder;
    // }

    public function addLastRenewalField(BaseBuilder $builder): BaseBuilder{
        $practitionersTable = $this->table;
        $renewalTable = "practitioner_renewal";
        $builder->select("(SELECT $renewalTable.uuid 
             FROM $renewalTable 
             WHERE $renewalTable.practitioner_uuid = $practitionersTable.uuid AND '$this->renewalDate' BETWEEN $renewalTable.year AND $renewalTable.expiry 
             LIMIT 1) AS last_renewal_uuid");
            return $builder;
    }


    public function getTableName(): string
    {
        return $this->table;
    }

    public function getDisplayColumnFilters(): array{
        return [];
    }
}
