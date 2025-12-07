<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateIntPrimaryKeysToBigint extends Migration
{
    /**
     * Tables with their child relationships
     * Format: 'parent_table' => ['child_table' => 'fk_column']
     */
    private array $tableRelationships = [
        'license_renewal' => [
            'practitioners_renewal' => 'renewal_id',
            'facility_renewal' => 'renewal_id',
            'otcms_renewal' => 'renewal_id',
        ],
        'users' => [
            'application_timeline' => 'user_id',
        ],
        'housemanship_facilities' => [
            'housemanship_facility_availability' => 'facility_id',
            'housemanship_facility_capacities' => 'facility_id',
            'housemanship_postings_details' => 'facility_id',
        ],
        'housemanship_disciplines' => [
            'housemanship_facility_availability' => 'discipline_id',
            'housemanship_facility_capacities' => 'discipline_id',
            'housemanship_postings_details' => 'discipline_id',
        ],
        'housemanship_postings' => [
            'housemanship_postings_applications' => 'posting_id',
            'housemanship_postings_details' => 'posting_id',
        ],
        'housemanship_postings_applications' => [
            'housemanship_postings_application_details' => 'application_id',
        ],
        'housemanship_postings_details' => [
            'housemanship_postings_application_details' => 'posting_detail_id',
        ],
        'invoices' => [
            'invoice_line_items' => 'invoice_id',
            'invoice_payment_options' => 'invoice_id',
        ],
        'print_queues' => [
            'print_queue_items' => 'queue_id',
        ],
        'specialties' => [
            'subspecialties' => 'specialty_id',
        ],
    ];

    /**
     * Standalone tables (no FK relationships to handle)
     */
    private array $standaloneTables = [
        'licenses' => false,
        'practitioners' => false,
        'facilities' => false,
        'otcms' => false,
        'mca' => false,
        'application_forms' => false,
        'application_form_templates' => false,
        'exam_candidates' => false,
        'examinations' => false,
        'examination_applications' => false,
        'examination_registrations' => false,
        'examination_letter_templates' => false,
        'examination_letter_template_criteria' => false,
        'housemanship_facility_preceptors' => false,
        'payment_file_uploads' => false,
        'print_history' => false,
        'print_templates' => false,
        'print_template_roles' => true,
        'practitioner_additional_qualifications' => false,
        'practitioner_portal_edits' => false,
        'practitioner_work_history' => false,
        'cpd_providers' => false,
        'cpd_topics' => false,
        'cpd_attendance' => false,
        'external_cpd_attendance' => false,
        'training_institutions' => false,
        'training_institutions_limits' => false,
        'activities' => false,
        'documents' => false,
        'fees' => false,
        'settings' => false,
        'districts' => false,
        'regions' => false,
        'student_indexes' => false,
        'auth_groups_users' => true,
        'auth_identities' => true,
        'auth_logins' => true,
        'auth_permissions_users' => true,
        'auth_remember_tokens' => true,
        'auth_token_logins' => true,
        'api_key_permissions' => true,
        'actions_audit' => true,
        'failed_actions' => true,
        'email_queue' => true,
        'email_queue_log' => true,
        'email_verification_tokens' => true,
        'app_settings_overrides' => true,
        'guests' => true,
        'password_history' => true,
        'password_reset_attempts' => true,
        'password_reset_tokens' => true,
        'role_permissions' => true,
        'verification_logs' => false,
    ];

    public function up()
    {
        // Disable foreign key checks
        $this->db->query('SET FOREIGN_KEY_CHECKS=0;');

        try {
            log_message('info', 'Starting INT to BIGINT conversion migration (Fixed version)');

            // Step 1: Convert child table foreign keys first
            $this->convertChildTableForeignKeys();

            // Step 2: Convert parent table primary keys
            $this->convertParentTablePrimaryKeys();

            // Step 3: Convert standalone tables
            $this->convertStandaloneTables();

            // Step 4: Recreate foreign keys on practitioners_renewal
            log_message('info', 'Step 4: Recreating foreign keys on practitioners_renewal');
            if ($this->db->tableExists('practitioners_renewal')) {
                $this->recreatePractitionersRenewalForeignKeys();
            }

            log_message('info', 'Successfully completed INT to BIGINT conversion migration');
        } catch (\Exception $e) {
            log_message('error', 'Error in INT to BIGINT migration: ' . $e->getMessage());
            throw $e;
        } finally {
            // Re-enable foreign key checks
            $this->db->query('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function down()
    {
        // Disable foreign key checks
        $this->db->query('SET FOREIGN_KEY_CHECKS=0;');

        try {
            log_message('info', 'Rolling back BIGINT to INT conversion');

            // Reverse order: parent tables first, then child tables
            foreach ($this->tableRelationships as $parentTable => $children) {
                if (!$this->db->tableExists($parentTable)) {
                    continue;
                }

                log_message('info', "Rolling back parent table: {$parentTable}");
                $sql = "ALTER TABLE `{$parentTable}` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT";
                $this->db->query($sql);

                // Then child foreign keys
                foreach ($children as $childTable => $fkColumn) {
                    if (!$this->db->tableExists($childTable)) {
                        continue;
                    }

                    log_message('info', "Rolling back child FK: {$childTable}.{$fkColumn}");
                    $sql = "ALTER TABLE `{$childTable}` MODIFY `{$fkColumn}` INT(11) NULL";
                    $this->db->query($sql);
                }
            }

            // Rollback standalone tables
            foreach ($this->standaloneTables as $table => $isUnsigned) {
                if (!$this->db->tableExists($table)) {
                    continue;
                }

                $unsigned = $isUnsigned ? 'unsigned' : '';
                $sql = "ALTER TABLE `{$table}` MODIFY `id` INT(11) {$unsigned} NOT NULL AUTO_INCREMENT";
                $this->db->query($sql);
            }

            log_message('info', 'Successfully rolled back BIGINT to INT conversion');
        } catch (\Exception $e) {
            log_message('error', 'Error rolling back INT to BIGINT migration: ' . $e->getMessage());
            throw $e;
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Convert foreign key columns in child tables FIRST
     * This must happen before converting parent table PKs
     */
    private function convertChildTableForeignKeys(): void
    {
        log_message('info', 'Step 1: Converting child table foreign key columns');

        foreach ($this->tableRelationships as $parentTable => $children) {
            log_message('info', "Processing children of: {$parentTable}");

            foreach ($children as $childTable => $fkColumn) {
                if (!$this->db->tableExists($childTable)) {
                    log_message('warning', "Child table {$childTable} does not exist, skipping");
                    continue;
                }

                if (!$this->columnExists($childTable, $fkColumn)) {
                    log_message('warning', "FK column {$childTable}.{$fkColumn} does not exist, skipping");
                    continue;
                }

                // Special handling for practitioners_renewal - drop its FKs first (only once)
                static $practitionersRenewalProcessed = false;
                if ($childTable === 'practitioners_renewal' && !$practitionersRenewalProcessed) {
                    log_message('info', "Dropping foreign keys on practitioners_renewal before conversion");
                    $this->dropPractitionersRenewalForeignKeys();
                    $practitionersRenewalProcessed = true;
                }

                log_message('info', "Converting FK: {$childTable}.{$fkColumn} to BIGINT");

                // Most foreign keys are nullable
                $sql = "ALTER TABLE `{$childTable}` MODIFY `{$fkColumn}` BIGINT(20) NULL";

                try {
                    $this->db->query($sql);
                    log_message('info', "✓ Successfully converted {$childTable}.{$fkColumn}");
                } catch (\Exception $e) {
                    log_message('error', "✗ Failed to convert {$childTable}.{$fkColumn}: " . $e->getMessage());
                    throw $e;
                }
            }
        }
    }

    /**
     * Convert primary keys in parent tables AFTER child FKs are converted
     */
    private function convertParentTablePrimaryKeys(): void
    {
        log_message('info', 'Step 2: Converting parent table primary keys');

        foreach ($this->tableRelationships as $parentTable => $children) {
            if (!$this->db->tableExists($parentTable)) {
                log_message('warning', "Parent table {$parentTable} does not exist, skipping");
                continue;
            }

            log_message('info', "Converting PK: {$parentTable}.id to BIGINT");

            // Determine if unsigned based on table name patterns
            $isUnsigned = (strpos($parentTable, 'auth_') === 0 ||
                strpos($parentTable, 'api_') === 0 ||
                $parentTable === 'users' ||
                $parentTable === 'application_timeline');

            $unsigned = $isUnsigned ? 'unsigned' : '';
            $sql = "ALTER TABLE `{$parentTable}` MODIFY `id` BIGINT(20) {$unsigned} NOT NULL AUTO_INCREMENT";

            try {
                $this->db->query($sql);
                log_message('info', "✓ Successfully converted {$parentTable}.id");
            } catch (\Exception $e) {
                log_message('error', "✗ Failed to convert {$parentTable}.id: " . $e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Convert standalone tables (no FK relationships)
     */
    private function convertStandaloneTables(): void
    {
        log_message('info', 'Step 3: Converting standalone table primary keys');

        foreach ($this->standaloneTables as $table => $isUnsigned) {
            if (!$this->db->tableExists($table)) {
                log_message('warning', "Table {$table} does not exist, skipping");
                continue;
            }

            log_message('info', "Converting PK: {$table}.id to BIGINT");

            $unsigned = $isUnsigned ? 'unsigned' : '';
            $sql = "ALTER TABLE `{$table}` MODIFY `id` BIGINT(20) {$unsigned} NOT NULL AUTO_INCREMENT";

            try {
                $this->db->query($sql);
                log_message('info', "✓ Successfully converted {$table}.id");
            } catch (\Exception $e) {
                log_message('error', "✗ Failed to convert {$table}.id: " . $e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Check if a column exists in a table
     */
    private function columnExists(string $table, string $column): bool
    {
        $fields = $this->db->getFieldData($table);
        foreach ($fields as $field) {
            if ($field->name === $column) {
                return true;
            }
        }
        return false;
    }

    /**
     * Drop foreign keys from practitioners_renewal table
     * This table has FKs to both licenses and license_renewal
     */
    private function dropPractitionersRenewalForeignKeys(): void
    {
        try {
            // Get existing foreign keys
            $foreignKeys = $this->db->query("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'practitioners_renewal'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->getResultArray();

            foreach ($foreignKeys as $fk) {
                $constraintName = $fk['CONSTRAINT_NAME'];
                log_message('info', "Dropping FK constraint: {$constraintName}");
                $this->db->query("ALTER TABLE `practitioners_renewal` DROP FOREIGN KEY `{$constraintName}`");
            }
        } catch (\Exception $e) {
            log_message('warning', "Could not drop FKs from practitioners_renewal: " . $e->getMessage());
        }
    }

    /**
     * Recreate foreign keys on practitioners_renewal table
     */
    private function recreatePractitionersRenewalForeignKeys(): void
    {
        try {
            // FK to licenses table (license_number is VARCHAR, not affected by this migration)
            if ($this->tableExists('licenses') && $this->columnExists('licenses', 'license_number')) {
                log_message('info', "Recreating FK: practitioners_renewal.license_number -> licenses.license_number");
                $this->db->query("
                    ALTER TABLE `practitioners_renewal`
                    ADD CONSTRAINT `practitioners_renewal_license_number_foreign`
                    FOREIGN KEY (`license_number`)
                    REFERENCES `licenses`(`license_number`)
                    ON DELETE CASCADE
                    ON UPDATE RESTRICT
                ");
            }

            // FK to license_renewal table (renewal_id is now BIGINT)
            if ($this->tableExists('license_renewal') && $this->columnExists('license_renewal', 'id')) {
                log_message('info', "Recreating FK: practitioners_renewal.renewal_id -> license_renewal.id");
                $this->db->query("
                    ALTER TABLE `practitioners_renewal`
                    ADD CONSTRAINT `practitioners_renewal_renewal_id_foreign`
                    FOREIGN KEY (`renewal_id`)
                    REFERENCES `license_renewal`(`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");
            }
        } catch (\Exception $e) {
            log_message('error', "Could not recreate FKs on practitioners_renewal: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if a table exists in the database
     */
    private function tableExists(string $table): bool
    {
        return $this->db->tableExists($table);
    }
}
