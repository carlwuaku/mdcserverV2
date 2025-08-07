#IMPORTANT:  CHECK THE NAMES OF THE DATABASES AND TABLES. 
#both databases should be on the same server
#ci4_pc2 should be the name of the database that you are migrating to
#council should be the name of the database that you are migrating from
#IMPORT SETTINGS#
INSERT INTO
    ci4_pc2.`settings` (
        `key`,
        `value`,
        `class`,
        `type`,
        `context`,
        `control_type`
    )
SELECT
    `name`,
    `value`,
    `module`,
    'string',
    null,
    `type`
FROM
    council.`bf_settings` ON DUPLICATE KEY
UPDATE
    `value` =
VALUES
    (`value`);

#there is a setting site_languages that causes problems. the value is some sort of serialized array. remove it
DELETE FROM
    ci4_pc2.`settings`
WHERE
    `key` = 'site.languages';

#END IMPORT SETTINGS#
#IMPORT REGIONS#
INSERT INTO
    ci4_pc2.`regions` (`name`)
SELECT
    `name`
FROM
    council.`bf_region` ON DUPLICATE KEY
UPDATE
    `name` =
VALUES
    (`name`);

#END IMPORT REGIONS#
#-IMPORT DISTRICTS#
INSERT
    IGNORE INTO ci4_pc2.`districts` (`district`, `region`, `region_id`)
SELECT
    `name`,
    (
        SELECT
            name
        FROM
            council.bf_region
        WHERE
            id = council.bf_district.region_Id
    ) as region,
    `region_Id`
FROM
    council.`bf_district`;

#END IMPORT DISTRICTS#
#IMPORT SPECIALTIES#
#NO SPECIALTIES
#END IMPORT SPECIALTIES#
#IMPORT ROLES#
INSERT INTO
    ci4_pc2.`roles` (`role_name`, `description`)
SELECT
    `role_name`,
    `description`
FROM
    council.`bf_roles` ON DUPLICATE KEY
UPDATE
    `role_name` =
VALUES
    (`role_name`);

#END IMPORT ROLES#
#IMPORT USERS#
INSERT
    IGNORE INTO ci4_pc2.`users` (
        `username`,
        `region`,
        `position`,
        `picture`,
        `phone`,
        `email_address`,
        `role_name`,
        `user_type`
    )
SELECT
    `username`,
    r.name as region,
    `position`,
    `picture`,
    `phone`,
    `email`,
    (
        SELECT
            role_name
        FROM
            council.bf_roles
        WHERE
            role_id = council.bf_users.role_id
    ) as role_name,
    'admin' as user_type
FROM
    council.`bf_users`
    LEFT JOIN council.bf_region r ON bf_users.regionId = r.id;

#END IMPORT USERS#
#CLEAN UP THE PHARMACIST TABLE#
#remove any regions that are not numbers
UPDATE
    council.bf_pharmacist
SET
    region = NULL
WHERE
    region IS NOT NULL
    AND region NOT REGEXP '^-?[0-9]+(\.[0-9]+)?$';

#remove any districts that are not numbers
UPDATE
    council.bf_pharmacist
SET
    district = NULL
WHERE
    district IS NOT NULL
    AND district NOT REGEXP '^-?[0-9]+(\.[0-9]+)?$';

#migration for PHARMACISTS. import the data from the old database to the licenses table
INSERT INTO
    ci4_pc2.`licenses`(
        `uuid`,
        `license_number`,
        `name`,
        `registration_date`,
        `status`,
        `email`,
        `postal_address`,
        `picture`,
        `type`,
        `phone`,
        `portal_access`,
        `created_on`,
        `region`,
        `district`,
        `register_type`
    )
SELECT
    '',
    registration_number,
    CONCAT_WS(
        ' ',
        COALESCE(first_name, ''),
        COALESCE(middle_name, ''),
        COALESCE(last_name, '')
    ) as name,
    registration_date,
    status,
    email,
    postal_address,
    picture,
    'practitioners',
    phone,
    'yes',
    created_on,
    r.name as region,
    d.name as district,
    CASE
        WHEN DATEDIFF(CURDATE(), bf_pharmacist.registration_date) < 365 THEN 'Provisional'
        WHEN DATEDIFF(CURDATE(), bf_pharmacist.registration_date) >= 365
        AND NOT EXISTS (
            SELECT
                1
            FROM
                council.bf_retention
            WHERE
                bf_retention.reg_num = bf_pharmacist.registration_number
                AND bf_retention.status = 'Approved'
        ) THEN 'Provisional'
        WHEN DATEDIFF(CURDATE(), bf_pharmacist.registration_date) >= 365
        AND EXISTS (
            SELECT
                1
            FROM
                council.bf_retention
            WHERE
                bf_retention.reg_num = bf_pharmacist.registration_number
                AND bf_retention.status = 'Approved'
        ) THEN 'Permanent'
        ELSE 'Provisional'
    END as register_type
FROM
    council.bf_pharmacist
    LEFT JOIN council.bf_region r ON bf_pharmacist.region = r.id
    LEFT JOIN council.bf_district d ON bf_pharmacist.district = d.id
WHERE
    council.bf_pharmacist.registration_number IS NOT NULL
    AND council.bf_pharmacist.registration_number != '';

########-END PHARMACISTS LICENSE MIGRATION#######-
##MIGRATE PHARMACISTS DETAILS INTO THE PRACTITIONERS TABLE##
INSERT INTO
    ci4_pc2.`practitioners`(
        `first_name`,
        `middle_name`,
        `last_name`,
        `date_of_birth`,
        `license_number`,
        `sex`,
        `title`,
        `maiden_name`,
        `marital_status`,
        `nationality`,
        `qualification_at_registration`,
        `training_institution`,
        `qualification_date`,
        `residential_address`,
        `hometown`,
        `active`,
        `mailing_city`,
        `residential_city`,
        `residential_region`,
        `intern_code`,
        `practitioner_type`
    )
select
    `first_name`,
    `middle_name`,
    `last_name`,
    `date_of_birth`,
    `registration_number`,
    `sex`,
    `title`,
    `maiden_name`,
    `marital_status`,
    `nationality`,
    `qualification_at_registration`,
    `training_institution`,
    `qualification_date`,
    `residential_address`,
    `hometown`,
    `active`,
    `mailing_city`,
    `residential_city`,
    `residential_region`,
    `intern_code`,
    'Pharmacist' as practitioner_type
from
    council.bf_pharmacist;

####-END PHARMACISTS MIGRATION#######-
#CLEAN UP THE TECHNICIANS TABLE#
#remove any regions that are not numbers
UPDATE
    council.bf_pharmacy_technicians
SET
    region = NULL
WHERE
    region IS NOT NULL
    AND region NOT REGEXP '^-?[0-9]+(\.[0-9]+)?$';

#migration for PHARMACY TECHNICIANS. import the data from the old database to the licenses table
INSERT INTO
    ci4_pc2.`licenses`(
        `uuid`,
        `license_number`,
        `name`,
        `registration_date`,
        `status`,
        `email`,
        `postal_address`,
        `picture`,
        `type`,
        `phone`,
        `portal_access`,
        `region`
    )
SELECT
    '',
    registration_number,
    CONCAT_WS(
        ' ',
        COALESCE(first_name, ''),
        COALESCE(middle_name, ''),
        COALESCE(last_name, '')
    ) as name,
    registration_date,
    status,
    email,
    postal_address,
    picture,
    'practitioners',
    phone,
    'yes',
    r.name as region
FROM
    council.bf_pharmacy_technicians
    LEFT JOIN council.bf_region r ON bf_pharmacy_technicians.region = r.id
WHERE
    council.bf_pharmacy_technicians.registration_number IS NOT NULL
    AND council.bf_pharmacy_technicians.registration_number != '';

########-END PHARMACY TECHNICIANS LICENSE MIGRATION#######-
##MIGRATE PHARMACY TECHNICIANS DETAILS INTO THE PRACTITIONERS TABLE##
INSERT INTO
    ci4_pc2.`practitioners`(
        `first_name`,
        `middle_name`,
        `last_name`,
        `date_of_birth`,
        `license_number`,
        `sex`,
        `maiden_name`,
        `marital_status`,
        `nationality`,
        `qualification_at_registration`,
        `training_institution`,
        `qualification_date`,
        `residential_address`,
        `hometown`,
        `practitioner_type`
    )
select
    `first_name`,
    `middle_name`,
    `last_name`,
    `date_of_birth`,
    `registration_number`,
    `sex`,
    `maiden_name`,
    `marital_status`,
    `nationality`,
    `qualification`,
    `training_institution`,
    `qualification_date`,
    `residential_address`,
    `hometown`,
    'Pharmacy Technician' as practitioner_type
from
    council.bf_pharmacy_technicians;

####-END PHARMACY TECHNICIANS MIGRATION#######-
#migration for PHARMACIES. import the data from the old database to the licenses table
UPDATE
    council.bf_pharmacies
SET
    district = ''
WHERE
    district IS NOT NULL
    AND district NOT REGEXP '^-?[0-9]+(\.[0-9]+)?$';

INSERT INTO
    ci4_pc2.`licenses`(
        `uuid`,
        `license_number`,
        `name`,
        `registration_date`,
        `status`,
        `email`,
        `postal_address`,
        `type`,
        `phone`,
        `portal_access`,
        `region`,
        `district`
    )
SELECT
    '',
    license_number,
    council.bf_pharmacies.name,
    registration_date,
    status,
    email,
    postal_address,
    'facilities',
    phone,
    'yes',
    r.name as region,
    d.name as district
FROM
    council.bf_pharmacies
    LEFT JOIN council.bf_region r ON council.bf_pharmacies.region = r.id
    LEFT JOIN council.bf_district d ON (
        council.bf_pharmacies.district IS NOT NULL
        AND council.bf_pharmacies.district != ''
        AND d.id = council.bf_pharmacies.district
    )
WHERE
    council.bf_pharmacies.license_number IS NOT NULL
    AND council.bf_pharmacies.license_number != '';

########-END PHARMACIES LICENSE MIGRATION#######-
##MIGRATE PHARMACIES DETAILS INTO THE FACILITIES TABLE##
INSERT INTO
    ci4_pc2.`facilities`(
        `name`,
        `license_number`,
        `town`,
        `suburb`,
        `business_type`,
        `house_number`,
        `coordinates`,
        `street`,
        `ghana_post_code`,
        `cbd`,
        `notes`
    )
select
    `name`,
    `license_number`,
    `town`,
    `suburb`,
    `business_type`,
    `house_number`,
    `coordinates`,
    `street`,
    `ghana_post_code`,
    `cbd`,
    `notes`
from
    council.bf_pharmacies;

####-END PHARMACIES MIGRATION#######-
#-PHARMACISTS renewal. the renewal is split into the licenses_renewal and practitioners_renewal. the licenses_renewal is the main table that holds the renewal data and the practitioners_renewal is the table that holds the additional data for the practitioners
INSERT INTO
    ci4_pc2.`license_renewal`(
        `license_number`,
        `created_by`,
        `created_on`,
        `start_date`,
        `receipt`,
        `qr_code`,
        `qr_text`,
        `status`,
        `payment_date`,
        `payment_file`,
        `payment_invoice_number`,
        `license_uuid`,
        `license_type`,
        `data_snapshot`
    )
select
    reg_num,
    created_by,
    created_on,
    year,
    receipt,
    qr_code,
    qr_text,
    status,
    payment_date,
    payment_file,
    payment_invoice_number,
    null,
    'practitioners',
    null
from
    council.bf_retention;

#############END PHARMACISTS RENEWAL MIGRATION###########
#update the license_renewal table to add the data_snapshot data . this should be a json object with all the data from the licenses + practitioners table
UPDATE
    ci4_pc2.`license_renewal`
    INNER JOIN council.`bf_pharmacist` ON council.`bf_pharmacist`.`registration_number` = ci4_pc2.`license_renewal`.`license_number`
    LEFT JOIN council.bf_region r ON (
        council.bf_pharmacist.region IS NOT NULL
        AND council.bf_pharmacist.region != ''
        AND council.bf_pharmacist.region = r.id
    )
    LEFT JOIN council.bf_district d ON (
        council.bf_pharmacist.district IS NOT NULL
        AND council.bf_pharmacist.district != ''
        AND council.bf_pharmacist.district = d.id
    )
SET
    ci4_pc2.`license_renewal`.`data_snapshot` = JSON_OBJECT(
        'first_name',
        council.bf_pharmacist.`first_name`,
        'middle_name',
        council.bf_pharmacist.`middle_name`,
        'last_name',
        council.bf_pharmacist.`last_name`,
        'date_of_birth',
        council.bf_pharmacist.`date_of_birth`,
        'license_number',
        council.bf_pharmacist.`registration_number`,
        'sex',
        council.bf_pharmacist.`sex`,
        'title',
        council.bf_pharmacist.`title`,
        'maiden_name',
        council.bf_pharmacist.`maiden_name`,
        'marital_status',
        council.bf_pharmacist.`marital_status`,
        'nationality',
        council.bf_pharmacist.`nationality`,
        'qualification_at_registration',
        council.bf_pharmacist.`qualification_at_registration`,
        'training_institution',
        council.bf_pharmacist.`training_institution`,
        'qualification_date',
        council.bf_pharmacist.`qualification_date`,
        'residential_address',
        council.bf_pharmacist.`residential_address`,
        'register_type',
        council.bf_pharmacist.`register_type`,
        'picture',
        council.bf_pharmacist.`picture`,
        'postal_address',
        council.bf_pharmacist.`postal_address`,
        'country_of_practice',
        council.bf_pharmacist.`country_of_practice`,
        'phone',
        council.bf_pharmacist.`phone`,
        'email',
        council.bf_pharmacist.`email`
    ),
    ci4_pc2.`license_renewal`.`name` = CONCAT_WS(
        ' ',
        council.bf_pharmacist.`first_name`,
        council.bf_pharmacist.`middle_name`,
        council.bf_pharmacist.`last_name`
    ),
    ci4_pc2.`license_renewal`.`region` = r.name,
    ci4_pc2.`license_renewal`.`district` = d.name,
    ci4_pc2.`license_renewal`.`email` = council.bf_pharmacist.`email`,
    ci4_pc2.`license_renewal`.`phone` = council.bf_pharmacist.`phone`,
    ci4_pc2.`license_renewal`.`picture` = council.bf_pharmacist.`picture`,
    ci4_pc2.`license_renewal`.`country_of_practice` = council.bf_pharmacist.`country_of_practice`
WHERE
    ci4_pc2.`license_renewal`.`data_snapshot` IS NULL;

#############-END UPDATE LICENSE DATA SNAPSHOT###########
########-PHARMACY TECHINCIANS RENEWAL########-
#-physician assistants renewal. the renewal is split into the licenses_renewal and practitioners_renewal. the licenses_renewal is the main table that holds the renewal data and the practitioners_renewal is the table that holds the additional data for the practitioners
INSERT INTO
    ci4_pc2.`license_renewal`(
        `license_number`,
        `created_by`,
        `created_on`,
        `start_date`,
        `receipt`,
        `qr_code`,
        `qr_text`,
        `status`,
        `payment_date`,
        `payment_file`,
        `payment_invoice_number`,
        `license_uuid`,
        `license_type`,
        `data_snapshot`
    )
select
    reg_num,
    created_by,
    created_on,
    year,
    receipt_number,
    qr_code,
    qr_text,
    status,
    payment_date,
    payment_file,
    payment_invoice_number,
    null,
    'practitioners',
    null
from
    council.bf_technicians_retention;

#############END TECHNICIANS RENEWAL MIGRATION###########
#update the license_renewal table to add the data_snapshot data . this should be a json object with all the data from the pharmacy_technicians table
UPDATE
    ci4_pc2.`license_renewal`
    INNER JOIN council.`bf_pharmacy_technicians` ON council.`bf_pharmacy_technicians`.`registration_number` = ci4_pc2.`license_renewal`.`license_number`
    LEFT JOIN council.bf_region r ON (
        council.bf_pharmacy_technicians.region IS NOT NULL
        AND council.bf_pharmacy_technicians.region != ''
        AND council.bf_pharmacy_technicians.region = r.id
    )
SET
    ci4_pc2.`license_renewal`.`data_snapshot` = JSON_OBJECT(
        'first_name',
        council.bf_pharmacy_technicians.`first_name`,
        'middle_name',
        council.bf_pharmacy_technicians.`middle_name`,
        'last_name',
        council.bf_pharmacy_technicians.`last_name`,
        'date_of_birth',
        council.bf_pharmacy_technicians.`date_of_birth`,
        'license_number',
        council.bf_pharmacy_technicians.`registration_number`,
        'sex',
        council.bf_pharmacy_technicians.`sex`,
        'marital_status',
        council.bf_pharmacy_technicians.`marital_status`,
        'nationality',
        council.bf_pharmacy_technicians.`nationality`,
        'qualification',
        council.bf_pharmacy_technicians.`qualification`,
        'training_institution',
        council.bf_pharmacy_technicians.`training_institution`,
        'qualification_date',
        council.bf_pharmacy_technicians.`qualification_date`,
        'residential_address',
        council.bf_pharmacy_technicians.`residential_address`,
        'picture',
        council.bf_pharmacy_technicians.`picture`,
        'postal_address',
        council.bf_pharmacy_technicians.`postal_address`,
        'country_of_practice',
        council.bf_pharmacy_technicians.`country_of_practice`,
        'phone',
        council.bf_pharmacy_technicians.`phone`,
        'email',
        council.bf_pharmacy_technicians.`email`
    ),
    ci4_pc2.`license_renewal`.`name` = CONCAT_WS(
        ' ',
        council.bf_pharmacy_technicians.`first_name`,
        council.bf_pharmacy_technicians.`middle_name`,
        council.bf_pharmacy_technicians.`last_name`
    ),
    ci4_pc2.`license_renewal`.`region` = r.name,
    ci4_pc2.`license_renewal`.`email` = council.bf_pharmacy_technicians.`email`,
    ci4_pc2.`license_renewal`.`phone` = council.bf_pharmacy_technicians.`phone`,
    ci4_pc2.`license_renewal`.`picture` = council.bf_pharmacy_technicians.`picture`,
    ci4_pc2.`license_renewal`.`country_of_practice` = council.bf_pharmacy_technicians.`country_of_practice`
WHERE
    ci4_pc2.`license_renewal`.`data_snapshot` IS NULL;

#############-END UPDATE LICENSE DATA SNAPSHOT###########
#-PHARMACIES    renewal. the renewal is split into the licenses_renewal and practitioners_renewal. the licenses_renewal is the main table that holds the renewal data and the facility_renewal is the table that holds the additional data for the pharmacies
INSERT INTO
    ci4_pc2.`license_renewal`(
        `license_number`,
        `created_by`,
        `created_on`,
        `start_date`,
        `qr_code`,
        `qr_text`,
        `status`,
        `payment_date`,
        `payment_file`,
        `payment_invoice_number`,
        `license_uuid`,
        `license_type`,
        `data_snapshot`,
        `approve_online_certificate`,
        `online_certificate_start_date`,
        `online_certificate_end_date`,
        `in_print_queue`,
        `batch_number`
    )
SELECT
    br.license_number,
    br.created_by,
    br.created_on,
    CONCAT(br.year, '-01-01'),
    br.qr_code,
    br.qr_text,
    CASE
        WHEN br.life_stage IN ('In Print Queue', 'Printed') THEN 'Approved'
        ELSE br.life_stage
    END,
    br.payment_date,
    br.payment_file,
    br.payment_invoice_number,
    NULL,
    'facilities',
    NULL,
    br.approve_online_certificate,
    br.online_certificate_start_date,
    br.online_certificate_end_date,
    CASE
        WHEN pq.renewal_id IS NOT NULL THEN 1
        ELSE 0
    END,
    br.batch_number
FROM
    council.bf_renewal br
    LEFT JOIN council.bf_print_queue pq ON br.id = pq.renewal_id;

#############END PHARMACIES RENEWAL MIGRATION###########
#update the license_renewal table to add the data_snapshot data . this should be a json object with all the data from the pharmacy_technicians table
UPDATE
    ci4_pc2.`license_renewal`
    INNER JOIN council.`bf_pharmacies` ON council.`bf_pharmacies`.`license_number` = ci4_pc2.`license_renewal`.`license_number`
    LEFT JOIN council.bf_region r ON (
        council.bf_pharmacies.region IS NOT NULL
        AND council.bf_pharmacies.region != ''
        AND council.bf_pharmacies.region = r.id
    )
SET
    ci4_pc2.`license_renewal`.`data_snapshot` = JSON_OBJECT(
        'name',
        council.bf_pharmacies.`name`,
        'business_type',
        council.bf_pharmacies.`business_type`,
        'status',
        council.bf_pharmacies.`status`,
        'registration_date',
        council.bf_pharmacies.`registration_date`,
        'license_number',
        council.bf_pharmacies.`license_number`,
        'house_number',
        council.bf_pharmacies.`house_number`,
        'street',
        council.bf_pharmacies.`street`,
        'town',
        council.bf_pharmacies.`town`,
        'district',
        council.bf_pharmacies.`district`,
        'suburb',
        council.bf_pharmacies.`suburb`,
        'region',
        council.bf_pharmacies.`region`,
        'phone',
        council.bf_pharmacies.`phone`,
        'email',
        council.bf_pharmacies.`email`,
        'latitude',
        council.bf_pharmacies.`latitude`,
        'longitude',
        council.bf_pharmacies.`longitude`,
        'ownership',
        council.bf_pharmacies.`ownership`,
        'postal_address',
        council.bf_pharmacies.`postal_address`,
        'application_code',
        council.bf_pharmacies.`application_code`,
        'company_id',
        council.bf_pharmacies.`company_id`,
        'ghana_post_code',
        council.bf_pharmacies.`ghana_post_code`,
        'notes',
        council.bf_pharmacies.`notes`,
        'cbd',
        council.bf_pharmacies.`cbd`
    ),
    ci4_pc2.`license_renewal`.`name` = council.bf_pharmacies.`name`,
    ci4_pc2.`license_renewal`.`region` = r.name,
    ci4_pc2.`license_renewal`.`email` = council.bf_pharmacies.`email`,
    ci4_pc2.`license_renewal`.`phone` = council.bf_pharmacies.`phone`
WHERE
    ci4_pc2.`license_renewal`.`data_snapshot` IS NULL;

#############-END UPDATE LICENSE DATA SNAPSHOT###########
# update the practitioner_renewal table with the license_renewal table id . Create a temporary table to store the mapping for the practitioner renewal
#RUN THIS ONLY WHEN ALL THE RENEWALS FOR PRACTITIONERS HAVE BEEN INSERTED INTO LICENSE RENEWAL
CREATE TEMPORARY TABLE temp_renewal_mapping AS
SELECT
    b.reg_num,
    b.year,
    l.id AS renewal_id
FROM
    council.bf_retention b
    JOIN ci4_pc2.license_renewal l ON (
        b.reg_num = l.license_number
        and b.year = l.start_date
    )
WHERE
    l.license_type = 'practitioners';

# Add an index for faster lookups
ALTER TABLE
    temp_renewal_mapping
ADD
    INDEX (reg_num);

INSERT
    IGNORE INTO ci4_pc2.practitioners_renewal(
        `renewal_id`,
        # Adding the parent ID
        `license_number`,
        `place_of_work`,
        `region`,
        `institution_type`,
        `practitioner_type`
    )
SELECT
    m.renewal_id,
    # Get parent ID from the mapping table
    b.reg_num,
    JSON_UNQUOTE(JSON_EXTRACT(b.work_history, '$[0].institution')) AS place_of_work,
    JSON_UNQUOTE(JSON_EXTRACT(b.work_history, '$[0].location')) AS region,
    JSON_UNQUOTE(
        JSON_EXTRACT(b.work_history, '$[0].institution_type')
    ) AS institution_type,
    'Pharmacist'
FROM
    council.bf_retention b
    JOIN temp_renewal_mapping m ON b.reg_num = m.reg_num
    and b.year = m.year;

# Clean up
DROP TEMPORARY TABLE temp_renewal_mapping;

#-END insert PRACTITIONER RENEWAL#########-
# update the practitioner_renewal table with the license_renewal table id . Create a temporary table to store the mapping for the practitioner renewal
#RUN THIS ONLY WHEN ALL THE RENEWALS FOR PRACTITIONERS HAVE BEEN INSERTED INTO LICENSE RENEWAL
CREATE TEMPORARY TABLE temp_renewal_mapping AS
SELECT
    b.reg_num,
    b.year,
    l.id AS renewal_id
FROM
    council.bf_technicians_retention b
    JOIN ci4_pc2.license_renewal l ON (
        b.reg_num = l.license_number
        and b.year = l.start_date
    )
WHERE
    l.license_type = 'practitioners'
    AND l.license_number LIKE '%PT%'
GROUP BY
    renewal_id;

# Add an index for faster lookups
ALTER TABLE
    temp_renewal_mapping
ADD
    INDEX (reg_num);

INSERT
    IGNORE INTO ci4_pc2.practitioners_renewal(
        `renewal_id`,
        # Adding the parent ID
        `license_number`,
        `practitioner_type`
    )
SELECT
    m.renewal_id,
    # Get parent ID from the mapping table
    b.reg_num,
    'Pharmacy Technician'
FROM
    council.bf_technicians_retention b
    JOIN temp_renewal_mapping m ON b.reg_num = m.reg_num
    and b.year = m.year;

# Clean up
DROP TEMPORARY TABLE temp_renewal_mapping;

#-END UPDATE PRACTITIONER RENEWAL#########-
#UPDATE PRACTITIONER RENEWAL DETAILS BASED ON REGISTRATION NUMBER  FOR pharmacists##
UPDATE
    ci4_pc2.`practitioners_renewal` pr
    JOIN council.`bf_pharmacist` d1 ON d1.`registration_number` = pr.`license_number`
SET
    pr.`first_name` = d1.`first_name`,
    pr.`middle_name` = d1.`middle_name`,
    pr.`last_name` = d1.`last_name`,
    pr.`title` = d1.`title`,
    pr.`maiden_name` = d1.`maiden_name`,
    pr.`marital_status` = d1.`marital_status`,
    pr.`sex` = d1.`sex`,
    pr.`practitioner_type` = 'Pharmacist',
    pr.`qualifications` = (
        SELECT
            CASE
                WHEN COUNT(*) = 0 THEN '[]'
                ELSE CONCAT(
                    '[',
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'qualification',
                            q.`qualification`,
                            'institution',
                            q.`institution`,
                            'start_date',
                            q.`start_date`,
                            'end_date',
                            q.`end_date`
                        ) SEPARATOR ','
                    ),
                    ']'
                )
            END
        FROM
            council.`bf_pharmacist_school` q
        WHERE
            q.`reg_num` = pr.`license_number`
    );

#############-END UPDATE PRACTITIONER RENEWAL DETAILS BASED ON REGISTRATION NUMBER###########
#UPDATE PRACTITIONER RENEWAL DETAILS BASED ON REGISTRATION NUMBER  FOR technicians##
UPDATE
    ci4_pc2.`practitioners_renewal` pr
    JOIN council.`bf_pharmacy_technicians` d1 ON d1.`registration_number` = pr.`license_number`
SET
    pr.`first_name` = d1.`first_name`,
    pr.`middle_name` = d1.`middle_name`,
    pr.`last_name` = d1.`last_name`,
    pr.`maiden_name` = d1.`maiden_name`,
    pr.`marital_status` = d1.`marital_status`,
    pr.`sex` = d1.`sex`,
    pr.`practitioner_type` = 'Pharmacy Technician',
    pr.`qualifications` = '[]';

#############-END UPDATE PRACTITIONER RENEWAL DETAILS BASED ON REGISTRATION NUMBER###########
#RUN THIS ONLY WHEN ALL THE RENEWALS FOR FACILITIES HAVE BEEN INSERTED INTO LICENSE RENEWAL
CREATE TEMPORARY TABLE temp_renewal_mapping AS
SELECT
    b.license_number,
    b.selection_date,
    l.id AS renewal_id
FROM
    council.bf_renewal b
    JOIN ci4_pc2.license_renewal l ON (
        b.license_number = l.license_number
        and CONCAT(b.year, '-01-01') = l.start_date
    )
WHERE
    l.license_type = 'facilities';

# Add an index for faster lookups
ALTER TABLE
    temp_renewal_mapping
ADD
    INDEX (license_number);

INSERT
    IGNORE INTO ci4_pc2.facility_renewal(
        `renewal_id`,
        # Adding the parent ID
        `license_number`,
        `practitioner_in_charge`,
        `weekdays_start_time`,
        `weekdays_end_time`,
        `weekend_start_time`,
        `weekend_end_time`,
        `in_charge_start_time`,
        `in_charge_end_time`,
        `selection_date`,
        `authorization_date`,
        `print_date`,
        `actual_print_date`,
        `authorized_by`,
        `printed_by`,
        `receive_date`,
        `received_by`,
        `support_staff`,
        `practitioner_in_charge_details`,
        `business_type`
    )
SELECT
    m.renewal_id,
    # Get parent ID from the mapping table
    b.license_number,
    b.reg_num,
    b.weekdays_start_time,
    b.weekdays_end_time,
    b.weekend_start_time,
    b.weekend_end_time,
    b.superintendent_start_time,
    b.superintendent_end_time,
    b.selection_date,
    b.authorization_date,
    b.print_date,
    b.actual_print_date,
    b.authorized_by,
    b.printed_by,
    b.receive_date,
    b.received_by,
    CASE
        WHEN b.support_staff IS NOT NULL
        AND JSON_VALID(b.support_staff) THEN b.support_staff
        ELSE NULL
    END,
    CASE
        WHEN p.registration_number IS NOT NULL THEN JSON_OBJECT(
            'first_name',
            p.first_name,
            'last_name',
            p.last_name,
            'email',
            p.email,
            'phone',
            p.phone,
            'registration_number',
            p.registration_number,
            'picture',
            p.picture
        )
        ELSE NULL
    END,
    SUBSTRING_INDEX(
        SUBSTRING_INDEX(b.license_number, '/', 3),
        '/',
        -1
    )
FROM
    council.bf_renewal b
    JOIN temp_renewal_mapping m ON (
        b.license_number = m.license_number
        and b.selection_date = m.selection_date
    )
    LEFT JOIN council.bf_pharmacist p ON (p.registration_number = b.reg_num);

# Clean up
DROP TEMPORARY TABLE temp_renewal_mapping;

#-END UPDATE PRACTITIONER RENEWAL#########-
#update the license_renewal table to add the license_uuid from the licenses table based on the license_number
CREATE TEMPORARY TABLE temp_license_map AS
SELECT
    license_number,
    uuid
FROM
    ci4_pc2.licenses;

# Update using the temporary table
UPDATE
    ci4_pc2.license_renewal lr
    JOIN temp_license_map tlm ON tlm.license_number = lr.license_number
SET
    lr.license_uuid = tlm.uuid
WHERE
    lr.license_uuid IS NULL;

# Clean up
DROP TEMPORARY TABLE temp_license_map;

#migration for OTCMS. import the data from the old database to the licenses table
INSERT
    IGNORE INTO ci4_pc2.`licenses`(
        `uuid`,
        `license_number`,
        `name`,
        `registration_date`,
        `status`,
        `email`,
        `postal_address`,
        `type`,
        `phone`,
        `portal_access`,
        `region`,
        `district`,
        `picture`
    )
SELECT
    '',
    license_number,
    council.bf_otcms.name,
    registration_date,
    status,
    email,
    postal_address,
    'otcms',
    phone,
    'yes',
    r.name as region,
    d.name as district,
    picture
FROM
    council.bf_otcms
    LEFT JOIN council.bf_region r ON (
        council.bf_otcms.regionId IS NOT NULL
        AND council.bf_otcms.regionId != ''
        AND council.bf_otcms.regionId = r.id
    )
    LEFT JOIN council.bf_district d ON (
        council.bf_otcms.districtId IS NOT NULL
        AND council.bf_otcms.districtId != ''
        AND bf_otcms.districtId = d.id
    )
WHERE
    council.bf_otcms.license_number IS NOT NULL
    AND council.bf_otcms.license_number != '';

########-END OTCMS LICENSE MIGRATION#######-
##MIGRATE OTCMS DETAILS INTO THE OTCMS TABLE##
INSERT
    IGNORE INTO ci4_pc2.`otcms`(
        `name`,
        `license_number`,
        `town`,
        `premises_address`,
        `picture`,
        `date_of_birth`,
        `sex`,
        `qualification`,
        `maiden_name`,
        `application_code`
    )
select
    `name`,
    `license_number`,
    `town`,
    `premises_address`,
    `picture`,
    `date_of_birth`,
    `sex`,
    `qualification`,
    `maiden_name`,
    `application_code`
from
    council.bf_otcms;

####-END OTCMS MIGRATION#######-
#-OTCMS    renewal. the renewal is split into the licenses_renewal and practitioners_renewal. the licenses_renewal is the main table that holds the renewal data and the otcms_renewal is the table that holds the additional data for the otcms
INSERT INTO
    ci4_pc2.`license_renewal`(
        `license_number`,
        `created_by`,
        `created_on`,
        `start_date`,
        `qr_code`,
        `qr_text`,
        `status`,
        `payment_invoice_number`,
        `license_uuid`,
        `license_type`,
        `data_snapshot`,
        `approve_online_certificate`,
        `in_print_queue`,
        `batch_number`
    )
SELECT
    br.license_number,
    br.created_by,
    br.created_on,
    CONCAT(br.year, '-01-01'),
    br.qr_code,
    br.qr_text,
    CASE
        WHEN life_stage = 0 THEN 'Pending Authorization'
        ELSE 'Approved'
    END,
    br.payment_invoice_number,
    NULL,
    'otcms',
    NULL,
    'no',
    CASE
        WHEN NOT EXISTS (
            SELECT
                1
            FROM
                council.bf_otcms_print_queue
            WHERE
                br.id = council.bf_otcms_print_queue.renewal_id
        ) THEN 1
        ELSE 0
    END,
    br.batch_number
FROM
    council.bf_otcms_renewal br;

#############END PHARMACIES RENEWAL MIGRATION###########
#update the license_renewal table to add the data_snapshot data . this should be a json object with all the data from the otcms table
UPDATE
    ci4_pc2.`license_renewal`
    INNER JOIN council.`bf_OTCMS` ON council.`bf_OTCMS`.`license_number` = ci4_pc2.`license_renewal`.`license_number`
    LEFT JOIN council.bf_region r ON (
        council.bf_otcms.regionId IS NOT NULL
        AND council.bf_otcms.regionId != 0
        AND council.bf_otcms.regionId = r.id
    )
    LEFT JOIN council.bf_district d ON (
        council.bf_otcms.districtId IS NOT NULL
        AND council.bf_otcms.districtId != 0
        AND council.bf_otcms.districtId = d.id
    )
SET
    ci4_pc2.`license_renewal`.`data_snapshot` = JSON_OBJECT(
        'name',
        council.bf_otcms.`name`,
        'status',
        council.bf_otcms.`status`,
        'registration_date',
        council.bf_otcms.`registration_date`,
        'license_number',
        council.bf_otcms.`license_number`,
        'premises_address',
        council.bf_otcms.`premises_address`,
        'picture',
        council.bf_otcms.`picture`,
        'town',
        council.bf_otcms.`town`,
        'district',
        d.name,
        'region',
        r.name,
        'phone',
        council.bf_otcms.`phone`,
        'email',
        council.bf_otcms.`email`,
        'latitude',
        council.bf_otcms.`latitude`,
        'longitude',
        council.bf_otcms.`longitude`,
        'date_of_birth',
        council.bf_otcms.`date_of_birth`,
        'postal_address',
        council.bf_otcms.`postal_address`,
        'application_code',
        council.bf_otcms.`application_code`,
        'sex',
        council.bf_otcms.`sex`,
        'qualification',
        council.bf_otcms.`qualification`,
        'maiden_name',
        council.bf_otcms.`maiden_name`
    ),
    ci4_pc2.`license_renewal`.`name` = council.bf_otcms.`name`,
    ci4_pc2.`license_renewal`.`region` = r.name,
    ci4_pc2.`license_renewal`.`district` = d.name,
    ci4_pc2.`license_renewal`.`email` = council.bf_otcms.`email`,
    ci4_pc2.`license_renewal`.`phone` = council.bf_otcms.`phone`
WHERE
    ci4_pc2.`license_renewal`.`data_snapshot` IS NULL
    AND ci4_pc2.`license_renewal`.`license_type` = 'otcms';

#############-END UPDATE LICENSE DATA SNAPSHOT###########
#update the license_renewal table to add the license_uuid from the licenses table based on the license_number
CREATE TEMPORARY TABLE temp_license_map AS
SELECT
    license_number,
    uuid
FROM
    ci4_pc2.licenses;

# Update using the temporary table
UPDATE
    ci4_pc2.license_renewal lr
    JOIN temp_license_map tlm ON tlm.license_number = lr.license_number
SET
    lr.license_uuid = tlm.uuid
WHERE
    lr.license_uuid IS NULL;

# Clean up
DROP TEMPORARY TABLE temp_license_map;

#RUN THIS ONLY WHEN ALL THE RENEWALS FOR otcms HAVE BEEN INSERTED INTO LICENSE RENEWAL
CREATE TEMPORARY TABLE temp_renewal_mapping AS
SELECT
    b.license_number,
    b.selection_date,
    l.id AS renewal_id
FROM
    council.bf_otcms_renewal b
    JOIN ci4_pc2.license_renewal l ON (
        b.license_number = l.license_number
        and CONCAT(b.year, '-01-01') = l.start_date
    )
WHERE
    l.license_type = 'otcms';

# Add an index for faster lookups
ALTER TABLE
    temp_renewal_mapping
ADD
    INDEX (license_number);

INSERT
    IGNORE INTO ci4_pc2.otcms_renewal(
        `renewal_id`,
        # Adding the parent ID
        `license_number`,
        `weekdays_start_time`,
        `weekdays_end_time`,
        `weekend_start_time`,
        `weekend_end_time`,
        `selection_date`,
        `authorization_date`,
        `print_date`,
        `actual_print_date`,
        `authorized_by`,
        `printed_by`,
        `receive_date`,
        `received_by`
    )
SELECT
    m.renewal_id,
    # Get parent ID from the mapping table
    b.license_number,
    b.weekdays_start_time,
    b.weekdays_end_time,
    b.weekend_start_time,
    b.weekend_end_time,
    b.selection_date,
    b.authorization_date,
    b.print_date,
    b.actual_print_date,
    b.authorized_by,
    b.printed_by,
    b.receive_date,
    b.received_by
FROM
    council.bf_otcms_renewal b
    JOIN temp_renewal_mapping m ON (
        b.license_number = m.license_number
        and b.selection_date = m.selection_date
    );

# Clean up
DROP TEMPORARY TABLE temp_renewal_mapping;

#END UPDATE OTCMS RENEWAL
#IMPORT PORTAL EDITS
# 1. PORTAL EDITS
-- Create a temporary table to store our results
CREATE TEMPORARY TABLE portal_edits_with_attachments AS
SELECT
    'practitioners' as practitioner_type,
    'Portal Edit' as form_type,
    e.registration_number as last_name,
    e.status,
    CONCAT('Edit_', e.id) as application_code,
    e.date as created_on,
    JSON_OBJECT(
        "registration_number",
        e.registration_number,
        "field",
        e.field,
        "value",
        e.value,
        "date",
        e.date,
        "status",
        e.status,
        "admin_date",
        e.admin_date,
        "action",
        e.action,
        "comment",
        e.comment,
        "admin_comment",
        e.admin_comment,
        "attachments",
        JSON_ARRAY()
    ) as form_data,
    e.id as edit_id
FROM
    council.`pharmacist_edit` e;

-- For each attachment, update the form_data to add to the attachments array
UPDATE
    portal_edits_with_attachments pe
    JOIN (
        SELECT
            edit_id,
            GROUP_CONCAT('"', filename, '"') as filenames
        FROM
            council.`pharmacist_edit_attachments`
        WHERE
            filename IS NOT NULL
            AND filename != ''
            AND filename != 'null'
            AND filename != 'undefined'
        GROUP BY
            edit_id
    ) a ON pe.edit_id = a.edit_id
SET
    pe.form_data = JSON_SET(
        pe.form_data,
        '$.attachments',
        CONCAT('[', a.filenames, ']')
    );

INSERT INTO
    ci4_pc2.`application_forms` (
        practitioner_type,
        form_type,
        last_name,
        status,
        application_code,
        created_on,
        form_data
    )
SELECT
    practitioner_type,
    form_type,
    last_name,
    status,
    application_code,
    created_on,
    form_data
FROM
    portal_edits_with_attachments;

#update portal edits status
update
    ci4_pc2.application_forms
set
    status = 'Pending approval'
where
    status = 'pending';

#END UPDATE PORTAL EDITS
#IMPORT PCIST_ADDITIONAL_QUALIFICATIONS
INSERT INTO
    ci4_pc2.`practitioner_additional_qualifications` (
        `uuid`,
        `registration_number`,
        `institution`,
        `qualification`,
        `start_date`,
        `end_date`,
        `created_on`
    )
SELECT
    '',
    `reg_num`,
    COALESCE(`institution`, ''),
    `qualification`,
    `start_date`,
    `end_date`,
    `created_on`
FROM
    council.`bf_pharmacist_school` ON DUPLICATE KEY
UPDATE
    `institution` =
VALUES
    (`institution`),
    `qualification` =
VALUES
    (`qualification`),
    `start_date` =
VALUES
    (`start_date`),
    `end_date` =
VALUES
    (`end_date`);

#END IMPORT PCIST_ADDITIONAL_QUALIFICATIONS#
#IMPORT TECHNICIAN_ADDITIONAL_QUALIFICATIONS
INSERT INTO
    ci4_pc2.`practitioner_additional_qualifications` (
        `uuid`,
        `registration_number`,
        `institution`,
        `qualification`,
        `start_date`,
        `end_date`,
        `created_on`
    )
SELECT
    '',
    `reg_num`,
    COALESCE(`institution`, ''),
    `qualification`,
    `start_date`,
    `end_date`,
    `created_on`
FROM
    council.`bf_pharmacist_school` ON DUPLICATE KEY
UPDATE
    `institution` =
VALUES
    (`institution`),
    `qualification` =
VALUES
    (`qualification`),
    `start_date` =
VALUES
    (`start_date`),
    `end_date` =
VALUES
    (`end_date`);

#END IMPORT PCIST_ADDITIONAL_QUALIFICATIONS#
#IMPORT pcist WORK HISTORY#
INSERT INTO
    ci4_pc2.`practitioner_work_history` (
        `uuid`,
        `registration_number`,
        `institution`,
        `start_date`,
        `end_date`,
        `position`,
        `region`,
        `location`,
        `institution_type`,
        `created_on`
    )
SELECT
    '',
    `reg_num`,
    `institution`,
    `start_date`,
    `end_date`,
    `position`,
    `region`,
    `location`,
    CASE
        WHEN `institution_type` IS NULL THEN ''
        ELSE `institution_type`
    END as institution_type,
    `created_on`
FROM
    council.`bf_pharmacist_work_history`
WHERE
    reg_num is not null;

#END IMPORT pcist WORK HISTORY#
INSERT INTO
    ci4_pc2.`practitioner_work_history` (
        `uuid`,
        `registration_number`,
        `institution`,
        `start_date`,
        `end_date`,
        `position`,
        `region`,
        `location`,
        `institution_type`,
        `created_on`
    )
SELECT
    '',
    `reg_num`,
    `institution`,
    `start_date`,
    `end_date`,
    `position`,
    `region`,
    `location`,
    CASE
        WHEN `institution_type` IS NULL THEN ''
        ELSE `institution_type`
    END as institution_type,
    `created_on`
FROM
    council.`bf_technicians_work_history`
WHERE
    reg_num is not null;

#END IMPORT pcist WORK HISTORY#
#IMPORT CPD PROVIDERS#
#BEFORE RUNNING THIS SCRIPT, MAKE SURE THE bf_cpd_facilities table has only unique names
INSERT
    IGNORE INTO ci4_pc2.`cpd_providers` (
        `name`,
        `location`,
        `phone`,
        `email`
    )
SELECT
    `name`,
    `location`,
    `phone`,
    `email`
FROM
    council.`bf_cpd_facilities`;

#END IMPORT CPD PROVIDERS#
#IMPORT CPD TOPICS#
CREATE TEMPORARY TABLE temp_cpd_provider_uuid_map AS
SELECT
    council.`bf_cpd_facilities`.id as pid,
    uuid
FROM
    ci4_pc2.`cpd_providers`
    JOIN council.`bf_cpd_facilities` ON council.`bf_cpd_facilities`.name = ci4_pc2.`cpd_providers`.name;

INSERT INTO
    ci4_pc2.`cpd_topics` (
        `topic`,
        `date`,
        `created_on`,
        `created_by`,
        `venue`,
        `group`,
        `end_date`,
        `credits`,
        `category`,
        `online`,
        `url`,
        `start_month`,
        `end_month`,
        `provider_uuid`
    )
SELECT
    `topic`,
    `date`,
    `created_on`,
    `created_by`,
    `venue`,
    `group`,
    `end_date`,
    `credits`,
    `category`,
    `online`,
    `url`,
    `start_month`,
    `end_month`,
    (
        SELECT
            uuid
        FROM
            temp_cpd_provider_uuid_map
        WHERE
            temp_cpd_provider_uuid_map.pid = council.`bf_cpd`.facility_id
    ) as provider_uuid
FROM
    council.`bf_cpd`;

# Clean up
# Clean up
DROP TEMPORARY TABLE temp_cpd_provider_uuid_map;

#END IMPORT CPD TOPICS#
#IMPORT CPD ATTENDANCE##
CREATE TEMPORARY TABLE temp_cpd_topic_uuid_map AS
SELECT
    council.`bf_cpd`.id as cid,
    uuid
FROM
    ci4_pc2.`cpd_topics`
    JOIN council.`bf_cpd` ON council.`bf_cpd`.topic = ci4_pc2.`cpd_topics`.topic
    AND council.`bf_cpd`.created_on = ci4_pc2.`cpd_topics`.created_on;

INSERT INTO
    ci4_pc2.`cpd_attendance` (
        `uuid`,
        `topic`,
        `attendance_date`,
        `license_number`,
        `venue`,
        `credits`,
        `category`,
        `cpd_uuid`
    )
SELECT
    '',
    `topic`,
    `attendance_date`,
    `lic_num`,
    council.`bf_cpd_attendance`.`venue`,
    `credits`,
    COALESCE(`category`, 1),
    (
        SELECT
            uuid
        FROM
            temp_cpd_topic_uuid_map
        WHERE
            temp_cpd_topic_uuid_map.cid = council.`bf_cpd`.id
        LIMIT
            1
    ) as cpd_uuid
FROM
    council.`bf_cpd_attendance`
    JOIN council.`bf_cpd` ON council.`bf_cpd`.id = council.`bf_cpd_attendance`.cpd_id;

DROP TEMPORARY TABLE temp_cpd_topic_uuid_map;

#END IMPORT CPD ATTENDANCE#
#IMPORT ONLINE CPD ATTENDANCE##
CREATE TEMPORARY TABLE temp_cpd_topic_uuid_map AS
SELECT
    council.`bf_cpd`.id as cid,
    uuid
FROM
    ci4_pc2.`cpd_topics`
    JOIN council.`bf_cpd` ON council.`bf_cpd`.topic = ci4_pc2.`cpd_topics`.topic
    AND council.`bf_cpd`.created_on = ci4_pc2.`cpd_topics`.created_on;

INSERT INTO
    ci4_pc2.`cpd_attendance` (
        `uuid`,
        `topic`,
        `attendance_date`,
        `license_number`,
        `venue`,
        `credits`,
        `category`,
        `cpd_uuid`
    )
SELECT
    '',
    `topic`,
    council.`bf_cpd_online_attendance`.`date`,
    `registration_number`,
    'online' as venue,
    `credits`,
    `category`,
    (
        SELECT
            uuid
        FROM
            temp_cpd_topic_uuid_map
        WHERE
            temp_cpd_topic_uuid_map.cid = council.`bf_cpd`.id
        LIMIT
            1
    ) as cpd_uuid
FROM
    council.`bf_cpd_online_attendance`
    JOIN council.`bf_cpd` ON council.`bf_cpd`.id = council.`bf_cpd_online_attendance`.cpd_id;

DROP TEMPORARY TABLE temp_cpd_topic_uuid_map;

#END IMPORT ONLINE CPD ATTENDANCE#
#IMPORT TEMP CPD ATTENDANCE INTO APPLICATION FORMS##
CREATE TEMPORARY TABLE temp_cpd_topic_uuid_map AS
SELECT
    council.`bf_cpd`.id as cid,
    uuid
FROM
    ci4_pc2.`cpd_topics`
    JOIN council.`bf_cpd` ON council.`bf_cpd`.topic = ci4_pc2.`cpd_topics`.topic
    AND council.`bf_cpd`.created_on = ci4_pc2.`cpd_topics`.created_on;

INSERT INTO
    ci4_pc2.`application_forms` (
        picture,
        first_name,
        last_name,
        middle_name,
        email,
        status,
        application_code,
        qr_code,
        practitioner_type,
        phone,
        created_on,
        form_data,
        form_type
    )
SELECT
    null,
    null,
    lic_num as last_name,
    null,
    null,
    'Pending approval' as status,
    concat('cpd_', council.`bf_cpd_attendance_temp`.`id`) as application_code,
    null,
    'practitioners' as practitioner_type,
    null,
    `date`,
    JSON_OBJECT(
        "topic",
        `topic`,
        "attendance_date",
        `attendance_date`,
        "venue",
        council.`bf_cpd_attendance_temp`.`venue`,
        "credits",
        `credits`,
        "category",
        `category`,
        "cpd_uuid",
        (
            SELECT
                uuid
            FROM
                temp_cpd_topic_uuid_map
            WHERE
                temp_cpd_topic_uuid_map.cid = council.`bf_cpd`.id
        ),
        "license_number",
        `lic_num`
    ) as form_data,
    'CPD Attendance' as form_type
FROM
    council.`bf_cpd_attendance_temp`
    JOIN council.`bf_cpd` ON council.`bf_cpd`.id = council.`bf_cpd_attendance_temp`.cpd_id;

DROP TEMPORARY TABLE temp_cpd_topic_uuid_map;

#END IMPORT TEMP CPD ATTENDANCE INTO APPLICATION FORMS##
#IMPORT HOUSEMANSHIP FACILITIES##
#BEFORE RUNNING THIS SCRIPT, MAKE SURE THE bf_intern_facilities table has only unique names
INSERT
    IGNORE INTO ci4_pc2.`housemanship_facilities` (
        `name`,
        `location`,
        `region`,
        `type`,
        `uuid`
    )
SELECT
    council.`bf_intern_facilities`.`name`,
    `location`,
    r.name,
    `type`,
    '' as uuid
FROM
    council.`bf_intern_facilities`
    LEFT JOIN council.bf_region r ON (
        council.bf_intern_facilities.region IS NOT NULL
        AND council.bf_intern_facilities.region = r.id
    );

#END IMPORT HOUSEMANSHIP FACILITIES#
#IMPORT PAYMENT FEES
INSERT
    IGNORE INTO ci4_pc2.`fees` (
        `payer_type`,
        `name`,
        `rate`,
        `created_on`,
        `category`,
        `service_code`,
        `chart_of_account`,
        `supports_variable_amount`,
        `currency`
    )
SELECT
    `payer_type`,
    `name`,
    `rate`,
    `created_on`,
    `category`,
    `service_code`,
    `chart_of_account`,
    `supports_variable_amount`,
    `currency`
FROM
    council.`bf_fees`;