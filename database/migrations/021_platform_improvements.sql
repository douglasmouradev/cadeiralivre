ALTER TABLE clients
    ADD COLUMN deleted_at DATETIME NULL AFTER updated_at,
    ADD KEY idx_clients_tenant_active (tenant_id, deleted_at);

ALTER TABLE tenants
    ADD COLUMN onboarding_completed_at DATETIME NULL AFTER subscription_status,
    ADD COLUMN webhook_url VARCHAR(500) NULL AFTER instagram_url;

CREATE TABLE tenant_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    actor_user_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(64) NOT NULL,
    meta_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tenant_audit (tenant_id, created_at),
    CONSTRAINT fk_tenant_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_tenant_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
