CREATE DATABASE IF NOT EXISTS gestor_tareas
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE gestor_tareas;

CREATE TABLE IF NOT EXISTS tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente', 'completada')
        NOT NULL DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
