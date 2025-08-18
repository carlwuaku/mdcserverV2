<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOfflinePaymentDetailsTable extends Migration
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
            'payment_uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
            ],
            'bank_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'branch_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'deposit_slip_number' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'teller_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'verification_status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'verified', 'rejected'],
                'default' => 'pending',
                'null' => false,
            ],
            'verified_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'verified_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'verification_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');

        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('payment_uuid');
        $this->forge->addKey('verification_status');
        $this->forge->addForeignKey('payment_uuid', 'payments', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->createTable('offline_payment_details', true);

        // Add UUID trigger for detail_id
        $trigger = "
       CREATE TRIGGER before_insert_offline_payment_details
       BEFORE INSERT ON offline_payment_details
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        $this->forge->dropTable('offline_payment_details');
    }
}
