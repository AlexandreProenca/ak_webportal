CREATE TABLE IF NOT EXISTS `#__ak_me_credentials` (
  `shipping_id` int unsigned NOT NULL,
  `environment` varchar(16) NOT NULL DEFAULT 'sandbox',
  `access_token` mediumtext NOT NULL,
  `refresh_token` mediumtext NOT NULL,
  `expires_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`shipping_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ak_me_shipments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `shipping_id` int unsigned NOT NULL,
  `service_id` int unsigned NOT NULL,
  `state` varchar(32) NOT NULL DEFAULT 'pending',
  `quote_json` mediumtext NOT NULL,
  `payload_hash` char(64) NOT NULL,
  `invoice_key` varchar(44) NOT NULL DEFAULT '',
  `last_error` text NULL,
  `locked_at` datetime NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ak_me_shipment_order` (`order_id`),
  KEY `idx_ak_me_shipment_state` (`state`),
  KEY `idx_ak_me_shipment_shipping` (`shipping_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ak_me_labels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `package_index` int unsigned NOT NULL DEFAULT 0,
  `external_id` varchar(64) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'cart',
  `tracking` varchar(128) NOT NULL DEFAULT '',
  `tracking_url` varchar(1024) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ak_me_label_external` (`external_id`),
  UNIQUE KEY `idx_ak_me_label_package` (`shipment_id`, `package_index`),
  KEY `idx_ak_me_label_shipment` (`shipment_id`),
  CONSTRAINT `fk_ak_me_label_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `#__ak_me_shipments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ak_me_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_hash` char(64) NOT NULL,
  `event_name` varchar(64) NOT NULL,
  `external_id` varchar(64) NOT NULL DEFAULT '',
  `payload_json` mediumtext NOT NULL,
  `processed_at` datetime NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ak_me_event_hash` (`event_hash`),
  KEY `idx_ak_me_event_external` (`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
