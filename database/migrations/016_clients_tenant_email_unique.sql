-- Um e-mail por tenant (vários NULL permitidos pelo MySQL em UNIQUE).
ALTER TABLE clients
    ADD UNIQUE KEY uk_clients_tenant_email (tenant_id, email);
