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