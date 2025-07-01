<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTriggerForExaminationCandidatesNameChange extends Migration
{
    public function up()
    {
        $trigger = "CREATE TRIGGER after_examination_candidates_insert 
        AFTER INSERT ON exam_candidates
        FOR EACH ROW 
        BEGIN
            UPDATE licenses 
            SET name = TRIM(CONCAT(IFNULL(NEW.first_name, ''), ' ', IFNULL(NEW.middle_name, ''),  ' ', IFNULL(NEW.last_name, '')))
            WHERE licenses.license_number = NEW.intern_code;
        END;";
        $this->db->query($trigger);

        $examCandidatesUpdateTrigger = "CREATE TRIGGER after_examination_candidates_update 
        AFTER UPDATE ON exam_candidates
        FOR EACH ROW 
        BEGIN
            IF (IFNULL(NEW.first_name,'') != IFNULL(OLD.first_name,'') OR 
                IFNULL(NEW.last_name,'') != IFNULL(OLD.last_name,'') OR
                IFNULL(NEW.middle_name,'') != IFNULL(OLD.middle_name,'')) THEN
                
                 -- Simple update when only names changed
                    UPDATE licenses 
                    SET name = TRIM(CONCAT(IFNULL(NEW.first_name, ''), ' ', IFNULL(NEW.middle_name, ''), ' ', IFNULL(NEW.last_name, '')))
                    WHERE license_number = NEW.intern_code;
            END IF;
        END";
        $this->db->query($examCandidatesUpdateTrigger);
    }

    public function down()
    {
        //
    }
}
