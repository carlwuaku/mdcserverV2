<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserFields extends Migration
{
    public function up()
    {
        $table = "users";
        //
        if (!$this->db->fieldExists('role_id', $table))
        $this->forge->addColumn("users", [
            'role_id' => [
                'type' => 'INT',
                'null' => false,
                'default'=> 1,
                'unsigned' => true,
            ],
         ]);

         if (!$this->db->fieldExists('regionId', $table))
         $this->forge->addColumn("users", [
            'regionId' => [
                'type' => 'INT',
                'null' => true,
                'default'=> null

            ],
         ]);

         if (!$this->db->fieldExists('position', $table))
         $this->forge->addColumn("users", [
            'position' => [
                'type' => 'VARCHAR',
                'null' => true,
                'default'=> null,
                'constraint' => 2500
            ],
         ]);

         if (!$this->db->fieldExists('picture', $table))
         $this->forge->addColumn("users", [
            'picture' => [
                'type' => 'VARCHAR',
                'null' => true,
                'default'=> null,
                'constraint' => 2500
            ],
         ]);

         if (!$this->db->fieldExists('phone', $table))
         $this->forge->addColumn("users", [
            'phone' => [
                'type' => 'VARCHAR',
                'null' => true,
                'default'=> null,
                'constraint' => 255
            ],
         ]);

         $this->forge->addForeignKey('role_id','roles', 'role_id','CASCADE','RESTRICT');
         $this->forge->processIndexes('users');


    }

    public function down()
    {
        //
    }
}
