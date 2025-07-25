<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentTriggersAndViews extends Migration
{
    public function up()
    {
        $this->db->query("DROP VIEW IF EXISTS payment_summary");
        $this->db->query("DROP VIEW IF EXISTS outstanding_invoices");
        $this->db->query("DROP TRIGGER IF EXISTS update_payment_audit_log");
        $this->db->query("DROP TRIGGER IF EXISTS create_payment_audit_log");
        $this->db->query("DROP TRIGGER IF EXISTS update_invoice_status_on_payment");
        // Create trigger to update invoice status when payment is completed
        $this->db->query("
            CREATE TRIGGER update_invoice_status_on_payment
            AFTER UPDATE ON payments
            FOR EACH ROW
            BEGIN
                IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
                    UPDATE invoices 
                    SET status = CASE 
                        WHEN (SELECT COALESCE(SUM(amount), 0) FROM payments 
                              WHERE invoice_number = NEW.invoice_number AND status = 'completed') >= amount 
                        THEN 'paid'
                        ELSE 'pending'
                    END,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE invoice_number = NEW.invoice_number;
                END IF;
            END
        ");

        // Create trigger for payment audit logging
        $this->db->query("
            CREATE TRIGGER create_payment_audit_log
            AFTER INSERT ON payments
            FOR EACH ROW
            BEGIN
                INSERT INTO payment_audit_log (payment_uuid, action, new_status, changed_by, change_reason, created_at)
                VALUES (NEW.uuid, 'created', NEW.status, 'system', 'Payment created', CURRENT_TIMESTAMP);
            END
        ");

        $this->db->query("
            CREATE TRIGGER update_payment_audit_log
            AFTER UPDATE ON payments
            FOR EACH ROW
            BEGIN
                IF OLD.status != NEW.status THEN
                    INSERT INTO payment_audit_log (payment_uuid, action, old_status, new_status, changed_by, change_reason, created_at)
                    VALUES (NEW.uuid, 'status_changed', OLD.status, NEW.status, 'system', 'Status updated', CURRENT_TIMESTAMP);
                END IF;
            END
        ");

        // Create view for outstanding invoices
        $this->db->query("
            CREATE VIEW outstanding_invoices AS
            SELECT 
                i.uuid,
                i.invoice_number,
                i.unique_id,
                i.name,
                i.email,
                i.amount,
                i.currency,
                i.due_date,
                i.status,
                COALESCE(SUM(p.amount), 0) as paid_amount,
                (i.amount - COALESCE(SUM(p.amount), 0)) as outstanding_amount,
                DATEDIFF(CURDATE(), i.due_date) as days_overdue
            FROM invoices i
            LEFT JOIN payments p ON i.invoice_number = p.invoice_number AND p.status = 'completed'
            WHERE i.status IN ('pending', 'overdue')
            GROUP BY i.invoice_number
            HAVING outstanding_amount > 0
        ");

        // Create view for payment summary
        $this->db->query("
            CREATE VIEW payment_summary AS
            SELECT 
                p.uuid,
                p.invoice_number,
                i.unique_id,
                i.name,
                p.amount,
                p.currency,
                p.payment_date,
                p.status,
                p.method_name,
                pm.method_type,
                p.reference_number
            FROM payments p
            LEFT JOIN invoices i ON p.invoice_number = i.invoice_number
            LEFT JOIN payment_methods pm ON p.method_name = pm.method_name
        ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS payment_summary");
        $this->db->query("DROP VIEW IF EXISTS outstanding_invoices");
        $this->db->query("DROP TRIGGER IF EXISTS update_payment_audit_log");
        $this->db->query("DROP TRIGGER IF EXISTS create_payment_audit_log");
        $this->db->query("DROP TRIGGER IF EXISTS update_invoice_status_on_payment");
    }
}
