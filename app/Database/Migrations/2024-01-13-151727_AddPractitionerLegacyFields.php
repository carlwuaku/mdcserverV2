<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPractitionerLegacyFields extends Migration
{
    public function up()
    {
        $fields = [
            'portal_access_message' => ['type' => 'VARCHAR', 'constraint' => '255','null' => true,
            'default' => null],
            'last_ip' => ['type' => 'VARCHAR','constraint' => '45','null' => true,
            'default' => null],
            'last_seen' => ['type' => 'VARCHAR','constraint' => '500','null' => true,
            'default' => null],
            'last_login' => ['type' => 'TIMESTAMP','null' => true,
            'default' => null],
            'password_hash' => ['type' => 'VARCHAR', 'constraint'=>'100','null' => true,
            'default' => null],
            'deleted_by' => ['type' => 'BIGINT', 'constraint'=>'20','null' => true,
            'default' => null],
            'modified_by' => ['type' => 'BIGINT', 'constraint'=>'20','null' => true,
            'default' => null],
            'created_by' => ['type' => 'BIGINT', 'constraint'=>'20','null' => true,
            'default' => null],
            'deleted' => ['type' => 'TINYINT', 'constraint'=>'4','null' => true,
            'default' => null],
        ];
        $this->forge->addColumn('practitioners', $fields);
    }

    public function down()
    {
        //
    }
}
