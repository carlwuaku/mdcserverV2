<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRevalidationToLicenses extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('last_revalidation_date', 'licenses')) {
            $this->forge->addColumn('licenses', [
                'last_revalidation_date' => [
                    'type' => 'DATE',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('require_revalidation', 'licenses')) {
            $this->forge->addColumn('licenses', [
                'require_revalidation' => [
                    'type' => 'ENUM',
                    'null' => false,
                    'default' => 'no',
                    'constraint' => ['yes', 'no'],
                ],
            ]);
        }
    }

    public function down()
    {
        //
    }
}
