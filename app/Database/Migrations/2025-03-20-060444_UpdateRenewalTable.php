<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateRenewalTable extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('data_snapshot', 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                'data_snapshot' => [
                    'type' => 'JSON',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
