<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTriggerForLicenseNameChanges extends Migration
{
    public function up()
    {
        $facilitiestrigger = "CREATE TRIGGER after_facilities_insert 
AFTER INSERT ON facilities
FOR EACH ROW 
BEGIN
    UPDATE licenses 
    SET name = NEW.name 
    WHERE licenses.license_number = NEW.license_number;
END;";
        $this->db->query($facilitiestrigger);

        $facilitiesUpdateTrigger = "CREATE TRIGGER after_facilities_update 
AFTER UPDATE ON facilities
FOR EACH ROW 
BEGIN
    IF (NEW.name != OLD.name) THEN
        
         -- Simple update when only names changed
            UPDATE licenses 
            SET name = NEW.name
            WHERE license_number = NEW.license_number;
    END IF;
END";
        $this->db->query($facilitiesUpdateTrigger);
    }

    public function down()
    {
        //
    }
}
