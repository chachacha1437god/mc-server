<?php
/** 认证页面专用样式，只加载认证所需 CSS。 */
$__authCssFiles = [
    'vendor/element-plus/index.css',
    'vendor/element-plus/dark/css-vars.css',
    'css/base.css',
    'css/auth.css',
    'css/effects.css',
    'css/element-bridge.css',
];
$__authCssVersion = (int)@filemtime(__DIR__ . '/auth-css.php');
foreach ($__authCssFiles as $__authCssFile) {
    $__authCssTime = (int)@filemtime(__DIR__ . '/' . $__authCssFile);
    if ($__authCssTime > $__authCssVersion) $__authCssVersion = $__authCssTime;
}
echo '    <link rel="stylesheet" href="auth-css.php?v=' . $__authCssVersion . '">' . "\n";
unset($__authCssFiles, $__authCssVersion, $__authCssFile, $__authCssTime);