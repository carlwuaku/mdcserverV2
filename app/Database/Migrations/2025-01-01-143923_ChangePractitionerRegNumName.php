<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangePractitionerRegNumName extends Migration
{
    public function up()
    {
        //delete the index for the registration_number
        try {
            $this->forge->dropForeignKey('housemanship_facility_preceptors', 'housemanship_facility_preceptors_registration_number_foreign');

        } catch (\Throwable $th) {
            //throw $th;
        }
        $this->forge->dropKey('practitioners', 'registration_number');
        $this->forge->modifyColumn('practitioners', [
            'registration_number' => [
                'name' => 'license_number',
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ]
        ]);
        $this->forge->addForeignKey('license_number', 'licenses', 'license_number', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('practitioners');
    }

    public function down()
    {
        //
    }
}
