<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Codeigniter\Database\RawSql;
class HousemanshipPosting extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 9,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'unique' => true,
            ],
            'registration_number' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'default' => null
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ],
            'facility_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false,
            ],


            'start_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null
            ],
            'session' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null
            ],
            'year' => [
                'type' => 'YEAR',
                'constraint' => 4,
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
        $this->forge->addKey('registration_number', false, true);
        $this->forge->addKey('facility_id');
        $this->forge->addKey('type');
        $this->forge->addKey('category');
        $this->forge->addKey('year');

        $this->forge->createTable('housemanship_postings', true);

        $this->forge->addForeignKey('facility_id', 'housemanship_facilities', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('registration_number', 'practitioners', 'registration_number', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('housemanship_postings');


        $trigger = "
       CREATE TRIGGER before_insert_housemanship_postings
       BEFORE INSERT ON housemanship_postings
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        //
    }
}
