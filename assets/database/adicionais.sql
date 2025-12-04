-- Adicionar tabelas para logs e backup (opcionais)
use trackbox;
-- Tabela para log de atividades
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela para backup de discos excluídos
CREATE TABLE IF NOT EXISTS deleted_disks_backup (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    disk_data JSON NOT NULL,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Adicionar campos de timestamp se não existirem
ALTER TABLE disks 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Adicionar campos de imagem na tabela disks
ALTER TABLE disks 
ADD COLUMN cover_image_url VARCHAR(500) DEFAULT NULL,
ADD COLUMN cover_image_source ENUM('upload', 'camera', 'api', 'url') DEFAULT NULL,
ADD COLUMN cover_image_thumbnail VARCHAR(500) DEFAULT NULL;

-- Tabela para armazenar múltiplas imagens do disco (opcional)
CREATE TABLE IF NOT EXISTS disk_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    disk_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_type ENUM('cover', 'back', 'disc', 'booklet', 'other') DEFAULT 'cover',
    image_source ENUM('upload', 'camera', 'api', 'url') NOT NULL,
    thumbnail_url VARCHAR(500),
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (disk_id) REFERENCES disks(id) ON DELETE CASCADE
);