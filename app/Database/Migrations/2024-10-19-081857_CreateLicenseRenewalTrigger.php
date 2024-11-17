<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLicenseRenewalTrigger extends Migration
{
    public function up()
    {
        $trigger = "
       CREATE TRIGGER after_insert_license_renewal
       AFTER INSERT ON license_renewal
       FOR EACH ROW
       BEGIN
       UPDATE licenses t1 
       JOIN (
        SELECT t2.license_uuid, t2.start_date, t2.expiry, status
        FROM license_renewal t2
        WHERE t2.license_uuid = NEW.license_uuid
        ORDER BY t2.id DESC
        LIMIT 1
    ) latest ON latest.license_uuid = t1.uuid
        SET t1.last_renewal_start = latest.start_date,
            t1.last_renewal_expiry = latest.expiry,
            t1.last_renewal_status = latest.status
        WHERE t1.uuid = latest.license_uuid;
       END;

        
       ";
        $this->db->query($trigger);
    }

    public function down()
    {
        //
    }
}
