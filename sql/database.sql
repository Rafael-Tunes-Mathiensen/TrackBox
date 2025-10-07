-- sql/database.sql
CREATE DATABASE IF NOT EXISTS trackbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trackbox;

-- Tabela de usuários
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de países
CREATE TABLE countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(100) NOT NULL,
    continent VARCHAR(50) NOT NULL,
    is_fake_prone BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de discos
CREATE TABLE disks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('CD', 'LP', 'BoxSet') NOT NULL,
    artist VARCHAR(200) NOT NULL,
    album_name VARCHAR(200) NOT NULL,
    year INT,
    label VARCHAR(100),
    country_id INT,
    is_imported BOOLEAN DEFAULT FALSE,
    edition ENUM('primeira_edicao', 'reedicao') DEFAULT 'primeira_edicao',
    condition_disk ENUM('G', 'G+', 'VG', 'VG+', 'E', 'E+', 'Mint') NOT NULL,
    condition_cover ENUM('G', 'G+', 'VG', 'VG+', 'E', 'E+', 'Mint') NOT NULL,
    is_sealed BOOLEAN DEFAULT FALSE,
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (country_id) REFERENCES countries(id)
);

-- Tabela de itens extras dos discos
CREATE TABLE disk_extras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    disk_id INT NOT NULL,
    has_booklet BOOLEAN DEFAULT FALSE,
    has_poster BOOLEAN DEFAULT FALSE,
    has_photos BOOLEAN DEFAULT FALSE,
    has_extra_disk BOOLEAN DEFAULT FALSE,
    has_lyrics BOOLEAN DEFAULT FALSE,
    other_extras TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (disk_id) REFERENCES disks(id) ON DELETE CASCADE
);

-- Tabela específica para BoxSets
CREATE TABLE boxset_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    disk_id INT NOT NULL,
    is_limited_edition BOOLEAN DEFAULT FALSE,
    edition_number INT,
    total_editions INT,
    special_items TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (disk_id) REFERENCES disks(id) ON DELETE CASCADE
);

-- Inserir países iniciais
INSERT INTO countries (country_name, continent, is_fake_prone) VALUES
('Brasil', 'América do Sul', FALSE),
('Estados Unidos', 'América do Norte', FALSE),
('Reino Unido', 'Europa', FALSE),
('Alemanha', 'Europa', FALSE),
('França', 'Europa', FALSE),
('Japão', 'Ásia', FALSE),
('Coreia do Sul', 'Ásia', FALSE),
('Canadá', 'América do Norte', FALSE),
('Austrália', 'Oceania', FALSE),
('Holanda', 'Europa', FALSE),
('Itália', 'Europa', FALSE),
('Espanha', 'Europa', FALSE),
('Suécia', 'Europa', FALSE),
('Noruega', 'Europa', FALSE),
('Rússia', 'Europa/Ásia', TRUE),
('Argentina', 'América do Sul', TRUE),
('China', 'Ásia', TRUE),
('México', 'América do Norte', FALSE),
('Chile', 'América do Sul', FALSE),
('Uruguai', 'América do Sul', FALSE);