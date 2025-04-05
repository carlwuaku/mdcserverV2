<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Add2FAToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('google_auth_secret', 'users')) {
            $this->forge->addColumn('users', [
                'google_auth_secret' => [
                    'type' => 'VARCHAR',
                    'null' => true,
                    'constraint' => 100,
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
