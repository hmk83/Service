<?php
/**
 * 서버 저장 PHP (범용 *.json 허용, data/ 디렉토리만)
 * 파일명: save.php
 * 경로: /public_html/serviceops/save.php
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dataDir = __DIR__ . '/data';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('POST only');
    }

    if (!is_dir($dataDir)) {
        if (!mkdir($dataDir, 0755, true)) {
            throw new Exception('data 디렉토리 생성 실패');
        }
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON 파싱 오류: ' . json_last_error_msg());
    }

    if (!is_array($payload) || !isset($payload['filename']) || !array_key_exists('content', $payload)) {
        throw new Exception('필수 파라미터 누락 (filename, content)');
    }

    // 보안: 경로 제거 + .json 확장자만 허용
    $filename = basename($payload['filename']);
    if (!preg_match('/\.json$/i', $filename)) {
        throw new Exception('허용되지 않는 확장자 (json만 가능)');
    }

    $filepath = $dataDir . '/' . $filename;
    $json = json_encode($payload['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new Exception('JSON 인코딩 오류: ' . json_last_error_msg());
    }

    $bytes = file_put_contents($filepath, $json, LOCK_EX);
    if ($bytes === false) {
        throw new Exception('파일 저장 실패');
    }

    @chmod($filepath, 0644);

    echo json_encode([
        'success' => true,
        'message' => '저장 완료',
        'bytes'   => $bytes,
        'file'    => $filename,
        'time'    => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
