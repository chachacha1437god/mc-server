<?php
/** 认证页面 Element Plus 资源，不加载完整图标库以降低首屏体积。 */
$__authElementVersion = static fn(string $relativePath): int => (int)@filemtime(__DIR__ . '/' . $relativePath);
?>
    <link rel="preload" href="vendor/vue/vue.global.prod.js?v=<?= $__authElementVersion('vendor/vue/vue.global.prod.js') ?>" as="script">
    <link rel="preload" href="vendor/element-plus/index.full.min.js?v=<?= $__authElementVersion('vendor/element-plus/index.full.min.js') ?>" as="script">
    <script defer src="vendor/vue/vue.global.prod.js?v=<?= $__authElementVersion('vendor/vue/vue.global.prod.js') ?>"></script>
    <script defer src="vendor/element-plus/index.full.min.js?v=<?= $__authElementVersion('vendor/element-plus/index.full.min.js') ?>"></script>
<?php unset($__authElementVersion); ?>