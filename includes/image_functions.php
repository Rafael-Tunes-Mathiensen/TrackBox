<?php
// includes/image_functions.php
require_once 'config/image_config.php';

class ImageHandler {
    
    // Buscar capa na API do Last.fm
    public static function searchLastFmCover($artist, $album) {
        $api_key = ImageConfig::LASTFM_API_KEY;
        $artist = urlencode($artist);
        $album = urlencode($album);
        
        $url = "http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key={$api_key}&artist={$artist}&album={$album}&format=json";
        
        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['album']['image'])) {
                foreach ($data['album']['image'] as $image) {
                    if ($image['size'] === 'extralarge' && !empty($image['#text'])) {
                        return $image['#text'];
                    }
                }
            }
        }
        return null;
    }
    
    // Buscar capa na API do Discogs
    public static function searchDiscogsCover($artist, $album) {
        $token = ImageConfig::DISCOGS_TOKEN;
        $query = urlencode($artist . ' ' . $album);
        
        $url = "https://api.discogs.com/database/search?q={$query}&type=release&token={$token}";
        
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: TrackBox/1.0\r\n"
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['results'][0]['cover_image'])) {
                return $data['results'][0]['cover_image'];
            }
        }
        return null;
    }
    
    // Buscar capa em múltiplas APIs
    public static function searchCoverImage($artist, $album) {
        // Tentar Last.fm primeiro
        $cover = self::searchLastFmCover($artist, $album);
        if ($cover) return $cover;
        
        // Tentar Discogs
        $cover = self::searchDiscogsCover($artist, $album);
        if ($cover) return $cover;
        
        return null;
    }
    
    // Validar arquivo de upload
    public static function validateUpload($file) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erro no upload do arquivo';
            return $errors;
        }
        
        if ($file['size'] > ImageConfig::MAX_FILE_SIZE) {
            $errors[] = 'Arquivo muito grande. Máximo: 5MB';
        }
        
        if (!in_array($file['type'], ImageConfig::ALLOWED_TYPES)) {
            $errors[] = 'Tipo de arquivo não permitido. Use: JPEG, PNG ou WebP';
        }
        
        return $errors;
    }
    
    // Redimensionar e comprimir imagem
    public static function processImage($source_path, $destination_path, $max_width = 800, $quality = 85) {
        $image_info = getimagesize($source_path);
        if (!$image_info) return false;
        
        $width = $image_info[0];
        $height = $image_info[1];
        $type = $image_info[2];
        
        // Calcular novas dimensões
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = ($height * $max_width) / $width;
        } else {
            $new_width = $width;
            $new_height = $height;
        }
        
        // Criar imagem source
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($source_path);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($source_path);
                break;
            default:
                return false;
        }
        
        // Criar nova imagem
        $destination = imagecreatetruecolor($new_width, $new_height);
        
        // Preservar transparência para PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Salvar
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($destination, $destination_path, $quality);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($destination, $destination_path, 9);
                break;
            case IMAGETYPE_WEBP:
                $result = imagewebp($destination, $destination_path, $quality);
                break;
        }
        
        // Limpar memória
        imagedestroy($source);
        imagedestroy($destination);
        
        return $result;
    }
    
    // Criar thumbnail
    public static function createThumbnail($source_path, $thumbnail_path, $size = 300) {
        return self::processImage($source_path, $thumbnail_path, $size, 80);
    }
    
    // Upload para Cloudinary
    public static function uploadToCloudinary($file_path, $public_id = null) {
        $url = ImageConfig::getCloudinaryUrl();
        $api_key = ImageConfig::CLOUDINARY_API_KEY;
        $api_secret = ImageConfig::CLOUDINARY_API_SECRET;
        $upload_preset = ImageConfig::CLOUDINARY_UPLOAD_PRESET;
        
        $timestamp = time();
        $public_id = $public_id ?: 'trackbox_' . uniqid();
        
        // Criar assinatura
        $params = [
            'public_id' => $public_id,
            'timestamp' => $timestamp,
            'upload_preset' => $upload_preset
        ];
        
        ksort($params);
        $signature_string = '';
        foreach ($params as $key => $value) {
            $signature_string .= $key . '=' . $value . '&';
        }
        $signature_string = rtrim($signature_string, '&') . $api_secret;
        $signature = sha1($signature_string);
        
        // Preparar dados
        $post_data = [
            'file' => new CURLFile($file_path),
            'public_id' => $public_id,
            'timestamp' => $timestamp,
            'api_key' => $api_key,
            'signature' => $signature,
            'upload_preset' => $upload_preset
        ];
        
        // Fazer upload
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            return $result['secure_url'] ?? null;
        }
        
        return null;
    }
    
    // Salvar imagem do disco
    public static function saveDiskImage($disk_id, $image_data) {
        require_once 'config/database.php';
        $database = new Database();
        $pdo = $database->getConnection();
        
        try {
            $stmt = $pdo->prepare("
                UPDATE disks SET 
                    cover_image_url = ?, 
                    cover_image_source = ?,
                    cover_image_thumbnail = ?,
                    updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            return $stmt->execute([
                $image_data['url'],
                $image_data['source'],
                $image_data['thumbnail'] ?? null,
                $disk_id,
                $_SESSION['user_id']
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao salvar imagem: " . $e->getMessage());
            return false;
        }
    }
}
?>