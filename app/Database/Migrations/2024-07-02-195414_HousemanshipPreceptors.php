<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class HousemanshipPreceptors extends Migration
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
                'null' => false
            ],
            'facility_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ],
            'expiry_date' => [
                'type' => 'DATE',
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
        $this->forge->addKey('registration_number');
        $this->forge->addKey('facility_id');
        $this->forge->addKey('expiry_date');

        
        $this->forge->createTable('housemanship_facility_preceptors', true);
        
        $this->forge->addForeignKey('facility_id', 'housemanship_facilities', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('registration_number', 'practitioners', 'registration_number', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('housemanship_facility_preceptors');

        $trigger = "
       CREATE TRIGGER before_insert_housemanship_facility_preceptors
       BEFORE INSERT ON housemanship_facility_preceptors
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
