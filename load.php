<?php
/**
 * 서버 불러오기 PHP (범용 *.json 허용, data/ 디렉토리만)
 * 파일명: load.php
 * 경로: /public_html/serviceops/load.php
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dataDir = __DIR__ . '/data';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('GET only');
    }

    if (!isset($_GET['filename'])) {
        throw new Exception('파일명 누락');
    }

    $filename = basename($_GET['filename']);
    if (!preg_match('/\.json$/i', $filename)) {
        throw new Exception('허용되지 않는 확장자 (json만 가능)');
    }

    $filepath = $dataDir . '/' . $filename;

    if (!file_exists($filepath)) {
        echo json_encode([
            'success' => true,
            'message' => 'No data',
            'data'    => []
        ]);
        exit;
    }

    $content = file_get_contents($filepath);
    if ($content === false) {
        throw new Exception('파일 읽기 실패');
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success'   => true,
            'message'   => 'JSON 파싱 오류: ' . json_last_error_msg(),
            'data'      => [],
            'file_size' => strlen($content)
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => '불러오기 완료',
        'data'    => $data,
        'time'    => date('Y-m-d H:i:s', filemtime($filepath))
    ]);
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data'    => []
    ]);
}
