<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicensesTableKeys extends Migration
{
    public function up()
    {

        $this->forge->addKey('status', false, false, 'status_licenses');
        $this->forge->addKey('email', false, false, 'email_licenses');
        $this->forge->addKey('phone', false, false, 'phone_licenses');
        $this->forge->addKey('type', false, false, 'type_licenses');
        $this->forge->addForeignKey('region', 'regions', 'name', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('district', 'districts', 'district', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('licenses');
    }

    public function down()
    {
        //
    }
}
