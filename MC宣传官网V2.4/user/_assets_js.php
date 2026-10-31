<?php
/**
 * 用户中心 JS 资源引入。
 * 通过 js.php 合并端点减少请求数，并按「当前 tab + 角色」动态加载：
 *  - base：基础运行时（总是加载）
 *  - 当前 tab 对应模块：首屏即加载
 *  - staff：仅工作人员角色加载
 *  - 其余 tab 模块：由 lazy-tabs.js 首次切换到该 tab 时懒加载
 *
 * 依赖面板已定义（panel.php 全局作用域）：$currentPage、$_isStaff。
 */
require_once __DIR__ . '/_asset_set.php';

$__cPage = $currentPage ?? (($_GET['tab'] ?? '') ?: 'panel');
$__cIsStaff = !empty($_isStaff) || !empty($isUserStaff);

$__jsKeys = ['base'];
$__curModule = userAssetTabModule($__cPage) ?: null;
if ($__curModule && $__curModule !== 'base' && $__curModule !== 'staff') {
    $__jsKeys[] = $__curModule;
}
if ($__cIsStaff) $__jsKeys[] = 'staff';
$__jsKeys = array_values(array_unique($__jsKeys));

$__jsFiles = userAssetFiles('js', $__jsKeys);
$__jsV = userAssetMaxMtime($__jsFiles, __DIR__, 'js.php');

// 全局版本（任意文件变动都让懒加载 URL 随之失效）
$__allJsV = userAssetMaxMtime(userAssetAllFiles('js'), __DIR__, 'js.php');
$__allCssV = $GLOBALS['__userCssV'] ?? userAssetMaxMtime(userAssetAllFiles('css'), __DIR__, 'css.php');
// 仅在懒加载时才会按需下发的 CSS 模块（base 恒为首屏加载，不列入）
$__cssOnlySets = array_keys(array_filter(userAssetModules(), static fn($m) => !empty($m['css'])));
$__cssOnlySets = array_values(array_filter($__cssOnlySets, static fn($k) => $k !== 'base'));

// 暴露懒加载清单：已加载集合 / 各模块版本 / 带 CSS 的模块
echo '    <script>window.userAssetLoadedSets=' . json_encode($__jsKeys) . ';'
   . 'window.userAssetVersions={js:' . (int)$__allJsV . ',css:' . (int)$__allCssV . '};'
   . 'window.userAssetCssSets=' . json_encode($__cssOnlySets) . ';</script>' . "\n";

echo '    <script src="js.php?set=' . implode(',', $__jsKeys) . '&v=' . $__jsV . '" defer></script>' . "\n";

unset($__cPage, $__cIsStaff, $__curModule, $__jsKeys, $__jsFiles, $__jsV, $__allJsV, $__allCssV, $__cssOnlySets);