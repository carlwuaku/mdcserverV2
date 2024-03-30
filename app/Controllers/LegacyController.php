<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class LegacyController extends BaseController
{

    public function getPractitionerDetails(){
        $legacy_db = db_connect('legacy');
        $builder = $legacy_db->table('bf_doctors');
        $builder->countAll();
        echo print_r($builder->getCompiledSelect(), true);
    }
}
