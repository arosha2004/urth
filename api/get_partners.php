<?php
/**
 * URTH Architecture Studio - Public API Endpoint: Active Partner Logos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';

try {
    $sql = "SELECT id, name, logo_url, website_url, display_order FROM partners WHERE is_active = 1 ORDER BY display_order ASC, id ASC";
    $result = $conn->query($sql);
    
    $partners = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $partners[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'logo_url' => $row['logo_url'],
                'website_url' => $row['website_url'] ?? '',
                'display_order' => (int)$row['display_order']
            ];
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'count' => count($partners),
        'partners' => $partners
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch partner logos: ' . $e->getMessage()
    ]);
}
