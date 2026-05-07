CREATE TABLE working_hours (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barber_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL COMMENT '0=Sun ... 6=Sat',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_day_off TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_wh_barber FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    CONSTRAINT fk_wh_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    KEY idx_wh_tenant (tenant_id),
    KEY idx_wh_barber_day (barber_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
