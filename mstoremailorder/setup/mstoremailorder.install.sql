-- Сначала удаляем таблицу cot_mstore_mailorder_status_history, чтобы избежать ошибки из-за внешнего ключа
DROP TABLE IF EXISTS `cot_mstore_mailorder_status_history`;

-- Затем удаляем таблицу cot_mstore_mailorders
DROP TABLE IF EXISTS `cot_mstore_mailorders`;

-- Создаем таблицу cot_mstore_mailorders
CREATE TABLE `cot_mstore_mailorders` (
  `order_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_item_id` int UNSIGNED NOT NULL,
  `order_user_id` int UNSIGNED DEFAULT '0',
  `order_seller_id` int UNSIGNED NOT NULL,
  `order_quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `order_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `order_date` int UNSIGNED NOT NULL,
  `order_ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_status` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `order_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`order_id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `order_user_id` (`order_user_id`),
  KEY `order_seller_id` (`order_seller_id`),
  CONSTRAINT `fk_order_item_id` FOREIGN KEY (`order_item_id`) REFERENCES `cot_mstore` (`msitem_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_seller_id` FOREIGN KEY (`order_seller_id`) REFERENCES `cot_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_user_id` FOREIGN KEY (`order_user_id`) REFERENCES `cot_users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создаем таблицу cot_mstore_mailorder_status_history
CREATE TABLE `cot_mstore_mailorder_status_history` (
  `history_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int UNSIGNED NOT NULL,
  `status` tinyint UNSIGNED NOT NULL,
  `change_date` int UNSIGNED NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `fk_history_order_id` FOREIGN KEY (`order_id`) REFERENCES `cot_mstore_mailorders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;