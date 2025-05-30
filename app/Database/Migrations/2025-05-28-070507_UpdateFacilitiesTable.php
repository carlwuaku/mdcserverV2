<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateFacilitiesTable extends Migration
{
    public function up()
    {

        if (!$this->db->fieldExists("street", 'facilities')) {
            $this->forge->addColumn('facilities', [
                "street" => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ]
            ]);
        }
        if (!$this->db->fieldExists("parent_company", 'facilities')) {
            $this->forge->addColumn('facilities', [
                "parent_company" => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ]
            ]);
        }
        if (!$this->db->fieldExists("ghana_post_code", 'facilities')) {
            $this->forge->addColumn('facilities', [
                "ghana_post_code" => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ]
            ]);
        }
        if (!$this->db->fieldExists("cbd", 'facilities')) {
            $this->forge->addColumn('facilities', [
                "cbd" => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ]
            ]);
        }
        if (!$this->db->fieldExists("notes", 'facilities')) {
            $this->forge->addColumn('facilities', [
                "notes" => [
                    'type' => 'TEXT',
                    'null' => true,
                ]
            ]);
        }

        $this->forge->addKey("cbd", false, false, "facilities_cbd");
        $this->forge->addKey("ghana_post_code", false, false, "facilities_ghana_post_code");
        $this->forge->addKey("street", false, false, "facilities_street");


        $this->forge->processIndexes('facilities');

    }

    public function down()
    {
        //
    }
}
