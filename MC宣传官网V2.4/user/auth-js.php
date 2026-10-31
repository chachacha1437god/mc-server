<?php
/**
 * 登录、注册、找回密码专用 JS 合并端点。
 * 认证页只需要 Element Plus 挂载桥和认证组件，不加载用户中心业务模块。
 */
declare(strict_types=1);

$files = [
    'js/element-bridge.js',
    'js/blocks/auth-native.js',
];

$base = __DIR__;
$signature = '';
$existing = [];
foreach ($files as $relativePath) {
    $path = $base . '/' . $relativePath;
    if (is_file($path)) {
        $existing[] = $path;
        $signature .= '|' . $relativePath . '|' . filemtime($path) . '|' . filesize($path);
    }
}
$etag = '"' . md5($signature) . '"';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: ' . $etag);
header('Vary: Accept-Encoding');
header('X-Content-Type-Options: nosniff');

if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

if (!ob_start('ob_gzhandler')) ob_start();
foreach ($existing as $path) {
    echo "// === " . basename($path) . " ===\n";
    readfile($path);
    echo "\n;\n";
}