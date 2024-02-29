<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class CreateActivities extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([
            
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
                
            ],
            'activity'=> [
                'type' => 'TEXT',
               'null' => false
            ],
            'module'=> [
                'type' => 'TEXT',
               'null' => false,
               'default' => 'General'
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
               'null' => true,
               'default' => null
            ],
            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('user_id', false);
        $this->forge->addKey('activity', false);
        $this->forge->addKey('created_on', false);

        $this->forge->createTable('activities', true);
    }

    public function down()
    {
        //
    }
}
