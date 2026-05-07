ALTER TABLE appointments
    ADD COLUMN payment_method VARCHAR(32) NULL DEFAULT NULL AFTER notes,
    ADD COLUMN payment_note TEXT NULL DEFAULT NULL AFTER payment_method;
