<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMoreFieldsToPractitionerRenewal extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('category', 'practitioners_renewal')) {
            $this->forge->addColumn('practitioners_renewal', [
                'category' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('sex', 'practitioners_renewal')) {
            $this->forge->addColumn('practitioners_renewal', [
                'sex' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('practitioner_type', 'practitioners_renewal')) {
            $this->forge->addColumn('practitioners_renewal', [
                'practitioner_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('register_type', 'practitioners_renewal')) {
            $this->forge->addColumn('practitioners_renewal', [
                'register_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
        if (!$this->db->fieldExists('qualifications', 'practitioners_renewal')) {
            $this->forge->addColumn('practitioners_renewal', [
                'qualifications' => [
                    'type' => 'JSON',
                    'null' => true,
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
