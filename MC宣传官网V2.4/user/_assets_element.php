<?php
/**
 * Element Plus (Vue 3) 本地资源引入 —— 文件托管在 user/vendor/，规避 CSP。
 * 桥接样式须在合并 CSS 之后加载，以便覆盖 Element Plus 默认变量。
 */
$__v  = static fn(string $rel): int => (int)@filemtime(__DIR__ . '/' . $rel);
$__bv = $__v('css/element-bridge.css');
?>
    <link rel="stylesheet" href="vendor/element-plus/index.css?v=<?= $__v('vendor/element-plus/index.css') ?>">
    <link rel="stylesheet" href="vendor/element-plus/dark/css-vars.css?v=<?= $__v('vendor/element-plus/dark/css-vars.css') ?>">
    <link rel="stylesheet" href="css/element-bridge.css?v=<?= $__bv ?>">
    <script defer src="vendor/vue/vue.global.prod.js?v=<?= $__v('vendor/vue/vue.global.prod.js') ?>"></script>
    <script defer src="vendor/element-plus/index.full.min.js?v=<?= $__v('vendor/element-plus/index.full.min.js') ?>"></script>
    <script defer src="vendor/element-plus/locale/zh-cn.min.js?v=<?= $__v('vendor/element-plus/locale/zh-cn.min.js') ?>"></script>
    <script defer src="vendor/icons-vue/index.iife.min.js?v=<?= $__v('vendor/icons-vue/index.iife.min.js') ?>"></script>
<?php unset($__v, $__bv); ?>
