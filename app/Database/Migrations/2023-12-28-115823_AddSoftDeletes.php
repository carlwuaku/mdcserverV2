<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeletes extends Migration
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
                  ],
               ]);
           }
           if (!$this->db->fieldExists('created_at', $table)) {
            $this->forge->addColumn($table, [
               'created_at' => [
                   'type' => 'DATETIME',
                   'null' => true,
               ],
            ]);
        }
       }
   }

   public function down()
   {
       // This is a destructive operation and should only be used in development environments
       // Get all table names
       $tables = $this->db->listTables();

       foreach ($tables as $table) {
           if ($this->db->fieldExists('deleted_at', $table)) {
               $this->forge->dropColumn($table, 'deleted_at');
           }

           if ($this->db->fieldExists('updated_at', $table)) {
               $this->forge->dropColumn($table, 'updated_at');
           }
       }
   }
}
