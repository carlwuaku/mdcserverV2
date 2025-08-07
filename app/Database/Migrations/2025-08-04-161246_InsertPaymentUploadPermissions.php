<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use App\Helpers\DatabaseMigrationHelper;
class InsertPaymentUploadPermissions extends Migration
{
    public function up()
    {
        $migrationHelper = new DatabaseMigrationHelper();
        $permissions = [
            ["name" => "Upload_Payment_Evidence_File", "description" => "Allow the user to submit evidence of payment for an invoice"],
            ["name" => "Delete_Payment_Evidence_File", "description" => "Allow the user to delete evidence of payment for an invoice"],
            ["name" => "Approve_Payment_Evidence_File", "description" => "Allow the user to approve evidence of payment for an invoice and mark invoice as paid"],
            ["name" => "View_Payment_Evidence_File", "description" => "Allow the user to view evidence of payment for an invoice and mark invoice as paid"],
        ];

        $migrationHelper->addPermissions($permissions);
    }

    public function down()
    {
        //
    }
}
