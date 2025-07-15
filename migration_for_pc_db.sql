#IMPORTANT:  CHECK THE NAMES OF THE DATABASES AND TABLES. 
#both databases should be on the same server
#ci4_pc should be the name of the database that you are migrating to
#council should be the name of the database that you are migrating from
#IMPORT SETTINGS#
INSERT INTO
    ci4_pc.`settings` (
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
    ci4_pc.`settings`
WHERE
    `key` = 'site.languages';

#END IMPORT SETTINGS#
#IMPORT REGIONS#
INSERT INTO
    ci4_pc.`regions` (`name`)
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
    IGNORE INTO ci4_pc.`districts` (`district`, `region`)
SELECT
    `name`,
    (
        SELECT
            name
        FROM
            council.bf_region
        WHERE
            id = council.bf_district.region_Id
    ) as region
FROM
    council.`bf_district`;

#END IMPORT DISTRICTS#
#IMPORT SPECIALTIES#
#NO SPECIALTIES
#END IMPORT SPECIALTIES#
#IMPORT ROLES#
INSERT INTO
    ci4_pc.`roles` (`role_name`, `description`)
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
    IGNORE INTO ci4_pc.`users` (
        `username`,
        `regionId`,
        `position`,
        `picture`,
        `phone`,
        `email`,
        `role_name`,
        `user_type`
    )
SELECT
    `username`,
    `regionId`,
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
    council.`bf_users`;

#END IMPORT USERS#
#migration for PHARMACISTS. import the data from the old database to the licenses table
INSERT INTO
    ci4_pc.`licenses`(
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
    d.district as district,
    register_type
FROM
    council.bf_pharmacist
    LEFT JOIN ci4_pc.regions r ON (
        bf_pharmacist.region REGEXP '^[0-9]+$'
        AND CAST(bf_pharmacist.region AS UNSIGNED) = r.id
    )
    LEFT JOIN ci4_pc.districts d ON (
        bf_pharmacist.district IS NOT NULL
        AND bf_pharmacist.district != ''
        AND bf_pharmacist.district != 'Other'
        AND d.district = bf_pharmacist.district
    )
WHERE
    council.bf_pharmacist.registration_number IS NOT NULL
    AND council.bf_pharmacist.registration_number != '';

########-END PHARMACISTS LICENSE MIGRATION#######-
##MIGRATE PHARMACISTS DETAILS INTO THE PRACTITIONERS TABLE##
INSERT INTO
    ci4_pc.`practitioners`(
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
        `register_type`,
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
    `register_type`,
    `mailing_city`,
    `residential_city`,
    `residential_region`,
    `intern_code`,
    'Pharmacist' as practitioner_type
from
    council.bf_pharmacist;

####-END PHARMACISTS MIGRATION#######-
#migration for PHARMACY TECHNICIANS. import the data from the old database to the licenses table
INSERT INTO
    ci4_pc.`licenses`(
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
    LEFT JOIN ci4_pc.regions r ON (
        bf_pharmacy_technicians.region REGEXP '^[0-9]+$'
        AND CAST(bf_pharmacy_technicians.region AS UNSIGNED) = r.id
    )
WHERE
    council.bf_pharmacy_technicians.registration_number IS NOT NULL
    AND council.bf_pharmacy_technicians.registration_number != '';

########-END PHARMACY TECHNICIANS LICENSE MIGRATION#######-
##MIGRATE PHARMACY TECHNICIANS DETAILS INTO THE PRACTITIONERS TABLE##
INSERT INTO
    ci4_pc.`practitioners`(
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
INSERT INTO
    ci4_pc.`licenses`(
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
    d.district as district
FROM
    council.bf_pharmacies
    LEFT JOIN ci4_pc.regions r ON (
        bf_pharmacies.region REGEXP '^[0-9]+$'
        AND CAST(bf_pharmacies.region AS UNSIGNED) = r.id
    )
    LEFT JOIN ci4_pc.districts d ON (
        bf_pharmacies.district IS NOT NULL
        AND bf_pharmacies.district != ''
        AND bf_pharmacies.district != 'Other'
        AND d.district = bf_pharmacies.district
    )
WHERE
    council.bf_pharmacies.license_number IS NOT NULL
    AND council.bf_pharmacies.license_number != '';

########-END PHARMACIES LICENSE MIGRATION#######-
##MIGRATE PHARMACIES DETAILS INTO THE FACILITIES TABLE##
INSERT INTO
    ci4_pc.`facilities`(
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
#migration for OTCMS. import the data from the old database to the licenses table
INSERT
    IGNORE INTO ci4_pc.`licenses`(
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
    d.district as district,
    picture
FROM
    council.bf_otcms
    LEFT JOIN ci4_pc.regions r ON (
        bf_otcms.regionId IS NOT NULL
        AND bf_otcms.regionId = r.id
    )
    LEFT JOIN ci4_pc.districts d ON (
        bf_otcms.districtId IS NOT NULL
        AND bf_otcms.districtId = d.id
    )
WHERE
    council.bf_otcms.license_number IS NOT NULL
    AND council.bf_otcms.license_number != '';

########-END OTCMS LICENSE MIGRATION#######-
##MIGRATE OTCMS DETAILS INTO THE OTCMS TABLE##
INSERT
    IGNORE INTO ci4_pc.`otcms`(
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
#-PHARMACISTS renewal. the renewal is split into the licenses_renewal and practitioners_renewal. the licenses_renewal is the main table that holds the renewal data and the practitioners_renewal is the table that holds the additional data for the practitioners
INSERT INTO
    ci4_pc.`license_renewal`(
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
    ci4_pc.`license_renewal`
SET
    `data_snapshot` = (
        SELECT
            JSON_OBJECT(
                'first_name',
                `first_name`,
                'middle_name',
                `middle_name`,
                'last_name',
                `last_name`,
                'date_of_birth',
                `date_of_birth`,
                'license_number',
                `registration_number`,
                'sex',
                `sex`,
                'title',
                `title`,
                'maiden_name',
                `maiden_name`,
                'marital_status',
                `marital_status`,
                'nationality',
                `nationality`,
                'qualification_at_registration',
                `qualification_at_registration`,
                'training_institution',
                `training_institution`,
                'qualification_date',
                `qualification_date`,
                'residential_address',
                `residential_address`,
                'register_type',
                `register_type`,
                'picture',
                `picture`,
                'postal_address',
                `postal_address`,
                'country_of_practice',
                `country_of_practice`,
                'phone',
                `phone`,
                'email',
                `email`
            ) as data_snapshot
        FROM
            council.`bf_pharmacist`
        WHERE
            council.`bf_pharmacist`.`registration_number` = `license_renewal`.`license_number`
    )
WHERE
    `data_snapshot` IS NULL;

#############-END UPDATE LICENSE DATA SNAPSHOT###########
########-PHARMACY TECHINCIANS RENEWAL########-
#-physician assistants renewal. the renewal is split into the licenses_renewal and practitioners_renewal. the licenses_renewal is the main table that holds the renewal data and the practitioners_renewal is the table that holds the additional data for the practitioners
INSERT INTO
    ci4_pc.`license_renewal`(
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

#############END PAS RENEWAL MIGRATION###########
#update the license_renewal table to add the data_snapshot data . this should be a json object with all the data from the licenses + practitioners table
UPDATE
    ci4_pc.`license_renewal`
SET
    `data_snapshot` = (
        SELECT
            JSON_OBJECT(
                'first_name',
                `first_name`,
                'middle_name',
                `middle_name`,
                'last_name',
                `last_name`,
                'date_of_birth',
                `date_of_birth`,
                'license_number',
                `registration_number`,
                'sex',
                `sex`,
                'maiden_name',
                `maiden_name`,
                'marital_status',
                `marital_status`,
                'nationality',
                `nationality`,
                'qualification',
                `qualification`,
                'training_institution',
                `training_institution`,
                'qualification_date',
                `qualification_date`,
                'residential_address',
                `residential_address`,
                'picture',
                `picture`,
                'postal_address',
                `postal_address`,
                'country_of_practice',
                `country_of_practice`,
                'phone',
                `phone`,
                'email',
                `email`
            ) as data_snapshot
        FROM
            council.`bf_pharmacy_technicians`
        WHERE
            council.`bf_pharmacy_technicians`.`registration_number` = `license_renewal`.`license_number`
    )
WHERE
    `data_snapshot` IS NULL;

#############-END UPDATE LICENSE DATA SNAPSHOT###########