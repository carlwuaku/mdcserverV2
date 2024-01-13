<?php

namespace App\Models;

use CodeIgniter\Model;

class PractitionerModel extends Model
{
    protected $table            = 'practitioners';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "first_name","middle_name","last_name","email","maiden_name","registration_number",
        "sex","registration_date","title","marital_status","nationality","postal_address","residential_address",
        "hometown","picture","status","date_of_birth", "provisional_number", "register_type", "specialty", "category",
        "place_of_birth","qualification_at_registration", "training_institution", "qualification_date", "phone",
        "year_of_permanent", "year_of_provisional","mailing_city",
         "mailing_region", "crime_details", "referee1_name",
        "referee1_phone", "referee1_email", "referee2_name",
        "referee2_phone", "referee2_email","subspecialty","region", 
        "institution_type","district","town","place_of_work","portal_access",
    "portal_access_message","college_membership"
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
