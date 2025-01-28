--IMPORTANT:  CHECK THE NAMES OF THE DATABASES AND TABLES
--migration for doctors
INSERT INTO
    ci4_mdc2.`licenses`(
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
    LEFT JOIN ci4_mdc2.districts d ON d.district = bf_doctor.district;

INSERT INTO
    `practitioners`(
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
        `practitioner_type`,
        `college_membership`,
        `portal_access_message`,
        `last_ip`,
        `last_seen`,
        `last_login`,
        `password_hash`
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
    `college_membership`,
    `portal_access_message`,
    `last_ip`,
    `last_seen`,
    `last_login`,
    `password_hash`
from
    mdc.bf_doctor;

---migration for physician assistants
INSERT INTO
    ci4_mdc2.`licenses`(
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
        ELSE NULLIF(bf_physician_assistant.district, '')
    END as district
from
    mdc.bf_physician_assistant
    LEFT JOIN ci4_mdc2.districts d ON d.district = bf_physician_assistant.district;

INSERT INTO
    `practitioners`(
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
        `practitioner_type`,
        `portal_access_message`,
        `last_ip`,
        `last_seen`,
        `last_login`,
        `password_hash`
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
    'Physician Assistant' as practitioner_type,
    `portal_access_message`,
    `last_ip`,
    `last_seen`,
    `last_login`,
    `password_hash`
from
    mdc.bf_physician_assistant;

---doctors renewal
INSERT INTO
    `license_renewal`(
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
        `license_type`
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
    'practitioners'
from
    mdc.bf_retention