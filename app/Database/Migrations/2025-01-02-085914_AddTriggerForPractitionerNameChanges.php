<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTriggerForPractitionerNameChanges extends Migration
{
    public function up()
    {
        $practitionerstrigger = "CREATE TRIGGER after_practitioners_insert 
AFTER INSERT ON practitioners
FOR EACH ROW 
BEGIN
    UPDATE licenses 
    SET name = TRIM(CONCAT(IFNULL(NEW.first_name, ''), ' ', IFNULL(NEW.middle_name, ''),  ' ', IFNULL(NEW.last_name, '')))
    WHERE licenses.license_number = NEW.license_number;
END;";
        $this->db->query($practitionerstrigger);

        $practitionersUpdateTrigger = "CREATE TRIGGER after_practitioners_update 
AFTER UPDATE ON practitioners
FOR EACH ROW 
BEGIN
    IF (IFNULL(NEW.first_name,'') != IFNULL(OLD.first_name,'') OR 
        IFNULL(NEW.last_name,'') != IFNULL(OLD.last_name,'') OR
        IFNULL(NEW.middle_name,'') != IFNULL(OLD.middle_name,'')) THEN
        
         -- Simple update when only names changed
            UPDATE licenses 
            SET name = TRIM(CONCAT(IFNULL(NEW.first_name, ''), ' ', IFNULL(NEW.middle_name, ''), ' ', IFNULL(NEW.last_name, '')))
            WHERE license_number = NEW.license_number;
    END IF;
END";
        $this->db->query($practitionersUpdateTrigger);
    }

    public function down()
    {
        //
    }
}
