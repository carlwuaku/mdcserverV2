<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLetterTemplateToHousemanshipPosting extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('letter_template', 'housemanship_postings')) {
            $this->forge->addColumn('housemanship_postings', [
                'letter_template' => [
                    'type' => 'VARCHAR',
                    'null' => true,
                    'constraint' => 100,
                    'default' => null,
                ],
            ]);


        }
    }

    public function down()
    {
        //
    }
}
