<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;


class CpdTopics extends Migration
{

    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([

            'topic' => [
                'type' => 'VARCHAR',
                'constraint' => '1000',
                'null' => true,
            ],
            'date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'deleted' => [
                'type' => 'TINYINT',
                'constraint' => 4,
                'null' => true,
            ],
            'modified_on' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_on' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => true,
            ],
            'modified_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => true,
            ],
            'deleted_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => true,
            ],
            'provider_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'venue' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'group' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => NULL,
            ],
            'credits' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'category' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'category of the cpd, 1 or 2 or 3',
            ],
            'online' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
                'default' => 'No',
            ],
            'url' => [
                'type' => 'VARCHAR',
                'constraint' => '1000',
                'null' => true,
            ],
            'start_month' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => 'January',
            ],
            'end_month' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
                'default' => 'December',
            ],

        ]);


        $this->forge->createTable('cpd_topics', true, [
            'ENGINE' => 'InnoDB',
        ]);


    }

    public function down()
    {
        //
    }
}
