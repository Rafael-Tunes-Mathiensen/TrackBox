<?php
require_once 'config/discogs_config.php';

echo "<h2>Teste da API Discogs</h2>\n";

try {
    $discogs = new DiscogsAPI();
    
    echo "<h3>1. Testando conexão...</h3>\n";
    $discogs->testConnection();
    echo "✅ Conexão OK!<br>\n";
    
    echo "<h3>2. Buscando 'Pink Floyd Dark Side'...</h3>\n";
    $results = $discogs->searchReleases('Pink Floyd Dark Side', '', 5, 1);
    
    if (isset($results['results']) && count($results['results']) > 0) {
        echo "✅ Busca OK! Encontrados " . count($results['results']) . " resultados<br>\n";
        
        echo "<h3>3. Primeiro resultado:</h3>\n";
        $firstResult = $results['results'][0];
        $formatted = $discogs->formatReleaseData($firstResult);
        
        echo "<strong>Artista:</strong> " . htmlspecialchars($formatted['artist']) . "<br>\n";
        echo "<strong>Álbum:</strong> " . htmlspecialchars($formatted['album_name']) . "<br>\n";
        echo "<strong>Ano:</strong> " . ($formatted['year'] ?? 'N/A') . "<br>\n";
        echo "<strong>Tipo:</strong> " . $formatted['type'] . "<br>\n";
        echo "<strong>Gravadora:</strong> " . htmlspecialchars($formatted['label']) . "<br>\n";
        echo "<strong>País:</strong> " . htmlspecialchars($formatted['country_name']) . "<br>\n";
        echo "<strong>Importado:</strong> " . ($formatted['is_imported'] ? 'Sim' : 'Não') . "<br>\n";
        
        if ($formatted['image_url']) {
            echo "<strong>Imagem:</strong> <img src='" . htmlspecialchars($formatted['image_url']) . "' width='100'><br>\n";
        }
        
        echo "<h3>4. Dados brutos (para debug):</h3>\n";
        echo "<pre>" . htmlspecialchars(json_encode($firstResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>\n";
        
    } else {
        echo "❌ Nenhum resultado encontrado<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . htmlspecialchars($e->getMessage()) . "<br>\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")<br>\n";
}
?>