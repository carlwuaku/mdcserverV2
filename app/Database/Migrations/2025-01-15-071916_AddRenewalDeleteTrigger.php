<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRenewalDeleteTrigger extends Migration
{
    public function up()
    {
        $trigger = "
        CREATE TRIGGER update_licenses_after_delete_license_renewal
        AFTER DELETE ON license_renewal
        FOR EACH ROW
        BEGIN
            UPDATE licenses t1
            LEFT JOIN (
                SELECT t2.license_uuid, t2.start_date, t2.expiry, t2.status
                FROM license_renewal t2
                WHERE t2.license_uuid = OLD.license_uuid
                ORDER BY t2.id DESC
                LIMIT 1
            ) latest ON latest.license_uuid = t1.uuid
            SET t1.last_renewal_start = latest.start_date,
                t1.last_renewal_expiry = latest.expiry,
                t1.last_renewal_status = latest.status
            WHERE t1.uuid = OLD.license_uuid;
        END;
        
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        //
    }
}
