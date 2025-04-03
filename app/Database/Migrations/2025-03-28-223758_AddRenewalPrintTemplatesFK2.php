<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalPrintTemplatesFK2 extends Migration
{
    public function up()
    {
        try {

            $this->forge->addKey('in_print_queue', false, false, 'license_renewal_in_print_queue_index');
            $this->forge->processIndexes('license_renewal');
        } catch (\Throwable $th) {
            log_message('error', 'Error adding foreign keys to license_renewal table: ' . $th->getMessage());
        }

    }

    public function down()
    {
        //
    }
}
