<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExamCountTriggerToRegistrations extends Migration
{
    public function up()
    {
        //create a trigger to update the exam count in the exam_candidates table based on the number of rows in the examination_registrations table for the intern_code
        $trigger = "
        CREATE TRIGGER after_examination_registrations_insert
        AFTER INSERT ON examination_registrations
        FOR EACH ROW
        BEGIN
            DECLARE exam_count INT;
            SELECT COUNT(*) INTO exam_count FROM examination_registrations WHERE intern_code = NEW.intern_code;
            UPDATE exam_candidates SET number_of_exams = exam_count WHERE intern_code = NEW.intern_code;
        END;
        ";
        $this->db->query($trigger);
        $updateTrigger = "
        CREATE TRIGGER after_examination_registrations_update
        AFTER UPDATE ON examination_registrations
        FOR EACH ROW
        BEGIN
            DECLARE exam_count INT;
            SELECT COUNT(*) INTO exam_count FROM examination_registrations WHERE intern_code = NEW.intern_code;
            UPDATE exam_candidates SET number_of_exams = exam_count WHERE intern_code = NEW.intern_code;
        END;
        ";
        $this->db->query($updateTrigger);
        $deleteTrigger = "
        CREATE TRIGGER after_examination_registrations_delete
        AFTER DELETE ON examination_registrations
        FOR EACH ROW
        BEGIN
            DECLARE exam_count INT;
            SELECT COUNT(*) INTO exam_count FROM examination_registrations WHERE intern_code = OLD.intern_code;
            UPDATE exam_candidates SET number_of_exams = exam_count WHERE intern_code = OLD.intern_code;
        END;
        ";
        $this->db->query($deleteTrigger);
    }

    public function down()
    {
        //
    }
}
