<?php
/**
 * 用户中心 CSS 合并端点
 * 把多个 CSS 文件拼成一个长缓存响应，首屏 8 个请求 → 1 个。
 *
 * 用法1（panel 默认值保持向后兼容）：<link rel="stylesheet" href="css.php?v=<mtime_max>">
 * 用法2（按需）：<link rel="stylesheet" href="css.php?set=base,staff&v=<mtime_max>">
 *
 * 命中条件：客户端 If-None-Match 与服务端 ETag 相同 → 304。
 * 部署/改 CSS 后 v 自动变更（mtime_max），URL 变 → 浏览器自动重取。
 */

declare(strict_types=1);

require_once __DIR__ . '/_asset_set.php';

$modules = userAssetModules();

// 解析 set：未传则退化为 base（兼容 shop_pay_return.php 等旧调用）。
$raw = array_filter(array_map('trim', explode(',', (string)($_GET['set'] ?? 'base'))));
$keys = userAssetNormalizeKeys($raw, $modules);
$files = userAssetFiles('css', $keys, $modules);

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

header('Content-Type: text/css; charset=utf-8');
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
    echo "/* === " . basename($path) . " === */\n";
    $content = (string)file_get_contents($path);
    // 去除 UTF-8 BOM：合并多文件时，文件中部的 BOM 会被 CSS 解析器视作非法字符，
    // 触发错误恢复并丢弃紧随其后的整条规则（例如 body.auth-page / .user-sidebar），
    // 导致登录页、用户中心整体排版崩溃。
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    echo $content;
    echo "\n";
}