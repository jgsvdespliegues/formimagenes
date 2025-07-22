-- =============================================
-- SCRIPT COMPLETO DE BASE DE DATOS
-- Sistema de Publicaciones con Cloudinary
-- =============================================

-- Eliminar base de datos si existe (para empezar desde cero)
DROP DATABASE IF EXISTS publicaciones_db;

-- Crear base de datos
CREATE DATABASE publicaciones_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE publicaciones_db;

-- Crear tabla de publicaciones
CREATE TABLE publicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL COMMENT 'Título de la publicación',
    comentario TEXT NOT NULL COMMENT 'Comentario o descripción',
    imagen1_url TEXT NULL COMMENT 'URL de la primera imagen en Cloudinary',
    imagen2_url TEXT NULL COMMENT 'URL de la segunda imagen en Cloudinary',
    imagen3_url TEXT NULL COMMENT 'URL de la tercera imagen en Cloudinary',
    video_url VARCHAR(500) NULL COMMENT 'URL completa del video de YouTube',
    mapa_iframe TEXT NULL COMMENT 'Código iframe completo de Google Maps',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación automática',
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última modificación',
    
    INDEX idx_fecha_creacion (fecha_creacion),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla principal para almacenar las publicaciones del sistema';

-- Crear usuario específico para la aplicación (opcional pero recomendado)
-- Descomenta las siguientes líneas si quieres crear un usuario específico
/*
CREATE USER 'publicaciones_user'@'localhost' IDENTIFIED BY 'tu_password_seguro';
GRANT SELECT, INSERT, UPDATE, DELETE ON publicaciones_db.* TO 'publicaciones_user'@'localhost';
FLUSH PRIVILEGES;
*/

-- Insertar datos de ejemplo (opcional)
INSERT INTO publicaciones (nombre, comentario, video_url) VALUES 
('Publicación de Ejemplo', 'Este es un comentario de ejemplo para probar el sistema.', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');

-- Mostrar estructura de la tabla
DESCRIBE publicaciones;

-- Mostrar las publicaciones (para verificar)
SELECT * FROM publicaciones ORDER BY fecha_creacion DESC;