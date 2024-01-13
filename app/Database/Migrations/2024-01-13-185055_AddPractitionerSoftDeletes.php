<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddPractitionerSoftDeletes extends Migration
{
    public function up()
    {
        // Get all table names
       $tables = $this->db->listTables();

       foreach ($tables as $table) {
           if (!$this->db->fieldExists('deleted_at', $table)) {
               $this->forge->addColumn($table, [
                  'deleted_at' => [
                      'type' => 'DATETIME',
                      'null' => true,
                  ],
               ]);
           }

           if (!$this->db->fieldExists('updated_at', $table)) {
               $this->forge->addColumn($table, [
                  'updated_at' => [
                      'type' => 'DATETIME',
                      'null' => true,
                      'default' => new RawSql('CURRENT_TIMESTAMP'),
                      'on_update' => new RawSql('CURRENT_TIMESTAMP'),
                  
                  ],
               ]);
           }
           if (!$this->db->fieldExists('created_at', $table)) {
            $this->forge->addColumn($table, [
               'created_at' => [
                   'type' => 'DATETIME',
                   'null' => true,
                   'default' => new RawSql('CURRENT_TIMESTAMP'),               
               ],
            ]);
        }
       }
    }

    public function down()
    {
        //
    }
}
