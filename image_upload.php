<?php
// image_upload.php
require_once 'includes/functions.php';
require_once 'includes/image_functions.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$disk_id = (int)($_POST['disk_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$disk_id) {
    echo json_encode(['success' => false, 'message' => 'ID do disco não fornecido']);
    exit;
}

try {
    switch ($action) {
        case 'upload':
            handleFileUpload($disk_id);
            break;
            
        case 'search_api':
            handleApiSearch($disk_id);
            break;
            
        case 'save_url':
            handleUrlSave($disk_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
} catch (Exception $e) {
    error_log("Erro no upload de imagem: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}

function handleFileUpload($disk_id) {
    if (!isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado']);
        return;
    }
    
    $file = $_FILES['image'];
    $errors = ImageHandler::validateUpload($file);
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        return;
    }
    
    // Gerar nomes únicos
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_name = 'disk_' . $disk_id . '_' . uniqid() . '.' . $file_extension;
    $upload_path = ImageConfig::UPLOAD_DIR . $unique_name;
    $thumbnail_name = 'thumb_' . $unique_name;
    $thumbnail_path = ImageConfig::THUMBNAIL_DIR . $thumbnail_name;
    
    // Mover arquivo temporário
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo']);
        return;
    }
    
    // Processar imagem (redimensionar e comprimir)
    $processed_path = ImageConfig::UPLOAD_DIR . 'processed_' . $unique_name;
    if (!ImageHandler::processImage($upload_path, $processed_path, 800, 85)) {
        unlink($upload_path);
        echo json_encode(['success' => false, 'message' => 'Erro ao processar imagem']);
        return;
    }
    
    // Criar thumbnail
    if (!ImageHandler::createThumbnail($processed_path, $thumbnail_path, 300)) {
        unlink($upload_path);
        unlink($processed_path);
        echo json_encode(['success' => false, 'message' => 'Erro ao criar thumbnail']);
        return;
    }
    
    // Upload para Cloudinary (opcional)
    $cloudinary_url = ImageHandler::uploadToCloudinary($processed_path, 'disk_' . $disk_id . '_' . time());
    
    // Usar Cloudinary se disponível, senão usar local
    $final_url = $cloudinary_url ?: ('/' . $processed_path);
    $thumbnail_url = $cloudinary_url ? 
        str_replace('/upload/', '/upload/c_fill,w_300,h_300/', $cloudinary_url) : 
        ('/' . $thumbnail_path);
    
    // Salvar no banco
    $image_data = [
        'url' => $final_url,
        'source' => 'upload',
        'thumbnail' => $thumbnail_url
    ];
    
    if (ImageHandler::saveDiskImage($disk_id, $image_data)) {
        // Limpar arquivos temporários se usando Cloudinary
        if ($cloudinary_url) {
            unlink($upload_path);
            unlink($processed_path);
            unlink($thumbnail_path);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Imagem enviada com sucesso!',
            'image_url' => $final_url,
            'thumbnail_url' => $thumbnail_url
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco de dados']);
    }
}

function handleApiSearch($disk_id) {
    $artist = $_POST['artist'] ?? '';
    $album = $_POST['album'] ?? '';
    
    if (empty($artist) || empty($album)) {
        echo json_encode(['success' => false, 'message' => 'Artista e álbum são obrigatórios']);
        return;
    }
    
    $cover_url = ImageHandler::searchCoverImage($artist, $album);
    
    if (!$cover_url) {
        echo json_encode(['success' => false, 'message' => 'Nenhuma capa encontrada']);
        return;
    }
    
    // Salvar no banco
    $image_data = [
        'url' => $cover_url,
        'source' => 'api',
        'thumbnail' => $cover_url // APIs já retornam imagens otimizadas
    ];
    
    if (ImageHandler::saveDiskImage($disk_id, $image_data)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Capa encontrada e salva!',
            'image_url' => $cover_url,
            'thumbnail_url' => $cover_url
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco de dados']);
    }
}

function handleUrlSave($disk_id) {
    $image_url = $_POST['image_url'] ?? '';
    
    if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'URL inválida']);
        return;
    }
    
    // Verificar se é uma imagem
    $headers = @get_headers($image_url, 1);
    $content_type = $headers['Content-Type'] ?? '';
    
    if (!str_contains($content_type, 'image/')) {
        echo json_encode(['success' => false, 'message' => 'URL não é uma imagem válida']);
        return;
    }
    
    // Salvar no banco
    $image_data = [
        'url' => $image_url,
        'source' => 'url',
        'thumbnail' => $image_url
    ];
    
    if (ImageHandler::saveDiskImage($disk_id, $image_data)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Imagem salva com sucesso!',
            'image_url' => $image_url,
            'thumbnail_url' => $image_url
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco de dados']);
    }
}
?>