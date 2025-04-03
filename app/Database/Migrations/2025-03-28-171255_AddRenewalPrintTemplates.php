<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalPrintTemplates extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('print_template', 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                'print_template' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'default' => null
                ],
            ]);
        }
        if (!$this->db->fieldExists('online_print_template', 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                'online_print_template' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'default' => null,
                    'collation' => 'utf8mb4_unicode_ci',
                ],
            ]);
        }
        if (!$this->db->fieldExists('in_print_queue', 'license_renewal')) {
            $this->forge->addColumn('license_renewal', [
                'in_print_queue' => [
                    'type' => 'BOOLEAN',
                    'null' => false,
                    'default' => 0
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('license_renewal', 'print_template');
        $this->forge->dropColumn('license_renewal', 'online_print_template');
        $this->forge->dropColumn('license_renewal', 'in_print_queue');
    }
}
