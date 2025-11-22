-- ============================================
-- CREACIÓN DE BASE DE DATOS
-- ============================================
CREATE DATABASE soporte_tickets;
USE soporte_tickets;

-- ============================================
-- TABLA: users
-- Roles posibles: 'gestor', 'admin'
-- ============================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('gestor', 'admin') NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

-- ============================================
-- TABLA: auth_tokens (token de sesión)
-- Se elimina cuando el usuario cierra sesión
-- ============================================
CREATE TABLE auth_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABLA: tickets
-- Estados: 'abierto', 'en_progreso', 'resuelto', 'cerrado'
-- ============================================
CREATE TABLE tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('abierto', 'en_progreso', 'resuelto', 'cerrado') DEFAULT 'abierto',
    gestor_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (gestor_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- ============================================
-- TABLA: ticket_actividad
-- Registro cronológico de eventos o comentarios
-- ============================================
CREATE TABLE ticket_actividad (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    mensaje TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================
-- INSERTS DE PRUEBA
-- ============================================

-- Usuarios
INSERT INTO users (name, email, password, role, created_at)
VALUES
('Juan Gestor', 'gestor1@example.com', 'password123', 'gestor', NOW()),
('Ana Gestor', 'gestor2@example.com', 'password123', 'gestor', NOW()),
('Carlos Admin', 'admin1@example.com', 'password123', 'admin', NOW());

-- Tokens (simulados)
INSERT INTO auth_tokens (user_id, token, created_at)
VALUES
(1, 'token_gestor_1_abc123', NOW()),
(3, 'token_admin_1_xyz789', NOW());

-- Tickets
INSERT INTO tickets (titulo, descripcion, estado, gestor_id, admin_id, created_at)
VALUES
('Error al iniciar sesión', 'El usuario no puede iniciar sesión en la app.', 'abierto', 1, 3, NOW()),
('Problema con carga de archivos', 'Los archivos no suben correctamente.', 'en_progreso', 2, 3, NOW());

-- Actividades de tickets
INSERT INTO ticket_actividad (ticket_id, user_id, mensaje, created_at)
VALUES
(1, 1, 'Se reporta el problema y se abre el ticket.', NOW()),
(1, 3, 'Admin toma el ticket para revisión.', NOW()),
(2, 2, 'Reporte inicial del problema con archivos.', NOW()),
(2, 3, 'Admin revisa el módulo de carga.', NOW());