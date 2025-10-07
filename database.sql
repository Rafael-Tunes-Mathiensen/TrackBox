-- database.sql
-- Script para criar o banco de dados TrackBox

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS trackbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE trackbox;

-- Tabela de Discos
CREATE TABLE IF NOT EXISTS discos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_midia ENUM('CD', 'LP', 'BoxSet') NOT NULL,
    artista VARCHAR(255) NOT NULL,
    nome_disco VARCHAR(255) NOT NULL,
    ano INT,
    gravadora VARCHAR(255),
    origem ENUM('nacional', 'importado') NOT NULL,
    pais VARCHAR(100),
    continente VARCHAR(50),
    edicao VARCHAR(100),
    condicao ENUM('G', 'G+', 'VG', 'VG+', 'E', 'E+', 'Mint') NOT NULL,
    lacrado BOOLEAN DEFAULT FALSE,