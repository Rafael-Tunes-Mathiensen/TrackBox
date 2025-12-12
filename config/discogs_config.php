<?php
// config/discogs_config.php

// Configurações da API do Discogs
define('DISCOGS_API_BASE_URL', 'https://api.discogs.com');
define('DISCOGS_USER_AGENT', 'TrackBox/1.0 +http://localhost'); // Substitua pela URL do seu site
define('DISCOGS_CONSUMER_KEY', 'HoEBjdWhTecTjROZHrtj');
define('DISCOGS_CONSUMER_SECRET', 'YTWceSpyUPdJOvtivlwkJAnSgMDkxZPi');

// Rate limiting
define('DISCOGS_RATE_LIMIT', 60);
define('DISCOGS_RATE_WINDOW', 60); // segundos

class DiscogsAPI {
    private $baseUrl;
    private $userAgent;
    private $consumerKey;
    private $consumerSecret;
    
    public function __construct() {
        $this->baseUrl = DISCOGS_API_BASE_URL;
        $this->userAgent = DISCOGS_USER_AGENT;
        $this->consumerKey = DISCOGS_CONSUMER_KEY;
        $this->consumerSecret = DISCOGS_CONSUMER_SECRET;
    }
    
    /**
     * Buscar releases por termo de pesquisa
     */
    public function searchReleases($query, $type = '', $per_page = 20, $page = 1) {
        $params = [
            'q' => $query,
            'type' => 'release',
            'per_page' => min($per_page, 100),
            'page' => $page
        ];
        
        if (!empty($type)) {
            // Mapear tipos do TrackBox para formatos do Discogs
            $formatMap = [
                'CD' => 'CD',
                'LP' => 'Vinyl',
                'BoxSet' => 'Box Set'
            ];
            if (isset($formatMap[$type])) {
                $params['format'] = $formatMap[$type];
            }
        }
        
        return $this->makeRequest('/database/search', $params);
    }
    
    /**
     * Obter detalhes de um release específico
     */
    public function getRelease($releaseId) {
        return $this->makeRequest("/releases/{$releaseId}");
    }
    
    /**
     * Fazer requisição para a API usando autenticação básica
     */
    private function makeRequest($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        
        // Adicionar parâmetros de autenticação
        $params['key'] = $this->consumerKey;
        $params['secret'] = $this->consumerSecret;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = [
            'User-Agent: ' . $this->userAgent,
            'Accept: application/vnd.discogs.v2.discogs+json',
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => $this->userAgent
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro cURL: ' . $error);
        }
        
        if ($httpCode === 401) {
            throw new Exception('Erro de autenticação. Verifique suas credenciais do Discogs.');
        }
        
        if ($httpCode === 429) {
            throw new Exception('Muitas requisições. Aguarde alguns segundos e tente novamente.');
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'HTTP ' . $httpCode;
            throw new Exception('Erro da API Discogs: ' . $errorMessage);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro ao decodificar resposta JSON: ' . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Processar dados do Discogs para formato do TrackBox
     */
    public function formatReleaseData($release) {
        // Determinar tipo de mídia
        $type = 'CD'; // padrão
        if (isset($release['formats']) && !empty($release['formats'])) {
            foreach ($release['formats'] as $format) {
                $formatName = strtolower($format['name']);
                if (strpos($formatName, 'vinyl') !== false || strpos($formatName, 'lp') !== false) {
                    $type = 'LP';
                    break;
                } elseif (strpos($formatName, 'box') !== false) {
                    $type = 'BoxSet';
                    break;
                } elseif (strpos($formatName, 'cd') !== false) {
                    $type = 'CD';
                    break;
                }
            }
        }
        
        // Extrair artistas
        $artists = [];
        if (isset($release['artists'])) {
            foreach ($release['artists'] as $artist) {
                // Remover números de artista do Discogs (ex: "Pink Floyd (2)")
                $artistName = preg_replace('/\s*\(\d+\)$/', '', $artist['name']);
                $artists[] = $artistName;
            }
        }
        $artist = implode(', ', $artists);
        
        // Extrair gravadoras
        $labels = [];
        if (isset($release['labels'])) {
            foreach ($release['labels'] as $label) {
                if (!empty($label['name']) && $label['name'] !== 'Not On Label') {
                    $labels[] = $label['name'];
                }
            }
        }
        $label = implode(', ', $labels);
        
        // Extrair ano
        $year = null;
        if (isset($release['year']) && $release['year'] > 0) {
            $year = $release['year'];
        } elseif (isset($release['released']) && !empty($release['released'])) {
            $releaseDate = $release['released'];
            if (preg_match('/(\d{4})/', $releaseDate, $matches)) {
                $year = (int) $matches[1];
            }
        }
        
        // Determinar se é importado baseado no país
        $isImported = false;
        $countryId = null;
        if (isset($release['country']) && !empty($release['country'])) {
            $country = $release['country'];
            if ($country !== 'Brazil' && $country !== 'Brasil') {
                $isImported = true;
                $countryId = $this->mapCountryToId($country);
            }
        }
        
        // Extrair imagem de melhor qualidade
        $imageUrl = '';
        if (isset($release['images']) && !empty($release['images'])) {
            // Procurar por imagem primária ou a primeira disponível
            foreach ($release['images'] as $image) {
                if ($image['type'] === 'primary') {
                    $imageUrl = $image['uri'];
                    break;
                }
            }
            // Se não encontrou primária, usar a primeira
            if (empty($imageUrl)) {
                $imageUrl = $release['images'][0]['uri'];
            }
        } elseif (isset($release['thumb']) && !empty($release['thumb'])) {
            $imageUrl = $release['thumb'];
        } elseif (isset($release['cover_image']) && !empty($release['cover_image'])) {
            $imageUrl = $release['cover_image'];
        }
        
        return [
            'type' => $type,
            'artist' => $artist,
            'album_name' => $release['title'] ?? '',
            'year' => $year,
            'label' => $label,
            'is_imported' => $isImported,
            'country_id' => $countryId,
            'country_name' => $release['country'] ?? '',
            'image_url' => $imageUrl,
            'discogs_id' => $release['id'] ?? null,
            'discogs_url' => isset($release['uri']) ? 'https://www.discogs.com' . $release['uri'] : '',
            'formats' => $release['formats'] ?? [],
            'genres' => $release['genres'] ?? [],
            'styles' => $release['styles'] ?? [],
            'catno' => $release['catno'] ?? '',
            'barcode' => isset($release['identifiers']) ? $this->extractBarcode($release['identifiers']) : ''
        ];
    }
    
    /**
     * Extrair código de barras dos identificadores
     */
    private function extractBarcode($identifiers) {
        foreach ($identifiers as $identifier) {
            if (isset($identifier['type']) && strtolower($identifier['type']) === 'barcode') {
                return $identifier['value'];
            }
        }
        return '';
    }
    
    /**
     * Mapear país do Discogs para ID do banco de dados
     */
    private function mapCountryToId($countryName) {
        $countryMap = [
            'US' => 1,           // Estados Unidos
            'Canada' => 2,       // Canadá
            'Mexico' => 3,       // México
            'Argentina' => 5,    // Argentina
            'UK' => 6,           // Reino Unido
            'Germany' => 7,      // Alemanha
            'France' => 8,       // França
            'Japan' => 9,        // Japão
            'China' => 10,       // China
            'Russia' => 11,      // Rússia
            'Australia' => 12,   // Austrália
            'South Africa' => 13, // África do Sul
            // Adicionar mais mapeamentos conforme necessário
            'Netherlands' => 7,   // Holanda -> Alemanha (Europa)
            'Italy' => 8,        // Itália -> França (Europa)
            'Spain' => 8,        // Espanha -> França (Europa)
            'Sweden' => 7,       // Suécia -> Alemanha (Europa)
            'Norway' => 7,       // Noruega -> Alemanha (Europa)
            'Denmark' => 7,      // Dinamarca -> Alemanha (Europa)
        ];
        
        return $countryMap[$countryName] ?? null;
    }
    
    /**
     * Testar conexão com a API
     */
    public function testConnection() {
        try {
            $result = $this->makeRequest('/database/search', ['q' => 'test', 'per_page' => 1]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Falha no teste de conexão: ' . $e->getMessage());
        }
    }
}
?>