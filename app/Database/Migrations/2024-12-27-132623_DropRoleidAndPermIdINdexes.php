<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropRoleidAndPermIdINdexes extends Migration
{
    public function up()
    {

        $this->forge->dropKey('role_permissions', 'permission_id_role_id');
        $this->forge->dropColumn('role_permissions', 'permission_id');
        $this->forge->dropColumn('role_permissions', 'role_id');
    }

    public function down()
    {
        //
    }
}
