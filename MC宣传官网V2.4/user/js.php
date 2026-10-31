<?php
/**
 * 用户中心 JS 合并端点
 * 把多个 JS 文件拼成一个长缓存响应，首屏 7 个请求 → 1 个。
 *
 * 用法1（默认 base 集合，保持向后兼容）：<script src="js.php?v=<mtime_max>">
 * 用法2（按角色/当前 tab 动态加载）：<script src="js.php?set=base,tickets&v=<mtime_max>">
 *
 * 命中条件：客户端 If-None-Match 与服务端 ETag 相同 → 304。
 * 部署/改 JS 后 v 自动变更（mtime_max），URL 变 → 浏览器自动重取。
 */

declare(strict_types=1);

require_once __DIR__ . '/_asset_set.php';

$modules = userAssetModules();

// 解析 set：未传则退化为 base。
$raw = array_filter(array_map('trim', explode(',', (string)($_GET['set'] ?? 'base'))));
$keys = userAssetNormalizeKeys($raw, $modules);

// 同一键内 js 文件须按顺序加载；多个键按 userAssetFiles 的规范顺序拼接（base 优先）。
$files = userAssetFiles('js', $keys, $modules);

$base = __DIR__;
$sig = '';
$exists = [];
foreach ($files as $rel) {
    $path = $base . '/' . $rel;
    if (is_file($path)) {
        $exists[] = $path;
        $sig .= '|' . $rel . '|' . filemtime($path) . '|' . filesize($path);
    }
}
$etag = '"' . md5($sig) . '"';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: ' . $etag);
header('Vary: Accept-Encoding');
header('X-Content-Type-Options: nosniff');

$ifNoneMatch = trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '');
if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
    http_response_code(304);
    exit;
}

if (!ob_start('ob_gzhandler')) ob_start();

foreach ($exists as $path) {
    echo "// === " . basename($path) . " ===\n";
    readfile($path);
    echo "\n;\n";
}