<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use App\Helpers\DatabaseMigrationHelper;

class InsertPaymentInvoicePermissions extends Migration
{
    public function up()
    {
        $migrationHelper = new DatabaseMigrationHelper();
        $permissions = [
            ["name" => "Create_Payment_Invoices", "description" => "Allow the user to create new payment invoices"],
            ["name" => "Update_Payment_Invoices", "description" => "Allow the user to update payment invoices"],
            ["name" => "Delete_Payment_Invoices", "description" => "Allow the user to delete payment invoices"],
            ["name" => "View_Payment_Invoices", "description" => "Allow the user to view payment invoices"],
        ];

        $migrationHelper->addPermissions($permissions);
    }

    public function down()
    {
        //
    }
}
