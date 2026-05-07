-- Seed demo: 1 tenant, 1 owner, 3 barbeiros, 5 serviços, 8 clientes, 20 agendamentos, pagamentos em concluídos passados.
-- Senha de todos os usuários seed: Senha1234

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO tenants (id, name, slug, email, phone, address, city, state, logo_path, primary_color, timezone, status, trial_ends_at, plan, created_at, updated_at)
VALUES (1, 'Barbearia Demo', 'demo-barbearia', 'contato@demo-barbearia.com', '11999990000', 'Rua das Flores, 100', 'São Paulo', 'SP', NULL, '#D4AF37', 'America/Sao_Paulo', 'active', NULL, 'pro', NOW(), NOW());

INSERT INTO users (id, tenant_id, name, email, password_hash, role, phone, avatar_path, is_active, email_verified_at, remember_token, created_at, updated_at) VALUES
(1, 1, 'Dono Demo', 'owner@demo.local', '$2y$12$nmxRrvGI55N8SVtHI0zibudC5ioBodPMVJzYy.Sr.eLzqJjGlMcu6', 'owner', '11988887777', NULL, 1, NOW(), NULL, NOW(), NOW()),
(2, 1, 'João Cortes', 'barber1@demo.local', '$2y$12$nmxRrvGI55N8SVtHI0zibudC5ioBodPMVJzYy.Sr.eLzqJjGlMcu6', 'barber', NULL, NULL, 1, NULL, NULL, NOW(), NOW()),
(3, 1, 'Pedro Navalha', 'barber2@demo.local', '$2y$12$nmxRrvGI55N8SVtHI0zibudC5ioBodPMVJzYy.Sr.eLzqJjGlMcu6', 'barber', NULL, NULL, 1, NULL, NULL, NOW(), NOW()),
(4, 1, 'Lucas Fade', 'barber3@demo.local', '$2y$12$nmxRrvGI55N8SVtHI0zibudC5ioBodPMVJzYy.Sr.eLzqJjGlMcu6', 'barber', NULL, NULL, 1, NULL, NULL, NOW(), NOW()),
(5, 1, 'Recepção Ana', 'recep@demo.local', '$2y$12$nmxRrvGI55N8SVtHI0zibudC5ioBodPMVJzYy.Sr.eLzqJjGlMcu6', 'receptionist', NULL, NULL, 1, NULL, NULL, NOW(), NOW());

INSERT INTO barbers (id, user_id, tenant_id, bio, specialties, commission_percent, is_available, created_at, updated_at) VALUES
(1, 2, 1, 'Especialista em degradê.', JSON_ARRAY('fade', 'social'), 35.00, 1, NOW(), NOW()),
(2, 3, 1, 'Barba e navalha.', JSON_ARRAY('barba', 'navalha'), 30.00, 1, NOW(), NOW()),
(3, 4, 1, 'Cortes clássicos.', JSON_ARRAY('clássico'), 40.00, 1, NOW(), NOW());

INSERT INTO services (id, tenant_id, name, description, duration_minutes, price, category, is_active, display_order, created_at, updated_at) VALUES
(1, 1, 'Corte social', 'Corte tradicional com acabamento.', 30, 45.00, 'Corte', 1, 0, NOW(), NOW()),
(2, 1, 'Degradê premium', 'Degradê com disfarce.', 45, 60.00, 'Corte', 1, 1, NOW(), NOW()),
(3, 1, 'Barba completa', 'Toalha quente e navalha.', 30, 35.00, 'Barba', 1, 2, NOW(), NOW()),
(4, 1, 'Hidratação capilar', 'Tratamento revitalizante.', 40, 55.00, 'Tratamento', 1, 3, NOW(), NOW()),
(5, 1, 'Combo corte + barba', 'Pacote completo.', 60, 85.00, 'Combo', 1, 4, NOW(), NOW());

INSERT INTO barber_services (barber_id, service_id, custom_price) VALUES
(1, 1, NULL), (1, 2, NULL), (1, 5, NULL),
(2, 1, NULL), (2, 3, NULL), (2, 5, NULL),
(3, 1, NULL), (3, 4, NULL), (3, 5, NULL);

INSERT INTO working_hours (barber_id, tenant_id, day_of_week, start_time, end_time, is_day_off)
SELECT b.id, 1, d.dow, '09:00:00', CASE WHEN d.dow = 6 THEN '14:00:00' ELSE '18:00:00' END, CASE WHEN d.dow = 0 THEN 1 ELSE 0 END
FROM barbers b
CROSS JOIN (SELECT 0 AS dow UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) d;

INSERT INTO clients (id, tenant_id, name, email, phone, birth_date, notes, created_at, updated_at) VALUES
(1, 1, 'Carlos Silva', 'carlos@cliente.com', '11911112222', '1990-05-10', NULL, NOW(), NOW()),
(2, 1, 'Marcos Souza', 'marcos@cliente.com', '11922223333', NULL, NULL, NOW(), NOW()),
(3, 1, 'Rafael Lima', 'rafael@cliente.com', '11933334444', NULL, NULL, NOW(), NOW()),
(4, 1, 'Felipe Dias', 'felipe@cliente.com', '11944445555', NULL, NULL, NOW(), NOW()),
(5, 1, 'André Costa', 'andre@cliente.com', '11955556666', NULL, NULL, NOW(), NOW()),
(6, 1, 'Bruno Alves', 'bruno@cliente.com', '11966667777', NULL, NULL, NOW(), NOW()),
(7, 1, 'Gustavo Rocha', 'gustavo@cliente.com', '11977778888', NULL, NULL, NOW(), NOW()),
(8, 1, 'Tiago Nunes', 'tiago@cliente.com', '11988889999', NULL, NULL, NOW(), NOW());

INSERT INTO appointments (id, tenant_id, client_id, barber_id, service_id, booked_by_user_id, start_datetime, end_datetime, status, price, discount, notes, cancellation_reason, reminder_sent_at, public_token, confirmation_code, review_token, created_at, updated_at) VALUES
(1, 1, 1, 1, 1, 1, DATE_ADD(CURDATE(), INTERVAL 10 HOUR), DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 10 HOUR), INTERVAL 30 MINUTE), 'pending', 45.00, 0, NULL, NULL, NULL, REPEAT('a', 64), '123456', REPEAT('b', 64), NOW(), NOW()),
(2, 1, 2, 2, 3, 1, DATE_ADD(CURDATE(), INTERVAL 11 HOUR), DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 11 HOUR), INTERVAL 30 MINUTE), 'confirmed', 35.00, 0, NULL, NULL, NULL, REPEAT('c', 64), '234567', REPEAT('d', 64), NOW(), NOW()),
(3, 1, 3, 3, 4, 1, DATE_ADD(CURDATE(), INTERVAL 14 HOUR), DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 14 HOUR), INTERVAL 40 MINUTE), 'confirmed', 55.00, 0, NULL, NULL, NULL, REPEAT('e', 64), '345678', REPEAT('f', 64), NOW(), NOW()),
(4, 1, 4, 1, 2, 1, DATE_ADD(CURDATE(), INTERVAL 15 HOUR), DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 15 HOUR), INTERVAL 45 MINUTE), 'in_progress', 60.00, 0, NULL, NULL, NULL, REPEAT('g', 64), '456789', REPEAT('h', 64), NOW(), NOW()),
(5, 1, 5, 2, 5, 1, DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 DAY), INTERVAL 10 HOUR), DATE_ADD(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 DAY), INTERVAL 10 HOUR), INTERVAL 60 MINUTE), 'completed', 85.00, 0, NULL, NULL, NULL, REPEAT('i', 64), '567890', REPEAT('j', 64), NOW(), NOW()),
(6, 1, 6, 3, 1, 1, DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 5 DAY), INTERVAL 9 HOUR), DATE_ADD(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 5 DAY), INTERVAL 9 HOUR), INTERVAL 30 MINUTE), 'completed', 45.00, 0, NULL, NULL, NULL, REPEAT('k', 64), '678901', REPEAT('l', 64), NOW(), NOW()),
(7, 1, 7, 1, 3, 1, DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 7 DAY), INTERVAL 16 HOUR), DATE_ADD(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 7 DAY), INTERVAL 16 HOUR), INTERVAL 30 MINUTE), 'cancelled', 35.00, 0, NULL, 'Cliente desistiu', NULL, REPEAT('m', 64), '789012', REPEAT('n', 64), NOW(), NOW()),
(8, 1, 8, 2, 1, 1, DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 8 DAY), INTERVAL 11 HOUR), DATE_ADD(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 8 DAY), INTERVAL 11 HOUR), INTERVAL 30 MINUTE), 'no_show', 45.00, 0, NULL, NULL, NULL, REPEAT('o', 64), '890123', REPEAT('p', 64), NOW(), NOW());

INSERT INTO appointments (id, tenant_id, client_id, barber_id, service_id, booked_by_user_id, start_datetime, end_datetime, status, price, discount, notes, cancellation_reason, reminder_sent_at, public_token, confirmation_code, review_token, created_at, updated_at)
SELECT 8 + n.id, 1, ((n.id - 1) MOD 8) + 1, ((n.id - 1) MOD 3) + 1, ((n.id - 1) MOD 5) + 1, 1,
       DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 15 DAY), INTERVAL n.id HOUR),
       DATE_ADD(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 15 DAY), INTERVAL n.id HOUR), INTERVAL 45 MINUTE),
       'completed', 50.00, 0, NULL, NULL, NULL,
       SHA2(CONCAT('tok', n.id), 256),
       LPAD(100000 + n.id, 6, '0'),
       SHA2(CONCAT('rev', n.id), 256),
       NOW(), NOW()
FROM (SELECT a.N + b.N * 10 + 1 AS id FROM
      (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
      CROSS JOIN (SELECT 0 AS N UNION SELECT 1) b
     ) n
WHERE n.id BETWEEN 1 AND 12;

INSERT INTO payments (appointment_id, tenant_id, amount, method, status, paid_at, notes, created_at)
SELECT id, 1, price, 'pix', 'paid', end_datetime, NULL, NOW()
FROM appointments
WHERE status = 'completed' AND id <= 20;

INSERT INTO reviews (appointment_id, tenant_id, client_id, rating, comment, is_public, created_at)
SELECT id, 1, client_id, 5, 'Excelente atendimento!', 1, NOW()
FROM appointments
WHERE status = 'completed' AND id IN (5, 6, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);

ALTER TABLE tenants AUTO_INCREMENT = 2;
ALTER TABLE users AUTO_INCREMENT = 10;
ALTER TABLE barbers AUTO_INCREMENT = 4;
ALTER TABLE services AUTO_INCREMENT = 6;
ALTER TABLE clients AUTO_INCREMENT = 9;
ALTER TABLE appointments AUTO_INCREMENT = 21;
ALTER TABLE payments AUTO_INCREMENT = 100;
ALTER TABLE reviews AUTO_INCREMENT = 100;

SET FOREIGN_KEY_CHECKS = 1;
