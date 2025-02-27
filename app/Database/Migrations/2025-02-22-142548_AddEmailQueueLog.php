<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailQueueLog extends Migration
{
    public function up()
    {
        $query = "
CREATE TABLE `email_queue_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email_queue_id` INT(11) UNSIGNED NOT NULL,
  `status` ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_email_queue_id` (`email_queue_id`),
  CONSTRAINT `fk_email_queue_log_email_queue_id` FOREIGN KEY (`email_queue_id`) REFERENCES `email_queue` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


        $this->db->query($query);
    }

    public function down()
    {
        //
    }
}
