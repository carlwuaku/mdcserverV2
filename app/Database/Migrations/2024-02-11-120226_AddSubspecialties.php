<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSubspecialties extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'subspecialty' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'unique' => false,
            ],
            'specialty'=> [
                'type' => 'VARCHAR',
               'constraint' => 255,
               'null' => false
            ],
            'specialty_id'=> [
                'type' => 'INT',
               'constraint' => 9,
               'null' => false
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('specialty', false, false, 'specialty_key');
        $this->forge->addForeignKey('specialty', 'specialties', 'name', 'CASCADE', 'CASCADE');

        $this->forge->createTable('subspecialties', true, [
            'ENGINE' => 'InnoDB',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('subspecialties');
    }
}
