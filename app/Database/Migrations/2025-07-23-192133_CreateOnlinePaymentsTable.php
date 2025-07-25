<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOnlinePaymentsTable extends Migration
{
    public function up()
    {

        $this->forge->addField('id');
        $this->forge->addField([

            'collection_agent_branch_code' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => NULL
            ],
            'mda_branch_code' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => NULL
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => NULL
            ],
            'phone_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => NULL
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => NULL
            ],
            'application_id' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => NULL
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'invoice_items' => [
                'type' => 'JSON',
                'null' => true,
                'default' => NULL
            ],
            'redirect_url' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'post_url' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'response_status' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'response_message' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'invoice_expires' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => NULL
            ],
            'invoice_total_amounts' => [
                'type' => 'JSON',
                'null' => true,
                'default' => NULL
            ],
            'response' => [
                'type' => 'JSON',
                'null' => true,
                'default' => NULL
            ],
            'invoice_currencies' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => NULL
            ],
            'payment_qr_code' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => NULL
            ],
            'unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'purpose' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'year' => [
                'type' => 'YEAR',
                'null' => true,
                'default' => NULL
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
                'default' => 'Pending'
            ],
            'invoice_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'default' => NULL
            ],
            'origin' => [
                'type' => 'TEXT',
                'null' => false,
                'default' => ''
            ]
        ]);

        $this->forge->addKey('mda_branch_code', false);
        $this->forge->addKey('collection_agent_branch_code', false);
        $this->forge->addKey('first_name', false);
        $this->forge->addKey('last_name', false);
        $this->forge->addKey('phone_number', false);
        $this->forge->addKey('email', false);
        $this->forge->addKey('unique_id', false, true);
        $this->forge->addKey('created_on', false);
        $this->forge->addKey('purpose', false);
        $this->forge->addKey('year', false);
        $this->forge->addKey('status', false);
        $this->forge->addKey('invoice_number', false);

        $this->forge->addKey('id', true);

        $this->forge->createTable('online_payments', true, [
            'ENGINE' => 'InnoDB',
            'comment' => 'records for the ghana.gov payment platform'
        ]);
    }

    public function down()
    {
        //
    }
}
