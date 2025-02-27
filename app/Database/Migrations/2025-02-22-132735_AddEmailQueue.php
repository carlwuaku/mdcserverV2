<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailQueue extends Migration
{
    public function up()
    {
        $query = "
        CREATE TABLE `email_queue` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `to_email` VARCHAR(255) NOT NULL,
  `from_email` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `cc` VARCHAR(255) DEFAULT NULL,
  `bcc` VARCHAR(255) DEFAULT NULL,
  `attachment_path` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
  `priority` TINYINT(1) UNSIGNED NOT NULL DEFAULT 3 COMMENT '1=high, 2=medium, 3=low',
  `attempts` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` TINYINT(1) UNSIGNED NOT NULL DEFAULT 3,
  `error_message` TEXT DEFAULT NULL,
  `headers` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `scheduled_at` DATETIME DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_scheduled_at` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $this->db->query($query);
    }

    public function down()
    {
        //
    }
}
