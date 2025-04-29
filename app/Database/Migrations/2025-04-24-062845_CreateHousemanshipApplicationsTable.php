<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class CreateHousemanshipApplicationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'unique' => true
            ],
            'license_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'date' => [
                'type' => 'DATE',
                'null' => false,
            ],

            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),

            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
            ],
            'year' => [
                'type' => 'INT',
                'constraint' => 4,
                'null' => true,
                'default' => null,
            ],
            'session' => [
                'type' => 'VARCHAR',
                'constraint' => 11,
                'null' => true,
                'default' => null,
            ],
            'tags' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Comma separated tags for filtering. e.g. direct entry,...'
            ],
        ]);

        $this->forge->addKey('license_number', false);
        $this->forge->addKey('date', false);
        $this->forge->addKey('created_at', false);
        $this->forge->addKey('deleted_at', false);
        $this->forge->addKey('category', false);
        $this->forge->addKey('type', false);
        $this->forge->addKey('year', false);
        $this->forge->addKey('session', false);
        $this->forge->addKey('tags', false);
        $this->forge->addForeignKey('license_number', 'licenses', 'license_number', 'CASCADE', 'RESTRICT');
        $this->forge->addKey('id', true);
        $this->forge->createTable('housemanship_postings_applications');
    }

    public function down()
    {
        //
    }
}
