<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueToRolePermissions extends Migration
{
    public function up()
    {
        //
        $this->forge->addKey(['permission_id', 'role_id'], false, true);
        $this->forge->processIndexes('role_permissions');
    }

    public function down()
    {
        //
    }
}
