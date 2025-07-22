<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeEmailInUsersTable extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('email', 'users')) {
            // Modify the name field to be NOT NULL
            $this->forge->modifyColumn('users', [
                'email' => [
                    'name' => 'email_address',
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false
                ]
            ]);
            $this->forge->addKey('email_address', false);
            $this->forge->processIndexes('users');
        } else {

            $this->forge->addColumn('users', [
                'email_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false
                ]
            ]);
            $this->forge->addKey('email_address', false);
            $this->forge->processIndexes('users');
        }
    }

    public function down()
    {
        //
    }
}
