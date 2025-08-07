<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePaymentViews extends Migration
{
    public function up()
    {
        $this->db->query("DROP VIEW IF EXISTS payment_summary");
        $this->db->query("DROP VIEW IF EXISTS outstanding_invoices");
    }

    public function down()
    {
        //
    }
}
