# App Settings Configuration Documentation

## Overview

This document describes the complete structure and usage of the application settings JSON files (`app-settings-mdc.json`, `app-settings-pc.json`). These files control the behavior, appearance, and business logic of the medical regulatory management system.

## File Selection

The application determines which settings file to use via the `APP_SETTINGS_FILE` environment variable in your `.env` file:

```env
APP_SETTINGS_FILE=app-settings-mdc.json
# or
APP_SETTINGS_FILE=app-settings-pc.json
```

## Accessing Settings

### In Code

Settings are retrieved using the `Utils::getAppSettings()` helper method:

```php
use App\Helpers\Utils;

// Get a single setting
$appName = Utils::getAppSettings('appName');

// Get all settings
$allSettings = Utils::getAppSettings();

// Get nested setting (for license types, etc.)
$licenseSetting = Utils::getLicenseSetting('practitioners');
```

**Location**: [app/Helpers/Utils.php:198](app/Helpers/Utils.php#L198)

### Database Overrides

Settings can be overridden at runtime through the database using the `app_settings_overrides` table. This allows administrators to modify configuration without editing JSON files.

**How it works:**
1. Settings are first read from the JSON file
2. The system checks for active overrides in the database
3. If an override exists, it's applied using the specified merge strategy
4. The result is cached for 1 hour

**Merge Strategies:**
- `replace`: Completely replace the file value with the override
- `merge`: Merge arrays/objects (for objects, override keys take precedence; for arrays, combine and deduplicate)
- `append`: Add override items to the end of arrays
- `prepend`: Add override items to the beginning of arrays

**Managing Overrides:**

API endpoints are available via [app/Controllers/AppSettingsController.php](app/Controllers/AppSettingsController.php):

- `GET /api/app-settings` - Get all settings with overrides
- `GET /api/app-settings/:key` - Get specific setting
- `POST /api/app-settings` - Create/update override
- `PUT /api/app-settings/:id` - Update override
- `DELETE /api/app-settings/:id` - Deactivate override
- `GET /api/app-settings/keys` - Get all available setting keys
- `POST /api/app-settings/clear-cache` - Clear settings cache

**Database Schema**: [app/Database/Migrations/2025-11-25-055442_CreateAppSettingsOverridesTable.php](app/Database/Migrations/2025-11-25-055442_CreateAppSettingsOverridesTable.php)

---

## Configuration Structure

### Top-Level Keys

The JSON file contains 44 top-level configuration keys organized by function:

| Key | Type | Description |
|-----|------|-------------|
| `appName` | string | Short application name (e.g., "MDC Ghana") |
| `appVersion` | string | Current version number |
| `appLongName` | string | Full official name of the institution |
| `logo` | string | Path to application logo (relative to public folder) |
| `portalName` | string | Display name for the practitioner portal |
| `defaultEmailSenderEmail` | string | From email address for system emails |
| `defaultEmailSenderName` | string | From name for system emails |
| `institutionName` | string | Institution's official name |
| `institutionLogo` | string | Path to institution logo |
| `institutionAddress` | string | Physical address of institution |
| `institutionEmail` | string | Contact email |
| `institutionPhone` | string | Contact phone number |
| `institutionWebsite` | string | Institution website URL |
| `institutionWhatsapp` | string | WhatsApp contact number |
| `portalUrl` | string | Base URL for practitioner portal |
| `portalFooterBackground` | string | Path to footer background image |
| `sidebarMenu` | array | Admin interface navigation menu structure |
| `dashboardMenu` | array | Dashboard widget and card definitions |
| `portalHomeMenu` | array | Practitioner portal home page menu items |
| `portalHomeSubTitleFields` | array | Fields to display in portal subtitle |
| `portalAlerts` | array | Conditional alert messages for portal users |
| `portalContactUsTitle` | string | Contact page title |
| `portalContactUsSubTitle` | string | Contact page subtitle |
| `searchTypes` | array | Available search configurations |
| `licenseTypes` | object | Complete license type definitions (see detailed section) |
| `applicationForms` | object | Application form metadata |
| `commonApplicationTemplates` | object | Quick links to application forms |
| `defaultApplicationFormTemplates` | array | Pre-defined application form templates |
| `defaultPrintTemplates` | array | Document/certificate print templates |
| `renewalRules` | array | Rules for calculating license renewal dates |
| `datePatterns` | object | Date calculation pattern definitions |
| `cpdFilterFields` | array | CPD search/filter field definitions |
| `basicStatisticsFields` | array | Chart/statistics field definitions |
| `basicStatisticsFilterFields` | array | Filters for statistics pages |
| `advancedStatisticsFilterFields` | array | Advanced statistics filters |
| `renewalBasicStatisticsFilterFields` | array | Renewal-specific statistics filters |
| `housemanship` | object | Housemanship program configuration |
| `examinations` | object | Examination system configuration |
| `payments` | object | Payment methods and purposes (see detailed section) |
| `letterContainer` | object | HTML template wrapper for letters/documents |
| `systemSettings` | object | System-wide behavior toggles |
| `trainingInstitutions` | object | Training institution configurations |
| `userTypesNames` | array | User type labels for registration |
| `allowedTestEmails` | array | Email addresses that bypass restrictions in test mode |

---

## Detailed Configuration Sections

### 1. License Types (`licenseTypes`)

**Type**: `object` (keyed by license type name)

**Purpose**: Defines all license types in the system including practitioners, facilities, exam candidates, students, etc. This is the most complex configuration section.

**Used in**:
- [app/Helpers/Utils.php:427](app/Helpers/Utils.php#L427) - `getLicenseSetting()`
- [app/Models/Licenses/LicensesModel.php](app/Models/Licenses/LicensesModel.php)
- Throughout the application for license management operations

**Structure**:

```json
{
  "licenseTypes": {
    "practitioners": {
      "table": "practitioners",
      "uniqueKeyField": "license_number",
      "licenseNumberFormats": [ /* ... */ ],
      "selectionFields": [ /* ... */ ],
      "displayColumns": [ /* ... */ ],
      "fields": [ /* ... */ ],
      "onCreateValidation": { /* ... */ },
      "onUpdateValidation": { /* ... */ },
      "renewalFields": [ /* ... */ ],
      "renewalTable": "",
      "renewalStages": { /* ... */ },
      "renewalSearchFields": { /* ... */ },
      "renewalJsonFields": [ /* ... */ ],
      "fieldsToUpdateOnRenewal": [ /* ... */ ],
      "renewalFilterFields": [ /* ... */ ],
      "canApplyForRenewalCriteria": [ /* ... */ ],
      "shouldApplyForRenewalCriteria": [ /* ... */ ],
      "mustBeInGoodStandingToRenew": false,
      "renewalCpdCategory1Cutoff": 0,
      "renewalCpdCategory2Cutoff": 0,
      "renewalCpdCategory3Cutoff": 0,
      "renewalCpdTotalCutoff": 0,
      "daysFromRenewalExpiryToOpenApplication": 0,
      "revalidationPeriodInYears": 3,
      "validRenewalStatuses": [ /* ... */ ],
      "revalidationMessage": "...",
      "detailsPageHeaderTabs": [ /* ... */ ],
      "detailsPageTabs": [ /* ... */ ],
      "searchFields": { /* ... */ },
      "basicStatisticsFields": [ /* ... */ ],
      "basicStatisticsFilterFields": [ /* ... */ ],
      "advancedStatisticsFields": [ /* ... */ ],
      "searchFormFields": [ /* ... */ ],
      "renewalBasicStatisticsFields": [ /* ... */ ],
      "renewalBasicStatisticsFilterFields": [ /* ... */ ],
      "gazetteTableColumns": { /* ... */ }
    }
  }
}
```

#### Key Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `table` | string | Yes | Database table name for this license type |
| `uniqueKeyField` | string | Yes | Primary identifier field (usually "license_number") |
| `licenseNumberFormats` | array | Yes | Rules for auto-generating license numbers based on criteria |
| `selectionFields` | array | No | Fields to select when querying licenses (null = all fields) |
| `displayColumns` | array | Yes | Columns to show in list views |
| `fields` | array | Yes | Form fields for creating/editing licenses |
| `onCreateValidation` | object | Yes | CodeIgniter validation rules for creating licenses |
| `onUpdateValidation` | object | No | Validation rules for updating licenses |
| `renewalFields` | array | No | Additional fields to show during renewal |
| `renewalTable` | string | No | Separate table for renewal data (usually empty) |
| `renewalStages` | object | Yes | Workflow stages for license renewals |
| `renewalSearchFields` | object | No | Fields and joins for searching renewals |
| `renewalJsonFields` | array | No | Fields stored as JSON that need special handling |
| `fieldsToUpdateOnRenewal` | array | No | License fields to update when renewal is approved |
| `renewalFilterFields` | array | No | Filter options for renewal list views |
| `canApplyForRenewalCriteria` | array | No | Conditions that must be met to apply for renewal |
| `shouldApplyForRenewalCriteria` | array | No | Conditions that trigger renewal reminders |
| `mustBeInGoodStandingToRenew` | boolean | No | Whether good standing is required for renewal |
| `renewalCpdCategory1Cutoff` | number | No | Minimum CPD points required in category 1 |
| `renewalCpdCategory2Cutoff` | number | No | Minimum CPD points required in category 2 |
| `renewalCpdCategory3Cutoff` | number | No | Minimum CPD points required in category 3 |
| `renewalCpdTotalCutoff` | number | No | Minimum total CPD points required |
| `daysFromRenewalExpiryToOpenApplication` | number | No | Days before expiry that renewal becomes available |
| `revalidationPeriodInYears` | number | No | How often practitioners must revalidate their data |
| `validRenewalStatuses` | array | No | License statuses that allow renewal |
| `revalidationMessage` | string | No | Message shown when revalidation is required |
| `detailsPageHeaderTabs` | array | No | Quick info tabs shown at top of details page |
| `detailsPageTabs` | array | No | Main content tabs on details page |
| `searchFields` | object | Yes | Fields and joins for searching this license type |
| `basicStatisticsFields` | array | No | Chart definitions for basic statistics |
| `basicStatisticsFilterFields` | array | No | Filter fields for basic statistics |
| `advancedStatisticsFields` | array | No | Advanced statistics configurations |
| `searchFormFields` | array | No | Fields for advanced search form |
| `renewalBasicStatisticsFields` | array | No | Statistics specific to renewals |
| `renewalBasicStatisticsFilterFields` | array | No | Filters for renewal statistics |
| `gazetteTableColumns` | object | No | Column configuration for gazette publications |

#### License Number Formats

**Purpose**: Defines patterns for automatically generating license numbers based on criteria.

**Used in**: License creation workflows

```json
"licenseNumberFormats": [
  {
    "criteria": [
      {
        "field": "practitioner_type",
        "operator": "equals",
        "value": ["Doctor"]
      }
    ],
    "format": "MDC/PN/{number:5}",
    "sequenceKey": "practitioner_type",
    "description": "Doctor - Provisional Register"
  }
]
```

**Format Placeholders**:
- `{number:X}`: Sequential number padded to X digits
- Text is used literally

**Sequence Key**: Groups sequences by this field (e.g., separate sequences per practitioner_type)

#### Form Fields

**Purpose**: Defines the structure of forms for creating/editing licenses.

**Used in**: Form generation throughout the UI

```json
"fields": [
  {
    "label": "Practitioner Type",
    "name": "practitioner_type",
    "hint": "Select the type of practitioner",
    "options": [
      {
        "key": "Doctor",
        "value": "Doctor"
      }
    ],
    "type": "select",
    "value": "",
    "required": true
  }
]
```

**Field Properties**:
- `label`: Display label
- `name`: Database field name
- `hint`: Help text shown to users
- `options`: Array of choices (for select/radio/checkbox fields)
- `type`: Input type (text, select, date, file, textarea, etc.)
- `value`: Default value
- `required`: Whether field is mandatory
- `api_url`: URL to fetch options from (for dynamic selects)
- `apiKeyProperty`: JSON key for option value
- `apiLabelProperty`: JSON key for option label
- `assetType`: Asset category (for file uploads)
- `showOnly`: If true, display but don't allow editing

#### Renewal Stages

**Purpose**: Defines workflow stages for license renewals including allowed transitions, actions, and permissions.

**Used in**:
- [app/Services/LicenseRenewalService.php](app/Services/LicenseRenewalService.php)
- Renewal workflow management

```json
"renewalStages": {
  "Pending Payment": {
    "label": "Pending Payment",
    "allowedTransitions": ["Pending Approval", "Approved"],
    "fields": [],
    "permission": "Mark_Practitioners_Renewal_Pending_Payment",
    "printable": false,
    "onlineCertificatePrintable": false,
    "deletableByUser": true,
    "userActions": "make_payment",
    "title": "Pending Payment",
    "active_children": false,
    "url": "/licenses/renewal/practitioners",
    "urlParams": {
      "status": "Pending Payment"
    },
    "icon": "list_alt",
    "children": [],
    "description": "View/Print approved applications",
    "apiCountUrl": "licenses/renewal-count?license_type=practitioners&status=Pending Payment",
    "apiCountText": "Renewals",
    "actions": [
      {
        "type": "email",
        "config_type": "email",
        "config": {
          "template": "your renewal is now pending payment",
          "subject": "Renewal Pending Payment",
          "admin_email": "",
          "endpoint": "",
          "method": "",
          "auth_token": "",
          "headers": [],
          "body_mapping": [],
          "query_params": []
        },
        "criteria": []
      },
      {
        "type": "create_invoice",
        "config_type": "payment",
        "config": {
          "paymentPurpose": "Doctors Renewal",
          "paymentInvoiceItems": []
        },
        "criteria": [
          {
            "field": "practitioner_type",
            "operator": "equals",
            "value": ["Doctor"]
          }
        ]
      }
    ]
  }
}
```

**Stage Properties**:
- `label`: Display name
- `allowedTransitions`: Array of stages this stage can transition to
- `fields`: Additional fields to collect at this stage
- `permission`: Required permission to manually set this stage
- `printable`: Whether certificates can be printed in this stage
- `onlineCertificatePrintable`: Whether practitioners can print their own certificate
- `deletableByUser`: Whether practitioners can delete applications in this stage
- `userActions`: Action button to show to practitioners (e.g., "make_payment")
- `url`: Admin UI route for viewing renewals in this stage
- `urlParams`: Query parameters for the route
- `icon`: Material icon name
- `description`: Explanation of this stage
- `apiCountUrl`: API endpoint to get count of items in this stage
- `actions`: Array of automated actions to execute when entering this stage

**Action Types**:
- `email`: Send email notification
- `create_invoice`: Generate payment invoice
- `api_call`: Make external API call
- `update_renewal_status`: Update renewal status (internal)

#### Search Fields

**Purpose**: Defines which fields can be searched and any necessary joins.

```json
"searchFields": {
  "fields": ["first_name", "last_name", "middle_name"],
  "joinCondition": "licenses.license_number = practitioners.license_number"
}
```

#### Statistics Fields

**Purpose**: Defines charts and visualizations for the statistics dashboard.

```json
"basicStatisticsFields": [
  {
    "label": "Practitioner Type",
    "name": "practitioner_type",
    "type": "bar",
    "xAxisLabel": "Practitioner Type",
    "yAxisLabel": "Number of Licenses"
  }
]
```

**Chart Types**: `bar`, `line`, `pie`, `doughnut`

---

### 2. Payments Configuration (`payments`)

**Type**: `object` with two main sections: `paymentMethods` and `purposes`

**Purpose**: Configures all payment-related functionality including payment gateways, invoice generation, and post-payment actions.

**Used in**:
- [app/Helpers/Utils.php:1353](app/Helpers/Utils.php#L1353) - `getPaymentSettings()`
- [app/Controllers/PaymentsController.php](app/Controllers/PaymentsController.php)

**Structure**:

```json
{
  "payments": {
    "paymentMethods": { /* ... */ },
    "purposes": { /* ... */ }
  }
}
```

#### Payment Methods (`payments.paymentMethods`)

**Type**: `object` (keyed by method name)

**Purpose**: Defines available payment methods and their configurations.

```json
"paymentMethods": {
  "Ghana.gov Platform": {
    "label": "Ghana.gov Platform",
    "type": "online",
    "isActive": true,
    "onStart": "ghana_gov",
    "onComplete": "ghana_gov",
    "description": "Pay this invoice online via the Ghana.gov platform",
    "logo": "assets/images/gov-logo.png",
    "paymentBranches": [
      {
        "name": "Head Office",
        "mda_code": "MDC_HQ"
      }
    ]
  },
  "In-Person": {
    "label": "In-Person Payment",
    "type": "offline",
    "isActive": true,
    "onStart": "upload_proof",
    "onComplete": "manual_verification",
    "description": "Pay at our office and upload proof of payment",
    "logo": "assets/images/cash-icon.png",
    "paymentBranches": []
  }
}
```

**Properties**:
- `label`: Display name for users
- `type`: "online" or "offline"
- `isActive`: Whether method is currently available
- `onStart`: Handler function name for initiating payment
- `onComplete`: Handler function name for completing payment
- `description`: User-facing description
- `logo`: Path to payment method logo
- `paymentBranches`: Array of branch/MDA codes (for multi-branch systems)

#### Payment Purposes (`payments.purposes`)

**Type**: `object` (keyed by purpose name)

**Purpose**: Defines what can be paid for, associated fees, and post-payment workflows.

**Used in**: [app/Helpers/Utils.php:1479](app/Helpers/Utils.php#L1479) - `getUuidDetailsForPayment()`

```json
"purposes": {
  "Doctors Renewal": {
    "paymentMethods": ["In-Person", "Ghana.gov Platform"],
    "defaultInvoiceItems": [
      {
        "criteria": [],
        "feeServiceCodes": [
          {
            "service_code": "D001",
            "quantity": 1
          }
        ]
      }
    ],
    "sourceTableName": "license_renewal",
    "description": "Doctors relicensure for [license_number] - [start_date] to [expiry]",
    "licenseTypes": ["practitioners"],
    "licenseCriteria": [
      {
        "field": "practitioner_type",
        "value": ["Doctor"]
      }
    ],
    "activityTypes": ["renewal"],
    "onPaymentCompletedActions": [
      {
        "type": "email",
        "config_type": "email",
        "config": {
          "template": "payment received successfully",
          "subject": "Payment Confirmation",
          "admin_email": "",
          "endpoint": "",
          "method": "GET",
          "auth_token": "",
          "headers": [],
          "body_mapping": [],
          "query_params": []
        }
      },
      {
        "type": "update_renewal_status",
        "config_type": "internal_api_call",
        "label": "Update renewal status",
        "config": {
          "body_mapping": {
            "status": "Approved",
            "in_print_queue": "0",
            "print_template": "Default Doctors/PA Renewal Certificate Template"
          }
        }
      }
    ],
    "onPaymentFileUploadedActions": [
      {
        "type": "update_renewal_status",
        "config_type": "internal_api_call",
        "config": {
          "body_mapping": {
            "status": "Pending Approval"
          }
        }
      }
    ],
    "onPaymentFileDeletedActions": [
      {
        "type": "update_renewal_status",
        "config_type": "internal_api_call",
        "config": {
          "body_mapping": {
            "status": "Pending Payment"
          }
        }
      }
    ]
  }
}
```

**Properties**:
- `paymentMethods`: Array of allowed payment method names (must match keys in `paymentMethods`)
- `defaultInvoiceItems`: Array of fee rules with criteria and service codes
- `sourceTableName`: Database table containing the entity being paid for ("license_renewal", "license", "application")
- `description`: Invoice description template (supports placeholders like [license_number], [start_date], [expiry])
- `licenseTypes`: Array of applicable license types
- `licenseCriteria`: Additional criteria to match licenses
- `activityTypes`: Types of activities this purpose covers (e.g., ["renewal"])
- `onPaymentCompletedActions`: Actions to execute when payment is confirmed
- `onPaymentFileUploadedActions`: Actions when payment proof is uploaded (for offline payments)
- `onPaymentFileDeletedActions`: Actions when payment proof is removed

**Invoice Items with Criteria**:

```json
"defaultInvoiceItems": [
  {
    "criteria": [
      {
        "field": "practitioner_type",
        "value": ["Doctor"]
      }
    ],
    "feeServiceCodes": [
      {
        "service_code": "D001",
        "quantity": 1
      }
    ]
  },
  {
    "criteria": [
      {
        "field": "practitioner_type",
        "value": ["Physician Assistant"]
      }
    ],
    "feeServiceCodes": [
      {
        "service_code": "PA001",
        "quantity": 1
      }
    ]
  }
]
```

The system evaluates criteria and applies the first matching fee. Service codes reference the `fees` table.

---

### 3. Renewal Rules (`renewalRules`)

**Type**: `array`

**Purpose**: Defines rules for calculating license start and expiry dates based on criteria.

**Used in**: [app/Helpers/LicenseRenewalDateGenerator.php](app/Helpers/LicenseRenewalDateGenerator.php)

```json
"renewalRules": [
  {
    "name": "Temporary License Rule",
    "description": "Temporary licenses are valid for 3 months from issue date",
    "criteria": [
      {
        "field": "license_type",
        "operator": "equals",
        "value": "practitioners"
      },
      {
        "field": "register_type",
        "operator": "equals",
        "value": "Temporary"
      }
    ],
    "start_date": "today",
    "expiry_date": "+3 months"
  },
  {
    "name": "Default Permanent License Rule",
    "description": "Default rule for permanent licenses - calendar year validity",
    "criteria": [],
    "start_date": "start_of_year",
    "expiry_date": "end_of_year"
  }
]
```

**Properties**:
- `name`: Rule identifier
- `description`: Human-readable explanation
- `criteria`: Array of conditions (empty array = default/fallback rule)
- `start_date`: Date calculation pattern
- `expiry_date`: Date calculation pattern

**Rule Evaluation**: Rules are evaluated in order. The first rule with matching criteria is used. A rule with empty criteria acts as a catch-all default.

#### Date Patterns (`datePatterns`)

Referenced by renewal rules for date calculations.

```json
"datePatterns": {
  "description": "Available date calculation patterns",
  "simple_patterns": [
    "today",
    "now",
    "start_of_year",
    "end_of_year"
  ],
  "relative_patterns": [
    "+1 day",
    "+1 week",
    "+1 month",
    "+1 year",
    "+3 months",
    "-1 day"
  ],
  "complex_patterns": {
    "description": "Object-based patterns for complex date calculations",
    "structure": {
      "base": "Base date (can be any simple pattern or 'start_date')",
      "modify": ["Array of date modifications"],
      "conditional": [
        {
          "criteria": "Array of criteria objects",
          "modify": "Array of modifications"
        }
      ]
    }
  }
}
```

**Pattern Types**:
1. **Simple**: Fixed reference points (today, start_of_year, etc.)
2. **Relative**: Add/subtract time periods (+1 month, -1 day)
3. **Complex**: JSON object with conditional logic

---

### 4. Application Forms

#### Common Application Templates (`commonApplicationTemplates`)

**Type**: `object`

**Purpose**: Quick reference links to application forms for different license types.

```json
"commonApplicationTemplates": {
  "exam_candidates": {
    "label": "Examination Candidates Application",
    "config_type": "url",
    "form_url": "licenses/config/exam_candidates"
  }
}
```

#### Default Application Form Templates (`defaultApplicationFormTemplates`)

**Type**: `array`

**Purpose**: Pre-defined application form structures with workflow stages.

**Used in**:
- [app/Helpers/Utils.php:1514](app/Helpers/Utils.php#L1514) - `getDefaultApplicationFormTemplates()`
- [app/Services/ApplicationTemplateService.php](app/Services/ApplicationTemplateService.php)
- [app/Controllers/ApplicationsController.php](app/Controllers/ApplicationsController.php)

```json
"defaultApplicationFormTemplates": [
  {
    "uuid": "practitioners-provisional-registration-application",
    "form_name": "Practitioners Provisional Registration Application",
    "description": null,
    "guidelines": "<p>Application guidelines HTML</p>",
    "header": "<p>Form header HTML</p>",
    "footer": "<p>Form footer HTML</p>",
    "data": [
      {
        "name": "first_name",
        "type": "text",
        "required": true,
        "hint": "Enter your first name",
        "label": "First Name",
        "options": [],
        "value": ""
      },
      {
        "label": "File attachment",
        "name": "file_attachment",
        "hint": "Upload supporting document",
        "options": [],
        "type": "file",
        "assetType": "applications",
        "value": "",
        "required": false,
        "api_url": "",
        "apiKeyProperty": "",
        "apiLabelProperty": "",
        "showOnly": false
      }
    ],
    "open_date": "2025-08-01",
    "close_date": "2100-08-31",
    "on_submit_email": null,
    "on_submit_message": "<p>Thank you for your application</p>",
    "deleted_at": null,
    "updated_at": null,
    "created_on": "2025-08-05 10:44:39",
    "modified_on": "2025-08-05 10:44:39",
    "on_approve_email_template": null,
    "on_deny_email_template": null,
    "approve_url": null,
    "deny_url": null,
    "stages": [
      {
        "name": "Received",
        "description": "Application received and under review",
        "allowedTransitions": ["Processed"],
        "allowedUserRoles": null,
        "actions": [
          {
            "type": "email",
            "config_type": "email",
            "config": {
              "template": "Your application has been received",
              "subject": "Application Received",
              "endpoint": "",
              "method": "GET",
              "admin_email": "",
              "auth_token": "",
              "headers": [],
              "body_mapping": [],
              "query_params": []
            }
          },
          {
            "type": "api_call",
            "config_type": "api_call",
            "config": {
              "endpoint": "http://localhost:8080/api/some-endpoint",
              "method": "PUT",
              "auth_token": "__self__",
              "headers": [],
              "body_mapping": [
                {
                  "api_field": "status",
                  "mapping_type": "static",
                  "source_field": "",
                  "static_value": "Processed",
                  "template": "",
                  "transform_type": "",
                  "default_value": ""
                }
              ],
              "query_params": []
            }
          }
        ]
      }
    ],
    "initialStage": "Received",
    "finalStage": "Processed",
    "picture": null,
    "restrictions": null,
    "available_externally": "0"
  }
]
```

**Properties**:
- `uuid`: Unique form identifier
- `form_name`: Display name
- `description`: Optional description
- `guidelines`: HTML content shown before form
- `header`: HTML shown at top of form
- `footer`: HTML shown at bottom of form
- `data`: Array of form field definitions (same structure as license type fields)
- `open_date`: When applications can start being submitted
- `close_date`: When applications close
- `on_submit_email`: Email template to send on submission (null = use stage actions)
- `on_submit_message`: Message shown to user after submission
- `stages`: Workflow stages for processing applications
- `initialStage`: Starting stage for new applications
- `finalStage`: Terminal stage
- `picture`: Optional image for the form
- `restrictions`: Criteria limiting who can apply
- `available_externally`: "0" or "1" - whether non-logged-in users can apply

**Stage Actions**:

Similar to renewal stage actions, but with additional mapping capabilities:

```json
{
  "type": "api_call",
  "config_type": "api_call",
  "config": {
    "endpoint": "https://external-api.com/endpoint",
    "method": "POST",
    "auth_token": "__self__",
    "headers": [
      {
        "key": "Content-Type",
        "value": "application/json"
      }
    ],
    "body_mapping": [
      {
        "api_field": "external_field_name",
        "mapping_type": "field",
        "source_field": "form_field_name",
        "static_value": "",
        "template": "",
        "transform_type": "",
        "default_value": ""
      },
      {
        "api_field": "status",
        "mapping_type": "static",
        "source_field": "",
        "static_value": "Approved",
        "template": "",
        "transform_type": "",
        "default_value": ""
      },
      {
        "api_field": "message",
        "mapping_type": "template",
        "source_field": "",
        "static_value": "",
        "template": "Application for {first_name} {last_name}",
        "transform_type": "",
        "default_value": ""
      }
    ],
    "query_params": [
      {
        "key": "id",
        "value": "{application_id}"
      }
    ]
  }
}
```

**Mapping Types**:
- `field`: Map from form field
- `static`: Use static value
- `template`: Use template with placeholders
- `transform`: Apply transformation to source field

**Special Auth Token**: `__self__` uses the application's own authentication token

---

### 5. Menu Configurations

#### Sidebar Menu (`sidebarMenu`)

**Type**: `array`

**Purpose**: Defines the main navigation menu in the admin interface.

**Used in**: Admin UI components

```json
"sidebarMenu": [
  {
    "title": "Dashboard",
    "url": "/dashboard",
    "icon": "dashboard",
    "color": "indigo",
    "children": []
  },
  {
    "title": "CPD",
    "active_children": false,
    "url": "",
    "icon": "auto_stories_outlined",
    "color": "deep-purple",
    "children": [
      {
        "title": "View/Search CPD Topics",
        "url": "/cpd",
        "icon": "",
        "children": []
      },
      {
        "title": "Add New CPD",
        "url": "/cpd/add",
        "icon": "",
        "children": []
      }
    ]
  }
]
```

**Properties**:
- `title`: Menu item label
- `url`: Navigation route
- `icon`: Material icon name
- `color`: CSS color value
- `children`: Nested menu items (recursive structure)
- `active_children`: Whether child items are shown by default
- `urlParams`: Query parameters to add to URL

#### Dashboard Menu (`dashboardMenu`)

**Type**: `array`

**Purpose**: Defines dashboard widgets/cards with counts and quick actions.

**Used in**: Dashboard page

```json
"dashboardMenu": [
  {
    "title": "Doctors",
    "active_children": false,
    "url": "/applications/application-types",
    "icon": "person_outlined",
    "color": "green",
    "children": [
      {
        "title": "New Provisional Registrations",
        "url": "/applications",
        "icon": "",
        "children": [],
        "urlParams": {
          "form_type": "Practitioners Provisional Registration Application",
          "status": "Pending Approval"
        },
        "apiCountUrl": "applications/count?status=Pending Approval&form_type=Practitioners Provisional Registration Application",
        "apiCountText": "Pending",
        "permissions": ["View_Application_Forms"]
      }
    ],
    "description": "Manage doctor registrations and renewals",
    "apiCountUrl": "",
    "apiCountText": ""
  }
]
```

**Additional Properties**:
- `apiCountUrl`: API endpoint returning count
- `apiCountText`: Label for count badge
- `permissions`: Required permissions to see item
- `description`: Tooltip or subtitle

#### Portal Home Menu (`portalHomeMenu`)

**Type**: `array`

**Purpose**: Menu cards shown on practitioner portal home page.

**Used in**: Practitioner portal homepage

```json
"portalHomeMenu": [
  {
    "title": "Personal Details",
    "image": "assets/images/medical-bg.jpg",
    "icon": "person_outlined",
    "dataPoints": [
      {
        "label": "License Number",
        "field": "license_number"
      },
      {
        "label": "Registration Date",
        "field": "registration_date"
      }
    ],
    "actions": [
      {
        "label": "View/Edit Your Personal Details",
        "icon": "",
        "type": "link",
        "linkProp": "",
        "url": "/profile",
        "urlParams": {},
        "criteria": []
      }
    ],
    "alerts": [
      {
        "criteria": [
          {
            "field": "require_revalidation",
            "operator": "equals",
            "value": ["yes"]
          }
        ],
        "message": "<p>Please update your information</p>",
        "type": "warning"
      }
    ],
    "description": "View or edit your name, address, phone number, etc.",
    "criteria": [
      {
        "field": "user_type",
        "operator": "equals",
        "value": ["license"]
      }
    ]
  }
]
```

**Properties**:
- `title`: Card title
- `image`: Background image
- `icon`: Material icon name
- `dataPoints`: Key information to display (with labels and field names)
- `actions`: Buttons/links with optional criteria
- `alerts`: Conditional warning/info messages
- `description`: Card description
- `criteria`: Conditions to show this card

**Alert Types**: `info`, `warning`, `danger`, `success`

---

### 6. Examinations (`examinations`)

**Type**: `object`

**Purpose**: Configures the examination management system.

**Used in**:
- [app/Helpers/ExaminationsUtils.php](app/Helpers/ExaminationsUtils.php)
- [app/Services/ExaminationService.php](app/Services/ExaminationService.php)
- [app/Models/Examinations/ExaminationsModel.php](app/Models/Examinations/ExaminationsModel.php)

```json
"examinations": {
  "applicantsDownloadFields": [
    "last_name",
    "first_name",
    "qualification"
  ],
  "examination_types": {
    "OSCE 1": {
      "name": "OSCE 1",
      "description": "Objective Structured Clinical Examination (OSCE) 1",
      "required_previous_exam_result": [],
      "available_for_license_types": {
        "exam_candidates": {
          "type": ["Doctor"]
        }
      },
      "score_fields": ["Practical 1", "Practical 2"],
      "candidate_state_after_result": {
        "Pass": "Apply for examination",
        "Fail": "Apply for examination"
      },
      "metadata_fields": [
        {
          "label": "Venue",
          "name": "venue",
          "type": "textarea",
          "required": false
        }
      ]
    }
  },
  "defaultLetterTypes": [
    {
      "type": "registration",
      "name": "Index number letter - All",
      "criteria": []
    }
  ],
  "filterFields": [],
  "metadataFields": [],
  "practitionerTypes": []
}
```

**Properties**:
- `applicantsDownloadFields`: Fields to include in applicant exports
- `examination_types`: Available examination configurations
- `defaultLetterTypes`: Pre-configured letter templates
- `filterFields`: Filter options for examination lists
- `metadataFields`: Additional data fields for exams
- `practitionerTypes`: Practitioner types that take exams

**Examination Type Properties**:
- `name`: Exam name
- `description`: Full description
- `required_previous_exam_result`: Array of prerequisite exams
- `available_for_license_types`: Which license types can take this exam (with criteria)
- `score_fields`: Array of score component names
- `candidate_state_after_result`: What state candidates move to after Pass/Fail
- `metadata_fields`: Additional fields to collect during exam setup

---

### 7. Housemanship (`housemanship`)

**Type**: `object`

**Purpose**: Configures housemanship/internship program management.

**Used in**: [app/Helpers/Utils.php:854](app/Helpers/Utils.php#L854) - `getHousemanshipSetting()`

```json
"housemanship": {
  "applicationFormTags": [
    {
      "name": "Direct Entry",
      "value": "Direct Entry",
      "implicit": false,
      "criteria": [
        {
          "field": "practitioner_type",
          "value": ["Physician Assistant"]
        }
      ],
      "description": "Physician Assistants enter directly"
    }
  ],
  "availabilityCategories": [
    {
      "name": "Available",
      "value": "available",
      "color": "green"
    }
  ],
  "sessions": {
    "1": {
      "number_of_facilities": 1,
      "number_of_disciplines": 2,
      "application_form_fields": [],
      "criteria": [
        {
          "field": "practitioner_type",
          "value": ["Physician Assistant", "Doctor"]
        }
      ],
      "allowRepeatRegion": true,
      "requireDisciplines": false
    }
  }
}
```

**Properties**:
- `applicationFormTags`: Categories for housemanship applications (with criteria for auto-tagging)
- `availabilityCategories`: Status options for facilities/positions
- `sessions`: Different housemanship sessions with requirements

**Session Properties**:
- `number_of_facilities`: How many facilities interns must rotate through
- `number_of_disciplines`: How many medical disciplines required
- `application_form_fields`: Additional fields for this session
- `criteria`: Who is eligible for this session
- `allowRepeatRegion`: Whether interns can rotate in same region twice
- `requireDisciplines`: Whether specific disciplines must be selected

---

### 8. System Settings (`systemSettings`)

**Type**: `object`

**Purpose**: Runtime toggles and configuration values for system behavior.

**Used in**: [app/Helpers/Utils.php:1640](app/Helpers/Utils.php#L1640) - `getSystemSetting()`

```json
"systemSettings": {
  "housemanship_session_1_applications_open": {
    "key": "housemanship_session_1_applications_open",
    "value": [
      {
        "criteria": [],
        "value": true
      }
    ],
    "label": "Housemanship applications open",
    "control_type": "yes-no",
    "class": "General",
    "type": "boolean",
    "description": "Whether housemanship applications can be submitted from the portal or not"
  },
  "maintenance_mode": {
    "key": "maintenance_mode",
    "value": [
      {
        "criteria": [],
        "value": false
      }
    ],
    "label": "Maintenance Mode",
    "control_type": "yes-no",
    "class": "System",
    "type": "boolean",
    "description": "Enable maintenance mode to prevent user access"
  }
}
```

**Properties**:
- `key`: Unique identifier (matches object key)
- `value`: Array of conditional values with criteria
- `label`: Display label for admin UI
- `control_type`: UI control type ("yes-no", "text", "select", etc.)
- `class`: Category/grouping for organization
- `type`: Data type ("boolean", "string", "number", etc.)
- `description`: Explanation of what this setting controls

**Conditional Values**: The `value` array allows different values based on criteria. First matching criteria is used.

---

### 9. Training Institutions (`trainingInstitutions`)

**Type**: `object`

**Purpose**: Maps practitioner types to their training/student tables.

**Used in**: [app/Helpers/Utils.php:1652](app/Helpers/Utils.php#L1652) - `getTrainingInstitutionsSettings()`

```json
"trainingInstitutions": {
  "practitioner_types": [
    {
      "key": "Medical Students",
      "value": "indexed_students"
    },
    {
      "key": "Dental Students",
      "value": "indexed_students"
    }
  ]
}
```

---

### 10. Print and Letter Templates

#### Default Print Templates (`defaultPrintTemplates`)

**Type**: `array`

**Purpose**: HTML templates for generating certificates and documents.

**Used in**: [app/Helpers/Utils.php:1627](app/Helpers/Utils.php#L1627) - `getDefaultPrintTemplates()`

```json
"defaultPrintTemplates": [
  {
    "template_name": "Default Doctors/PA Renewal Certificate Template",
    "template_content": "<html><body><h1>Certificate of Renewal</h1><p>This certifies that {first_name} {last_name}...</p></body></html>"
  }
]
```

**Placeholders**: Use `{field_name}` syntax to insert data from licenses/renewals.

#### Letter Container (`letterContainer`)

**Type**: `object`

**Purpose**: Standard HTML wrapper for all letters/documents.

**Used in**: [app/Helpers/Utils.php:1118](app/Helpers/Utils.php#L1118) - `addLetterStyling()`

```json
"letterContainer": {
  "html": "<!DOCTYPE html><html><head><style>/* CSS */</style></head><body><div class='header'><img src='logo.png'/></div><div class='content'>[##content##]</div><div class='footer'>Footer</div></body></html>"
}
```

**Placeholder**: `[##content##]` is replaced with actual letter content

---

### 11. Portal Configuration

#### Portal Alerts (`portalAlerts`)

**Type**: `array`

**Purpose**: Conditional alert banners shown to practitioners.

```json
"portalAlerts": [
  {
    "criteria": [
      {
        "field": "require_revalidation",
        "operator": "equals",
        "value": ["yes"]
      }
    ],
    "message": "<p><strong>Action Required:</strong> You must revalidate your information before renewing.</p>",
    "type": "danger"
  },
  {
    "criteria": [
      {
        "field": "license_status",
        "operator": "equals",
        "value": ["Expiring Soon"]
      }
    ],
    "message": "<p>Your license expires in 30 days. Please renew now.</p>",
    "type": "warning"
  }
]
```

**Alert Types**:
- `danger`: Red, for critical issues
- `warning`: Yellow, for important notices
- `info`: Blue, for informational messages
- `success`: Green, for positive confirmations

#### Portal Home Subtitle Fields (`portalHomeSubTitleFields`)

**Type**: `array`

**Purpose**: Dynamic information shown below practitioner name on portal home.

```json
"portalHomeSubTitleFields": [
  {
    "field": "license_number",
    "label": "License number",
    "criteria": [
      {
        "field": "type",
        "operator": "equals",
        "value": ["practitioners"]
      }
    ],
    "template": "{license_number}"
  }
]
```

---

### 12. Search and Filter Configurations

#### Search Types (`searchTypes`)

**Type**: `array`

**Purpose**: Quick search options available in the search menu.

```json
"searchTypes": [
  {
    "title": "Search Doctors",
    "active_children": false,
    "url": "/licenses/list/practitioners",
    "icon": "person_outlined",
    "children": [],
    "urlParams": {
      "child_practitioner_type": "Doctor"
    }
  }
]
```

#### CPD Filter Fields (`cpdFilterFields`)

**Type**: `array`

**Purpose**: Defines filter options for CPD lists.

```json
"cpdFilterFields": [
  {
    "label": "Search",
    "name": "param",
    "hint": "Search by topic",
    "options": [],
    "type": "text",
    "value": "",
    "required": false
  },
  {
    "label": "Category",
    "name": "category",
    "hint": "Filter by CPD category",
    "options": [
      {"key": "1", "value": "Category 1"},
      {"key": "2", "value": "Category 2"}
    ],
    "type": "select",
    "value": "",
    "required": false
  }
]
```

#### Statistics Filter Fields

Similar structure for:
- `basicStatisticsFilterFields`
- `advancedStatisticsFilterFields`
- `renewalBasicStatisticsFilterFields`

---

### 13. User Types (`userTypesNames`)

**Type**: `array`

**Purpose**: Defines user types for registration/login.

```json
"userTypesNames": [
  {
    "value": "license",
    "label": "Registered Doctors and Physician Assistants"
  },
  {
    "value": "cpd",
    "label": "CPD Institutions"
  },
  {
    "value": "facility",
    "label": "Healthcare Facilities"
  }
]
```

---

### 14. Miscellaneous Settings

#### Allowed Test Emails (`allowedTestEmails`)

**Type**: `array`

**Purpose**: Email addresses that can receive emails in test/development mode.

```json
"allowedTestEmails": [
  "developer@example.com",
  "tester@example.com"
]
```

---

## Criteria System

Many configuration objects use a `criteria` array to conditionally apply settings. Understanding this system is crucial.

**Structure**:

```json
"criteria": [
  {
    "field": "practitioner_type",
    "operator": "equals",
    "value": ["Doctor"]
  },
  {
    "field": "register_type",
    "operator": "not_equals",
    "value": ["Temporary"]
  }
]
```

**Operators**:
- `equals`: Field value matches any value in array
- `not_equals`: Field value doesn't match any value in array
- `greater_than`: Field value is greater than value
- `less_than`: Field value is less than value
- `contains`: Field value contains substring

**Special Values**:
- `[1]`: Any non-empty value
- `[0]`: Empty or null value

**Logic**: All criteria in an array must match (AND logic). To achieve OR logic, create multiple objects.

**Used in**: [app/Helpers/Utils.php:1383](app/Helpers/Utils.php#L1383) - `criteriaMatch()`

---

## Validation Rules

Many sections use CodeIgniter validation rule syntax:

```json
"onCreateValidation": {
  "first_name": "required|min_length[2]|max_length[100]",
  "email": "required|valid_email|is_unique[practitioners.email]",
  "phone": "permit_empty|regex_match[/^[0-9+\\-\\s()]+$/]"
}
```

**Common Rules**:
- `required`: Field must have a value
- `permit_empty`: Allow empty values (for optional fields)
- `min_length[n]`, `max_length[n]`: String length constraints
- `valid_email`: Must be valid email format
- `is_unique[table.field]`: Must be unique in database
- `regex_match[pattern]`: Must match regex pattern
- `in_list[a,b,c]`: Must be one of specified values

---

## Best Practices

### 1. Creating a New Configuration File

When creating a configuration for a new institution:

1. Copy an existing file (e.g., `app-settings-mdc.json`)
2. Update basic information:
   - `appName`, `appLongName`, `institutionName`
   - Contact details (email, phone, address, website)
   - Logos and images
3. Review license types and adjust as needed
4. Configure payment methods and purposes
5. Customize menus and navigation
6. Set renewal rules appropriate for your institution
7. Test thoroughly, especially payment and renewal workflows

### 2. Modifying Existing Configurations

- **Use database overrides** for temporary or frequently changing values
- **Edit JSON files** for permanent structural changes
- **Clear cache** after changes: Call `/api/app-settings/clear-cache`
- **Version control**: Commit JSON changes to git with descriptive messages
- **Test in development** before deploying to production

### 3. License Type Configuration

When adding a new license type:

1. Create database table for license data
2. Add migration for the table
3. Define the license type in `licenseTypes` with:
   - `table`, `uniqueKeyField`
   - `licenseNumberFormats` with appropriate criteria
   - `fields` for the creation form
   - `onCreateValidation` rules
   - `renewalStages` for workflow
   - `searchFields` for finding licenses
4. Add payment purpose for renewals (if applicable)
5. Add menu items in `sidebarMenu` and `dashboardMenu`
6. Add search type in `searchTypes`

### 4. Payment Configuration

When configuring payments:

1. Define service codes in the `fees` database table
2. Create payment purpose with appropriate criteria
3. Map service codes to invoice items
4. Configure post-payment actions
5. Test the complete payment workflow
6. Verify email notifications work correctly

### 5. Security Considerations

- **Never commit** sensitive data (API keys, passwords) to JSON files
- Use environment variables for sensitive configuration
- **Review criteria carefully** to prevent unauthorized access
- Test permission checks on all renewal stages
- Validate file uploads properly in form configurations

### 6. Performance

- Complex criteria and large license type configurations can slow down the system
- Use database overrides sparingly for frequently accessed settings
- Monitor cache hit rates
- Consider splitting very large license types into subtypes

---

## Troubleshooting

### Settings Not Applying

1. Check `APP_SETTINGS_FILE` in `.env` points to correct file
2. Verify JSON syntax is valid (use a JSON validator)
3. Clear settings cache: `POST /api/app-settings/clear-cache`
4. Check for active database overrides that might be conflicting
5. Review application logs for errors

### License Numbers Not Generating

1. Verify `licenseNumberFormats` has correct criteria
2. Check that `sequenceKey` matches an existing field
3. Ensure the sequence exists in the database (check `sequences` table)
4. Review criteria evaluation order (first match wins)

### Renewal Dates Wrong

1. Check `renewalRules` criteria are correctly ordered
2. Verify date patterns in `datePatterns` are valid
3. Review license data to ensure criteria fields exist and have expected values
4. Check for empty criteria rule that acts as default/fallback

### Payment Actions Not Firing

1. Verify payment purpose matches exactly
2. Check criteria on invoice items
3. Review action configuration syntax
4. Check logs for action execution errors
5. Ensure external API endpoints are accessible (if using api_call actions)

### Forms Not Showing

1. Check `open_date` and `close_date` are valid
2. Verify `available_externally` setting
3. Review `restrictions` criteria
4. Check user permissions
5. Ensure form data fields are valid

---

## Migration from Old Versions

If migrating from an older system:

1. **Backup** existing configuration files
2. Compare structures with new template
3. Add any missing top-level keys with default values
4. Update license type structures to include new fields
5. Convert old date format strings to new pattern system
6. Update payment configurations to new structure
7. Test all workflows thoroughly after migration
8. Run database migrations to add override table if needed

---

## API Documentation

For detailed API documentation related to settings management, see:

- `GET /api/app-settings` - Retrieve all settings
- `GET /api/app-settings/:key` - Get specific setting
- `POST /api/app-settings` - Create override
- `PUT /api/app-settings/:id` - Update override
- `DELETE /api/app-settings/:id` - Remove override
- `GET /api/app-settings/keys` - List all available keys
- `POST /api/app-settings/clear-cache` - Clear settings cache

---

## Additional Resources

- **Code References**:
  - Settings Helper: [app/Helpers/Utils.php](app/Helpers/Utils.php)
  - Override Model: [app/Models/AppSettingsOverridesModel.php](app/Models/AppSettingsOverridesModel.php)
  - Settings Controller: [app/Controllers/AppSettingsController.php](app/Controllers/AppSettingsController.php)
  - License Utils: [app/Helpers/LicenseUtils.php](app/Helpers/LicenseUtils.php)
  - Renewal Date Generator: [app/Helpers/LicenseRenewalDateGenerator.php](app/Helpers/LicenseRenewalDateGenerator.php)

- **Database Tables**:
  - `app_settings_overrides`: Runtime setting overrides
  - `sequences`: Auto-increment sequences for license numbers
  - `fees`: Service codes and pricing
  - `licenses`: Main license table
  - `license_renewal`: Renewal records

- **Configuration Files**:
  - `.env`: Environment-specific settings
  - `app/Config/Routes.php`: Route definitions with permissions
  - Main project documentation: [CLAUDE.md](CLAUDE.md)

---

## Glossary

- **CPD**: Continuing Professional Development - ongoing education requirements
- **MDA Code**: Ministry, Department, Agency code for government payment systems
- **License Type**: Category of license (practitioners, facilities, etc.)
- **Renewal Stage**: Workflow step in license renewal process
- **Criteria**: Conditional logic for applying configuration rules
- **Service Code**: Fee identifier in the fees table
- **Merge Strategy**: Method for combining file settings with database overrides
- **Payment Purpose**: What is being paid for (renewal, application, etc.)
- **User Type**: Category of portal user (license holder, CPD provider, facility)

---

**Document Version**: 1.0
**Last Updated**: 2025-12-11
**Maintainer**: Development Team
