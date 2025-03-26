#IMPORTANT:  CHECK THE NAMES OF THE DATABASES AND TABLES
#ci4_mdc3 should be the name of the database that you are migrating to
#mdc should be the name of the database that you are migrating from
#IMPORT SETTINGS#
INSERT INTO
    ci4_mdc3.`settings` (
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
    ci4_mdc3.`regions` (`name`)
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
    ci4_mdc3.`districts` (`district`, `region`)
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
    ci4_mdc3.`specialties` (`name`)
SELECT
    `name`
FROM
    mdc.`specialties`;

#END IMPORT SPECIALTIES#
#IMPORT SUBSPECIALTIES#
INSERT INTO
    ci4_mdc3.`subspecialties` (`subspecialty`, `specialty`)
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
    ci4_mdc3.`roles` (`role_name`, `description`)
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
    IGNORE INTO ci4_mdc3.`users` (
        `username`,
        `regionId`,
        `position`,
        `picture`,
        `phone`,
        `email`,
        `role_name`
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
    ) as role_name
FROM
    mdc.`bf_users`;

#END IMPORT USERS#
#migration for doctors. import the data from the old database to the licenses table
INSERT INTO
    ci4_mdc3.`licenses`(
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
        `district`
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
    END as district
from
    mdc.bf_doctor
    LEFT JOIN ci4_mdc3.districts d ON d.district = bf_doctor.district;

########-END DOCTORS LICENSE MIGRATION#######-
##MIGRATE DOCTORS DETAILS INTO THE PRACTITIONERS TABLE##
INSERT INTO
    ci4_mdc3.`practitioners`(
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
    ci4_mdc3.`licenses`(
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
        `district`
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
    END as district
from
    mdc.bf_physician_assistant
    LEFT JOIN ci4_mdc3.districts d ON d.district = bf_physician_assistant.district;

INSERT INTO
    ci4_mdc3.`practitioners`(
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
    ci4_mdc3.`license_renewal`(
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
#########-END UPDATE LICENSE UUID#########-
#update the license_renewal table to add the data_snapshot data . this should be a json object with all the data from the licenses + practitioners table
UPDATE
    ci4_mdc3.`license_renewal`
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
            mdc.`bf_doctor`
        WHERE
            mdc.`bf_doctor`.`registration_number` = `license_renewal`.`license_number`
    )
WHERE
    `data_snapshot` IS NULL;

#############-END UPDATE LICENSE DATA SNAPSHOT###########
#UPDATE SNAPSHOT BASED ON PROVISIONAL NUMBER##
UPDATE
    ci4_mdc3.`license_renewal`
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
            mdc.`bf_doctor`
        WHERE
            mdc.`bf_doctor`.`provisional_number` = `license_renewal`.`license_number` ##THIS SUBQUERY RETURNS MORE THAN ONE ROW. NEED TO FIX THIS
    )
WHERE
    `data_snapshot` IS NULL;

##END UPDATE SNAPSHOT BASED ON PROVISIONAL NUMBER##
#########-END UPDATE PRACTITIONER RENEWAL#########-
########-PHYSICIAN ASSISTANTS RENEWAL########-
#-physician assistants renewal. the renewal is split into the licenses_renewal and practitioners_renewal. the licenses_renewal is the main table that holds the renewal data and the practitioners_renewal is the table that holds the additional data for the practitioners
INSERT INTO
    ci4_mdc3.`license_renewal`(
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
    ci4_mdc3.license_renewal lr
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
    ci4_mdc3.license_renewal lr
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
    ci4_mdc3.`license_renewal`
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
    JOIN ci4_mdc3.license_renewal l ON b.reg_num = l.license_number
    and b.created_on = l.created_on
WHERE
    l.license_type = 'practitioners';

# Add an index for faster lookups
ALTER TABLE
    temp_renewal_mapping
ADD
    INDEX (reg_num);

INSERT INTO
    ci4_mdc3.practitioners_renewal(
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
    JOIN ci4_mdc3.license_renewal l ON b.reg_num = l.license_number
    and b.created_on = l.created_on
WHERE
    l.license_type = 'practitioners';

# Add an index for faster lookups
ALTER TABLE
    temp_renewal_mapping
ADD
    INDEX (reg_num);

INSERT INTO
    ci4_mdc3.practitioners_renewal(
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
############import applications##########
#IMPORT PERMANENT REGISTRATIONS##
INSERT INTO
    ci4_mdc3.`application_forms` (
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
    ci4_mdc3.`application_forms` (
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
    ci4_mdc3.`application_forms` (
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
    ci4_mdc3.`application_forms` (
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
    ci4_mdc3.`practitioner_additional_qualifications` (
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
    ci4_mdc3.`practitioner_additional_qualifications` (
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
    ci4_mdc3.`practitioner_work_history` (
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
    mdc.`bf_doctor_work_history` #END IMPORT DOCTOR WORK HISTORY#
    #IMPORT PA WORK HISTORY#
INSERT INTO
    ci4_mdc3.`practitioner_work_history` (
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
    mdc.`bf_pa_work_history` #END IMPORT PA WORK HISTORY#
    #IMPORT CPD PROVIDERS#
INSERT INTO
    ci4_mdc3.`cpd_providers` (
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
    ci4_mdc3.`cpd_providers`
    JOIN mdc.`bf_cpd_facilities` ON mdc.`bf_cpd_facilities`.name = ci4_mdc3.`cpd_providers`.name;

INSERT INTO
    ci4_mdc3.`cpd_topics` (
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
    ci4_mdc3.`cpd_topics`
    JOIN mdc.`bf_cpd` ON mdc.`bf_cpd`.topic = ci4_mdc3.`cpd_topics`.topic
    AND mdc.`bf_cpd`.created_on = ci4_mdc3.`cpd_topics`.created_on;

INSERT INTO
    ci4_mdc3.`cpd_attendance` (
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
    ci4_mdc3.`cpd_topics`
    JOIN mdc.`bf_cpd` ON mdc.`bf_cpd`.topic = ci4_mdc3.`cpd_topics`.topic
    AND mdc.`bf_cpd`.created_on = ci4_mdc3.`cpd_topics`.created_on;

INSERT INTO
    ci4_mdc3.`cpd_attendance` (
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
    ci4_mdc3.`cpd_topics`
    JOIN mdc.`bf_cpd` ON mdc.`bf_cpd`.topic = ci4_mdc3.`cpd_topics`.topic
    AND mdc.`bf_cpd`.created_on = ci4_mdc3.`cpd_topics`.created_on;

INSERT INTO
    ci4_mdc3.`application_forms` (
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