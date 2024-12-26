<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCpdUUIdAndProviderFK extends Migration
{
    public function up()
    {
        $this->forge->addColumn('cpd_topics', [
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'unique' => true
            ]
        ]);
        $trigger = "
       CREATE TRIGGER before_insert_cpd_topic
       BEFORE INSERT ON cpd_topics
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;

        
       ";
        $this->db->query($trigger);

        $this->forge->addForeignKey('provider_id', 'cpd_providers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->processIndexes('cpd_topics');
    }

    public function down()
    {
        //
    }
}
