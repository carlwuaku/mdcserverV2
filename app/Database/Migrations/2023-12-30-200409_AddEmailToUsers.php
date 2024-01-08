<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailToUsers extends Migration
{
    public function up()
    {
        $table = "users";
        //
        if (!$this->db->fieldExists('email', $table))
         $this->forge->addColumn("users", [
            'email' => [
                'type' => 'VARCHAR',
                'null' => true,
                'default'=> null,
                'constraint' => 255,
                'unique' => true
            ],
         ]);
    }

    public function down()
    {
        //
    }
}
