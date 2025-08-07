<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentFileUploadsView extends Migration
{
    public function up()
    {
        // Create view for payment summary
        $this->db->query("
            CREATE VIEW payment_file_uploads_view AS
            SELECT 
                p.id,
                p.invoice_uuid,
                i.unique_id,
                i.first_name,
                i.last_name,
                i.email,
                i.phone_number,
                i.application_id,
                i.amount,
                i.purpose,
                i.due_date,
                i.description,
                i.status as invoice_status,
                p.file_path,
                p.created_at,
                p.payment_date,
                p.reference_number,
                p.status as file_status
                
            FROM payment_file_uploads p
            LEFT JOIN invoices i ON p.invoice_uuid = i.uuid
        ");
    }

    public function down()
    {
        //
    }
}
