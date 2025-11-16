<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoiceUuidTrigger extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER before_insert_invoices
       BEFORE INSERT ON invoices
       FOR EACH ROW
       BEGIN
        SET NEW.uuid = UUID();
       END;
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        //
    }
}
