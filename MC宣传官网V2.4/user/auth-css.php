<?php
/**
 * 登录、注册、找回密码专用 CSS 合并端点。
 * 只包含认证页基础样式、认证样式、必要动画和认证组件样式。
 */
declare(strict_types=1);

$files = [
    'vendor/element-plus/index.css',
    'vendor/element-plus/dark/css-vars.css',
    'css/base.css',
    'css/auth.css',
    'css/effects.css',
    'css/element-bridge.css',
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

header('Content-Type: text/css; charset=utf-8');
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
    echo "/* === " . basename($path) . " === */\n";
    $content = (string)file_get_contents($path);
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") $content = substr($content, 3);
    echo $content . "\n";
}