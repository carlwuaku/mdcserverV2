<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class PractitionerPortalEdits extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([
            
            'uuid' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'unique' => true,
                'default' => new RawSql('UUID()'),
            ],
            'registration_number'=> [
                'type' => 'VARCHAR',
               'constraint' => '100',
               'null' => false
            ],
            'field'=> [
                'type' => 'VARCHAR',
               'constraint' => '150',
               'null' => false
            ],
            'value'=> [
                'type' => 'VARCHAR',
               'constraint' => '1000',
               'null' => false
            ],
            'date'=> [
                'type' => 'TIMESTAMP',
               'null' => false,
               'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'status'=> [
                'type' => 'VARCHAR',
               'constraint' => '100',
               'null' => false,
               'default'=> 'pending',
            ],
            'admin_date'=> [
                'type' => 'DATE',
               'null' => true,
               'default'=> null,
            ],
            'action'=> [
                'type' => 'VARCHAR',
               'constraint' => '100',
               'null' => false,
               'default'=> 'change'
            ],
            'comment'=> [
                'type' => 'VARCHAR',
               'constraint' => '1500',
               'null' => true,
               'default'=> null,
            ],
            'admin_comment'=> [
                'type' => 'VARCHAR',
               'constraint' => '1500',
               'null' => true,
               'default'=> null,
            ],
        ]);
        $this->forge->addKey('registration_number', false);
        $this->forge->addKey('field', false);
        $this->forge->addKey('value', false);
        $this->forge->addKey('date', false);
        $this->forge->addKey('status', false);
        $this->forge->addKey('admin_date', false);
        $this->forge->addKey('action', false);
        $this->forge->addKey('comment', false);
        $this->forge->addKey('admin_comment', false);
        $this->forge->addForeignKey('registration_number', 'practitioners', 'registration_number', 'CASCADE', 'CASCADE');

        $this->forge->createTable('practitioner_portal_edits', true);
    }

    public function down()
    {
        $this->forge->dropTable('practitioner_portal_edits');
    }
}
