<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCpdProviderUUID extends Migration
{
    public function up()
    {
        $this->forge->addColumn('cpd_providers', [
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'unique' => true
            ]
        ]);
        $trigger = "
       CREATE TRIGGER before_insert_cpd_provider
       BEFORE INSERT ON cpd_providers
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
