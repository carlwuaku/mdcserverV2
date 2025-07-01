#IMPORTANT:  CHECK THE NAMES OF THE DATABASES AND TABLES
#ci4_mdc4 should be the name of the database that you are migrating to
#mdc should be the name of the database that you are migrating from
#IMPORT SETTINGS#
INSERT INTO
    ci4_mdc4.`settings` (
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
    mdc.`bf_settings` ON DUPLICATE KEY
UPDATE
    `value` =
VALUES
    (`value`);

#END IMPORT SETTINGS#
#IMPORT REGIONS#
INSERT INTO
    ci4_mdc4.`regions` (`name`)
SELECT
    `name`
FROM
    mdc.`bf_region` ON DUPLICATE KEY
UPDATE
    `name` =
VALUES
    (`name`);

#END IMPORT REGIONS#
#-IMPORT DISTRICTS#
INSERT INTO
    ci4_mdc4.`districts` (`district`, `region`)
SELECT
    `district`,
    (
        SELECT
            name
        FROM
            mdc.bf_region
        WHERE
            id = mdc.bf_district.region
    ) as region
FROM
    mdc.`bf_district`;

#END IMPORT DISTRICTS#
#IMPORT SPECIALTIES#
INSERT INTO
    ci4_mdc4.`specialties` (`name`)
SELECT
    `name`
FROM
    mdc.`specialties`;

#END IMPORT SPECIALTIES#
#IMPORT SUBSPECIALTIES#
INSERT INTO
    ci4_mdc4.`subspecialties` (`subspecialty`, `specialty`)
SELECT
    `subspecialty`,
    (
        SELECT
            name
        FROM
            mdc.specialties
        WHERE
            id = mdc.subspecialties.specialty
    ) as specialty
FROM
    mdc.`subspecialties`;

#END IMPORT SUBSPECIALTIES#
#IMPORT ROLES#
INSERT INTO
    ci4_mdc4.`roles` (`role_name`, `description`)
SELECT
    `role_name`,
    `description`
FROM
    mdc.`bf_roles` ON DUPLICATE KEY
UPDATE
    `role_name` =
VALUES
    (`role_name`);

#END IMPORT ROLES#
#IMPORT USERS#
INSERT
    IGNORE INTO ci4_mdc4.`users` (
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
            mdc.bf_roles
        WHERE
            role_id = mdc.bf_users.role_id
    ) as role_name,
    'admin' as user_type
FROM
    mdc.`bf_users`;

#END IMPORT USERS#
#migration for doctors. import the data from the old database to the licenses table
INSERT INTO
    ci4_mdc4.`licenses`(
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
    portal_access,
    created_on,
    NULLIF(mdc.bf_doctor.region, '') as region,
    CASE
        WHEN d.id IS NULL THEN NULL
        ELSE NULLIF(bf_doctor.district, '')
    END as district,
    register_type
from
    mdc.bf_doctor
    LEFT JOIN ci4_mdc4.districts d ON d.district = bf_doctor.district
WHERE
    mdc.bf_doctor.registration_number IS NOT NULL
    AND mdc.bf_doctor.registration_number != '';

########-END DOCTORS LICENSE MIGRATION#######-
##MIGRATE DOCTORS DETAILS INTO THE PRACTITIONERS TABLE##
INSERT INTO
    ci4_mdc4.`practitioners`(
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
        `provisional_number`,
        `register_type`,
        `specialty`,
        `category`,
        `place_of_birth`,
        `year_of_provisional`,
        `year_of_permanent`,
        `mailing_city`,
        `mailing_region`,
        `residential_city`,
        `residential_region`,
        `criminal_offense`,
        `crime_details`,
        `referee1_name`,
        `referee1_phone`,
        `referee1_email`,
        `referee2_name`,
        `referee2_phone`,
        `referee2_email`,
        `subspecialty`,
        `institution_type`,
        `town`,
        `place_of_work`,
        `intern_code`,
        `practitioner_type`,
        `college_membership`
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
    `provisional_number`,
    `register_type`,
    `specialty`,
    `category`,
    `place_of_birth`,
    `year_of_provisional`,
    `year_of_permanent`,
    `mailing_city`,
    `mailing_region`,
    `residential_city`,
    `residential_region`,
    `criminal_offense`,
    `crime_details`,
    `referee1_name`,
    `referee1_phone`,
    `referee1_email`,
    `referee2_name`,
    `referee2_phone`,
    `referee2_email`,
    `subspecialty`,
    `institution_type`,
    `town`,
    `place_of_work`,
    `intern_code`,
    'Doctor' as practitioner_type,
    `college_membership`
from
    mdc.bf_doctor;

####-END DOCTORS MIGRATION#######-
#-migration for physician assistants
INSERT INTO
    ci4_mdc4.`licenses`(
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
    portal_access,
    created_on,
    NULLIF(mdc.bf_physician_assistant.region, '') as region,
    CASE
        WHEN d.id IS NULL THEN NULL
        ELSE NULLIF(mdc.bf_physician_assistant.district, '')
    END as district,
    register_type
from
    mdc.bf_physician_assistant
    LEFT JOIN ci4_mdc4.districts d ON d.district = bf_physician_assistant.district
WHERE
    mdc.bf_physician_assistant.registration_number IS NOT NULL
    AND mdc.bf_physician_assistant.registration_number != '';

INSERT INTO
    ci4_mdc4.`practitioners`(
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
        `provisional_number`,
        `register_type`,
        `specialty`,
        `category`,
        `place_of_birth`,
        `year_of_provisional`,
        `year_of_permanent`,
        `subspecialty`,
        `institution_type`,
        `town`,
        `place_of_work`,
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
    `provisional_number`,
    `register_type`,
    `specialty`,
    `category`,
    `place_of_birth`,
    `year_of_provisional`,
    `year_of_permanent`,
    `subspecialty`,
    `institution_type`,
    `town`,
    `place_of_work`,
    `intern_code`,
    'Physician Assistant' as practitioner_type
from
    mdc.bf_physician_assistant;

######END PHYSICIAN ASSISTANTS MIGRATION#######-
#-doctors renewal. the renewal is split into the licenses_renewal and practitioners_renewal. the licenses_renewal is the main table that holds the renewal data and the practitioners_renewal is the table that holds the additional data for the practitioners
INSERT INTO
    ci4_mdc4.`license_renewal`(
        `license_number`,
        `created_by`,
        `created_on`,
        `start_date`,
        `receipt`,
        `qr_code`,
        `qr_text`,
        `expiry`,
        `status`,
        `batch_number`,
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
    expiry,
    status,
    null,
    payment_date,
    payment_file,
    payment_invoice_number,
    null,
    'practitioners',
    null
from
    mdc.bf_retention;

#############END DOCTORS RENEWAL MIGRATION###########
#update the license_renewal table to add the data_snapshot data . this should be a json object with all the data from the licenses + practitioners table
UPDATE
    ci4_mdc4.`license_renewal`
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
                'specialty',
                `specialty`,
                'category',
                `category`,
                'picture',
                `picture`,
                'postal_address',
                `postal_address`
            ) as data_snapshot
        FROM
            mdc.`bf_doctor`
        WHERE
            mdc.`bf_doctor`.`registration_number` = `license_renewal`.`license_number`
    )
WHERE
    `data_snapshot` IS NULL;

#############-END UPDATE LICENSE DATA SNAPSHOT###########
#UPDATE SNAPSHOT BASED ON PROVISIONAL NUMBER##
UPDATE
    ci4_mdc4.`license_renewal`
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
                'provisional',
                'specialty',
                `specialty`,
                'category',
                `category`,
                'picture',
                `picture`,
                'postal_address',
                `postal_address`
            ) as data_snapshot
        FROM
            mdc.`bf_doctor` d1
        WHERE
            d1.`provisional_number` = `license_renewal`.`license_number`
            AND NOT EXISTS (
                SELECT
                    1
                FROM
                    mdc.`bf_doctor` d2
                WHERE
                    d2.`provisional_number` = `license_renewal`.`license_number`
                    AND d2.`id` != d1.`id`
            ) # this is to check if there is more than one record with the same provisional number. we can't just arbitrarily pick one since it could belong to the wrong person
    )
WHERE
    `data_snapshot` IS NULL;

##END UPDATE SNAPSHOT BASED ON PROVISIONAL NUMBER##
#UPDATE PRACTITIONER RENEWAL DETAILS BASED ON REGISTRATION NUMBER  FOR DOCTORS##
UPDATE
    ci4_mdc4.`practitioners_renewal` pr
    JOIN mdc.`bf_doctor` d1 ON d1.`registration_number` = pr.`license_number`
SET
    pr.`first_name` = d1.`first_name`,
    pr.`middle_name` = d1.`middle_name`,
    pr.`last_name` = d1.`last_name`,
    pr.`title` = d1.`title`,
    pr.`maiden_name` = d1.`maiden_name`,
    pr.`marital_status` = d1.`marital_status`,
    pr.`category` = d1.`category`,
    pr.`sex` = d1.`sex`,
    pr.`practitioner_type` = 'Doctor',
    pr.`register_type` = d1.`register_type`,
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
            mdc.`bf_doctor_school` q
        WHERE
            q.`reg_num` = pr.`license_number`
    );

#############-END UPDATE PRACTITIONER RENEWAL DETAILS BASED ON REGISTRATION NUMBER###########
#UPDATE  PRACTITIONER RENEWAL DETAILS BASED ON PROVISIONAL NUMBER##
UPDATE
    ci4_mdc4.`practitioners_renewal` pr
    JOIN mdc.`bf_doctor` d1 ON d1.`registration_number` = pr.`license_number`
SET
    pr.`first_name` = d1.`first_name`,
    pr.`middle_name` = d1.`middle_name`,
    pr.`last_name` = d1.`last_name`,
    pr.`title` = d1.`title`,
    pr.`maiden_name` = d1.`maiden_name`,
    pr.`marital_status` = d1.`marital_status`,
    pr.`category` = d1.`category`,
    pr.`sex` = d1.`sex`,
    pr.`practitioner_type` = 'Doctor',
    pr.`register_type` = 'Provisional',
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
            mdc.`bf_doctor_school` q
        WHERE
            q.`reg_num` = pr.`license_number`
    )
WHERE
    pr.`first_name` IS NULL;

##END UPDATE PRACTITIONER RENEWAL DETAILS BASED ON PROVISIONAL NUMBER FOR DOCTORS##
#########-END UPDATE PRACTITIONER RENEWAL#########-
########-PHYSICIAN ASSISTANTS RENEWAL########-
#-physician assistants renewal. the renewal is split into the licenses_renewal and practitioners_renewal. the licenses_renewal is the main table that holds the renewal data and the practitioners_renewal is the table that holds the additional data for the practitioners
INSERT INTO
    ci4_mdc4.`license_renewal`(
        `license_number`,
        `created_by`,
        `created_on`,
        `start_date`,
        `receipt`,
        `qr_code`,
        `qr_text`,
        `expiry`,
        `status`,
        `batch_number`,
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
    expiry,
    status,
    null,
    payment_date,
    payment_file,
    payment_invoice_number,
    null,
    'practitioners',
    null
from
    mdc.bf_pa_retention;

#############END PAS RENEWAL MIGRATION###########
#update the license_renewal table to add the license_uuid from the licenses table based on the license_number
CREATE TEMPORARY TABLE temp_license_map AS
SELECT
    license_number,
    uuid
FROM
    licenses;

# Update using the temporary table
UPDATE
    ci4_mdc4.license_renewal lr
    JOIN temp_license_map tlm ON tlm.license_number = lr.license_number
SET
    lr.license_uuid = tlm.uuid
WHERE
    lr.license_uuid IS NULL;

# Clean up
DROP TEMPORARY TABLE temp_license_map;

#########-END UPDATE LICENSE UUID#########-
#update the license_renewal table to add the license_uuid from the licenses table based on the PROVISIONAL license_number
CREATE TEMPORARY TABLE temp_license_map AS
SELECT
    provisional_number as license_number,
    uuid
FROM
    practitioners
    JOIN licenses ON licenses.license_number = practitioners.license_number
WHERE
    provisional_number IS NOT NULL
    AND provisional_number != ''
    AND provisional_number != practitioners.license_number;

# Update using the temporary table
UPDATE
    ci4_mdc4.license_renewal lr
    JOIN temp_license_map tlm ON tlm.license_number = lr.license_number
SET
    lr.license_uuid = tlm.uuid
WHERE
    lr.license_uuid IS NULL;

# Clean up
DROP TEMPORARY TABLE temp_license_map;

#########-END UPDATE LICENSE UUID#########-
#update the license_renewal table to add the data_snapshot data .for PAs this should be a json object with all the data from the licenses + practitioners table
UPDATE
    ci4_mdc4.`license_renewal`
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
                'specialty',
                `specialty`,
                'category',
                `category`,
                'picture',
                `picture`
            ) as data_snapshot
        FROM
            mdc.`bf_physician_assistant`
        WHERE
            mdc.`bf_physician_assistant`.`registration_number` = `license_renewal`.`license_number`
    )
WHERE
    `data_snapshot` IS NULL;

#############-END UPDATE LICENSE DATA SNAPSHOT PAs###########
# update the practitioner_renewal table with the license_renewal table id . Create a temporary table to store the mapping for the practitioner renewal
#RUN THIS ONLY WHEN ALL THE RENEWALS FOR PRACTITIONERS HAVE BEEN INSERTED INTO LICENSE RENEWAL
CREATE TEMPORARY TABLE temp_renewal_mapping AS
SELECT
    b.reg_num,
    b.created_on,
    l.id AS renewal_id
FROM
    mdc.bf_retention b
    JOIN ci4_mdc4.license_renewal l ON b.reg_num = l.license_number
    and b.created_on = l.created_on
WHERE
    l.license_type = 'practitioners';

# Add an index for faster lookups
ALTER TABLE
    temp_renewal_mapping
ADD
    INDEX (reg_num);

INSERT INTO
    ci4_mdc4.practitioners_renewal(
        `renewal_id`,
        # Adding the parent ID
        `license_number`,
        `specialty`,
        `place_of_work`,
        `region`,
        `institution_type`,
        `district`,
        `subspecialty`,
        `college_membership`
    )
SELECT
    m.renewal_id,
    # Get parent ID from the mapping table
    b.reg_num,
    b.specialty,
    b.place_of_work,
    b.region,
    b.institution_type,
    b.district,
    b.subspecialty,
    b.college_membership
FROM
    mdc.bf_retention b
    JOIN temp_renewal_mapping m ON b.reg_num = m.reg_num
    and b.created_on = m.created_on;

# Clean up
DROP TEMPORARY TABLE temp_renewal_mapping;

#-END UPDATE PRACTITIONER RENEWAL#########-
#RUN THIS ONLY WHEN ALL THE RENEWALS FOR PHYSICIAN ASSISTANT PRACTITIONERS HAVE BEEN INSERTED INTO LICENSE RENEWAL
CREATE TEMPORARY TABLE temp_renewal_mapping AS
SELECT
    b.reg_num,
    b.created_on,
    l.id AS renewal_id
FROM
    mdc.bf_pa_retention b
    JOIN ci4_mdc4.license_renewal l ON b.reg_num = l.license_number
    and b.created_on = l.created_on
WHERE
    l.license_type = 'practitioners';

# Add an index for faster lookups
ALTER TABLE
    temp_renewal_mapping
ADD
    INDEX (reg_num);

INSERT INTO
    ci4_mdc4.practitioners_renewal(
        `renewal_id`,
        # Adding the parent ID
        `license_number`,
        `place_of_work`,
        `region`,
        `institution_type`,
        `district`
    )
SELECT
    m.renewal_id,
    # Get parent ID from the mapping table
    b.reg_num,
    b.place_of_work,
    b.region,
    b.institution_type,
    b.district
FROM
    mdc.bf_pa_retention b
    JOIN temp_renewal_mapping m ON b.reg_num = m.reg_num
    and b.created_on = m.created_on;

# Clean up
DROP TEMPORARY TABLE temp_renewal_mapping;

#-END UPDATE PRACTITIONER RENEWAL#########-
#UPDATE PRACTITIONER RENEWAL DETAILS BASED ON REGISTRATION NUMBER  FOR PAS##
UPDATE
    ci4_mdc4.`practitioners_renewal` pr
    JOIN mdc.`bf_physician_assistant` d1 ON d1.`registration_number` = pr.`license_number`
SET
    pr.`first_name` = d1.`first_name`,
    pr.`middle_name` = d1.`middle_name`,
    pr.`last_name` = d1.`last_name`,
    pr.`title` = d1.`title`,
    pr.`maiden_name` = d1.`maiden_name`,
    pr.`marital_status` = d1.`marital_status`,
    pr.`category` = d1.`category`,
    pr.`sex` = d1.`sex`,
    pr.`practitioner_type` = 'Physician Assistant',
    pr.`register_type` = d1.`register_type`,
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
            mdc.`bf_pa_school` q
        WHERE
            q.`reg_num` = pr.`license_number`
    )
WHERE
    pr.`first_name` IS NULL;

#############-END UPDATE PRACTITIONER RENEWAL DETAILS BASED ON REGISTRATION NUMBER###########
#UPDATE  PRACTITIONER RENEWAL DETAILS BASED ON PROVISIONAL NUMBER##
UPDATE
    ci4_mdc4.`practitioners_renewal` pr
    JOIN mdc.`bf_physician_assistant` d1 ON d1.`registration_number` = pr.`license_number`
SET
    pr.`first_name` = d1.`first_name`,
    pr.`middle_name` = d1.`middle_name`,
    pr.`last_name` = d1.`last_name`,
    pr.`title` = d1.`title`,
    pr.`maiden_name` = d1.`maiden_name`,
    pr.`marital_status` = d1.`marital_status`,
    pr.`category` = d1.`category`,
    pr.`sex` = d1.`sex`,
    pr.`practitioner_type` = 'Physician Assistant',
    pr.`register_type` = 'Provisional',
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
            mdc.`bf_pa_school` q
        WHERE
            q.`reg_num` = pr.`license_number`
    )
WHERE
    pr.`first_name` IS NULL;

##END UPDATE PRACTITIONER RENEWAL DETAILS BASED ON PROVISIONAL NUMBER FOR DOCTORS##
############import applications##########
#IMPORT PERMANENT REGISTRATIONS##
INSERT INTO
    ci4_mdc4.`application_forms` (
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
    `picture`,
    `first_name`,
    `last_name`,
    `middle_name`,
    `email`,
    `status`,
    `intern_code` as application_code,
    `qrcode` as qr_code,
    `type` as practitioner_type,
    `phone`,
    `created_on`,
    JSON_OBJECT(
        "first_name",
        `first_name`,
        "middle_name",
        `middle_name`,
        "last_name",
        `last_name`,
        "email",
        `email`,
        "intern_code",
        `intern_code`,
        "sex",
        `sex`,
        "registration_date",
        `registration_date`,
        "nationality",
        `nationality`,
        "postal_address",
        `postal_address`,
        "residential_address",
        `residential_address`,
        "residential_city",
        `residential_city`,
        "picture",
        `picture`,
        "status",
        `status`,
        "residential_region",
        `residential_region`,
        "criminal_offense",
        `criminal_offense`,
        "training_institution",
        `training_institution`,
        "date_of_graduation",
        `date_of_graduation`,
        "qualification",
        `qualification`,
        "date_of_birth",
        `date_of_birth`,
        "mailing_city",
        `mailing_city`,
        "phone",
        `phone`,
        "place_of_birth",
        `place_of_birth`,
        "mailing_region",
        `mailing_region`,
        "crime_details",
        `crime_details`,
        "referee1_name",
        `referee1_name`,
        "referee1_phone",
        `referee1_phone`,
        "referee1_email",
        `referee1_email`,
        "referee2_name",
        `referee2_name`,
        "referee2_phone",
        `referee2_phone`,
        "referee2_email",
        `referee2_email`,
        "referee1_letter_attachment",
        `referee1_letter_attachment`,
        "referee2_letter_attachment",
        `referee2_letter_attachment`,
        "certificate",
        `certificate`,
        "category",
        `category`,
        "type",
        `type`,
        "title",
        `title`,
        "provisional_registration_number",
        `provisional_registration_number`,
        "date_of_provisional",
        `date_of_provisional`,
        "specialty",
        `specialty`,
        "disciplinary_action",
        `disciplinary_action`,
        "disciplinary_action_details",
        `disciplinary_action_details`,
        "licensing_authority",
        `licensing_authority`,
        "hospital_1",
        `hospital_1`,
        "specialty_1",
        `specialty_1`,
        "start_1",
        `start_1`,
        "end_1",
        `end_1`,
        "hospital_2",
        `hospital_2`,
        "specialty_2",
        `specialty_2`,
        "start_2",
        `start_2`,
        "end_2",
        `end_2`,
        "hospital_3",
        `hospital_3`,
        "specialty_3",
        `specialty_3`,
        "start_3",
        `start_3`,
        "end_3",
        `end_3`,
        "hospital_4",
        `hospital_4`,
        "specialty_4",
        `specialty_4`,
        "start_4",
        `start_4`,
        "end_4",
        `end_4`,
        "other_hospital_1",
        `other_hospital_1`,
        "other_specialty_1",
        `other_specialty_1`,
        "other_start_1",
        `other_start_1`,
        "other_end_1",
        `other_end_1`,
        "other_hospital_2",
        `other_hospital_2`,
        "other_specialty_2",
        `other_specialty_2`,
        "other_start_2",
        `other_start_2`,
        "other_end_2",
        `other_end_2`,
        "other_hospital_3",
        `other_hospital_3`,
        "other_specialty_3",
        `other_specialty_3`,
        "other_start_3",
        `other_start_3`,
        "other_end_3",
        `other_end_3`,
        "rank_1",
        `rank_1`,
        "rank_2",
        `rank_2`,
        "rank_3",
        `rank_3`,
        "cv",
        `cv`,
        "license",
        `license`
    ) as form_data,
    'Practitioners Permanent Registration Application' as form_type
FROM
    mdc.`bf_permanent_registration_application`;

##END PERMANENT REGISTRATIONS##
# IMPORT TEMPORARY REGISTRATIONS##
INSERT INTO
    ci4_mdc4.`application_forms` (
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
    `picture`,
    `first_name`,
    `last_name`,
    `middle_name`,
    `email`,
    `status`,
    `intern_code` as application_code,
    `qrcode` as qr_code,
    `type` as practitioner_type,
    `phone`,
    `created_on`,
    JSON_OBJECT(
        "first_name",
        `first_name`,
        "middle_name",
        `middle_name`,
        "last_name",
        `last_name`,
        "email",
        `email`,
        "intern_code",
        `intern_code`,
        "sex",
        `sex`,
        "registration_date",
        `registration_date`,
        "nationality",
        `nationality`,
        "postal_address",
        `postal_address`,
        "residential_address",
        `residential_address`,
        "residential_city",
        `residential_city`,
        "picture",
        `picture`,
        "status",
        `status`,
        "residential_region",
        `residential_region`,
        "criminal_offense",
        `criminal_offense`,
        "training_institution",
        `training_institution`,
        "date_of_graduation",
        `date_of_graduation`,
        "qualification",
        `qualification`,
        "date_of_birth",
        `date_of_birth`,
        "mailing_city",
        `mailing_city`,
        "phone",
        `phone`,
        "place_of_birth",
        `place_of_birth`,
        "mailing_region",
        `mailing_region`,
        "crime_details",
        `crime_details`,
        "referee1_name",
        `referee1_name`,
        "referee1_phone",
        `referee1_phone`,
        "referee1_email",
        `referee1_email`,
        "referee2_name",
        `referee2_name`,
        "referee2_phone",
        `referee2_phone`,
        "referee2_email",
        `referee2_email`,
        "referee1_letter_attachment",
        `referee1_letter_attachment`,
        "referee2_letter_attachment",
        `referee2_letter_attachment`,
        "certificate",
        `certificate`,
        "category",
        `category`,
        "type",
        `type`,
        "title",
        `title`,
        "provisional_registration_number",
        `provisional_registration_number`,
        "date_of_provisional",
        `date_of_provisional`,
        "specialty",
        `specialty`,
        "disciplinary_action",
        `disciplinary_action`,
        "disciplinary_action_details",
        `disciplinary_action_details`,
        "licensing_authority",
        `licensing_authority`,
        "hospital_1",
        `hospital_1`,
        "specialty_1",
        `specialty_1`,
        "start_1",
        `start_1`,
        "end_1",
        `end_1`,
        "hospital_2",
        `hospital_2`,
        "specialty_2",
        `specialty_2`,
        "start_2",
        `start_2`,
        "end_2",
        `end_2`,
        "hospital_3",
        `hospital_3`,
        "specialty_3",
        `specialty_3`,
        "start_3",
        `start_3`,
        "end_3",
        `end_3`,
        "hospital_4",
        `hospital_4`,
        "specialty_4",
        `specialty_4`,
        "start_4",
        `start_4`,
        "end_4",
        `end_4`,
        "other_hospital_1",
        `other_hospital_1`,
        "other_specialty_1",
        `other_specialty_1`,
        "other_start_1",
        `other_start_1`,
        "other_end_1",
        `other_end_1`,
        "other_hospital_2",
        `other_hospital_2`,
        "other_specialty_2",
        `other_specialty_2`,
        "other_start_2",
        `other_start_2`,
        "other_end_2",
        `other_end_2`,
        "other_hospital_3",
        `other_hospital_3`,
        "other_specialty_3",
        `other_specialty_3`,
        "other_start_3",
        `other_start_3`,
        "other_end_3",
        `other_end_3`,
        "rank_1",
        `rank_1`,
        "rank_2",
        `rank_2`,
        "rank_3",
        `rank_3`,
        "cv",
        `cv`,
        "license",
        `license`,
        "additional_qualification",
        `additional_qualification`
    ) as form_data,
    'Practitioners Temporary Registration Application' as form_type
FROM
    mdc.`bf_temporary_registration_application`;

##END TEMPORARY REGISTRATIONS##
##PROVISIONAL REGISTRATIONS##
INSERT INTO
    ci4_mdc4.`application_forms` (
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
    `picture`,
    `first_name`,
    `last_name`,
    `middle_name`,
    `email`,
    `status`,
    concat('provisional_', `intern_code`) as application_code,
    `qrcode` as qr_code,
    `type` as practitioner_type,
    `phone`,
    `created_on`,
    JSON_OBJECT(
        "first_name",
        `first_name`,
        "middle_name",
        `middle_name`,
        "last_name",
        `last_name`,
        "email",
        `email`,
        "intern_code",
        `intern_code`,
        "sex",
        `sex`,
        "registration_date",
        `registration_date`,
        "nationality",
        `nationality`,
        "postal_address",
        `postal_address`,
        "residential_address",
        `residential_address`,
        "residential_city",
        `residential_city`,
        "picture",
        `picture`,
        "status",
        `status`,
        "residential_region",
        `residential_region`,
        "criminal_offense",
        `criminal_offense`,
        "training_institution",
        `training_institution`,
        "date_of_graduation",
        `date_of_graduation`,
        "qualification",
        `qualification`,
        "date_of_birth",
        `date_of_birth`,
        "mailing_city",
        `mailing_city`,
        "phone",
        `phone`,
        "place_of_birth",
        `place_of_birth`,
        "mailing_region",
        `mailing_region`,
        "crime_details",
        `crime_details`,
        "referee1_name",
        `referee1_name`,
        "referee1_phone",
        `referee1_phone`,
        "referee1_email",
        `referee1_email`,
        "referee2_name",
        `referee2_name`,
        "referee2_phone",
        `referee2_phone`,
        "referee2_email",
        `referee2_email`,
        "referee1_letter_attachment",
        `referee1_letter_attachment`,
        "referee2_letter_attachment",
        `referee2_letter_attachment`,
        "certificate",
        `certificate`,
        "category",
        `category`,
        "type",
        `type`
    ) as form_data,
    'Practitioners Provisional Registration Application' as form_type
FROM
    mdc.`bf_intern_pre_registration`;

##END PROVISIONAL REGISTRATIONS##
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
    mdc.`bf_doctor_edit` e;

-- For each attachment, update the form_data to add to the attachments array
UPDATE
    portal_edits_with_attachments pe
    JOIN (
        SELECT
            edit_id,
            GROUP_CONCAT('"', filename, '"') as filenames
        FROM
            mdc.`bf_doctor_edit_attachments`
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
    ci4_mdc4.`application_forms` (
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
    application_forms
set
    status = 'Pending approval'
where
    status = 'pending';

#END UPDATE PORTAL EDITS#
#-END IMPORT APPLICATIONS#
#IMPORT DOCTOR_ADDITIONAL_QUALIFICATIONS
INSERT INTO
    ci4_mdc4.`practitioner_additional_qualifications` (
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
    `institution`,
    `qualification`,
    `start_date`,
    `end_date`,
    `created_on`
FROM
    mdc.`bf_doctor_school` ON DUPLICATE KEY
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

#END IMPORT DOCTOR_ADDITIONAL_QUALIFICATIONS#
#IMPORT PA ADDITIONAL QUALIFICATIONS
INSERT INTO
    ci4_mdc4.`practitioner_additional_qualifications` (
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
    `institution`,
    `qualification`,
    `start_date`,
    `end_date`,
    `created_on`
FROM
    mdc.`bf_pa_school`;

#END IMPORT PA ADDITIONAL QUALIFICATIONS#
#IMPORT DOCTOR WORK HISTORY#
INSERT INTO
    ci4_mdc4.`practitioner_work_history` (
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
    mdc.`bf_doctor_work_history`;

#END IMPORT DOCTOR WORK HISTORY#
#IMPORT PA WORK HISTORY#
INSERT INTO
    ci4_mdc4.`practitioner_work_history` (
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
    mdc.`bf_pa_work_history`;

#END IMPORT PA WORK HISTORY#
#IMPORT CPD PROVIDERS#
#BEFORE RUNNING THIS SCRIPT, MAKE SURE THE bf_cpd_facilities table has only unique names
INSERT INTO
    ci4_mdc4.`cpd_providers` (
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
    mdc.`bf_cpd_facilities`;

#END IMPORT CPD PROVIDERS#
#IMPORT CPD TOPICS#
CREATE TEMPORARY TABLE temp_cpd_provider_uuid_map AS
SELECT
    mdc.`bf_cpd_facilities`.id as pid,
    uuid
FROM
    ci4_mdc4.`cpd_providers`
    JOIN mdc.`bf_cpd_facilities` ON mdc.`bf_cpd_facilities`.name = ci4_mdc4.`cpd_providers`.name;

INSERT INTO
    ci4_mdc4.`cpd_topics` (
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
            temp_cpd_provider_uuid_map.pid = mdc.`bf_cpd`.facility_id
    ) as provider_uuid
FROM
    mdc.`bf_cpd`;

# Clean up
# Clean up
DROP TEMPORARY TABLE temp_cpd_provider_uuid_map;

#END IMPORT CPD TOPICS#
#IMPORT CPD ATTENDANCE##
CREATE TEMPORARY TABLE temp_cpd_topic_uuid_map AS
SELECT
    mdc.`bf_cpd`.id as cid,
    uuid
FROM
    ci4_mdc4.`cpd_topics`
    JOIN mdc.`bf_cpd` ON mdc.`bf_cpd`.topic = ci4_mdc4.`cpd_topics`.topic
    AND mdc.`bf_cpd`.created_on = ci4_mdc4.`cpd_topics`.created_on;

INSERT INTO
    ci4_mdc4.`cpd_attendance` (
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
    mdc.`bf_cpd_attendance`.`venue`,
    `credits`,
    `category`,
    (
        SELECT
            uuid
        FROM
            temp_cpd_topic_uuid_map
        WHERE
            temp_cpd_topic_uuid_map.cid = mdc.`bf_cpd`.id
    ) as cpd_uuid
FROM
    mdc.`bf_cpd_attendance`
    JOIN mdc.`bf_cpd` ON mdc.`bf_cpd`.id = mdc.`bf_cpd_attendance`.cpd_id;

DROP TEMPORARY TABLE temp_cpd_topic_uuid_map;

#END IMPORT CPD ATTENDANCE#
#IMPORT ONLINE CPD ATTENDANCE##
CREATE TEMPORARY TABLE temp_cpd_topic_uuid_map AS
SELECT
    mdc.`bf_cpd`.id as cid,
    uuid
FROM
    ci4_mdc4.`cpd_topics`
    JOIN mdc.`bf_cpd` ON mdc.`bf_cpd`.topic = ci4_mdc4.`cpd_topics`.topic
    AND mdc.`bf_cpd`.created_on = ci4_mdc4.`cpd_topics`.created_on;

INSERT INTO
    ci4_mdc4.`cpd_attendance` (
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
    mdc.`bf_cpd_online_attendance`.`date`,
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
            temp_cpd_topic_uuid_map.cid = mdc.`bf_cpd`.id
    ) as cpd_uuid
FROM
    mdc.`bf_cpd_online_attendance`
    JOIN mdc.`bf_cpd` ON mdc.`bf_cpd`.id = mdc.`bf_cpd_online_attendance`.cpd_id;

DROP TEMPORARY TABLE temp_cpd_topic_uuid_map;

#END IMPORT ONLINE CPD ATTENDANCE#
#IMPORT TEMP CPD ATTENDANCE INTO APPLICATION FORMS##
CREATE TEMPORARY TABLE temp_cpd_topic_uuid_map AS
SELECT
    mdc.`bf_cpd`.id as cid,
    uuid
FROM
    ci4_mdc4.`cpd_topics`
    JOIN mdc.`bf_cpd` ON mdc.`bf_cpd`.topic = ci4_mdc4.`cpd_topics`.topic
    AND mdc.`bf_cpd`.created_on = ci4_mdc4.`cpd_topics`.created_on;

INSERT INTO
    ci4_mdc4.`application_forms` (
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
    concat('cpd_', mdc.`bf_cpd_attendance_temp`.`id`) as application_code,
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
        mdc.`bf_cpd_attendance_temp`.`venue`,
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
                temp_cpd_topic_uuid_map.cid = mdc.`bf_cpd`.id
        ),
        "license_number",
        `lic_num`
    ) as form_data,
    'CPD Attendance' as form_type
FROM
    mdc.`bf_cpd_attendance_temp`
    JOIN mdc.`bf_cpd` ON mdc.`bf_cpd`.id = mdc.`bf_cpd_attendance_temp`.cpd_id;

#IMPORT HOUSEMANSHIP FACILITIES##
#BEFORE RUNNING THIS SCRIPT, MAKE SURE THE bf_intern_facilities table has only unique names
INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facilities` (
        `name`,
        `location`,
        `region`,
        `type`,
        `uuid`
    )
SELECT
    `name`,
    `location`,
    `region`,
    `type`,
    '' as uuid
FROM
    mdc.`bf_intern_facilities`;

#END IMPORT HOUSEMANSHIP FACILITIES#
#IMPORT HOUSEMANSHIP FACILITY AVAILABILITY#
#previously the availability was stored in the bf_intern_facilities table as columns. they now have their own table so they can be stored per year per facility
INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_availability` (facility_name, year, category, available)
SELECT
    name AS facility_name,
    2025 AS year,
    'available' AS category,
    CASE
        WHEN available = 'Yes' THEN 1
        ELSE 0
    END AS available
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_availability` (facility_name, year, category, available)
SELECT
    name AS facility_name,
    2025 AS year,
    'medical' AS category,
    CASE
        WHEN medical = 'Yes' THEN 1
        ELSE 0
    END AS available
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_availability` (facility_name, year, category, available)
SELECT
    name AS facility_name,
    2025 AS year,
    'dental' AS category,
    CASE
        WHEN dental = 'Yes' THEN 1
        ELSE 0
    END AS available
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_availability` (facility_name, year, category, available)
SELECT
    name AS facility_name,
    2025 AS year,
    'available_pa_selection' AS category,
    CASE
        WHEN available_pa_selection = 'Yes' THEN 1
        ELSE 0
    END AS available
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_availability` (facility_name, year, category, available)
SELECT
    name AS facility_name,
    2025 AS year,
    'available_pa_medical_selection' AS category,
    CASE
        WHEN available_pa_medical_selection = 'Yes' THEN 1
        ELSE 0
    END AS available
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_availability` (facility_name, year, category, available)
SELECT
    name AS facility_name,
    2025 AS year,
    'available_pa_dental_selection' AS category,
    CASE
        WHEN available_pa_dental_selection = 'Yes' THEN 1
        ELSE 0
    END AS available
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_availability` (facility_name, year, category, available)
SELECT
    name AS facility_name,
    2025 AS year,
    'available_pa_cra_selection' AS category,
    CASE
        WHEN available_pa_cra_selection = 'Yes' THEN 1
        ELSE 0
    END AS available
FROM
    mdc.`bf_intern_facilities`;

#END IMPORT HOUSEMANSHIP FACILITY AVAILABILITY#
#IMPORT HOUSEMANSHIP CAPACITY#
#ADD EACH DISCIPLINE FIRST THEN THE MAX CAPACITY
INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
VALUES
    ('General medicine');

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_capacities` (facility_name, year, discipline, capacity)
SELECT
    name AS facility_name,
    2025 AS year,
    'General medicine' AS discipline,
    general_medicine AS capacity
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
VALUES
    ('Surgery');

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_capacities` (facility_name, year, discipline, capacity)
SELECT
    name AS facility_name,
    2025 AS year,
    'Surgery' AS discipline,
    surgery AS capacity
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
VALUES
    ('Obstetrics and gynaecology');

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_capacities` (facility_name, year, discipline, capacity)
SELECT
    name AS facility_name,
    2025 AS year,
    'Obstetrics
and gynaecology' AS discipline,
    gynae AS capacity
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
VALUES
    ('Paediatrics');

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_capacities` (facility_name, year, discipline, capacity)
SELECT
    name AS facility_name,
    2025 AS year,
    'Paediatrics' AS discipline,
    paediatrics AS capacity
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
VALUES
    ('Psychiatry');

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_capacities` (facility_name, year, discipline, capacity)
SELECT
    name AS facility_name,
    2025 AS year,
    'Psychiatry' AS discipline,
    psychiatry AS capacity
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
VALUES
    ('Anaesthesia');

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_capacities` (facility_name, year, discipline, capacity)
SELECT
    name AS facility_name,
    2025 AS year,
    'Anaesthesia' AS discipline,
    anaesthesia AS capacity
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
VALUES
    ('Dentistry');

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_capacities` (facility_name, year, discipline, capacity)
SELECT
    name AS facility_name,
    2025 AS year,
    'Dentistry' AS discipline,
    dentistry AS capacity
FROM
    mdc.`bf_intern_facilities`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
VALUES
    ('Emergency medicine');

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_facility_capacities` (facility_name, year, discipline, capacity)
SELECT
    name AS facility_name,
    2025 AS year,
    'Emergency medicine' AS discipline,
    emergency_medicine AS capacity
FROM
    mdc.`bf_intern_facilities`;

#END IMPORT HOUSEMANSHIP CAPACITY#
#IMPORT HOUSEMANSHIP POSTINGS SESSION 1#
INSERT
    IGNORE INTO ci4_mdc4.`housemanship_postings` (
        uuid,
        license_number,
        type,
        category,
        session,
        year,
        created_at
    )
SELECT
    '' as uuid,
    `registration_number` as license_number,
    `type`,
    `category`,
    '1' as session,
    `year`,
    `created_on` as created_at
FROM
    mdc.`bf_housemanship_posting`;

#END IMPORT HOUSEMANSHIP POSTINGS SESSION 1#
#IMPORT HOUSEMANSHIP POSTINGS SESSION 1 DETAILS#
CREATE TEMPORARY TABLE temp_housemanship_posting_uuid_map AS
SELECT
    mdc.`bf_housemanship_posting`.id as posting_id,
    uuid,
    name,
    region
FROM
    ci4_mdc4.`housemanship_postings`
    JOIN mdc.`bf_housemanship_posting` ON mdc.`bf_housemanship_posting`.registration_number = ci4_mdc4.`housemanship_postings`.license_number
    AND mdc.`bf_housemanship_posting`.created_on = ci4_mdc4.`housemanship_postings`.created_at
    JOIN mdc.`bf_intern_facilities` ON mdc.`bf_intern_facilities`.id = mdc.`bf_housemanship_posting`.facility_id;

;

INSERT INTO
    ci4_mdc4.`housemanship_postings_details` (
        posting_uuid,
        start_date,
        end_date,
        discipline,
        facility_name,
        facility_region
    )
SELECT
    (
        SELECT
            uuid
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_posting`.id
    ) as posting_uuid,
    `start_date`,
    `end_date`,
    `discipline_1` as discipline,
    (
        SELECT
            name
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_posting`.id
    ) as facility_name,
    (
        SELECT
            region
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_posting`.id
    ) as facility_region
FROM
    mdc.`bf_housemanship_posting`;

INSERT INTO
    ci4_mdc4.`housemanship_postings_details` (
        posting_uuid,
        start_date,
        end_date,
        discipline,
        facility_name,
        facility_region
    )
SELECT
    (
        SELECT
            uuid
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_posting`.id
    ) as posting_uuid,
    `discipline_2_start` as start_date,
    `discipline_2_end` as end_date,
    `discipline_2` as discipline,
    (
        SELECT
            name
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_posting`.id
    ) as facility_name,
    (
        SELECT
            region
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_posting`.id
    ) as facility_region
FROM
    mdc.`bf_housemanship_posting`;

DROP TEMPORARY TABLE temp_housemanship_posting_uuid_map;

#END IMPORT HOUSEMANSHIP POSTINGS SESSION 1 DETAILS#
#IMPORT HOUSEMANSHIP POSTINGS SESSION 2#
INSERT
    IGNORE INTO ci4_mdc4.`housemanship_postings` (
        uuid,
        license_number,
        type,
        category,
        session,
        year,
        created_at
    )
SELECT
    '' as uuid,
    `registration_number` as license_number,
    `type`,
    `category`,
    '2' as session,
    `year`,
    `created_on` as created_at
FROM
    mdc.`bf_housemanship_2_posting`;

#END IMPORT HOUSEMANSHIP POSTINGS SESSION 2#
#IMPORT HOUSEMANSHIP POSTINGS SESSION 2 DETAILS#
CREATE TEMPORARY TABLE temp_housemanship_posting_uuid_map AS
SELECT
    mdc.`bf_housemanship_2_posting`.id as posting_id,
    uuid,
    (
        SELECT
            name
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            id = mdc.`bf_housemanship_2_posting`.discipline_1_facility
    ) as facility_1_name,
    (
        SELECT
            region
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            id = mdc.`bf_housemanship_2_posting`.discipline_1_facility
    ) as facility_1_region,
    (
        SELECT
            name
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            id = mdc.`bf_housemanship_2_posting`.discipline_2_facility
    ) as facility_2_name,
    (
        SELECT
            region
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            id = mdc.`bf_housemanship_2_posting`.discipline_2_facility
    ) as facility_2_region
FROM
    ci4_mdc4.`housemanship_postings`
    JOIN mdc.`bf_housemanship_2_posting` ON mdc.`bf_housemanship_2_posting`.registration_number = ci4_mdc4.`housemanship_postings`.license_number
    AND mdc.`bf_housemanship_2_posting`.created_on = ci4_mdc4.`housemanship_postings`.created_at;

INSERT INTO
    ci4_mdc4.`housemanship_postings_details` (
        posting_uuid,
        start_date,
        end_date,
        discipline,
        facility_name,
        facility_region
    )
SELECT
    (
        SELECT
            uuid
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_2_posting`.id
    ) as posting_uuid,
    `discipline_1_start` as start_date,
    `discipline_1_end` as end_date,
    `discipline_1` as discipline,
    (
        SELECT
            facility_1_name
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_2_posting`.id
    ) as facility_name,
    (
        SELECT
            facility_1_region
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_2_posting`.id
    ) as facility_region
FROM
    mdc.`bf_housemanship_2_posting`;

INSERT INTO
    ci4_mdc4.`housemanship_postings_details` (
        posting_uuid,
        start_date,
        end_date,
        discipline,
        facility_name,
        facility_region
    )
SELECT
    (
        SELECT
            uuid
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_2_posting`.id
    ) as posting_uuid,
    `discipline_2_start` as start_date,
    `discipline_2_end` as end_date,
    `discipline_2` as discipline,
    (
        SELECT
            facility_2_name
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_2_posting`.id
    ) as facility_name,
    (
        SELECT
            facility_2_region
        FROM
            temp_housemanship_posting_uuid_map
        WHERE
            posting_id = mdc.`bf_housemanship_2_posting`.id
    ) as facility_region
FROM
    mdc.`bf_housemanship_2_posting`;

DROP TEMPORARY TABLE temp_housemanship_posting_uuid_map;

#END IMPORT HOUSEMANSHIP POSTINGS SESSION 1 DETAILS#
#UPDATE HOUSEMANSHIP POSTING PRACTITIONER DETAILS#
UPDATE
    ci4_mdc4.`housemanship_postings`
SET
    `practitioner_details` = (
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
                'specialty',
                `specialty`,
                'category',
                `category`,
                'picture',
                `picture`
            ) as practitioner_details
        FROM
            mdc.`bf_doctor`
        WHERE
            mdc.`bf_doctor`.`registration_number` = ci4_mdc4.`housemanship_postings`.`license_number`
            OR mdc.`bf_doctor`.`provisional_number` = ci4_mdc4.`housemanship_postings`.`license_number`
        LIMIT
            1
    )
WHERE
    `practitioner_details` IS NULL;

UPDATE
    ci4_mdc4.`housemanship_postings`
SET
    `practitioner_details` = (
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
                'specialty',
                `specialty`,
                'category',
                `category`,
                'picture',
                `picture`
            ) as practitioner_details
        FROM
            mdc.`bf_physician_assistant`
        WHERE
            mdc.`bf_physician_assistant`.`registration_number` = ci4_mdc4.`housemanship_postings`.`license_number`
            OR mdc.`bf_physician_assistant`.`provisional_number` = ci4_mdc4.`housemanship_postings`.`license_number`
        LIMIT
            1
    )
WHERE
    `practitioner_details` IS NULL
    AND `type` = 'PA';

#END UPDATE HOUSEMANSHIP POSTING PRACTITIONER DETAILS#
#UPDATE HOUSEMANSHIP POSTING DETAILS FACILITY DETAILS#
UPDATE
    ci4_mdc4.`housemanship_postings_details`
SET
    `facility_details` = (
        SELECT
            JSON_OBJECT(
                'name',
                `name`,
                'region',
                `region`,
                'location',
                `location`,
                'type',
                `type`
            ) as facility_details
        FROM
            ci4_mdc4.`housemanship_facilities`
        WHERE
            ci4_mdc4.`housemanship_postings_details`.facility_name = ci4_mdc4.`housemanship_facilities`.`name`
    )
WHERE
    `facility_details` IS NULL;

#END UPDATE HOUSEMANSHIP POSTING DETAILS FACILITY DETAILS#
#IMPORT HOUSEMANSHIP POSTING APPLICATIONS##
INSERT INTO
    ci4_mdc4.`housemanship_postings_applications` (
        uuid,
        license_number,
        type,
        year,
        created_at,
        session,
        status,
        date,
        tags,
        category
    )
SELECT
    '' as uuid,
    `registration_number` as license_number,
    `type`,
    year(date) as year,
    `created_on` as created_at,
    `session`,
    'Pending approval' as status,
    `date`,
    `extra_info` as tags,
    `category`
FROM
    mdc.`bf_housemanship_application`;

#END IMPORT HOUSEMANSHIP POSTING APPLICATIONS#
#IMPORT HOUSEMANSHIP POSTING APPLICATIONS DETAILS##
CREATE TEMPORARY TABLE temp_housemanship_posting_application_uuid_map AS
SELECT
    mdc.`bf_housemanship_application`.id as application_id,
    uuid
FROM
    ci4_mdc4.`housemanship_postings_applications`
    JOIN mdc.`bf_housemanship_application` ON mdc.`bf_housemanship_application`.registration_number = ci4_mdc4.`housemanship_postings_applications`.license_number
    AND mdc.`bf_housemanship_application`.created_on = ci4_mdc4.`housemanship_postings_applications`.created_at;

INSERT INTO
    ci4_mdc4.`housemanship_postings_application_details` (
        application_uuid,
        first_choice,
        first_choice_region,
        second_choice,
        second_choice_region
    )
SELECT
    (
        SELECT
            uuid
        FROM
            temp_housemanship_posting_application_uuid_map
        WHERE
            application_id = mdc.`bf_housemanship_application`.id
    ) as application_uuid,
    (
        SELECT
            name
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            first_choice = mdc.`bf_intern_facilities`.id
    ) as first_choice,
    (
        SELECT
            region
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            first_choice = mdc.`bf_intern_facilities`.id
    ) as first_choice_region,
    (
        SELECT
            name
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            second_choice = mdc.`bf_intern_facilities`.id
    ) as second_choice,
    (
        SELECT
            region
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            second_choice = mdc.`bf_intern_facilities`.id
    ) as second_choice_region
FROM
    mdc.`bf_housemanship_application`;

DROP TEMPORARY TABLE temp_housemanship_posting_application_uuid_map;

#END IMPORT HOUSEMANSHIP POSTING APPLICATIONS DETAILS#
#IMPORT HOUSEMANSHIP POSTING APPLICATIONS##
INSERT INTO
    ci4_mdc4.`housemanship_postings_applications` (
        uuid,
        license_number,
        type,
        year,
        created_at,
        session,
        status,
        date,
        category
    )
SELECT
    '' as uuid,
    `registration_number` as license_number,
    `type`,
    year,
    `created_on` as created_at,
    '2' as session,
    'Pending approval' as status,
    `date`,
    `category`
FROM
    mdc.`bf_housemanship_2_application`;

#END IMPORT HOUSEMANSHIP POSTING APPLICATIONS#
#IMPORT HOUSEMANSHIP POSTING APPLICATIONS DETAILS##
#GET THE DISCIPLINES INTO THE HOUSEMANSHIP DISCIPLINES TABLE
INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
SELECT
    `discipline_1` as name
FROM
    mdc.`bf_housemanship_2_application`;

INSERT
    IGNORE INTO ci4_mdc4.`housemanship_disciplines` (name)
SELECT
    `discipline_2` as name
FROM
    mdc.`bf_housemanship_2_application`;

CREATE TEMPORARY TABLE temp_housemanship_posting_application_uuid_map AS
SELECT
    mdc.`bf_housemanship_2_application`.id as application_id,
    uuid
FROM
    ci4_mdc4.`housemanship_postings_applications`
    JOIN mdc.`bf_housemanship_2_application` ON mdc.`bf_housemanship_2_application`.registration_number = ci4_mdc4.`housemanship_postings_applications`.license_number
    AND mdc.`bf_housemanship_2_application`.created_on = ci4_mdc4.`housemanship_postings_applications`.created_at;

INSERT INTO
    ci4_mdc4.`housemanship_postings_application_details` (
        application_uuid,
        discipline,
        first_choice,
        first_choice_region,
        second_choice,
        second_choice_region
    )
SELECT
    (
        SELECT
            uuid
        FROM
            temp_housemanship_posting_application_uuid_map
        WHERE
            application_id = mdc.`bf_housemanship_2_application`.id
    ) as application_uuid,
    `discipline_1` as discipline,
    (
        SELECT
            name
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            discipline_1_first_choice = mdc.`bf_intern_facilities`.id
    ) as first_choice,
    (
        SELECT
            region
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            discipline_1_first_choice = mdc.`bf_intern_facilities`.id
    ) as first_choice_region,
    (
        SELECT
            name
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            discipline_1_second_choice = mdc.`bf_intern_facilities`.id
    ) as second_choice,
    (
        SELECT
            region
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            discipline_1_second_choice = mdc.`bf_intern_facilities`.id
    ) as second_choice_region
FROM
    mdc.`bf_housemanship_2_application`;

INSERT INTO
    ci4_mdc4.`housemanship_postings_application_details` (
        application_uuid,
        discipline,
        first_choice,
        first_choice_region,
        second_choice,
        second_choice_region
    )
SELECT
    (
        SELECT
            uuid
        FROM
            temp_housemanship_posting_application_uuid_map
        WHERE
            application_id = mdc.`bf_housemanship_2_application`.id
    ) as application_uuid,
    `discipline_2` as discipline,
    (
        SELECT
            name
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            discipline_2_first_choice = mdc.`bf_intern_facilities`.id
    ) as first_choice,
    (
        SELECT
            region
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            discipline_2_first_choice = mdc.`bf_intern_facilities`.id
    ) as first_choice_region,
    (
        SELECT
            name
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            discipline_2_second_choice = mdc.`bf_intern_facilities`.id
    ) as second_choice,
    (
        SELECT
            region
        FROM
            mdc.`bf_intern_facilities`
        WHERE
            discipline_2_second_choice = mdc.`bf_intern_facilities`.id
    ) as second_choice_region
FROM
    mdc.`bf_housemanship_2_application`;

DROP TEMPORARY TABLE temp_housemanship_posting_application_uuid_map;

#END IMPORT HOUSEMANSHIP POSTING APPLICATIONS DETAILS#
#MIGRATE APPROVED EXAMINATION CANDIDATES INTO THE LICENSES TABLE. THOSE NOT APPROVED WILL BE MOVED TO THE APPLICATIONS TABLE##
INSERT INTO
    ci4_mdc4.`licenses`(
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
    intern_code,
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
    'exam_candidates',
    phone,
    portal_access,
    created_on,
    NULL,
    NULL,
    NULL
from
    mdc.bf_intern
WHERE
    mdc.bf_intern.intern_code IS NOT NULL
    AND mdc.bf_intern.intern_code != ''
    AND mdc.bf_intern.status = 'Approved';

########-END EXAMINATION CANDIDATES LICENSE MIGRATION#######- 
##MIGRATE EXAMINATION CANDIDATES DETAILS INTO THE exam_candidates TABLE##
INSERT INTO
    ci4_mdc4.`exam_candidates`(
        `first_name`,
        `middle_name`,
        `last_name`,
        `date_of_birth`,
        `intern_code`,
        `sex`,
        `registration_date`,
        `nationality`,
        `qualification`,
        `training_institution`,
        `qualification_date`,
        `state`,
        `specialty`,
        `category`,
        `practitioner_type`,
        `number_of_exams`,
        `metadata`
    )
select
    `first_name`,
    `middle_name`,
    `last_name`,
    `date_of_birth`,
    `intern_code`,
    `sex`,
    `registration_date`,
    `nationality`,
    `qualification`,
    `training_institution`,
    `date_of_graduation` as qualification_date,
    'Apply for examination' as state,
    `specialty`,
    `category`,
    `type` as practitioner_type,
    (
        SELECT
            COUNT(*)
        FROM
            mdc.bf_intern_exam_registration
        WHERE
            mdc.bf_intern_exam_registration.intern_code = mdc.bf_intern.intern_code
    ) as number_of_exams,
    JSON_OBJECT(
        'place_of_birth',
        `place_of_birth`,
        'mailing_city',
        `mailing_city`,
        'mailing_region',
        `mailing_region`,
        'residential_address',
        `residential_address`,
        'residential_city',
        `residential_city`,
        'residential_region',
        `residential_region`,
        'criminal_offense',
        `criminal_offense`,
        'crime_details',
        `crime_details`,
        'referee1_name',
        `referee1_name`,
        'referee1_phone',
        `referee1_phone`,
        'referee1_email',
        `referee1_email`,
        'referee2_name',
        `referee2_name`,
        'referee2_phone',
        `referee2_phone`,
        'referee2_email',
        `referee2_email`,
        'referee1_letter_attachment',
        `referee1_letter_attachment`,
        'referee2_letter_attachment',
        `referee2_letter_attachment`,
        'certificate',
        `certificate`,
        'cv_attachment',
        `cv_attachment`,
        'passport_attachment',
        `passport_attachment`,
        'wassce_attachment',
        `wassce_attachment`,
        'transcript_attachment',
        `transcript_attachment`,
        'residence_permit',
        `residence_permit`,
        'start_1',
        `start_1`,
        'end_1',
        `end_1`,
        'hospital_1',
        `hospital_1`,
        'specialty_1',
        `specialty_1`,
        'rank_1',
        `rank_1`,
        'start_2',
        `start_2`,
        'end_2',
        `end_2`,
        'hospital_2',
        `hospital_2`,
        'specialty_2',
        `specialty_2`,
        'rank_2',
        `rank_2`,
        'start_3',
        `start_3`,
        'end_3',
        `end_3`,
        'hospital_3',
        `hospital_3`,
        'specialty_3',
        `specialty_3`,
        'rank_3',
        `rank_3`,
        'start_4',
        `start_4`,
        'end_4',
        `end_4`,
        'hospital_4',
        `hospital_4`,
        'specialty_4',
        `specialty_4`,
        'other_start_1',
        `other_start_1`,
        'other_end_1',
        `other_end_1`,
        'other_hospital_1',
        `other_hospital_1`,
        'other_specialty_1',
        `other_specialty_1`,
        'other_start_2',
        `other_start_2`,
        'other_end_2',
        `other_end_2`,
        'other_hospital_2',
        `other_hospital_2`,
        'other_specialty_2',
        `other_specialty_2`,
        'other_start_3',
        `other_start_3`,
        'other_end_3',
        `other_end_3`,
        'other_hospital_3',
        `other_hospital_3`,
        'other_specialty_3',
        `other_specialty_3`,
        'remarks',
        `remarks`,
        'is_specialist',
        `is_specialist`,
        'specialist_qualification',
        `specialist_qualification`,
        'specialist_qualification_attachment',
        `specialist_qualification_attachment`,
        'current_license',
        `current_license`
    ) as metadata
from
    mdc.bf_intern
WHERE
    mdc.bf_intern.intern_code IS NOT NULL
    AND mdc.bf_intern.intern_code != ''
    AND mdc.bf_intern.status = 'Approved';

#END MIGRATE EXAMINATION CANDIDATES DETAILS INTO THE exam_candidates TABLE##
#UPDATE THE STATE FOR THE CANDIDATES.#
#if the candidate has no records in the bf_intern_exam_registration table, then they have not registered for any exams and should be in the 'Apply for examination' state
#if there are records, and one of them has a result 'Pass' and the exam_type is 'Regular', then they get the state 'Apply for migration'.
#if there are records and one of them has a result 'Pass' and exam_type 'OSCE 1', but no 'Pass' in 'OSCE 2', then they get the state 'Apply for examination'.
#if there are records and one of them has a result 'Pass' in 'OSCE 2', then they get the state 'Apply for migration'.
UPDATE
    ci4_mdc4.`exam_candidates`
SET
    `state` = CASE
        WHEN (
            SELECT
                COUNT(*)
            FROM
                mdc.bf_intern_exam_registration
                JOIN mdc.bf_intern_exam ON mdc.bf_intern_exam.id = mdc.bf_intern_exam_registration.exam_id
            WHERE
                mdc.bf_intern_exam_registration.intern_code = ci4_mdc4.`exam_candidates`.intern_code
        ) = 0 THEN 'Apply for examination'
        WHEN (
            SELECT
                COUNT(*)
            FROM
                mdc.bf_intern_exam_registration
                JOIN mdc.bf_intern_exam ON mdc.bf_intern_exam.id = mdc.bf_intern_exam_registration.exam_id
            WHERE
                mdc.bf_intern_exam_registration.intern_code = ci4_mdc4.`exam_candidates`.intern_code
                AND mdc.bf_intern_exam_registration.result = 'Pass'
                AND mdc.bf_intern_exam.exam_type = 'Regular'
        ) > 0 THEN 'Apply for migration'
        WHEN (
            SELECT
                COUNT(*)
            FROM
                mdc.bf_intern_exam_registration
                JOIN mdc.bf_intern_exam ON mdc.bf_intern_exam.id = mdc.bf_intern_exam_registration.exam_id
            WHERE
                mdc.bf_intern_exam_registration.intern_code = ci4_mdc4.`exam_candidates`.intern_code
                AND mdc.bf_intern_exam_registration.result = 'Pass'
                AND mdc.bf_intern_exam.exam_type = 'OSCE 1'
        ) > 0
        AND (
            SELECT
                COUNT(*)
            FROM
                mdc.bf_intern_exam_registration
                JOIN mdc.bf_intern_exam ON mdc.bf_intern_exam.id = mdc.bf_intern_exam_registration.exam_id
            WHERE
                mdc.bf_intern_exam_registration.intern_code = ci4_mdc4.`exam_candidates`.intern_code
                AND mdc.bf_intern_exam_registration.result = 'Pass'
                AND mdc.bf_intern_exam.exam_type = 'OSCE 2'
        ) = 0 THEN 'Apply for examination'
        WHEN (
            SELECT
                COUNT(*)
            FROM
                mdc.bf_intern_exam_registration
                JOIN mdc.bf_intern_exam ON mdc.bf_intern_exam.id = mdc.bf_intern_exam_registration.exam_id
            WHERE
                mdc.bf_intern_exam_registration.intern_code = ci4_mdc4.`exam_candidates`.intern_code
                AND mdc.bf_intern_exam_registration.result = 'Pass'
                AND mdc.bf_intern_exam.exam_type = 'OSCE 2'
        ) > 0 THEN 'Apply for migration'
        ELSE `state` -- keep the current state if none of the conditions match
    END;

#if a candidate has a state of 'Apply for migration', then check if there is a record in the practitioners table with the same first_name, last_name, and date_of_birth.
UPDATE
    ci4_mdc4.`exam_candidates`
SET
    `state` = 'Migrated'
WHERE
    `state` = 'Apply for migration'
    AND EXISTS (
        SELECT
            1
        FROM
            ci4_mdc4.`practitioners`
        WHERE
            ci4_mdc4.`practitioners`.first_name = ci4_mdc4.`exam_candidates`.first_name
            AND ci4_mdc4.`practitioners`.last_name = ci4_mdc4.`exam_candidates`.last_name
            AND ci4_mdc4.`practitioners`.date_of_birth = ci4_mdc4.`exam_candidates`.date_of_birth
    );

#END UPDATE THE STATE FOR THE CANDIDATES.#
#MIGRATE EXAM CANDIDATES PENDING APPROVAL INTO THE APPLICATIONS TABLE##
INSERT INTO
    ci4_mdc4.`application_forms` (
        picture,
        first_name,
        last_name,
        middle_name,
        email,
        status,
        application_code,
        practitioner_type,
        phone,
        created_on,
        form_data,
        form_type
    )
SELECT
    `picture`,
    `first_name`,
    `last_name`,
    `middle_name`,
    `email`,
    `status`,
    `intern_code` as application_code,
    `type` as practitioner_type,
    `phone`,
    `created_on`,
    JSON_OBJECT(
        'picture',
        `picture`,
        'first_name',
        `first_name`,
        'middle_name',
        `middle_name`,
        'last_name',
        `last_name`,
        'date_of_birth',
        `date_of_birth`,
        'intern_code',
        `intern_code`,
        'sex',
        `sex`,
        'registration_date',
        `registration_date`,
        'nationality',
        `nationality`,
        'qualification',
        `qualification`,
        'training_institution',
        `training_institution`,
        'date_of_graduation',
        `date_of_graduation`,
        'specialty',
        `specialty`,
        'category',
        `category`,
        'type',
        `type`,
        'place_of_birth',
        `place_of_birth`,
        'mailing_city',
        `mailing_city`,
        'mailing_region',
        `mailing_region`,
        'residential_address',
        `residential_address`,
        'residential_city',
        `residential_city`,
        'residential_region',
        `residential_region`,
        'criminal_offense',
        `criminal_offense`,
        'crime_details',
        `crime_details`,
        'referee1_name',
        `referee1_name`,
        'referee1_phone',
        `referee1_phone`,
        'referee1_email',
        `referee1_email`,
        'referee2_name',
        `referee2_name`,
        'referee2_phone',
        `referee2_phone`,
        'referee2_email',
        `referee2_email`,
        'referee1_letter_attachment',
        `referee1_letter_attachment`,
        'referee2_letter_attachment',
        `referee2_letter_attachment`,
        'certificate',
        `certificate`,
        'cv_attachment',
        `cv_attachment`,
        'passport_attachment',
        `passport_attachment`,
        'wassce_attachment',
        `wassce_attachment`,
        'transcript_attachment',
        `transcript_attachment`,
        'residence_permit',
        `residence_permit`,
        'start_1',
        `start_1`,
        'end_1',
        `end_1`,
        'hospital_1',
        `hospital_1`,
        'specialty_1',
        `specialty_1`,
        'rank_1',
        `rank_1`,
        'start_2',
        `start_2`,
        'end_2',
        `end_2`,
        'hospital_2',
        `hospital_2`,
        'specialty_2',
        `specialty_2`,
        'rank_2',
        `rank_2`,
        'start_3',
        `start_3`,
        'end_3',
        `end_3`,
        'hospital_3',
        `hospital_3`,
        'specialty_3',
        `specialty_3`,
        'rank_3',
        `rank_3`,
        'start_4',
        `start_4`,
        'end_4',
        `end_4`,
        'hospital_4',
        `hospital_4`,
        'specialty_4',
        `specialty_4`,
        'other_start_1',
        `other_start_1`,
        'other_end_1',
        `other_end_1`,
        'other_hospital_1',
        `other_hospital_1`,
        'other_specialty_1',
        `other_specialty_1`,
        'other_start_2',
        `other_start_2`,
        'other_end_2',
        `other_end_2`,
        'other_hospital_2',
        `other_hospital_2`,
        'other_specialty_2',
        `other_specialty_2`,
        'other_start_3',
        `other_start_3`,
        'other_end_3',
        `other_end_3`,
        'other_hospital_3',
        `other_hospital_3`,
        'other_specialty_3',
        `other_specialty_3`,
        'remarks',
        `remarks`,
        'is_specialist',
        `is_specialist`,
        'specialist_qualification',
        `specialist_qualification`,
        'specialist_qualification_attachment',
        `specialist_qualification_attachment`,
        'current_license',
        `current_license`
    ) as form_data,
    'Examination Candidates Registration Application' as form_type
FROM
    mdc.bf_intern
WHERE
    mdc.bf_intern.intern_code IS NOT NULL
    AND mdc.bf_intern.intern_code != ''
    AND mdc.bf_intern.status != 'Approved';

#END MIGRATE EXAMINATION CANDIDATES DETAILS INTO THE exam_candidates TABLE##
#MIGRATE EXAMINATIONS INTO EXAMINATIONS TABLE##
INSERT INTO
    ci4_mdc4.`examinations` (
        uuid,
        title,
        exam_type,
        open_from,
        open_to,
        type,
        publish_scores,
        publish_score_date,
        next_exam_month,
        scores_names,
        metadata
    )
SELECT
    '',
    `title`,
    `exam_type`,
    `open_from`,
    `open_to`,
    `type`,
    `publish_scores`,
    `publish_score_date`,
    `next_exam_month`,
    JSON_ARRAY('MCQ', 'PROBLEM SOLVING', 'ORALS') as scores_names,
    JSON_OBJECT(
        'venue',
        `venue`,
        'oral_location',
        `oral_location`,
        'oral_date',
        `oral_date`,
        'written_location',
        `written_location`,
        'written_date',
        `written_date`,
        'duration',
        `duration`,
        'specialist_date',
        `specialist_date`
    ) as metadata
FROM
    mdc.`bf_intern_exam`;

#END MIGRATE EXAMINATIONS INTO EXAMINATIONS TABLE##
#MIGRATE EXAMINATION LETTERS INTO EXAMINATION LETTERS TABLE##
INSERT INTO
    ci4_mdc4.`examination_letter_templates` (
        name,
        exam_id,
        type,
        content
    )
SELECT
    'Index number letter - All',
    examinations.`id`,
    'registration',
    COALESCE(index_number_letter, '')
FROM
    mdc.`bf_intern_exam`
    JOIN ci4_mdc4.`examinations` examinations ON mdc.`bf_intern_exam`.title = examinations.`title`;

INSERT INTO
    ci4_mdc4.`examination_letter_templates` (
        name,
        exam_id,
        type,
        content
    )
SELECT
    'Pass letter - All',
    examinations.`id`,
    'pass',
    COALESCE(pass_letter, '')
FROM
    mdc.`bf_intern_exam`
    JOIN ci4_mdc4.`examinations` examinations ON mdc.`bf_intern_exam`.title = examinations.`title`;

INSERT INTO
    ci4_mdc4.`examination_letter_templates` (
        name,
        exam_id,
        type,
        content
    )
SELECT
    'Fail letter - All',
    examinations.`id`,
    'fail',
    COALESCE(fail_letter, '')
FROM
    mdc.`bf_intern_exam`
    JOIN ci4_mdc4.`examinations` examinations ON mdc.`bf_intern_exam`.title = examinations.`title`;

INSERT INTO
    ci4_mdc4.`examination_letter_templates` (
        name,
        exam_id,
        type,
        content
    )
SELECT
    'Index number letter - Specialists',
    examinations.`id`,
    'registration',
    COALESCE(specialist_index_number_letter, '')
FROM
    mdc.`bf_intern_exam`
    JOIN ci4_mdc4.`examinations` examinations ON mdc.`bf_intern_exam`.title = examinations.`title`;

INSERT INTO
    ci4_mdc4.`examination_letter_templates` (
        name,
        exam_id,
        type,
        content
    )
SELECT
    'Pass letter - Specialists',
    examinations.`id`,
    'pass',
    COALESCE(specialist_pass_letter, '')
FROM
    mdc.`bf_intern_exam`
    JOIN ci4_mdc4.`examinations` examinations ON mdc.`bf_intern_exam`.title = examinations.`title`;

INSERT INTO
    ci4_mdc4.`examination_letter_templates` (
        name,
        exam_id,
        type,
        content
    )
SELECT
    'Fail letter - Specialists',
    examinations.`id`,
    'fail',
    COALESCE(specialist_fail_letter, '')
FROM
    mdc.`bf_intern_exam`
    JOIN ci4_mdc4.`examinations` examinations ON mdc.`bf_intern_exam`.title = examinations.`title`;

INSERT INTO
    ci4_mdc4.`examination_letter_templates` (
        name,
        exam_id,
        type,
        content
    )
SELECT
    'Index number letter - Dental',
    examinations.`id`,
    'registration',
    COALESCE(dental_index_number_letter, '')
FROM
    mdc.`bf_intern_exam`
    JOIN ci4_mdc4.`examinations` examinations ON mdc.`bf_intern_exam`.title = examinations.`title`;

INSERT INTO
    ci4_mdc4.`examination_letter_templates` (
        name,
        exam_id,
        type,
        content
    )
SELECT
    'Pass letter - Ghanaian Doctors',
    examinations.`id`,
    'pass',
    COALESCE(ghanaian_doctors_pass_letter, '')
FROM
    mdc.`bf_intern_exam`
    JOIN ci4_mdc4.`examinations` examinations ON mdc.`bf_intern_exam`.title = examinations.`title`;

#END MIGRATE EXAMINATION LETTERS INTO EXAMINATION LETTERS TABLE##
#INSERT LETTER TEMPLATE CRITERIA##
INSERT INTO
    ci4_mdc4.`examination_letter_template_criteria` (
        `letter_id`,
        `field`,
        `value`
    )
SELECT
    `id`,
    'category',
    JSON_ARRAY('Dental')
FROM
    ci4_mdc4.`examination_letter_templates`
WHERE
    name LIKE '%Dental%';

INSERT INTO
    ci4_mdc4.`examination_letter_template_criteria` (
        `letter_id`,
        `field`,
        `value`
    )
SELECT
    `id`,
    'specialty',
    JSON_ARRAY('1')
FROM
    ci4_mdc4.`examination_letter_templates`
WHERE
    name LIKE '%Specialists%';

INSERT INTO
    ci4_mdc4.`examination_letter_template_criteria` (
        `letter_id`,
        `field`,
        `value`
    )
SELECT
    `id`,
    'nationality',
    JSON_ARRAY('Ghana', 'Ghanaian')
FROM
    ci4_mdc4.`examination_letter_templates`
WHERE
    name LIKE '%Ghanaian%';

#END INSERT LETTER TEMPLATE CRITERIA##
#IMPORT EXAM REGISTRATIONS##
CREATE TEMPORARY TABLE temp_exam_registration_title_mapping AS
SELECT
    ie.id as old_id,
    ex.id as new_id,
    ex.title AS title,
    ie.publish_score_date as publish_score_date
FROM
    mdc.bf_intern_exam ie
    JOIN ci4_mdc4.examinations ex ON ie.title = ex.title;

INSERT INTO
    ci4_mdc4.`examination_registrations` (
        uuid,
        intern_code,
        exam_id,
        index_number,
        result,
        created_at,
        registration_letter,
        result_letter,
        publish_result_date,
        scores
    )
SELECT
    uuid,
    intern_code,
    new_id,
    index_number,
    result,
    date as created_at,
    registration_letter,
    result_letter,
    publish_score_date,
    JSON_ARRAY(
        JSON_OBJECT(
            'title',
            'MCQ',
            'score',
            `mcq_score`
        ),
        JSON_OBJECT(
            'title',
            'PROBLEM SOLVING',
            'score',
            `problem_solving_score`
        ),
        JSON_OBJECT(
            'title',
            'ORALS',
            'score',
            `orals_score`
        )
    )
FROM
    mdc.`bf_intern_exam_registration`
    JOIN temp_exam_registration_title_mapping temp ON mdc.`bf_intern_exam_registration`.exam_id = temp.`old_id`;

DROP TABLE temp_exam_registration_title_mapping;

#END IMPORT EXAM REGISTRATIONS##
#IMPORT EXAM APPLICATIONS##
CREATE TEMPORARY TABLE temp_exam_registration_title_mapping AS
SELECT
    ie.id as old_id,
    ex.id as new_id,
    ex.title AS title
FROM
    mdc.bf_intern_exam ie
    JOIN ci4_mdc4.examinations ex ON ie.title = ex.title;

INSERT INTO
    ci4_mdc4.`examination_applications` (
        intern_code,
        exam_id,
        application_status,
        created_at
    )
SELECT
    intern_code,
    new_id,
    application_status,
    created_on
FROM
    mdc.`bf_intern_exam_application`
    JOIN temp_exam_registration_title_mapping temp ON mdc.`bf_intern_exam_application`.exam_id = temp.`old_id`;

DROP TABLE temp_exam_registration_title_mapping;

#END IMPORT EXAM APPLICATIONS##