<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class HousemanshipMetadata extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'posting_id' => [
                'type' => 'INT',
                'constraint' => 9   ,
                'null' => false,
            ],
            'name' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ],


            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null
            ],


            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'modified_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('posting_id');
        $this->forge->addKey('name');
        $this->forge->addKey('value');

        $this->forge->createTable('housemanship_postings_metadata', true);
        $this->forge->addForeignKey('posting_id', 'housemanship_postings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('housemanship_postings_metadata');
    }

    public function down()
    {
        //
    }
}
