<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentMethodsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 20,
                'AUTO_INCREMENT' => true
            ],
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'method_code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'method_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'method_type' => [
                'type' => 'ENUM',
                'constraint' => ['online', 'offline'],
                'null' => false,
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('method_code');
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('method_name');
        $this->forge->createTable('payment_methods');

        // Add UUID trigger for method_id
        $this->db->query("ALTER TABLE payment_methods MODIFY uuid CHAR(36) DEFAULT (UUID())");
    }

    public function down()
    {
        $this->forge->dropTable('payment_methods');
    }
}
