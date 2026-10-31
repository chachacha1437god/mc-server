<?php
/**
 * 用户中心 CSS 资源引入
 * 通过 css.php 合并端点，按「角色」动态拼接 CSS（base 基础 + staff 工作人员）。
 * 各 tab 无独立 CSS，因此 CSS 只区分 base 与 staff 两档。
 * 注意：此文件直接输出 <link> 标签，请在 <head> 中 include。
 *
 * 依赖面板已定义（panel.php 全局作用域）：$currentPage、$_isStaff。
 */
require_once __DIR__ . '/_asset_set.php';

$__cPage = $currentPage ?? (($_GET['tab'] ?? '') ?: 'panel');
$__cIsStaff = !empty($_isStaff) || !empty($isUserStaff);

$__cssKeys = ['base'];
if ($__cIsStaff) $__cssKeys[] = 'staff';

$__cssFiles = userAssetFiles('css', $__cssKeys);
$__cssV = userAssetMaxMtime($__cssFiles, __DIR__, 'css.php');

// 暴露给 _assets_js.php 的全局版本（用于懒加载模块的 CSS 缓存失效）
$GLOBALS['__userCssV'] = $__cssV;

echo '    <link rel="stylesheet" href="css.php?set=' . implode(',', $__cssKeys) . '&v=' . $__cssV . '">' . "\n";

unset($__cPage, $__cIsStaff, $__cssKeys, $__cssFiles, $__cssV);