<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveRoleIdFromUsers extends Migration
{
    public function up()
    {
        $this->forge->dropForeignKey('users', 'users_role_id_foreign');
        $this->forge->dropColumn('users', 'role_id');
    }

    public function down()
    {
        //
    }
}
