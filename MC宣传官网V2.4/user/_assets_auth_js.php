<?php
/** 认证页面专用脚本，只加载 Element Plus 挂载桥与认证组件。 */
$__authJsFiles = [
    'js/element-bridge.js',
    'js/blocks/auth-native.js',
];
$__authJsVersion = (int)@filemtime(__DIR__ . '/auth-js.php');
foreach ($__authJsFiles as $__authJsFile) {
    $__authJsTime = (int)@filemtime(__DIR__ . '/' . $__authJsFile);
    if ($__authJsTime > $__authJsVersion) $__authJsVersion = $__authJsTime;
}
echo '    <script src="auth-js.php?v=' . $__authJsVersion . '" defer></script>' . "\n";
unset($__authJsFiles, $__authJsVersion, $__authJsFile, $__authJsTime);