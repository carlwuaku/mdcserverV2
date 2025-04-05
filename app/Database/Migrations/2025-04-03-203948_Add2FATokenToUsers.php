<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Add2FATokenToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('two_fa_verification_token', 'users')) {
            $this->forge->addColumn('users', [
                'two_fa_verification_token' => [
                    'type' => 'VARCHAR',
                    'null' => true,
                    'constraint' => 255,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('two_fa_setup_token', 'users')) {
            $this->forge->addColumn('users', [
                'two_fa_setup_token' => [
                    'type' => 'VARCHAR',
                    'null' => true,
                    'constraint' => 255,
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
