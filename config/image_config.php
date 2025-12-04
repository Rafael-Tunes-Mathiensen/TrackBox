<?php
// config/image_config.php

class ImageConfig {
    // Cloudinary (CDN gratuito) - Cadastre-se em cloudinary.com
    const CLOUDINARY_CLOUD_NAME = 'seu_cloud_name'; // Substitua pelo seu
    const CLOUDINARY_API_KEY = 'sua_api_key';       // Substitua pela sua
    const CLOUDINARY_API_SECRET = 'seu_api_secret'; // Substitua pelo seu
    const CLOUDINARY_UPLOAD_PRESET = 'trackbox_preset'; // Criar no painel
    
    // APIs para busca de capas (todas gratuitas)
    const LASTFM_API_KEY = 'sua_lastfm_key'; // Last.fm API (gratuita)
    const DISCOGS_TOKEN = 'seu_discogs_token'; // Discogs API (gratuita)
    
    // Configurações de upload
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    const THUMBNAIL_SIZE = 300; // pixels
    const QUALITY = 85; // qualidade JPEG
    
    // Diretórios locais (backup)
    const UPLOAD_DIR = 'uploads/disks/';
    const THUMBNAIL_DIR = 'uploads/thumbnails/';
    
    public static function getCloudinaryUrl() {
        return "https://api.cloudinary.com/v1_1/" . self::CLOUDINARY_CLOUD_NAME . "/image/upload";
    }
    
    public static function getCloudinaryWidget() {
        return "https://widget.cloudinary.com/v2.0/global/all.js";
    }
}

// Criar diretórios se não existirem
if (!file_exists(ImageConfig::UPLOAD_DIR)) {
    mkdir(ImageConfig::UPLOAD_DIR, 0755, true);
}
if (!file_exists(ImageConfig::THUMBNAIL_DIR)) {
    mkdir(ImageConfig::THUMBNAIL_DIR, 0755, true);
}
?>