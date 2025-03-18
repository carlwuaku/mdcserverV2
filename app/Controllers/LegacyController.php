<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;


/**
 * @OA\Info(title="API Name", version="1.0")
 * @OA\Tag(name="Tag Name", description="Tag description")
 * @OA\Tag(
 *     name="Legacy",
 *     description="Operations for managing and viewing legacy data"
 * )
 */
class LegacyController extends BaseController
{

    public function getPractitionerDetails(){
        $legacy_db = db_connect('legacy');
        $builder = $legacy_db->table('bf_doctors');
        $builder->countAll();
        echo print_r($builder->getCompiledSelect(), true);
    }
}
