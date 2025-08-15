Examples of application form actions
```
{
            "type": "email",
            "config_type": "email",
            "label": "Send Email",
            "config": {
                "template": "",
                "subject": ""
            }
        },
        {
            "type": "admin_email",
            "config_type": "admin_email",
            "label": "Send Admin Email",
            "config": {
                "template": "",
                "subject": "",
                "admin_email": ""
            }
        },
        {
            "type": "api_call",
            "config_type": "api_call",
            "label": "API Call",
            "config": {
                "endpoint": "",
                "method": "GET",
                "auth_token": "__self__",
                "headers": [],
                "body_mapping": [],
                "query_params": []
            }
        },
        {
            "type": "create_practitioners",
            "config_type": "internal_api_call",
            "label": "Create Practitioners Instance",
            "config": {
                "body_mapping": {
                    "type": "practitioners",
                    "license_number": "@license_number",
                    "registration_date": "2025-08-15",
                    "status": "@status",
                    "email": "@email",
                    "picture": "@picture",
                    "postal_address": "@postal_address",
                    "phone": "@phone",
                    "region": "@region",
                    "district": "@district",
                    "portal_access": "@portal_access",
                    "last_revalidation_date": "@last_revalidation_date",
                    "require_revalidation": "@require_revalidation",
                    "register_type": "@register_type",
                    "practitioner_type": "@practitioner_type",
                    "first_name": "@first_name",
                    "middle_name": "@middle_name",
                    "last_name": "@last_name",
                    "date_of_birth": "@date_of_birth",
                    "sex": "@sex",
                    "title": "@title",
                    "maiden_name": "@maiden_name",
                    "marital_status": "@marital_status",
                    "nationality": "@nationality",
                    "training_institution": "@training_institution",
                    "qualification_date": "@qualification_date",
                    "place_of_work": "@place_of_work",
                    "institution_type": "@institution_type",
                    "specialty": "@specialty",
                    "subspecialty": "@subspecialty",
                    "college_membership": "@college_membership"
                }
            }
        },
        {
            "type": "create_facilities",
            "config_type": "internal_api_call",
            "label": "Create Facilities Instance",
            "config": {
                "body_mapping": {
                    "type": "facilities",
                    "license_number": "@license_number",
                    "registration_date": "2025-08-15",
                    "status": "@status",
                    "email": "@email",
                    "picture": "@picture",
                    "postal_address": "@postal_address",
                    "phone": "@phone",
                    "region": "@region",
                    "district": "@district",
                    "portal_access": "@portal_access",
                    "last_revalidation_date": "@last_revalidation_date",
                    "require_revalidation": "@require_revalidation",
                    "register_type": "@register_type",
                    "name": "@name",
                    "business_type": "@business_type",
                    "town": "@town",
                    "suburb": "@suburb",
                    "street": "@street",
                    "house_number": "@house_number",
                    "coordinates": "@coordinates",
                    "application_code": "@application_code",
                    "parent_company": "@parent_company",
                    "ghana_post_code": "@ghana_post_code",
                    "notes": "@notes",
                    "cbd": "@cbd"
                }
            }
        },
        {
            "type": "create_otcms",
            "config_type": "internal_api_call",
            "label": "Create Otcms Instance",
            "config": {
                "body_mapping": {
                    "type": "otcms",
                    "license_number": "@license_number",
                    "registration_date": "2025-08-15",
                    "status": "@status",
                    "email": "@email",
                    "picture": "@picture",
                    "postal_address": "@postal_address",
                    "phone": "@phone",
                    "region": "@region",
                    "district": "@district",
                    "portal_access": "@portal_access",
                    "last_revalidation_date": "@last_revalidation_date",
                    "require_revalidation": "@require_revalidation",
                    "register_type": "@register_type",
                    "name": "@name",
                    "maiden_name": "@maiden_name",
                    "date_of_birth": "@date_of_birth",
                    "sex": "@sex",
                    "qualification": "@qualification",
                    "premises_address": "@premises_address",
                    "town": "@town",
                    "coordinates": "@coordinates",
                    "application_code": "@application_code"
                }
            }
        },
        {
            "type": "create_exam_candidates",
            "config_type": "internal_api_call",
            "label": "Create Exam candidates Instance",
            "config": {
                "body_mapping": {
                    "type": "exam_candidates",
                    "license_number": "@intern_code",
                    "registration_date": "2025-08-15",
                    "status": "@status",
                    "email": "@email",
                    "picture": "@picture",
                    "postal_address": "@postal_address",
                    "phone": "@phone",
                    "region": "@region",
                    "district": "@district",
                    "portal_access": "@portal_access",
                    "last_revalidation_date": "@last_revalidation_date",
                    "require_revalidation": "@require_revalidation",
                    "register_type": "@register_type",
                    "practitioner_type": "@practitioner_type",
                    "intern_code": "@intern_code",
                    "first_name": "@first_name",
                    "middle_name": "@middle_name",
                    "last_name": "@last_name",
                    "date_of_birth": "@date_of_birth",
                    "sex": "@sex",
                    "nationality": "@nationality",
                    "training_institution": "@training_institution",
                    "qualification": "@qualification",
                    "qualification_date": "@qualification_date"
                }
            }
        }
```