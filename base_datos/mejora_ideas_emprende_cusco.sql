-- =============================================================
-- EMPRENDE CUSCO - ESTRUCTURA DEL MÓDULO DE EMPRENDIMIENTO
-- Compatible con MySQL 8.x / phpMyAdmin.
-- No elimina registros existentes.
-- =============================================================
CREATE DATABASE IF NOT EXISTS emprende_cusco_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE emprende_cusco_db;

CREATE TABLE IF NOT EXISTS ideas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(180) NOT NULL,
  categoria VARCHAR(100) NOT NULL,
  descripcion TEXT NOT NULL,
  inversion VARCHAR(100) NULL,
  ubicacion VARCHAR(180) DEFAULT 'Cusco',
  img VARCHAR(500) NULL,
  flyer VARCHAR(500) NULL,
  youtube_url VARCHAR(500) NULL,
  nivel VARCHAR(50) DEFAULT 'Medio',
  modelo_negocio VARCHAR(255) NULL,
  cliente_objetivo VARCHAR(255) NULL,
  productos_servicios TEXT NULL,
  canales_venta TEXT NULL,
  potencial_crecimiento VARCHAR(100) NULL,
  riesgos TEXT NULL,
  personal_necesario VARCHAR(100) NULL,
  tiempo_inicio VARCHAR(100) NULL,
  beneficios TEXT NULL,
  pasos_inicio TEXT NULL,
  palabras_clave TEXT NULL,
  ventajas TEXT NULL,
  competencia TEXT NULL,
  mercado_objetivo VARCHAR(255) NULL,
  rentabilidad VARCHAR(100) NULL,
  presupuesto_min DECIMAL(12,2) NULL,
  presupuesto_max DECIMAL(12,2) NULL,
  provincia VARCHAR(100) DEFAULT 'Cusco',
  modalidad VARCHAR(100) NULL,
  estado VARCHAR(50) NOT NULL DEFAULT 'Publicado',
  vistas INT UNSIGNED NOT NULL DEFAULT 0,
  creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estas sentencias agregan columnas solo cuando no existen.
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS flyer VARCHAR(500) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS youtube_url VARCHAR(500) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS nivel VARCHAR(50) DEFAULT 'Medio';
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS modelo_negocio VARCHAR(255) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS cliente_objetivo VARCHAR(255) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS productos_servicios TEXT NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS canales_venta TEXT NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS potencial_crecimiento VARCHAR(100) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS riesgos TEXT NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS personal_necesario VARCHAR(100) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS tiempo_inicio VARCHAR(100) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS beneficios TEXT NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS pasos_inicio TEXT NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS palabras_clave TEXT NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS ventajas TEXT NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS competencia TEXT NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS mercado_objetivo VARCHAR(255) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS rentabilidad VARCHAR(100) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS presupuesto_min DECIMAL(12,2) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS presupuesto_max DECIMAL(12,2) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS provincia VARCHAR(100) DEFAULT 'Cusco';
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS modalidad VARCHAR(100) NULL;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS estado VARCHAR(50) NOT NULL DEFAULT 'Publicado';
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS vistas INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

-- Tabla utilizada por publicar-idea.php para propuestas enviadas por usuarios.
CREATE TABLE IF NOT EXISTS ideas_negocio (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(180) NOT NULL,
  categoria VARCHAR(100) NOT NULL,
  descripcion TEXT NOT NULL,
  problema TEXT NULL,
  solucion TEXT NULL,
  clientes TEXT NULL,
  inversion VARCHAR(100) NULL,
  ubicacion VARCHAR(180) NULL,
  venta TEXT NULL,
  contacto VARCHAR(30) NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
  fecha_publicacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_ideas_negocio_estado (estado),
  INDEX idx_ideas_negocio_categoria (categoria),
  INDEX idx_ideas_negocio_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Base del módulo de ideas preparada correctamente.' AS resultado;
