<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class SetDefaultPractitionerTableUUID extends Migration
{
    public function up()
   {
       // Get db instance
       $db = \Config\Database::connect();

       // Execute raw SQL statement
       $db->query("ALTER TABLE practitioners MODIFY COLUMN uuid CHAR(36) DEFAULT (UUID()) ");
   }

   public function down()
   {
       // Get db instance
       $db = \Config\Database::connect();

       // Execute raw SQL statement
       $db->query("ALTER TABLE practitioners MODIFY COLUMN uuid CHAR(36) NOT NULL");
   }
}
