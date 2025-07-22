<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePaymentDetailsFromRenewalSubTables extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('payment_date', 'practitioners_renewal')) {
            $this->forge->dropColumn('practitioners_renewal', 'payment_date');

        }
        if ($this->db->fieldExists('payment_file', 'practitioners_renewal')) {
            $this->forge->dropColumn('practitioners_renewal', 'payment_file');

        }
        if ($this->db->fieldExists('payment_file_date', 'practitioners_renewal')) {
            $this->forge->dropColumn('practitioners_renewal', 'payment_file_date');

        }
        if ($this->db->fieldExists('payment_invoice_number', 'practitioners_renewal')) {
            $this->forge->dropColumn('practitioners_renewal', 'payment_invoice_number');

        }

        if ($this->db->fieldExists('payment_date', 'facility_renewal')) {
            $this->forge->dropColumn('facility_renewal', 'payment_date');

        }
        if ($this->db->fieldExists('payment_file', 'facility_renewal')) {
            $this->forge->dropColumn('facility_renewal', 'payment_file');

        }
        if ($this->db->fieldExists('payment_file_date', 'facility_renewal')) {
            $this->forge->dropColumn('facility_renewal', 'payment_file_date');

        }
        if ($this->db->fieldExists('payment_invoice_number', 'facility_renewal')) {
            $this->forge->dropColumn('facility_renewal', 'payment_invoice_number');
        }

    }

    public function down()
    {
        //
    }
}
