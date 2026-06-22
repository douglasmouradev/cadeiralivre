CREATE TABLE saas_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(64) NOT NULL,
    tenant_id BIGINT UNSIGNED NULL,
    meta_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_saas_audit_tenant (tenant_id, created_at),
    KEY idx_saas_audit_actor (actor_user_id, created_at),
    CONSTRAINT fk_saas_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_saas_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
