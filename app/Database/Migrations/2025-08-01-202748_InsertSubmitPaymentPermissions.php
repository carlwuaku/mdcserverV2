<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use App\Helpers\DatabaseMigrationHelper;
class InsertSubmitPaymentPermissions extends Migration
{
    public function up()
    {
        $migrationHelper = new DatabaseMigrationHelper();
        $permissions = [
            ["name" => "Submit_Invoice_Payments", "description" => "Allow the user to submit invoice payments"],
        ];

        $migrationHelper->addPermissions($permissions);
    }

    public function down()
    {
        //
    }
}
