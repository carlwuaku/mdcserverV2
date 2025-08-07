<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoiceTableTriggers extends Migration
{
    public function up()
    {
        $this->db->query("DROP TRIGGER IF EXISTS update_invoice_total_from_line_items");
        $this->db->query("DROP TRIGGER IF EXISTS update_invoice_total_on_line_update");
        $this->db->query("DROP TRIGGER IF EXISTS update_invoice_total_on_line_delete");

        // Add computed total trigger
        $this->db->query("
            CREATE TRIGGER update_invoice_total_from_line_items
            AFTER INSERT ON invoice_line_items
            FOR EACH ROW
            BEGIN
                UPDATE invoices 
                SET amount = (
                    SELECT COALESCE(SUM(line_total), 0) 
                    FROM invoice_line_items 
                    WHERE invoice_uuid = NEW.invoice_uuid
                )
                WHERE uuid = NEW.invoice_uuid;
            END
        ");

        $this->db->query("
            CREATE TRIGGER update_invoice_total_on_line_update
            AFTER UPDATE ON invoice_line_items
            FOR EACH ROW
            BEGIN
                UPDATE invoices 
                SET amount = (
                    SELECT COALESCE(SUM(line_total), 0) 
                    FROM invoice_line_items 
                    WHERE invoice_uuid = NEW.invoice_uuid
                )
                WHERE  uuid = NEW.invoice_uuid;
            END
        ");

        $this->db->query("
            CREATE TRIGGER update_invoice_total_on_line_delete
            AFTER DELETE ON invoice_line_items
            FOR EACH ROW
            BEGIN
                UPDATE invoices 
                SET amount = (
                    SELECT COALESCE(SUM(line_total), 0) 
                    FROM invoice_line_items 
                    WHERE invoice_uuid = OLD.invoice_uuid
                )
                WHERE uuid = OLD.invoice_uuid;
            END
        ");
    }

    public function down()
    {
        $this->db->query("DROP TRIGGER IF EXISTS update_invoice_total_on_line_delete");
        $this->db->query("DROP TRIGGER IF EXISTS update_invoice_total_on_line_update");
        $this->db->query("DROP TRIGGER IF EXISTS update_invoice_total_from_line_items");
    }
}
