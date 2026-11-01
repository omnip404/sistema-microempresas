-- =====================================================================
-- Sistema Web de Control y Gestión de Microempresas con Panel Estadístico
-- Script de creación de la base de datos (MySQL 8) · Fase II, sección 2.3.4.1
-- Ingeniería de Software · UPEA · paralelo 7toC
-- En el sistema, estas mismas tablas se crean con: php artisan migrate --seed
-- =====================================================================

CREATE DATABASE IF NOT EXISTS microempresa_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE microempresa_db;

CREATE TABLE roles (
  id           TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(30)  NOT NULL UNIQUE,
  descripcion  VARCHAR(150) NULL,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE usuarios (
  id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol_id             TINYINT UNSIGNED NOT NULL,
  nombre             VARCHAR(100) NOT NULL,
  email              VARCHAR(120) NOT NULL UNIQUE,
  password           VARCHAR(255) NOT NULL,
  activo             TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta    DATETIME NULL,
  remember_token     VARCHAR(100) NULL,
  created_at         TIMESTAMP NULL,
  updated_at         TIMESTAMP NULL,
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE categorias (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(60)  NOT NULL UNIQUE,
  descripcion  VARCHAR(150) NULL,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE productos (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id   BIGINT UNSIGNED NOT NULL,
  codigo         VARCHAR(30)  NOT NULL UNIQUE,
  nombre         VARCHAR(120) NOT NULL,
  unidad_medida  VARCHAR(20)  NOT NULL DEFAULT 'unidad',
  precio_compra  DECIMAL(10,2) NOT NULL DEFAULT 0,
  precio_venta   DECIMAL(10,2) NOT NULL,
  stock          INT UNSIGNED NOT NULL DEFAULT 0,
  stock_minimo   INT UNSIGNED NOT NULL DEFAULT 5,
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
  CONSTRAINT chk_productos_precios CHECK (precio_compra >= 0 AND precio_venta >= 0),
  INDEX idx_productos_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE proveedores (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(120) NOT NULL,
  nit         VARCHAR(20)  NULL UNIQUE,
  telefono    VARCHAR(20)  NULL,
  direccion   VARCHAR(150) NULL,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE clientes (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(120) NOT NULL,
  ci_nit      VARCHAR(20)  NULL,
  telefono    VARCHAR(20)  NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  INDEX idx_clientes_ci_nit (ci_nit)
) ENGINE=InnoDB;

CREATE TABLE ventas (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id        BIGINT UNSIGNED NOT NULL,
  cliente_id        BIGINT UNSIGNED NULL,
  fecha             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total             DECIMAL(12,2) NOT NULL DEFAULT 0,
  estado            ENUM('REGISTRADA','ANULADA') NOT NULL DEFAULT 'REGISTRADA',
  motivo_anulacion  VARCHAR(200) NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  CONSTRAINT fk_ventas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  CONSTRAINT fk_ventas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
  INDEX idx_ventas_fecha (fecha)
) ENGINE=InnoDB;

CREATE TABLE detalle_ventas (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  venta_id         BIGINT UNSIGNED NOT NULL,
  producto_id      BIGINT UNSIGNED NOT NULL,
  cantidad         INT UNSIGNED NOT NULL,
  precio_unitario  DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_dv_venta    FOREIGN KEY (venta_id)    REFERENCES ventas(id) ON DELETE CASCADE,
  CONSTRAINT fk_dv_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
  CONSTRAINT chk_dv_cantidad CHECK (cantidad > 0),
  UNIQUE KEY uq_dv_venta_producto (venta_id, producto_id)
) ENGINE=InnoDB;

CREATE TABLE compras (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id       BIGINT UNSIGNED NOT NULL,
  proveedor_id     BIGINT UNSIGNED NOT NULL,
  fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nro_comprobante  VARCHAR(30) NULL,
  total            DECIMAL(12,2) NOT NULL DEFAULT 0,
  estado           ENUM('REGISTRADA','ANULADA') NOT NULL DEFAULT 'REGISTRADA',
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  CONSTRAINT fk_compras_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id),
  CONSTRAINT fk_compras_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
  INDEX idx_compras_fecha (fecha)
) ENGINE=InnoDB;

CREATE TABLE detalle_compras (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  compra_id       BIGINT UNSIGNED NOT NULL,
  producto_id     BIGINT UNSIGNED NOT NULL,
  cantidad        INT UNSIGNED NOT NULL,
  costo_unitario  DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_dc_compra   FOREIGN KEY (compra_id)   REFERENCES compras(id) ON DELETE CASCADE,
  CONSTRAINT fk_dc_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
  CONSTRAINT chk_dc_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB;

CREATE TABLE movimientos_inventario (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id       BIGINT UNSIGNED NOT NULL,
  usuario_id        BIGINT UNSIGNED NOT NULL,
  tipo              ENUM('ENTRADA','SALIDA','AJUSTE') NOT NULL,
  cantidad          INT NOT NULL,
  stock_resultante  INT UNSIGNED NOT NULL,
  referencia        VARCHAR(50)  NULL,
  observacion       VARCHAR(200) NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mov_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
  CONSTRAINT fk_mov_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id),
  INDEX idx_mov_producto_fecha (producto_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE bitacora (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id   BIGINT UNSIGNED NULL,
  accion       VARCHAR(30) NOT NULL,
  tabla        VARCHAR(40) NOT NULL,
  registro_id  BIGINT UNSIGNED NULL,
  detalle      JSON NULL,
  ip           VARCHAR(45) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bitacora_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_bitacora_fecha (created_at)
) ENGINE=InnoDB;

-- Vista de apoyo para las alertas de stock bajo (RF08)
CREATE VIEW v_productos_stock_bajo AS
  SELECT p.id, p.codigo, p.nombre, c.nombre AS categoria, p.stock, p.stock_minimo
  FROM productos p JOIN categorias c ON c.id = p.categoria_id
  WHERE p.activo = 1 AND p.stock <= p.stock_minimo;

-- Datos iniciales
INSERT INTO roles (nombre, descripcion, created_at) VALUES
  ('Administrador', 'Acceso total: catálogo, compras, reportes, usuarios y bitácora', NOW()),
  ('Vendedor', 'Registra ventas y consulta productos y stock', NOW());
