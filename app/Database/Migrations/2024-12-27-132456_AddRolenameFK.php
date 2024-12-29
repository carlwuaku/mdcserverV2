<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRolenameFK extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('role', 'roles', 'role_name', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('role_permissions');


    }

    public function down()
    {
        //
    }
}
