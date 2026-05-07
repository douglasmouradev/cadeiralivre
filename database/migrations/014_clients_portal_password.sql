ALTER TABLE clients
    ADD COLUMN portal_password_hash VARCHAR(255) NULL AFTER notes;

UPDATE clients SET portal_password_hash = '$2y$12$nmxRrvGI55N8SVtHI0zibudC5ioBodPMVJzYy.Sr.eLzqJjGlMcu6'
WHERE tenant_id = 1 AND email = 'carlos@cliente.com' AND portal_password_hash IS NULL
LIMIT 1;
