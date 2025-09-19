<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR') || define('HOUR', 3600);
defined('DAY') || define('DAY', 86400);
defined('WEEK') || define('WEEK', 604800);
defined('MONTH') || define('MONTH', 2_592_000);
defined('YEAR') || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS') || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR') || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG') || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE') || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS') || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT') || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE') || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN') || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX') || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_LOW instead.
 */
define('EVENT_PRIORITY_LOW', 200);

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_NORMAL instead.
 */
define('EVENT_PRIORITY_NORMAL', 100);

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_HIGH instead.
 */
define('EVENT_PRIORITY_HIGH', 10);

define('PAGINATION_NUM_ROWS', 100);

define("practitionersImagesLocation", "./assets/images/doctors_pictures/");
define("PRACTITIONERS_RENEWAL_TABLE", "practitioners_renewal");
define("PRACTITIONERS_TABLE", "practitioners");
define("APPLICATIONS_ASSETS_FOLDER", "applications");
define("PRACTITIONERS_ASSETS_FOLDER", "practitioners_images");
define("PAYMENTS_ASSETS_FOLDER", "payments");
define("QRCODES_ASSETS_FOLDER", "qr_codes");
define("UPLOADS_FOLDER", "uploads");
define("PRIORITY_FIELDS", ["picture", "license_number", "name", "first_name", "middle_name", "last_name", "status", "facility_type", "email", "phone_number"]);
define("USER_TYPES", ['admin', 'license', 'cpd', 'student_index', 'guest', 'housemanship_facility', 'exam_candidate']);
define("USER_TYPES_LICENSED_USERS", ['exam_candidate', 'license']);
define("DATABASE_DATE_FIELDS", [
    'date_of_birth',
    'registration_date',
    'qualification_date',
    'date_of_graduation',
    'date_of_expiry',
    'expiry_date',
    'start_date',
    'end_date',
    'from_date',
    'to_date',
    'open_from',
    'open_to',
    'publish_score_date'
]);
define("EXAM_CANDIDATES_VALID_STATES", ['Apply for examination', 'Apply for migration', 'Migrated']);
define("APPLY_FOR_MIGRATION", "Apply for migration");
define("APPLY_FOR_EXAMINATION", "Apply for examination");
define("MIGRATED", "Migrated");
define("VALID_EXAMINATION_RESULTS", ["Pass", "Fail", "Absent"]);
define("VALID_PAYMENT_INVOICE_STATUSES", ['Pending', 'Paid', 'Overdue', 'Cancelled', 'Payment Approved']);
define('EVENT_INVOICE_CREATED', 'invoice_created');

define("SETTING_RESET_PASSWORD_EMAIL_TEMPLATE", "General.reset_password_email_template");
define("SETTING_RESET_PASSWORD_EMAIL_SUBJECT", "General.reset_password_email_subject");
define("SETTING_RESET_PASSWORD_CONFIRMATION_EMAIL_TEMPLATE", "General.reset_password_confirmation_email_template");
define("SETTING_RESET_PASSWORD_CONFIRMATION_EMAIL_SUBJECT", "General.reset_password_confirmation_email_subject");
define("SETTING_2_FACTOR_AUTHENTICATION_SETUP_EMAIL_TEMPLATE", "General.two_factor_authentication_setup_email_template");
define("SETTING_2_FACTOR_AUTHENTICATION_SETUP_EMAIL_TEMPLATE_CODE_ONLY", "General.two_factor_authentication_setup_email_template_code_only");
define("SETTING_2_FACTOR_AUTHENTICATION_SETUP_EMAIL_SUBJECT", "General.two_factor_authentication_setup_email_subject");
define("SETTING_2_FACTOR_AUTHENTICATION_VERIFICATION_EMAIL_TEMPLATE", "General.two_factor_authentication_verification_email_template");
define("SETTING_2_FACTOR_AUTHENTICATION_VERIFICATION_EMAIL_SUBJECT", "General.two_factor_authentication_verification_email_subject");
define("SETTING_2_FACTOR_AUTHENTICATION_DISABLED_EMAIL_TEMPLATE", "General.two_factor_authentication_disabled_email_template");
define("SETTING_2_FACTOR_AUTHENTICATION_DISABLED_EMAIL_SUBJECT", "General.two_factor_authentication_disabled_email_subject");
define("SETTING_USER_ADMIN_ADDED_EMAIL_TEMPLATE", "General.user_admin_added_email_template");
define("SETTING_USER_ADMIN_ADDED_EMAIL_SUBJECT", "General.user_admin_added_email_subject");
define("SETTING_USER_LICENSE_ADDED_EMAIL_TEMPLATE", "General.user_license_added_email_template");
define("SETTING_USER_LICENSE_ADDED_EMAIL_SUBJECT", "General.user_license_added_email_subject");
define("SETTING_USER_CPD_ADDED_EMAIL_TEMPLATE", "General.user_cpd_added_email_template");
define("SETTING_USER_CPD_ADDED_EMAIL_SUBJECT", "General.user_cpd_added_email_subject");
define("SETTING_USER_STUDENT_ADDED_EMAIL_TEMPLATE", "General.user_student_added_email_template");
define("SETTING_USER_STUDENT_ADDED_EMAIL_SUBJECT", "General.user_student_added_email_subject");
define("SETTING_USER_GUEST_ADDED_EMAIL_TEMPLATE", "General.user_guest_added_email_template");
define("SETTING_USER_GUEST_ADDED_EMAIL_SUBJECT", "General.user_guest_added_email_subject");
define("SETTING_USER_HOUSEMANSHIP_FACILITY_ADDED_EMAIL_TEMPLATE", "General.user_housemanship_facility_added_email_template");
define("SETTING_USER_HOUSEMANSHIP_FACILITY_ADDED_EMAIL_SUBJECT", "General.user_housemanship_facility_added_email_subject");
define("SETTING_USER_EXAM_CANDIDATE_ADDED_EMAIL_TEMPLATE", "General.user_exam_candidate_added_email_template");
define("SETTING_USER_EXAM_CANDIDATE_ADDED_EMAIL_SUBJECT", "General.user_exam_candidate_added_email_subject");
define("SETTING_EMAIL_HEADER_AND_FOOTER_TEMPLATE", "General.email_header_and_footer_template");
define("SETTING_PASSWORD_RESET_TOKEN_TIMEOUT", "General.password_reset_token_timeout");
define('SETTING_PORTAL_EDITABLE_FIELDS', 'portal_editable_fields');//for now we're not adding the class (General) to the key. we'll use the settings model directly for it

define("DEFAULT_APPLICATION_FORM_TEMPLATES", "defaultApplicationFormTemplates");

define("EVENT_USER_ADDED", "user_added");
define("EVENT_INVOICE_PAYMENT_COMPLETED", "invoice_payment_completed");
define("EVENT_APPLICATION_FORM_ACTION_COMPLETED", "application_form_action_completed");

define("PORTAL_EDIT_FORM_TYPE", "Portal Edit");
define("CACHE_KEY_PREFIX_LICENSES", "app_licenses_");
define("CACHE_KEY_PREFIX_RENEWALS", "app_licenses_renewals_");

define("IN_GOOD_STANDING", "In Good Standing");
define("NOT_IN_GOOD_STANDING", "Not In Good Standing");
define("PERMANENT", "Permanent");
define("APPROVED", "Approved");
define("PAYMENT_METHOD_IN_PERSON", "In-Person");
define("PAYMENT_METHOD_GHANA_GOV_PLATFORM", "Ghana.gov Platform");

