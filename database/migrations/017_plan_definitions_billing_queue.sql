CREATE TABLE plan_definitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(32) NOT NULL,
    name VARCHAR(80) NOT NULL,
    max_barbers INT UNSIGNED NULL COMMENT 'NULL = ilimitado',
    max_appointments_per_month INT UNSIGNED NULL COMMENT 'NULL = ilimitado',
    monthly_price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    stripe_price_id VARCHAR(120) NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plan_definitions_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO plan_definitions (slug, name, max_barbers, max_appointments_per_month, monthly_price_cents, sort_order) VALUES
('free', 'Grátis / Trial', 3, 200, 0, 1),
('pro', 'Profissional', 20, NULL, 9900, 2),
('enterprise', 'Empresarial', NULL, NULL, 29900, 3);

ALTER TABLE tenants
    ADD COLUMN plan_definition_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER plan,
    ADD COLUMN subscription_status ENUM('none','trialing','active','past_due','canceled') NOT NULL DEFAULT 'none' AFTER plan_definition_id,
    ADD COLUMN billing_provider VARCHAR(32) NULL DEFAULT NULL AFTER subscription_status,
    ADD COLUMN billing_customer_id VARCHAR(191) NULL DEFAULT NULL AFTER billing_provider,
    ADD COLUMN billing_subscription_id VARCHAR(191) NULL DEFAULT NULL AFTER billing_customer_id,
    ADD CONSTRAINT fk_tenants_plan_definition FOREIGN KEY (plan_definition_id) REFERENCES plan_definitions(id) ON DELETE SET NULL;

UPDATE tenants t
INNER JOIN plan_definitions p ON p.slug = t.plan
SET t.plan_definition_id = p.id;

UPDATE tenants SET subscription_status = 'trialing' WHERE status = 'trial';

CREATE TABLE outbound_emails (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(190) NOT NULL,
    to_name VARCHAR(120) NOT NULL DEFAULT '',
    subject VARCHAR(255) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error VARCHAR(500) NULL,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_outbound_pending (sent_at, available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
