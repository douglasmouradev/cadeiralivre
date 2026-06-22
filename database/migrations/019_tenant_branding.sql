ALTER TABLE tenants
    ADD COLUMN cover_path VARCHAR(255) NULL AFTER logo_path,
    ADD COLUMN public_tagline VARCHAR(160) NULL AFTER cover_path,
    ADD COLUMN instagram_url VARCHAR(255) NULL AFTER public_tagline;
