<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/backup.php';
requireLogin();
requireAdIntegrity();

// 启用输出缓冲，页面末尾做广告输出校验（防注释包裹 / 内联隐藏 / 节点被抽走）
ob_start();

$content = loadContent();
$settings = loadSettings();
$csrf = generateCsrf();
$currentTab = $_GET['tab'] ?? 'dashboard';
$tabs = [
    'dashboard'  => ['label' => '后台首页',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>'],
    'site'       => ['label' => '网站设置',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>'],
    'hero'       => ['label' => '首页横幅',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>'],
    'specs'      => ['label' => '服务器配置', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>'],
    'help'       => ['label' => '加入指南',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'],
    'features'   => ['label' => '游戏特色',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'],
    'gallery'    => ['label' => '游戏截图',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>'],
    'team'       => ['label' => '管理团队',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
    'contact'    => ['label' => '联系我们',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>'],
    'monitor'    => ['label' => '实时监控', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'],
    'messages'   => ['label' => '消息通知',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>'],
    'announcements' => ['label' => '公告管理', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>'],
    'tickets'    => ['label' => '工单管理',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'],
    'logs'       => ['label' => '行为日志',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>'],
    'risk'       => ['label' => '风控分析',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-5"/></svg>'],
    'users'      => ['label' => '用户管理',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>'],
    'applications' => ['label' => '入服申请',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-5"/></svg>'],
    'shop_revenue' => ['label' => '收益概览', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-7"/><path d="M14 7h5v5"/></svg>'],
    'shop_products' => ['label' => '商品管理', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7L12 12l8.7-5"/><path d="M12 22V12"/></svg>'],
    'shop_orders' => ['label' => '订单管理', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2z"/><path d="M9 7h6"/><path d="M9 11h6"/><path d="M9 15h4"/></svg>'],
    'shop_inventory' => ['label' => '库存管理', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/></svg>'],
    'shop_delivery' => ['label' => '发货链路', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"/><path d="M21 3l-7 7"/><path d="M21 14v5a2 2 0 0 1-2 2h-5"/><path d="M3 10V5a2 2 0 0 1 2-2h5"/><path d="M3 14l7 7"/><path d="M3 21h5v-5"/></svg>'],
    'shop_payments' => ['label' => '支付设置', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M7 15h1"/><path d="M11 15h3"/></svg>'],
    'community'  => ['label' => '社区链接',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'],
    'footer'     => ['label' => '页脚设置',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="15" x2="21" y2="15"/></svg>'],
    'backup'     => ['label' => '备份恢复',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>'],
    'images'     => ['label' => '图片管理',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/><line x1="12" y1="3" x2="12" y2="21" stroke-width="0"/></svg>'],
    'ai_settings' => ['label' => 'AI 配置',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 9h6v6H9z"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>'],
    'ai_playground' => ['label' => 'AI 测试台', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>'],
];
$siteTabs = ['site', 'hero', 'specs', 'help', 'features', 'gallery', 'team', 'contact', 'monitor', 'community', 'footer', 'images'];
$shopTabs = ['shop_revenue', 'shop_products', 'shop_orders', 'shop_inventory', 'shop_delivery', 'shop_payments'];
$aiTabs = ['ai_settings', 'ai_playground'];
$mainTabs = ['dashboard', 'messages', 'announcements', 'tickets', 'logs', 'risk', 'users', 'applications'];
$systemTabs = ['backup'];

/**
 * 设置类标签按需渲染：
 * - 当前页就是设置标签之一 → 把所有 9 个设置表单全部渲染到 DOM，子菜单之间走 SPA 切换。
 * - 否则 → 9 个设置标签只输出占位空 div，节省首屏 HTML（~100KB→~10KB）。
 * 注意：monitor 由于依赖大量 JS 行为，始终保留在 DOM 中（不算"纯表单"），不参与按需渲染。
 */
$lazySettingsTabs = ['site', 'hero', 'specs', 'help', 'features', 'gallery', 'team', 'contact', 'community', 'footer', 'images'];
$renderSettingsTabs = in_array($currentTab, $siteTabs, true);

$msg = $_GET['msg'] ?? '';

$imgAttr = function($url, $tab) use ($currentTab) {
    if (empty($url)) return '';
    $fullUrl = "../" . e($url);
    if ($tab === $currentTab) {
        return 'src="' . $fullUrl . '"';
    }
    return 'src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="' . $fullUrl . '"';
};

// Calculate unread messages（直接 SQL COUNT，避免读全表）
$unreadCount = isUserSystemInstalled() ? countUnreadMessages() : 0;
$pendingApplicationsCount = 0;
$openTicketsCount = 0;
$dashboardStats = ['today_users' => 0, 'today_applications' => 0, 'pending_applications' => 0, 'approved_unsynced' => 0, 'need_more_info' => 0, 'open_tickets' => 0, 'recent_rejected' => 0];
if (isUserSystemInstalled()) {
    try {
        $dashboardStats = getDashboardStats();
        $pendingApplicationsCount = $dashboardStats['pending_applications'];
        $openTicketsCount = $dashboardStats['open_only_tickets'] ?? $dashboardStats['open_tickets'];
    } catch (Throwable $e) {
        $pendingApplicationsCount = 0;
        $openTicketsCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站管理后台</title>
    <meta name="robots" content="noindex, nofollow">
    <?php renderFaviconLink(); ?>
    <?php $cssV = max(
        @filemtime(__DIR__.'/css/variables.css'),
        @filemtime(__DIR__.'/css/layout.css'),
        @filemtime(__DIR__.'/css/forms.css'),
        @filemtime(__DIR__.'/css/components.css'),
        @filemtime(__DIR__.'/css/shop.css'),
        @filemtime(__DIR__.'/css/login.css'),
        @filemtime(__DIR__.'/css/monitor.css'),
        @filemtime(__DIR__.'/css/modals.css'),
        @filemtime(__DIR__.'/css/utilities.css'),
        @filemtime(__DIR__.'/css/theme.css')
    ); ?>
    <script src="js/theme.js?v=<?= @filemtime(__DIR__.'/js/theme.js') ?: 0 ?>"></script>
    <link rel="stylesheet" href="css.php?b=panel&amp;v=<?= $cssV ?>">
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4H20V20H4V4Z"/><path d="M4 12H20"/><path d="M12 4V20"/></svg>
                <span class="sidebar-title">FoxMC 后台</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">常用管理</div>
                <?php foreach ($mainTabs as $key): $t = $tabs[$key]; ?>
                <a href="#tab-<?= $key ?>" onclick="switchTab('<?= $key ?>'); return false;" class="nav-item <?= $currentTab === $key ? 'active' : '' ?>" id="nav-<?= $key ?>">
                    <?= $t['icon'] ?>
                    <span><?= $t['label'] ?></span>
                    <?php if ($key === 'messages' && $unreadCount > 0): ?>
                        <span class="badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                    <?php endif; ?>
                    <?php if ($key === 'applications' && $pendingApplicationsCount > 0): ?>
                        <span class="badge" id="pendingApplicationsBadge"><?= $pendingApplicationsCount > 99 ? '99+' : $pendingApplicationsCount ?></span>
                    <?php elseif ($key === 'applications'): ?>
                        <span class="badge" id="pendingApplicationsBadge" style="display:none;"></span>
                    <?php endif; ?>
                    <?php if ($key === 'tickets' && $openTicketsCount > 0): ?>
                        <span class="badge" id="openTicketsBadge"><?= $openTicketsCount > 99 ? '99+' : $openTicketsCount ?></span>
                    <?php elseif ($key === 'tickets'): ?>
                        <span class="badge" id="openTicketsBadge" style="display:none;"></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="nav-section">
                <div class="nav-group <?= in_array($currentTab, $shopTabs, true) ? 'open' : '' ?>">
                    <button type="button" class="nav-item nav-group-toggle <?= in_array($currentTab, $shopTabs, true) ? 'active' : '' ?>" onclick="toggleNavGroup(this)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                        <span>商城管理</span>
                        <svg class="nav-group-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="nav-submenu">
                        <?php foreach ($shopTabs as $key): $t = $tabs[$key]; ?>
                        <a href="#tab-<?= $key ?>" onclick="switchTab('<?= $key ?>'); return false;" class="nav-subitem <?= $currentTab === $key ? 'active' : '' ?>" id="nav-<?= $key ?>">
                            <span><?= $t['label'] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-group <?= in_array($currentTab, $aiTabs, true) ? 'open' : '' ?>">
                    <button type="button" class="nav-item nav-group-toggle <?= in_array($currentTab, $aiTabs, true) ? 'active' : '' ?>" onclick="toggleNavGroup(this)">
                        <?= $tabs['ai_settings']['icon'] ?>
                        <span>AI 管理</span>
                        <svg class="nav-group-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="nav-submenu">
                        <?php foreach ($aiTabs as $key): $t = $tabs[$key]; ?>
                        <a href="#tab-<?= $key ?>" onclick="switchTab('<?= $key ?>'); return false;" class="nav-subitem <?= $currentTab === $key ? 'active' : '' ?>" id="nav-<?= $key ?>">
                            <span><?= $t['label'] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-group <?= in_array($currentTab, $siteTabs, true) ? 'open' : '' ?>">
                    <button type="button" class="nav-item nav-group-toggle <?= in_array($currentTab, $siteTabs, true) ? 'active' : '' ?>" onclick="toggleNavGroup(this)">
                        <?= $tabs['site']['icon'] ?>
                        <span>网站设置</span>
                        <svg class="nav-group-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="nav-submenu">
                        <?php foreach ($siteTabs as $key): $t = $tabs[$key]; ?>
                        <?php
                        // monitor 始终在 DOM 中，可以走 SPA 切换；其余设置标签当前未渲染时需要整页加载
                        $needsReload = !$renderSettingsTabs && in_array($key, $lazySettingsTabs, true);
                        ?>
                        <?php if ($needsReload): ?>
                        <a href="?tab=<?= $key ?>" class="nav-subitem <?= $currentTab === $key ? 'active' : '' ?>" id="nav-<?= $key ?>">
                            <span><?= $t['label'] ?></span>
                        </a>
                        <?php else: ?>
                        <a href="#tab-<?= $key ?>" onclick="switchTab('<?= $key ?>'); return false;" class="nav-subitem <?= $currentTab === $key ? 'active' : '' ?>" id="nav-<?= $key ?>">
                            <span><?= $t['label'] ?></span>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">系统</div>
                <?php foreach ($systemTabs as $key): $t = $tabs[$key]; ?>
                <a href="#tab-<?= $key ?>" onclick="switchTab('<?= $key ?>'); return false;" class="nav-item <?= $currentTab === $key ? 'active' : '' ?>" id="nav-<?= $key ?>">
                    <?= $t['icon'] ?>
                    <span><?= $t['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="nav-divider"></div>
            <a href="../index.html" class="nav-item" target="_blank">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                <span>前往前台</span>
            </a>
            <a href="index.php?action=logout" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>退出登录</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <h2 class="topbar-title" id="page-title"><?= e($tabs[$currentTab]['label'] ?? '管理后台') ?></h2>
            <div class="topbar-actions">
                <button type="button" class="theme-trigger" onclick="FoxmcTheme.open()" aria-label="外观设置" title="外观设置">
                    <svg class="theme-icon-light" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
                    <svg class="theme-icon-dark" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                <div class="topbar-profile" onclick="openProfileModal()">
                    <img src="<?= !empty($settings['admin_avatar']) ? e($settings['admin_avatar']) : '../assets/images/cat.jpg' ?>" alt="Avatar" id="topbarAvatar">
                    <span class="topbar-user"><?= e(getAdminUsername()) ?></span>
                </div>
            </div>
        </header>

        <!-- Profile Modal -->
        <div id="profileModal" class="modal-overlay" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>管理员账号设置</h3>
                    <button type="button" class="close-modal" onclick="closeProfileModal()">×</button>
                </div>
                <form id="profileForm" method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="profile">
                    
                    <div class="form-group" style="text-align: center;">
                        <div class="avatar-upload-preview">
                            <img src="<?= !empty($settings['admin_avatar']) ? e($settings['admin_avatar']) : '../assets/images/cat.jpg' ?>" id="avatarPreview">
                            <label for="avatarInput" class="avatar-edit-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            </label>
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;" onchange="previewAvatar(this)">
                        </div>
                        <p class="file-hint">点击图标修改头像</p>
                    </div>

                    <div class="form-group">
                        <label>管理员账号</label>
                        <input type="text" name="admin_username" class="form-input" value="<?= e(getAdminUsername()) ?>" pattern="[A-Za-z0-9_]{3,32}" title="3-32 位字母、数字或下划线" placeholder="登录后台的账号名" autocomplete="off">
                        <p class="file-hint">修改后下次登录请使用新账号</p>
                    </div>

                    <div class="form-group">
                        <label>新密码 (留空则不修改)</label>
                        <input type="password" name="new_password" minlength="6" maxlength="6" class="form-input" placeholder="输入 6 位新密码">
                    </div>
                    
                    <div class="form-group">
                        <label>确认新密码</label>
                        <input type="password" name="confirm_password" class="form-input" placeholder="再次输入新密码">
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeProfileModal()">取消</button>
                        <button type="submit" class="btn-save">保存设置</button>
                    </div>
                </form>
            </div>
        </div>

<?= renderAdBanner($settings) ?>

        <?php
            // U1: 检测当前是否仍在用默认密码 123456
            $usingDefaultPass = false;
            try {
                $usingDefaultPass = password_verify(ADMIN_DEFAULT_PASS, getAdminPassHash());
            } catch (Throwable $_passEx) { /* 静默处理：仅是提示，不影响主流程 */ }
        ?>
        <?php if ($usingDefaultPass): ?>
        <div class="security-notice security-notice--warning">
            <div class="security-notice__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <div class="security-notice__body">
                <div class="security-notice__title">您当前仍在使用默认密码 <code>123456</code></div>
                <div class="security-notice__desc">为了账号安全，建议立即前往「个人资料」修改为只有您知道的强密码。</div>
            </div>
            <button type="button" onclick="openProfileModal()" class="security-notice__action security-notice__action--warning">
                立即修改密码
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </div>
        <?php endif; ?>

        <?php if ($msg === 'ok'): ?>
        <div class="alert success">保存成功！内容已更新。</div>
        <?php elseif ($msg === 'err'): ?>
        <div class="alert error">保存失败，请检查文件权限。</div>
        <?php elseif ($msg === 'csrf'): ?>
        <div class="alert error">安全验证失败，请重新提交。</div>
        <?php endif; ?>

        <div class="page-content">
            
            <div id="tab-dashboard" class="tab-pane" style="display: <?= $currentTab === 'dashboard' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">运营待办概览</h3>
                    <div class="dash-stat-grid">
                        <div class="dash-stat dash-stat--green"><div class="dash-stat-num"><?= (int)$dashboardStats['today_users'] ?></div><div class="dash-stat-label">今日新增注册</div></div>
                        <div class="dash-stat dash-stat--green"><div class="dash-stat-num"><?= (int)$dashboardStats['today_applications'] ?></div><div class="dash-stat-label">今日新增申请</div></div>
                        <div class="dash-stat dash-stat--amber"><div class="dash-stat-num"><?= (int)$dashboardStats['pending_applications'] ?></div><div class="dash-stat-label">待审核申请</div></div>
                        <div class="dash-stat dash-stat--blue"><div class="dash-stat-num"><?= (int)$dashboardStats['approved_unsynced'] ?></div><div class="dash-stat-label">已通过未同步白名单</div></div>
                    </div>
                    <div class="dash-todo-grid">
                        <div class="dash-todo">
                            <div class="dash-todo-head"><strong>申请审核</strong><span class="dash-todo-count dash-todo-count--amber"><?= (int)$dashboardStats['pending_applications'] ?></span></div>
                            <p class="dash-todo-desc">优先处理待审核申请，降低玩家等待时间。</p>
                            <button type="button" onclick="switchTab('applications')" class="dash-todo-btn dash-todo-btn--green">去审核</button>
                        </div>
                        <div class="dash-todo">
                            <div class="dash-todo-head"><strong>白名单同步</strong><span class="dash-todo-count dash-todo-count--blue"><?= (int)$dashboardStats['approved_unsynced'] ?></span></div>
                            <p class="dash-todo-desc">已通过但未同步的申请需要复制命令到服务器执行。</p>
                            <button type="button" onclick="switchTab('applications')" class="dash-todo-btn dash-todo-btn--blue">去同步</button>
                        </div>
                        <div class="dash-todo">
                            <div class="dash-todo-head"><strong>待跟进申请</strong><span class="dash-todo-count dash-todo-count--amber"><?= (int)($dashboardStats['need_more_info'] ?? 0) ?></span></div>
                            <p class="dash-todo-desc">已要求补充信息的申请，适合定期复查或通知提醒。</p>
                            <button type="button" onclick="switchTab('applications')" class="dash-todo-btn dash-todo-btn--amber">查看申请</button>
                        </div>
                        <div class="dash-todo">
                            <div class="dash-todo-head"><strong>待处理工单</strong><span class="dash-todo-count dash-todo-count--red"><?= (int)($dashboardStats['open_tickets'] ?? 0) ?></span></div>
                            <p class="dash-todo-desc">玩家反馈、举报、申诉和建议需要及时回复。</p>
                            <button type="button" onclick="switchTab('tickets')" class="dash-todo-btn dash-todo-btn--red">处理工单</button>
                        </div>
                    </div>
                    <div class="dash-quick-actions">
                        <button type="button" onclick="switchTab('applications')" class="dash-todo-btn dash-todo-btn--green">处理入服申请</button>
                        <button type="button" onclick="switchTab('messages')" class="dash-todo-btn dash-todo-btn--ghost-green">查看消息通知</button>
                        <button type="button" onclick="switchTab('risk')" class="dash-todo-btn dash-todo-btn--ghost-red">查看风险分析</button>
                    </div>
                </div>
            </div>

            <div id="tab-announcements" class="tab-pane" style="display: <?= $currentTab === 'announcements' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">公告管理</h3>
                    <details id="announcementComposer" class="announcement-composer">
                        <summary>
                            <span>发布 / 编辑公告</span>
                            <span id="announcementsCount">点击展开填写公告内容</span>
                        </summary>
                        <div class="announcement-editor">
                            <input type="hidden" id="announcementId" value="">
                            <div class="announcement-main">
                                <div class="form-group"><label>公告标题</label><input type="text" id="announcementTitle" class="form-input" maxlength="120" placeholder="例如：周末活动公告"></div>
                                <div class="form-group announcement-content-field"><label>公告内容</label><textarea id="announcementContent" class="form-input" rows="4" placeholder="输入公告内容..."></textarea><?php if (!empty($settings['ai_content_enabled'])): ?><button type="button" class="btn-secondary" style="margin-top:6px;" onclick="aiGenerateInto('announcementContent','announcement')">✨ AI 生成公告</button><?php endif; ?></div>
                            </div>
                            <div class="announcement-side">
                                <div class="announcement-side-row">
                                    <div class="form-group"><label>公告类型</label><select id="announcementLevel" class="form-input"><option value="info">普通</option><option value="success">活动</option><option value="warning">维护</option><option value="danger">紧急</option></select></div>
                                    <div class="form-group"><label>发布时间</label><input type="datetime-local" id="announcementPublishAt" class="form-input"></div>
                                </div>
                                <div class="announcement-side-row">
                                    <div class="form-group"><label>开始生效时间</label><input type="datetime-local" id="announcementStartAt" class="form-input"></div>
                                    <div class="form-group"><label>结束时间</label><input type="datetime-local" id="announcementEndAt" class="form-input"></div>
                                </div>
                                <div class="announcement-options">
                                    <label><input type="checkbox" id="announcementPinned"> 置顶</label>
                                    <label><input type="checkbox" id="announcementActive" checked> 启用</label>
                                    <label><input type="checkbox" id="announcementShowInHome"> 首页显示</label>
                                    <label><input type="checkbox" id="announcementShowInUserCenter" checked> 用户中心显示</label>
                                    <label><input type="checkbox" id="announcementShowAsPopup"> 维护弹窗</label>
                                </div>
                                <div class="announcement-actions">
                                    <button type="button" onclick="saveAnnouncement()" class="btn-primary">保存公告</button>
                                    <button type="button" onclick="resetAnnouncementForm()" class="btn-secondary">清空</button>
                                </div>
                            </div>
                        </div>
                    </details>
                    <div id="announcementsList" style="display:grid;gap:10px;"></div>
                </div>
            </div>

            <div id="tab-tickets" class="tab-pane" style="display: <?= $currentTab === 'tickets' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">工单管理</h3>
                    <details class="application-settings-panel" style="margin-bottom:16px;">
                        <summary class="application-settings-summary">
                            <span>工单频率限制</span>
                            <span>0 表示不限制</span>
                        </summary>
                        <form method="POST" action="save.php" data-ajax="true" class="application-settings-form" style="margin-top:14px;">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="tab" value="ticket_settings">
                            <div class="settings-panel-head">
                                <div>
                                    <h4>提交频率</h4>
                                    <p>限制普通用户在指定时间窗口内可新建的工单数量；不影响已有工单回复。</p>
                                </div>
                                <button type="submit" class="btn-save small">保存限制</button>
                            </div>
                            <div class="compact-field-grid">
                                <div class="form-group">
                                    <label>时间窗口（小时）</label>
                                    <input type="number" min="0" max="8760" name="ticket_rate_limit_hours" value="<?= e((string)($settings['ticket_rate_limit_hours'] ?? 0)) ?>" class="form-input" placeholder="例如 24">
                                </div>
                                <div class="form-group">
                                    <label>最多提交工单数</label>
                                    <input type="number" min="0" max="999" name="ticket_rate_limit_count" value="<?= e((string)($settings['ticket_rate_limit_count'] ?? 0)) ?>" class="form-input" placeholder="例如 3">
                                </div>
                            </div>
                        </form>
                    </details>
                    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
                        <select id="ticketStatusFilter" class="form-input" style="width:auto;min-width:140px;">
                            <option value="">全部工单</option>
                            <option value="open">待处理</option>
                            <option value="replied">已回复</option>
                            <option value="closed">已关闭</option>
                        </select>
                        <button type="button" onclick="loadTicketsList()" style="border:none;padding:10px 16px;border-radius:8px;background:#16a34a;color:#fff;cursor:pointer;">刷新</button>
                    </div>
                    <div id="ticketsList" style="display:grid;gap:12px;"></div>
                    <div id="ticketsPagination" style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:16px;"></div>
                </div>
            </div>

            <div id="tab-logs" class="tab-pane" style="display: <?= $currentTab === 'logs' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">行为日志</h3>
                    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
                        <div style="flex:1;min-width:220px;position:relative;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="logSearchInput" class="form-input" placeholder="搜索用户名 / 邮箱 / 游戏ID / IP / 详情..." style="padding-left:36px;">
                        </div>
                        <select id="logActionFilter" class="form-input" style="width:auto;min-width:160px;">
                            <option value="">全部行为</option>
                        </select>
                        <button type="button" onclick="loadUserLogs(1)" style="border:none;padding:10px 16px;border-radius:8px;background:#16a34a;color:#fff;cursor:pointer;">查询</button>
                    </div>
                    <div class="logs-table-wrap" style="overflow-x:auto;background:#fff;border:1px solid #e2e8f0;border-radius:14px;">
                        <table class="logs-table" style="width:100%;border-collapse:collapse;font-size:.9em;">
                            <thead style="background:#f8fafc;color:#64748b;">
                                <tr>
                                    <th style="padding:12px;text-align:left;">时间</th>
                                    <th style="padding:12px;text-align:left;">用户</th>
                                    <th style="padding:12px;text-align:left;">行为</th>
                                    <th style="padding:12px;text-align:left;">详情</th>
                                    <th style="padding:12px;text-align:left;">IP</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <tr><td colspan="5" style="padding:32px;text-align:center;color:#94a3b8;">加载中...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="logsPagination" style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-top:16px;"></div>
                </div>
            </div>

            <div id="tab-risk" class="tab-pane" style="display: <?= $currentTab === 'risk' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">风控分析</h3>
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:14px;">
                        <p style="color:#64748b;font-size:.9em;margin:0;">综合登录暴破、锁定状态、注册集群、IP 黑名单等信号，并提供一键封禁 / 解锁动作。</p>
                        <button type="button" onclick="loadRiskSummary()" style="border:none;padding:9px 14px;border-radius:8px;background:#16a34a;color:#fff;cursor:pointer;">刷新分析</button>
                    </div>
                    <div id="riskThreatBanner" style="margin-bottom:12px;"></div>
                    <div id="riskStatsGrid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:12px;"></div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;margin-bottom:12px;">
                        <div style="background:#fff;border:1px solid #fecaca;border-radius:14px;padding:14px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
                                <h4 style="margin:0;color:#dc2626;">登录暴破 IP（24h）</h4>
                                <span style="color:#94a3b8;font-size:.82em;">失败 ≥5 次</span>
                            </div>
                            <div id="riskBruteForceList" style="display:grid;gap:8px;"></div>
                        </div>
                        <div style="background:#fff;border:1px solid #fde68a;border-radius:14px;padding:14px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
                                <h4 style="margin:0;color:#ca8a04;">当前被锁定的 IP / 账号</h4>
                                <span style="color:#94a3b8;font-size:.82em;">可一键解锁</span>
                            </div>
                            <div id="riskLockedList" style="display:grid;gap:8px;"></div>
                        </div>
                    </div>

                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                            <h4 style="margin:0;color:#0f172a;">IP 黑名单</h4>
                            <form id="riskAddBlocklistForm" onsubmit="return riskAddBlocklist(event);" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                <input type="text" id="riskBlockIpInput" placeholder="IP 地址" required style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;width:160px;">
                                <input type="text" id="riskBlockReasonInput" placeholder="封禁原因（可选）" maxlength="200" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;width:200px;">
                                <select id="riskBlockTtlInput" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;">
                                    <option value="0">永久</option>
                                    <option value="3600">1 小时</option>
                                    <option value="86400">1 天</option>
                                    <option value="604800">7 天</option>
                                    <option value="2592000">30 天</option>
                                </select>
                                <button type="submit" style="border:none;padding:6px 12px;border-radius:8px;background:#dc2626;color:#fff;cursor:pointer;font-size:.86em;">封禁</button>
                            </form>
                        </div>
                        <div id="riskBlocklistList" style="display:grid;gap:8px;"></div>
                    </div>

                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;">
                            <h4 style="margin:0;color:#0f172a;">近期需关注申请</h4>
                            <span style="color:#94a3b8;font-size:.84em;">优先复查拒绝、需补充和异常申请</span>
                        </div>
                        <div id="riskApplicationsList" style="display:grid;gap:8px;"></div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;margin-bottom:12px;">
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;">
                            <h4 style="margin:0 0 10px;color:#dc2626;">注册集群（24h 内同 IP 多账号注册）</h4>
                            <div id="riskRegisterClusterList" style="display:grid;gap:8px;"></div>
                        </div>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;">
                            <h4 style="margin:0 0 10px;color:#ca8a04;">新注册即申请（≤5 分钟）</h4>
                            <div id="riskQuickApplyList" style="display:grid;gap:8px;"></div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;">
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;">
                            <h4 style="margin:0 0 10px;color:#0f172a;">同 IP 多账号</h4>
                            <div id="riskMultiIpList" style="display:grid;gap:8px;"></div>
                        </div>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;">
                            <h4 style="margin:0 0 10px;color:#0f172a;">24 小时高频 IP</h4>
                            <div id="riskHighFreqList" style="display:grid;gap:8px;"></div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;margin-top:12px;">
                        <details open style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;">
                            <summary style="cursor:pointer;color:#0f172a;font-weight:800;">申请来源统计</summary>
                            <div id="riskSourceStatsList" style="display:grid;gap:8px;margin-top:10px;"></div>
                        </details>
                        <details style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;">
                            <summary style="cursor:pointer;color:#0f172a;font-weight:800;">审核质量分析</summary>
                            <div id="riskReviewQualityList" style="display:grid;gap:8px;margin-top:10px;"></div>
                        </details>
                    </div>
                </div>
            </div>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-site" class="tab-pane" style="display: <?= $currentTab === 'site' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="site">
                    <div class="form-section">
                        <h3 class="section-title">服务器类型</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">选择您服务器所属的平台类型。</p>
                        <div class="server-mode-selector">
                            <?php
                            $currentMode = $content['site']['server_mode'] ?? 'international';
                            $serverModes = [
                                'international' => ['label' => '官方国际服', 'icon' => '../egg/mc.webp'],
                                'netease'       => ['label' => '网易山头服', 'icon' => '../egg/sbwangyi.webp'],
                            ];
                            foreach ($serverModes as $modeVal => $modeInfo):
                            ?>
                            <label class="server-mode-card server-mode-card--<?= $modeVal ?> <?= $currentMode === $modeVal ? 'is-active' : '' ?>">
                                <input type="radio" name="site[server_mode]" value="<?= $modeVal ?>" <?= $currentMode === $modeVal ? 'checked' : '' ?> onchange="updateModeCards()">
                                <img src="<?= $modeInfo['icon'] ?>" alt="<?= $modeInfo['label'] ?>">
                                <span><?= $modeInfo['label'] ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <?php
                        $currentTier = $content['site']['netease_tier'] ?? 'shangyao';
                        $tiers = [
                            'shangyao' => ['name' => '山腰', 'players' => 4,  'saves' => 1],
                            'shanfeng' => ['name' => '山峰', 'players' => 12, 'saves' => 3],
                            'yunding'  => ['name' => '云顶', 'players' => 40, 'saves' => 3],
                        ];
                        ?>
                        <div class="netease-tier-section" id="neteaseTierSection" style="display: <?= $currentMode === 'netease' ? 'block' : 'none' ?>">
                            <p class="netease-tier-title">选择套餐规格</p>
                            <div class="tier-selector">
                                <?php foreach ($tiers as $tierVal => $tierInfo): ?>
                                <label class="tier-card <?= $currentTier === $tierVal ? 'is-active' : '' ?>">
                                    <input type="radio" name="site[netease_tier]" value="<?= $tierVal ?>" <?= $currentTier === $tierVal ? 'checked' : '' ?> onchange="updateTierCards()">
                                    <span class="tier-name"><?= $tierInfo['name'] ?></span>
                                    <span class="tier-spec">至多 <?= $tierInfo['players'] ?> 名玩家</span>
                                    <span class="tier-spec"><?= $tierInfo['saves'] ?> 个存档位置</span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="tier-common-note">全部套餐均包含：全天候畅玩 &middot; 成员免费游玩 &middot; 存档自动备份</p>
                        </div>
                    </div>
                    <div class="form-section">
                        <h3 class="section-title">网站基本信息</h3>
                        <div class="form-group">
                            <label>网站标题</label>
                            <input type="text" name="site[title]" value="<?= e($content['site']['title'] ?? '') ?>" class="form-input">
                        </div>
                        <div class="form-group">
                            <label>网站描述 (SEO)</label>
                            <textarea name="site[description]" class="form-input" rows="3"><?= e($content['site']['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>服务器 IP 地址</label>
                            <input type="text" name="site[server_ip]" value="<?= e($content['site']['server_ip'] ?? '') ?>" class="form-input" placeholder="play.example.com">
                        </div>
                    </div>
                    <div class="form-section">
                        <h3 class="section-title">导航栏 LOGO 设置</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">上传图片后将替换默认文字 LOGO；清空图片则恢复为文字显示。</p>
                        <div class="form-group">
                            <label>LOGO 文字（默认显示）</label>
                            <input type="text" name="site[logo_text]" value="<?= e($content['site']['logo_text'] ?? '我的世界服务器') ?>" class="form-input" placeholder="我的世界服务器">
                        </div>
                        <div class="form-group">
                            <label>LOGO 图片（可选，优先于文字）</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['site']['logo_image'])): ?>
                                <img src="../<?= e($content['site']['logo_image']) ?>" class="preview-img small" alt="当前LOGO">
                                <?php endif; ?>
                                <input type="file" name="site_logo_image" accept="image/*" class="form-file">
                                <input type="hidden" name="site[logo_image]" value="<?= e($content['site']['logo_image'] ?? '') ?>">
                                <span class="file-hint">建议高度 40px，PNG 透明背景效果最佳。留空则使用文字 LOGO。</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="switch" style="margin-top: 8px;">
                                <input class="toggle" type="checkbox" name="site[clear_logo]" value="1">
                                <span class="slider"></span>
                            </label>
                            <span style="margin-top: 8px; font-size: 0.85rem; color: var(--text-muted);">勾选后保存将清除图片 LOGO，恢复为文字显示</span>
                        </div>
                        <div class="form-group">
                            <label>浏览器 ICO 图标（Favicon，可选，与 LOGO 独立）</label>
                            <div class="image-upload-group" data-ico="1">
                                <?php if (!empty($content['site']['favicon_ico'])): ?>
                                <img src="../<?= e($content['site']['favicon_ico']) ?>" class="preview-img small" alt="当前ICO图标">
                                <?php endif; ?>
                                <input type="file" name="site_favicon_ico" accept=".ico,image/x-icon,image/vnd.microsoft.icon" class="form-file">
                                <input type="hidden" name="site[favicon_ico]" value="<?= e($content['site']['favicon_ico'] ?? '') ?>">
                                <span class="file-hint">仅支持 .ico 格式，建议尺寸 32×32 或 64×64，单文件 ≤ 1MB。上传后将替换浏览器标签页与收藏夹中的站点图标，与导航栏 LOGO 互不影响。</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>ICO 图标效果预览</label>
                            <div class="favicon-preview<?= empty($content['site']['favicon_ico']) ? ' is-empty' : '' ?>">
                                <div class="favicon-preview-item">
                                    <div class="favicon-mock-tab">
                                        <img class="favicon-mock-img" <?= !empty($content['site']['favicon_ico']) ? 'src="../' . e($content['site']['favicon_ico']) . '"' : '' ?> alt="">
                                        <span class="favicon-mock-title"><?= e($content['site']['title'] ?: '网站标题') ?></span>
                                        <span class="favicon-mock-close">×</span>
                                    </div>
                                    <span class="favicon-preview-label">标签页 16×16</span>
                                </div>
                                <div class="favicon-preview-item">
                                    <div class="favicon-mock-bookmark">
                                        <img class="favicon-mock-img favicon-mock-img--32" <?= !empty($content['site']['favicon_ico']) ? 'src="../' . e($content['site']['favicon_ico']) . '"' : '' ?> alt="">
                                    </div>
                                    <span class="favicon-preview-label">书签栏 32×32</span>
                                </div>
                                <span class="favicon-preview-placeholder">尚未设置 ICO 图标，选择文件后可在此实时预览效果</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="switch" style="margin-top: 8px;">
                                <input class="toggle" type="checkbox" name="site[clear_favicon]" value="1">
                                <span class="slider"></span>
                            </label>
                            <span style="margin-top: 8px; font-size: 0.85rem; color: var(--text-muted);">勾选后保存将清除自定义 ICO 图标，恢复浏览器默认图标</span>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
                
                <div class="form-section" style="margin-top: 32px;">
                    <h3 class="section-title">后台管理设置</h3>
                    <form method="POST" action="save.php" data-ajax="true">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="tab" value="general_settings">
                        
                        <div class="form-group" style="display: flex; flex-direction: column; align-items: center;">
                            <label class="switch">
                                <input class="toggle" type="checkbox" name="hide_ad_banner" value="1" <?= !empty($settings['hide_ad_banner']) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <span style="margin-top: 12px; font-weight: 500; color: var(--text-primary);">永久关闭全局广告横幅</span>
                            <p style="margin-top: 8px; font-size: 0.85rem; color: var(--text-muted); text-align: center;">开启此选项后，后台顶部的雨云IDC广告将不再显示。</p>
                        </div>

                        <hr style="border:none;border-top:1px dashed #e2e8f0;margin:24px 0;">

                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:600;color:#0f172a;">
                                可信反代 / CDN 出口段（高级）
                                <span style="font-weight:400;color:#64748b;font-size:0.8em;">影响登录限流、日志真实 IP</span>
                            </label>
                            <?php
                                $detectedRemote = $_SERVER['REMOTE_ADDR'] ?? '-';
                                $detectedReal   = function_exists('getRealClientIp') ? getRealClientIp() : $detectedRemote;
                                $isProxied      = $detectedRemote !== $detectedReal;
                                $isRemoteTrusted = function_exists('isTrustedProxy') && isTrustedProxy($detectedRemote);
                            ?>
                            <div style="background:<?= $isProxied ? '#f0fdf4' : '#fef9c3' ?>;border:1px solid <?= $isProxied ? '#bbf7d0' : '#fde68a' ?>;border-radius:10px;padding:12px;margin:8px 0 12px;font-size:0.86rem;line-height:1.6;color:#334155;">
                                <div><strong>当前检测：</strong></div>
                                <div>· 反代地址 (REMOTE_ADDR)：<code style="background:#fff;padding:2px 6px;border-radius:4px;border:1px solid #e2e8f0;"><?= e($detectedRemote) ?></code><?= $isRemoteTrusted ? ' <span style="color:#16a34a;font-weight:700;">✓ 已识别为可信代理</span>' : ' <span style="color:#b45309;">（未列入可信段，header 将被忽略）</span>' ?></div>
                                <div>· 解析后的真实 IP：<code style="background:#fff;padding:2px 6px;border-radius:4px;border:1px solid #e2e8f0;"><?= e($detectedReal) ?></code><?= $isProxied ? '' : '<span style="color:#94a3b8;"> （与反代地址相同 = 当前判定无反代或反代未被信任）</span>' ?></div>
                            </div>
                            <textarea name="admin_trusted_proxies" rows="5" class="form-input" style="font-family:Consolas,monospace;font-size:0.86em;" placeholder="每行一个 IP 或 CIDR，例如：&#10;10.0.0.0/8&#10;192.168.1.5&#10;172.20.0.0/16"><?= e((string)($settings['admin_trusted_proxies'] ?? '')) ?></textarea>
                            <div style="margin-top:8px;font-size:0.82rem;color:#64748b;line-height:1.7;">
                                <strong>什么时候需要填？</strong>站点放在 <em>nginx 反代 / Cloudflare（中国版）/ 阿里云 / 腾讯云 / 自建 CDN</em> 后面时，把反代的真实出口 IP 段填进来；这样登录失败次数才能正确按访客的真实 IP 限流，否则所有人共享反代 IP，单个攻击者就能把全网都锁死 5 分钟。
                                <br><br>
                                <strong>不需要填的情况：</strong>站点直连公网（无反代/无 CDN）；或者用 <em>Cloudflare 国际版</em>（已内置默认信任）；或者 nginx 同机部署（127.0.0.1 已内置）。
                                <br><br>
                                <strong>怎么验证：</strong>填完保存后刷新本页，从手机移动网络访问后台，看上面的"解析后的真实 IP"是不是变成你的真实手机 IP。
                                <br><br>
                                <button type="button" onclick="document.querySelector('[name=admin_trusted_proxies]').value+='\n10.0.0.0/8\n172.16.0.0/12\n192.168.0.0/16'" style="border:1px solid #cbd5e1;background:#fff;color:#475569;padding:4px 10px;border-radius:6px;cursor:pointer;font-size:0.82em;">追加内网三段</button>
                                <button type="button" onclick="document.querySelector('[name=admin_trusted_proxies]').value=''" style="border:1px solid #fca5a5;background:#fff;color:#b91c1c;padding:4px 10px;border-radius:6px;cursor:pointer;font-size:0.82em;margin-left:6px;">清空</button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">保存设置</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div id="tab-site" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-hero" class="tab-pane" style="display: <?= $currentTab === 'hero' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="hero">
                    <div class="form-section">
                        <h3 class="section-title">首页横幅内容</h3>
                        <div class="form-group">
                            <label>顶部标签文字</label>
                            <input type="text" name="hero[badge]" value="<?= e($content['hero']['badge'] ?? '') ?>" class="form-input">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>标题第一行</label>
                                <input type="text" name="hero[title_line1]" value="<?= e($content['hero']['title_line1'] ?? '') ?>" class="form-input">
                            </div>
                            <div class="form-group">
                                <label>标题高亮部分</label>
                                <input type="text" name="hero[title_highlight]" value="<?= e($content['hero']['title_highlight'] ?? '') ?>" class="form-input">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>副标题</label>
                            <textarea name="hero[subtitle]" class="form-input" rows="2"><?= e($content['hero']['subtitle'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>特性标签 (每行一个)</label>
                            <textarea name="hero[features_text]" class="form-input" rows="3"><?= e(implode("\n", $content['hero']['features'] ?? [])) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>背景图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['hero']['bg_image'])): ?>
                                <img <?= $imgAttr($content['hero']['bg_image'], 'hero') ?> class="preview-img" alt="">
                                <?php endif; ?>
                                <input type="file" name="hero_bg_image" accept="image/*" class="form-file">
                                <input type="hidden" name="hero[bg_image]" value="<?= e($content['hero']['bg_image'] ?? '') ?>">
                                <span class="file-hint">留空则保持当前图片不变</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div id="tab-hero" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-specs" class="tab-pane" style="display: <?= $currentTab === 'specs' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="specs">
                    <div class="form-section">
                        <h3 class="section-title">服务器配置板块</h3>
                        <div class="form-row">
                            <div class="form-group"><label>板块标题</label><input type="text" name="specs[title]" value="<?= e($content['specs']['title'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>板块副标题</label><input type="text" name="specs[subtitle]" value="<?= e($content['specs']['subtitle'] ?? '') ?>" class="form-input"></div>
                        </div>
                        <div class="form-group">
                            <label>板块背景图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['specs']['bg_image'])): ?><img <?= $imgAttr($content['specs']['bg_image'], 'specs') ?> class="preview-img" alt=""><?php endif; ?>
                                <input type="file" name="specs_bg_image" accept="image/*" class="form-file">
                                <input type="hidden" name="specs[bg_image]" value="<?= e($content['specs']['bg_image'] ?? '') ?>">
                                <span class="file-hint">留空则保持当前图片不变</span>
                            </div>
                        </div>
                    </div>
                    <?php foreach (($content['specs']['items'] ?? []) as $i => $item): ?>
                    <div class="form-section">
                        <h3 class="section-title">配置项 <?= $i + 1 ?></h3>
                        <div class="form-row">
                            <div class="form-group"><label>标题</label><input type="text" name="specs[items][<?= $i ?>][title]" value="<?= e($item['title'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>参数值</label><input type="text" name="specs[items][<?= $i ?>][value]" value="<?= e($item['value'] ?? '') ?>" class="form-input"></div>
                        </div>
                        <div class="form-group"><label>描述</label><textarea name="specs[items][<?= $i ?>][desc]" class="form-input" rows="2"><?= e($item['desc'] ?? '') ?></textarea></div>
                        <div class="form-group">
                            <label>图标</label>
                            <div class="image-upload-group">
                                <?php if (!empty($item['icon'])): ?><img <?= $imgAttr($item['icon'], 'specs') ?> class="preview-img small" alt=""><?php endif; ?>
                                <input type="file" name="specs_icon_<?= $i ?>" accept="image/*" class="form-file">
                                <input type="hidden" name="specs[items][<?= $i ?>][icon]" value="<?= e($item['icon'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div id="tab-specs" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-help" class="tab-pane" style="display: <?= $currentTab === 'help' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="help">
                    <div class="form-section">
                        <h3 class="section-title">加入指南板块</h3>
                        <div class="form-row">
                            <div class="form-group"><label>板块标题</label><input type="text" name="help[title]" value="<?= e($content['help']['title'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>板块副标题</label><input type="text" name="help[subtitle]" value="<?= e($content['help']['subtitle'] ?? '') ?>" class="form-input"></div>
                        </div>
                        <div class="form-group">
                            <label>板块背景图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['help']['bg_image'])): ?><img <?= $imgAttr($content['help']['bg_image'], 'help') ?> class="preview-img" alt=""><?php endif; ?>
                                <input type="file" name="help_bg_image" accept="image/*" class="form-file">
                                <input type="hidden" name="help[bg_image]" value="<?= e($content['help']['bg_image'] ?? '') ?>">
                                <span class="file-hint">留空则保持当前图片不变</span>
                            </div>
                        </div>
                    </div>
                    <?php foreach (($content['help']['steps'] ?? []) as $i => $step): ?>
                    <div class="form-section">
                        <h3 class="section-title">步骤 <?= $i + 1 ?></h3>
                        <div class="form-group"><label>标题</label><input type="text" name="help[steps][<?= $i ?>][title]" value="<?= e($step['title'] ?? '') ?>" class="form-input"></div>
                        <div class="form-group"><label>描述</label><textarea name="help[steps][<?= $i ?>][desc]" class="form-input" rows="2"><?= e($step['desc'] ?? '') ?></textarea></div>
                        <?php if (isset($step['link_text'])): ?>
                        <div class="form-row">
                            <div class="form-group"><label>按钮文字</label><input type="text" name="help[steps][<?= $i ?>][link_text]" value="<?= e($step['link_text'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>按钮链接</label><input type="text" name="help[steps][<?= $i ?>][link_url]" value="<?= e($step['link_url'] ?? '') ?>" class="form-input"></div>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($step['highlight'])): ?>
                        <div class="form-group"><label>高亮文字</label><input type="text" name="help[steps][<?= $i ?>][highlight]" value="<?= e($step['highlight'] ?? '') ?>" class="form-input"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div id="tab-help" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-features" class="tab-pane" style="display: <?= $currentTab === 'features' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="features">
                    <div class="form-section">
                        <h3 class="section-title">游戏特色板块</h3>
                        <div class="form-row">
                            <div class="form-group"><label>板块标题</label><input type="text" name="features[title]" value="<?= e($content['features']['title'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>板块副标题</label><input type="text" name="features[subtitle]" value="<?= e($content['features']['subtitle'] ?? '') ?>" class="form-input"></div>
                        </div>
                        <div class="form-group">
                            <label>板块背景图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['features']['bg_image'])): ?><img <?= $imgAttr($content['features']['bg_image'], 'features') ?> class="preview-img" alt=""><?php endif; ?>
                                <input type="file" name="features_bg_image" accept="image/*" class="form-file">
                                <input type="hidden" name="features[bg_image]" value="<?= e($content['features']['bg_image'] ?? '') ?>">
                                <span class="file-hint">留空则保持当前图片不变</span>
                            </div>
                        </div>
                    </div>
                    <?php foreach (($content['features']['items'] ?? []) as $i => $item): ?>
                    <div class="form-section">
                        <h3 class="section-title">特色 <?= $i + 1 ?></h3>
                        <div class="form-group"><label>标题</label><input type="text" name="features[items][<?= $i ?>][title]" value="<?= e($item['title'] ?? '') ?>" class="form-input"></div>
                        <div class="form-group"><label>描述</label><textarea name="features[items][<?= $i ?>][desc]" class="form-input" rows="2"><?= e($item['desc'] ?? '') ?></textarea></div>
                        <div class="form-group">
                            <label>图标</label>
                            <div class="image-upload-group">
                                <?php if (!empty($item['icon'])): ?><img <?= $imgAttr($item['icon'], 'features') ?> class="preview-img small" alt=""><?php endif; ?>
                                <input type="file" name="features_icon_<?= $i ?>" accept="image/*" class="form-file">
                                <input type="hidden" name="features[items][<?= $i ?>][icon]" value="<?= e($item['icon'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div id="tab-features" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-gallery" class="tab-pane" style="display: <?= $currentTab === 'gallery' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="gallery">
                    <div class="form-section">
                        <h3 class="section-title">游戏截图板块</h3>
                        <div class="form-row">
                            <div class="form-group"><label>板块标题</label><input type="text" name="gallery[title]" value="<?= e($content['gallery']['title'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>板块副标题</label><input type="text" name="gallery[subtitle]" value="<?= e($content['gallery']['subtitle'] ?? '') ?>" class="form-input"></div>
                        </div>
                        <div class="form-group">
                            <label>板块背景图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['gallery']['bg_image'])): ?><img <?= $imgAttr($content['gallery']['bg_image'], 'gallery') ?> class="preview-img" alt=""><?php endif; ?>
                                <input type="file" name="gallery_bg_image" accept="image/*" class="form-file">
                                <input type="hidden" name="gallery[bg_image]" value="<?= e($content['gallery']['bg_image'] ?? '') ?>">
                                <span class="file-hint">留空则保持当前图片不变</span>
                            </div>
                        </div>
                    </div>
                    <?php foreach (($content['gallery']['items'] ?? []) as $i => $item): ?>
                    <div class="form-section" id="gallery-item-<?= $i ?>">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                            <h3 class="section-title" style="margin:0" id="gallery-title-<?= $i ?>">截图 <?= $i + 1 ?></h3>
                            <button type="button" onclick="markGalleryDelete(<?= $i ?>)" style="border:1px solid #fecaca;background:#fef2f2;color:#ef4444;border-radius:8px;padding:5px 12px;cursor:pointer;font-size:0.85rem;">删除截图</button>
                        </div>
                        <input type="hidden" name="gallery[items][<?= $i ?>][_delete]" value="0" id="gallery-del-<?= $i ?>">
                        <div class="form-group"><label>图片说明</label><input type="text" name="gallery[items][<?= $i ?>][caption]" value="<?= e($item['caption'] ?? '') ?>" class="form-input"></div>
                        <div class="form-group">
                            <label>图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($item['src'])): ?><img <?= $imgAttr($item['src'], 'gallery') ?> class="preview-img" alt=""><?php endif; ?>
                                <input type="file" name="gallery_img_<?= $i ?>" accept="image/*" class="form-file">
                                <input type="hidden" name="gallery[items][<?= $i ?>][src]" value="<?= e($item['src'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-section">
                        <h3 class="section-title">添加新截图</h3>
                        <div class="form-group"><label>图片说明</label><input type="text" name="gallery_new_caption" class="form-input" placeholder="输入图片描述..."></div>
                        <div class="form-group">
                            <label>上传图片</label>
                            <div class="image-upload-group">
                                <input type="file" name="gallery_new_img" accept="image/*" class="form-file">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div id="tab-gallery" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-team" class="tab-pane" style="display: <?= $currentTab === 'team' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="team">
                    <div class="form-section">
                        <h3 class="section-title">管理团队板块</h3>
                        <div class="form-row">
                            <div class="form-group"><label>板块标题</label><input type="text" name="team[title]" value="<?= e($content['team']['title'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>板块副标题</label><input type="text" name="team[subtitle]" value="<?= e($content['team']['subtitle'] ?? '') ?>" class="form-input"></div>
                        </div>
                        <div class="form-group">
                            <label>板块背景图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['team']['bg_image'])): ?><img <?= $imgAttr($content['team']['bg_image'], 'team') ?> class="preview-img" alt=""><?php endif; ?>
                                <input type="file" name="team_bg_image" accept="image/*" class="form-file">
                                <input type="hidden" name="team[bg_image]" value="<?= e($content['team']['bg_image'] ?? '') ?>">
                                <span class="file-hint">留空则保持当前图片不变</span>
                            </div>
                        </div>
                    </div>
                    <?php foreach (($content['team']['members'] ?? []) as $i => $member): ?>
                    <div class="form-section" id="team-member-<?= $i ?>">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                            <h3 class="section-title" style="margin:0" id="team-title-<?= $i ?>">成员 <?= $i + 1 ?>: <?= e($member['name'] ?? '') ?></h3>
                            <button type="button" onclick="markTeamMemberDelete(<?= $i ?>)" style="border:1px solid #fecaca;background:#fef2f2;color:#ef4444;border-radius:8px;padding:5px 12px;cursor:pointer;font-size:0.85rem;">删除成员</button>
                        </div>
                        <input type="hidden" name="team[members][<?= $i ?>][_delete]" value="0" id="team-del-<?= $i ?>">
                        <div class="form-row">
                            <div class="form-group"><label>名称</label><input type="text" name="team[members][<?= $i ?>][name]" value="<?= e($member['name'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>英文职位</label><input type="text" name="team[members][<?= $i ?>][role]" value="<?= e($member['role'] ?? '') ?>" class="form-input"></div>
                        </div>
                        <div class="form-group"><label>描述</label><textarea name="team[members][<?= $i ?>][desc]" class="form-input" rows="2"><?= e($member['desc'] ?? '') ?></textarea></div>
                        <div class="form-group"><label>联系链接</label><input type="text" name="team[members][<?= $i ?>][contact_link]" value="<?= e($member['contact_link'] ?? '') ?>" class="form-input" placeholder="如: https://example.com 或 #contact"></div>
                        <div class="form-group">
                            <label>头像</label>
                            <div class="image-upload-group">
                                <?php if (!empty($member['avatar'])): ?><img <?= $imgAttr($member['avatar'], 'team') ?> class="preview-img small round" alt=""><?php endif; ?>
                                <input type="file" name="team_avatar_<?= $i ?>" accept="image/*" class="form-file">
                                <input type="hidden" name="team[members][<?= $i ?>][avatar]" value="<?= e($member['avatar'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-section">
                        <h3 class="section-title">添加新成员</h3>
                        <div class="form-row">
                            <div class="form-group"><label>名称</label><input type="text" name="team_new_name" class="form-input" placeholder="成员名称..."></div>
                            <div class="form-group"><label>英文职位</label><input type="text" name="team_new_role" class="form-input" placeholder="如: Moderator"></div>
                        </div>
                        <div class="form-group"><label>描述</label><textarea name="team_new_desc" class="form-input" rows="2" placeholder="成员职责描述..."></textarea></div>
                        <div class="form-group"><label>联系链接</label><input type="text" name="team_new_contact_link" class="form-input" placeholder="如: https://example.com 或 #contact"></div>
                        <div class="form-group">
                            <label>头像</label>
                            <div class="image-upload-group">
                                <input type="file" name="team_new_avatar" accept="image/*" class="form-file">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div id="tab-team" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-contact" class="tab-pane" style="display: <?= $currentTab === 'contact' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="contact">
                    <div class="form-section">
                        <h3 class="section-title">联系我们板块</h3>
                        <div class="form-group">
                            <label>板块背景图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['contact']['bg_image'])): ?><img <?= $imgAttr($content['contact']['bg_image'], 'contact') ?> class="preview-img" alt=""><?php endif; ?>
                                <input type="file" name="contact_bg_image" accept="image/*" class="form-file">
                                <input type="hidden" name="contact[bg_image]" value="<?= e($content['contact']['bg_image'] ?? '') ?>">
                                <span class="file-hint">留空则保持当前图片不变</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div id="tab-contact" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <div id="tab-monitor" class="tab-pane" style="display: <?= $currentTab === 'monitor' ? 'block' : 'none' ?>">

                <div style="display:flex;align-items:center;gap:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:0.88rem;color:#92400e;line-height:1.5;">
                    <svg style="flex-shrink:0;color:#d97706;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span><strong>注意：</strong>服务器监控功能仅适用于雨云 <strong>游戏云（RGS）</strong> 实例，其他服务商或自建/网易山头服暂不支持。</span>
                </div>

                <div class="form-section">
                    <h3 class="section-title">
                        实时监控
                        <span id="monStatusBadge" class="mon-status-badge" style="display:none;"></span>
                        <span id="monRefreshHint" style="margin-left:auto;font-size:0.78rem;font-weight:400;color:var(--text-muted);display:none;">
                            <span style="width:7px;height:7px;background:var(--green);border-radius:50%;display:inline-block;animation:pulse-live 2s infinite;vertical-align:middle;"></span>
                            每 3 秒自动刷新
                        </span>
                    </h3>
                    <div id="monLiveWrap" class="mon-live-wrap">
                        <div class="mon-live-gauges">
                            <div class="mon-mini-gauge">
                                <div class="mon-mini-ring-wrap">
                                    <svg viewBox="0 0 110 110" class="mon-mini-svg">
                                        <circle class="mon-ring-bg" cx="55" cy="55" r="42" stroke-width="7"/>
                                        <circle class="mon-ring-fill" id="gaugeFilCpu" cx="55" cy="55" r="42" stroke="#10b981" stroke-width="7" stroke-dasharray="263.9" stroke-dashoffset="263.9"/>
                                    </svg>
                                    <div class="mon-mini-center"><span class="mon-mini-pct" id="gaugePctCpu">—</span></div>
                                </div>
                                <div class="mon-mini-label">CPU</div>
                            </div>
                            <div class="mon-mini-gauge">
                                <div class="mon-mini-ring-wrap">
                                    <svg viewBox="0 0 110 110" class="mon-mini-svg">
                                        <circle class="mon-ring-bg" cx="55" cy="55" r="42" stroke-width="7"/>
                                        <circle class="mon-ring-fill" id="gaugeFilMem" cx="55" cy="55" r="42" stroke="#10b981" stroke-width="7" stroke-dasharray="263.9" stroke-dashoffset="263.9"/>
                                    </svg>
                                    <div class="mon-mini-center"><span class="mon-mini-pct" id="gaugePctMem">—</span></div>
                                </div>
                                <div class="mon-mini-label">内存</div>
                            </div>
                        </div>
                        <div class="mon-live-right">
                            <div class="mon-bw-row">
                                <span class="mon-bw-up">↑ <span id="gaugeValUp">—</span></span>
                                <span class="mon-bw-dn">↓ <span id="gaugeValDown">—</span></span>
                            </div>
                            <div id="monDisksContainer"></div>
                        </div>
                    </div>
                    <div id="monNotConfigured" style="display:none;text-align:center;padding:24px 0;color:var(--text-muted);">
                        请在下方配置雨云 API 密钥和实例 ID 以启用监控
                    </div>
                    <div id="monError" class="mon-error-msg" style="display:none;"></div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">
                        服务器信息
                        <div class="mon-action-bar">
                            <button class="mon-action-btn sm start" id="monBtnStart" onclick="monAction('start')" title="开机"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>开机</button>
                            <button class="mon-action-btn sm restart" id="monBtnRestart" onclick="monAction('restart')" title="重启"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>重启</button>
                            <button class="mon-action-btn sm stop" id="monBtnStop" onclick="monAction('stop')" title="关机"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>关机</button>
                            <button class="mon-action-btn sm reset-pass" id="monBtnResetPass" onclick="monAction('reset_pass')" title="重置密码"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>重置密码</button>
                        </div>
                    </h3>
                    <div id="monActionMsg" class="mon-action-msg" style="display:none;"></div>
                    <div class="mon-rows">
                        <div class="mon-row"><span class="mon-rl">产品 ID</span><span class="mon-rv" id="monProductId">—</span></div>
                        <div class="mon-row"><span class="mon-rl">标签</span><span class="mon-rv" id="monTag">—</span></div>
                        <div class="mon-row"><span class="mon-rl">运行状态</span><span class="mon-rv" id="monStatus"><span class="mon-dot stopped"></span><strong style="color:var(--text-muted)">加载中…</strong></span></div>
                        <div class="mon-row"><span class="mon-rl">节点</span><span class="mon-rv" id="monNode">—</span></div>
                        <div class="mon-row"><span class="mon-rl">剩余可用 CPU 点数</span><span class="mon-rv mon-accent" id="monCpuPower">—</span></div>
                        <div class="mon-row"><span class="mon-rl">每日消耗积分</span><span class="mon-rv" id="monDailyCost">—</span></div>
                        <div class="mon-row"><span class="mon-rl">创建日期</span><span class="mon-rv" id="monCreateDate">—</span></div>
                        <div class="mon-row" style="border-bottom:none"><span class="mon-rl">到期日期</span><span class="mon-rv" id="monExpire">—</span></div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">远程连接</h3>
                    <div class="mon-rows">
                        <div class="mon-row">
                            <span class="mon-rl">远程连接地址 (RDP/SSH)</span>
                            <span class="mon-rv"><strong id="monRdpAddr">—</strong><button class="mon-copy-btn" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('monRdpAddr').textContent)">复制</button></span>
                        </div>
                        <div class="mon-row">
                            <span class="mon-rl">远程用户名</span>
                            <span class="mon-rv"><strong id="monRdpUser">—</strong><button class="mon-copy-btn" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('monRdpUser').textContent)">复制</button></span>
                        </div>
                        <div class="mon-row" style="border-bottom:none">
                            <span class="mon-rl">远程密码</span>
                            <span class="mon-rv" id="monPwRow">
                                <span id="monPwDots" style="font-family:monospace;letter-spacing:2px">••••••••••</span>
                                <button class="mon-copy-btn" id="monPwCopyBtn" onclick="monCopyPw()">复制</button>
                                <button class="mon-copy-btn" id="monPwToggleBtn" onclick="monTogglePw()">查看</button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">配置信息</h3>
                    <div class="mon-rows">
                        <div class="mon-row"><span class="mon-rl">套餐</span><span class="mon-rv"><strong id="monPlan">—</strong></span></div>
                        <div class="mon-row"><span class="mon-rl">配置</span><span class="mon-rv" id="monSpecs">—</span></div>
                        <div class="mon-row"><span class="mon-rl">操作系统</span><span class="mon-rv" id="monOs">—</span></div>
                        <div class="mon-row"><span class="mon-rl">网络区域</span><span class="mon-rv" id="monZone">—</span></div>
                        <div class="mon-row" style="border-bottom:none"><span class="mon-rl">NAT 公网 IP</span><span class="mon-rv" id="monNatIp">—</span></div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">NAT 端口映射</h3>
                    <div class="mon-rows" id="monNatList">
                        <div class="mon-row" style="border-bottom:none"><span class="mon-rl" style="color:var(--text-muted);">加载中…</span></div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">雨云 API 配置</h3>
                    <form method="POST" action="save.php" data-ajax="true">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="tab" value="monitor_settings">
                        <div class="form-group">
                            <label>雨云 API 密钥 (x-api-key)</label>
                            <div style="position:relative;">
                                <input type="password" id="rainyunApiKeyInput" name="rainyun_api_key" value="" class="form-input" autocomplete="new-password" style="padding-right:64px;" placeholder="<?= !empty($settings['rainyun_api_key']) ? '已保存，留空不改' : '在雨云用户中心 → API 管理中生成' ?>">
                                <button type="button" onclick="toggleSensitiveField('rainyunApiKeyInput')" title="显示/隐藏" style="position:absolute;right:30px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;font-size:14px;padding:4px;">👁</button>
                                <input type="hidden" name="rainyun_api_key_clear" id="rainyunApiKeyClear" value="">
                                <?php if (!empty($settings['rainyun_api_key'])): ?>
                                <button type="button" onclick="clearSensitiveField('rainyunApiKeyInput','rainyunApiKeyClear','rainyunApiKeyHint','清除已保存的 API Key 后监控功能将失效')" title="清除已保存的 API Key" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#dc2626;font-size:14px;padding:4px;">✕</button>
                                <?php endif; ?>
                            </div>
                            <small id="rainyunApiKeyHint" style="display:none;color:#dc2626;margin-top:4px;font-size:0.78rem;">保存后将清除已保存的 API Key</small>
                        </div>
                        <div class="form-group">
                            <label>RGS 实例 ID</label>
                            <input type="text" name="rainyun_rgs_id" value="<?= e($settings['rainyun_rgs_id'] ?? '') ?>" class="form-input" placeholder="例如: 86524">
                        </div>
                        <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:16px;">配置完成后，监控数据将自动从雨云 API 实时获取并展示。API 密钥仅存储在服务端，不会暴露到前端。</p>
                        <div class="form-actions">
                            <button type="submit" class="btn-save">保存配置</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="tab-messages" class="tab-pane" style="display: <?= $currentTab === 'messages' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h3 class="section-title" style="margin-bottom:0;padding-bottom:0;border-bottom:none;">收件箱 <span id="msgCountLabel" style="font-weight:400;font-size:0.85em;color:var(--text-muted);"></span></h3>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span id="msgLiveIndicator" style="display:inline-flex;align-items:center;gap:6px;font-size:0.8em;color:var(--green);">
                                <span style="width:7px;height:7px;background:var(--green);border-radius:50%;display:inline-block;animation:pulse-live 2s infinite;"></span>
                                实时刷新中
                            </span>
                        </div>
                    </div>
                    <div id="messagesList" class="messages-list"></div>
                    <div id="msgPagination" style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:20px;flex-wrap:wrap;"></div>
                </div>

                <div class="form-section">
                    <details class="message-settings-panel">
                        <summary>
                            <span>消息通知设置</span>
                            <span>免打扰、邮件、清理和白名单</span>
                        </summary>
                    <form method="POST" action="save.php" data-ajax="true">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="tab" value="messages_settings">
                        
                        <div class="message-toggle-grid">
                            <div class="message-toggle-card">
                                <div>
                                    <strong>免打扰模式</strong>
                                    <p>开启后不发送邮件通知</p>
                                </div>
                                <label class="switch">
                                    <input class="toggle" type="checkbox" name="dnd_mode" value="1" <?= !empty($settings['dnd_mode']) ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="message-toggle-card">
                                <div>
                                    <strong>工单回复邮件</strong>
                                    <p>回复工单时同步发送邮件</p>
                                </div>
                                <label class="switch">
                                    <input class="toggle" type="checkbox" name="ticket_reply_mail_enabled" value="1" <?= !empty($settings['ticket_reply_mail_enabled']) ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>自动清理已读消息 (天)</label>
                                <input type="number" name="msg_auto_clean_days" value="<?= e($settings['msg_auto_clean_days'] ?? '0') ?>" class="form-input" min="0" max="3650" placeholder="0 表示不自动清理">
                                <p style="margin:4px 0 0;font-size:0.82em;color:var(--text-muted);">填 0 或留空则不清理；例如填 30 表示自动删除 30 天前的已读消息</p>
                            </div>
                            <div class="form-group">
                                <label>每页显示消息数</label>
                                <input type="number" name="msg_per_page" value="<?= e($settings['msg_per_page'] ?? '10') ?>" class="form-input" min="5" max="100" placeholder="默认 10">
                                <p style="margin:4px 0 0;font-size:0.82em;color:var(--text-muted);">每页展示多少条消息，范围 5~100</p>
                            </div>
                        </div>

                        <div class="form-section" style="margin:20px 0;padding:20px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                                <div>
                                    <h4 style="margin:0;color:var(--text-primary);font-size:1em;">邮箱后缀白名单</h4>
                                    <p style="margin:4px 0 0;font-size:0.85em;color:var(--text-muted);">开启后，仅允许指定邮箱后缀的用户提交消息</p>
                                </div>
                                <label class="switch">
                                    <input class="toggle" type="checkbox" name="email_whitelist_enabled" value="1" <?= !empty($settings['email_whitelist_enabled']) ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>允许的邮箱后缀 (每行一个，例如 qq.com)</label>
                                <textarea name="email_whitelist_text" class="form-input" rows="4" placeholder="qq.com&#10;163.com&#10;gmail.com"><?= e(implode("\n", $settings['email_whitelist'] ?? [])) ?></textarea>
                            </div>
                        </div>

                        <details>
                            <summary style="cursor:pointer;margin:15px 0;color:var(--green-dark);font-weight:500;">配置 SMTP 邮件服务器 (点击展开)</summary>
                            <div style="background:#f8fafc;padding:20px;border-radius:10px;margin-bottom:20px;">
                                <div class="form-row">
                                    <div class="form-group"><label>SMTP 主机</label><input type="text" name="smtp_host" value="<?= e($settings['smtp_host'] ?? '') ?>" class="form-input" placeholder="例如: smtp.qq.com"></div>
                                    <div class="form-group"><label>SMTP 端口</label><input type="text" name="smtp_port" value="<?= e($settings['smtp_port'] ?? '587') ?>" class="form-input" placeholder="例如: 465 或 587"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group"><label>SMTP 用户名 (邮箱账号)</label><input type="text" name="smtp_user" value="<?= e($settings['smtp_user'] ?? '') ?>" class="form-input"></div>
                                    <div class="form-group">
                                        <label>SMTP 密码 (授权码)</label>
                                        <div style="position:relative;">
                                            <input type="password" id="smtpPassInput" name="smtp_pass" value="" class="form-input" autocomplete="new-password" style="padding-right:64px;" placeholder="<?= !empty($settings['smtp_pass']) ? '已保存，留空不改' : '邮箱授权码或 SMTP 密码' ?>">
                                            <button type="button" onclick="toggleSensitiveField('smtpPassInput')" title="显示/隐藏" style="position:absolute;right:30px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;font-size:14px;padding:4px;">👁</button>
                                            <input type="hidden" name="smtp_pass_clear" id="smtpPassClear" value="">
                                            <?php if (!empty($settings['smtp_pass'])): ?>
                                            <button type="button" onclick="clearSensitiveField('smtpPassInput','smtpPassClear','smtpPassHint','清除已保存的 SMTP 密码后所有邮件功能将失效')" title="清除已保存的密码" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#dc2626;font-size:14px;padding:4px;">✕</button>
                                            <?php endif; ?>
                                        </div>
                                        <small id="smtpPassHint" style="display:none;color:#dc2626;margin-top:4px;font-size:0.78rem;">保存后将清除已保存的 SMTP 密码</small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group"><label>发件人邮箱</label><input type="email" name="smtp_from_email" value="<?= e($settings['smtp_from_email'] ?? '') ?>" class="form-input"></div>
                                    <div class="form-group"><label>发件人名称</label><input type="text" name="smtp_from_name" value="<?= e($settings['smtp_from_name'] ?? 'FoxMC Admin') ?>" class="form-input"></div>
                                </div>
                                <div class="form-group"><label>通知接收邮箱 (留空则发给SMTP用户)</label><input type="email" name="notification_email" value="<?= e($settings['notification_email'] ?? '') ?>" class="form-input"></div>
                                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px;margin-bottom:14px;">
                                    <label style="display:block;font-weight:600;color:#15803d;margin-bottom:8px;">SMTP 测试发送</label>
                                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                        <input type="email" id="smtpTestEmailInput" class="form-input" value="<?= e($settings['notification_email'] ?? $settings['smtp_user'] ?? '') ?>" placeholder="测试收件邮箱" style="flex:1;min-width:220px;">
                                        <button type="button" onclick="sendSmtpTestMail()" style="border:none;padding:9px 16px;border-radius:8px;background:#16a34a;color:#fff;cursor:pointer;">发送测试邮件</button>
                                    </div>
                                    <div style="font-size:.82em;color:#64748b;margin-top:8px;">请先保存 SMTP 配置，再发送测试邮件。</div>
                                </div>
                                <div class="form-group"><label>回复邮件模板 ({name}, {subject}, {reply_content}, {site} 为占位符)</label><textarea name="reply_email_template" class="form-input" rows="4"><?= e($settings['reply_email_template'] ?? ("亲爱的 {name}，</br>\n\n您好！</br>\n\n我们已收到您关于「{subject}」的反馈，以下是我们的回复：</br>\n\n{reply_content}</br>\n\n如有其他问题，欢迎随时联系我们。</br>\n\n此致</br>\n" . ((function_exists('getSiteTitle') && getSiteTitle() !== '') ? getSiteTitle() . ' 管理团队' : 'FoxMC 管理团队'))) ?></textarea></div>
                            </div>
                        </details>
                        <div class="message-settings-actions">
                            <button type="submit" class="btn-save">保存设置</button>
                        </div>
                    </form>
                    </details>
                </div>
            </div>

            <div id="tab-users" class="tab-pane" style="display: <?= $currentTab === 'users' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">
                        用户管理
                        <span style="margin-left:auto;font-size:0.8em;font-weight:400;color:var(--text-muted);">管理已注册的 Minecraft 用户</span>
                    </h3>

                    <?php if (!isUserSystemInstalled()): ?>
                    <div style="background:linear-gradient(135deg,rgba(56,189,248,0.1),rgba(139,92,246,0.1));border:1px solid rgba(56,189,248,0.3);border-radius:12px;padding:24px;margin-bottom:20px;text-align:center;">
                        <div style="font-size:2em;margin-bottom:10px;">🔧</div>
                        <h4 style="margin-bottom:6px;">用户系统尚未安装</h4>
                        <p style="color:var(--text-muted);font-size:0.9em;margin-bottom:14px;">需要先连接 MySQL 数据库才能使用用户管理功能</p>
                        <a href="../install/" style="display:inline-block;padding:10px 24px;background:linear-gradient(135deg,#38bdf8,#0ea5e9);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.95em;transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">一键安装</a>
                    </div>
                    <?php endif; ?>

                    <!-- 搜索 & 筛选栏 -->
                    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
                        <div style="flex:1;min-width:200px;position:relative;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="userSearchInput" class="form-input" placeholder="搜索用户名 / 游戏ID / 邮箱..." style="padding-left:36px;">
                        </div>
                        <select id="userStatusFilter" class="form-input" style="width:auto;min-width:120px;">
                            <option value="">全部状态</option>
                            <option value="active">正常</option>
                            <option value="banned">已封禁</option>
                        </select>
                        <button type="button" id="createUserBtn" onclick="openCreateUserModal()" title="手动创建新的用户账号，可直接指定用户角色" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,var(--green,#10b981),var(--green-dark,#059669));color:#fff;border:none;padding:9px 18px;border-radius:10px;font-weight:600;font-size:0.9em;cursor:pointer;transition:transform .15s,box-shadow .15s;white-space:nowrap;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 10px rgba(16,185,129,.35)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            添加用户
                        </button>
                    </div>
                    <details class="batch-notice-panel">
                        <summary>
                            <span>批量通知</span>
                            <small>按当前搜索和状态筛选发送，单次最多 500 人</small>
                        </summary>
                        <div class="batch-notice-form">
                            <input type="text" id="batchNotifTitleInput" class="form-input" placeholder="通知标题">
                            <textarea id="batchNotifContentInput" class="form-input" rows="3" placeholder="通知内容"></textarea>
                            <div class="batch-notice-actions">
                                <button type="button" class="batch-notice-submit" onclick="sendBatchUserNotification()">发送批量通知</button>
                            </div>
                        </div>
                    </details>

                    <!-- 统计卡片 -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px;">
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;text-align:center;"><div id="usersStatTotal" style="font-size:1.5em;font-weight:700;color:#334155;">-</div><div style="font-size:0.85em;color:#64748b;">总用户数</div></div>
                        <div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:14px;text-align:center;"><div id="usersStatPendingApps" style="font-size:1.5em;font-weight:700;color:#ca8a04;">-</div><div style="font-size:0.85em;color:#a16207;">待审核申请</div></div>
                        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;text-align:center;"><div id="usersStatBanned" style="font-size:1.5em;font-weight:700;color:#dc2626;">-</div><div style="font-size:0.85em;color:#b91c1c;">已封禁</div></div>
                        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px;text-align:center;"><div id="usersStatNew" style="font-size:1.5em;font-weight:700;color:#2563eb;">-</div><div style="font-size:0.85em;color:#1d4ed8;">本周新增</div></div>
                    </div>

                    <!-- 用户列表表格 -->
                    <div class="users-table-wrap" style="overflow-x:auto;border:1px solid #e2e8f0;border-radius:10px;background:#fff;">
                        <table class="users-table" style="width:100%;border-collapse:collapse;font-size:0.9em;">
                            <thead>
                                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                                    <th style="padding:12px 14px;text-align:left;font-weight:600;color:var(--text-primary);white-space:nowrap;">用户名</th>
                                    <th style="padding:12px 14px;text-align:left;font-weight:600;color:var(--text-primary);white-space:nowrap;">游戏ID</th>
                                    <th style="padding:12px 14px;text-align:left;font-weight:600;color:var(--text-primary);white-space:nowrap;">邮箱</th>
                                    <th style="padding:12px 14px;text-align:center;font-weight:600;color:var(--text-primary);white-space:nowrap;">账号状态</th>
                                    <th style="padding:12px 14px;text-align:center;font-weight:600;color:var(--text-primary);white-space:nowrap;">申请状态</th>
                                    <th style="padding:12px 14px;text-align:center;font-weight:600;color:var(--text-primary);white-space:nowrap;">资料完善度</th>
                                    <th style="padding:12px 14px;text-align:center;font-weight:600;color:var(--text-primary);white-space:nowrap;">注册时间</th>
                                    <th style="padding:12px 14px;text-align:center;font-weight:600;color:var(--text-primary);white-space:nowrap;">操作</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <tr><td colspan="8" style="padding:40px;text-align:center;color:#94a3b8;">加载中...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div id="usersPagination" style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:16px;"></div>
                </div>
            </div>

            <div id="tab-applications" class="tab-pane" style="display: <?= $currentTab === 'applications' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">
                        入服申请管理
                        <span style="margin-left:auto;font-size:0.8em;font-weight:400;color:var(--text-muted);">集中审核、编辑和处理白名单申请</span>
                    </h3>
                    <?php if (!isUserSystemInstalled()): ?>
                    <div style="background:linear-gradient(135deg,rgba(56,189,248,0.1),rgba(139,92,246,0.1));border:1px solid rgba(56,189,248,0.3);border-radius:12px;padding:24px;margin-bottom:20px;text-align:center;">
                        <div style="font-size:2em;margin-bottom:10px;">🔧</div>
                        <h4 style="margin-bottom:6px;">用户系统尚未安装</h4>
                        <p style="color:var(--text-muted);font-size:0.9em;margin-bottom:14px;">需要先连接 MySQL 数据库才能使用入服申请管理</p>
                        <a href="../install/" style="display:inline-block;padding:10px 24px;background:linear-gradient(135deg,#38bdf8,#0ea5e9);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.95em;">一键安装</a>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
                        <div style="flex:1;min-width:200px;position:relative;">
                            <input type="text" id="appSearchInput" class="form-input" placeholder="搜索用户名 / 游戏ID / 邮箱...">
                        </div>
                        <select id="appStatusFilter" class="form-input" style="width:auto;min-width:140px;">
                            <option value="">全部申请</option>
                            <option value="pending">待审核</option>
                            <option value="need_more_info">需补充</option>
                            <option value="approved">已通过</option>
                            <option value="rejected">已拒绝</option>
                        </select>
                        <button type="button" onclick="loadApplicationsList(1)" style="border:none;padding:10px 16px;border-radius:8px;background:#16a34a;color:#fff;cursor:pointer;">刷新</button>
                        <button type="button" onclick="exportWhitelistCommands()" style="border:1px solid #bbf7d0;padding:10px 16px;border-radius:8px;background:#f0fdf4;color:#15803d;cursor:pointer;font-weight:700;">复制全部白名单命令</button>
                    </div>
                    <div id="applicationsBatchBar" style="display:none;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px 14px;margin-bottom:16px;">
                        <strong style="color:#15803d;">已选择 <span id="applicationsSelectedCount">0</span> 个申请</strong>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="button" onclick="selectAllVisibleApplications()" style="border:1px solid #bbf7d0;padding:7px 12px;border-radius:8px;background:#fff;color:#15803d;cursor:pointer;">全选当前页</button>
                            <button type="button" onclick="clearSelectedApplications()" style="border:1px solid #e2e8f0;padding:7px 12px;border-radius:8px;background:#fff;color:#475569;cursor:pointer;">清空选择</button>
                            <button type="button" onclick="batchReviewSelectedApplications('approved')" style="border:none;padding:7px 12px;border-radius:8px;background:#16a34a;color:#fff;cursor:pointer;">批量通过</button>
                            <button type="button" onclick="batchReviewSelectedApplications('rejected')" style="border:none;padding:7px 12px;border-radius:8px;background:#dc2626;color:#fff;cursor:pointer;">批量拒绝</button>
                            <button type="button" onclick="batchReviewSelectedApplications('need_more_info')" style="border:none;padding:7px 12px;border-radius:8px;background:#2563eb;color:#fff;cursor:pointer;">批量需补充</button>
                            <button type="button" onclick="copySelectedWhitelistCommands()" style="border:none;padding:7px 12px;border-radius:8px;background:#16a34a;color:#fff;cursor:pointer;">复制选中白名单</button>
                            <button type="button" onclick="batchMarkSelectedSynced(1)" style="border:none;padding:7px 12px;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer;">批量标记同步</button>
                            <button type="button" onclick="batchMarkSelectedSynced(0)" style="border:none;padding:7px 12px;border-radius:8px;background:#64748b;color:#fff;cursor:pointer;">批量取消同步</button>
                            <button type="button" onclick="batchSyncSelectedApplications()" title="对选中的申请发起 RCON 自动同步，一次最多 50 条" style="border:none;padding:7px 12px;border-radius:8px;background:#7c3aed;color:#fff;cursor:pointer;">批量 RCON 同步</button>
                            <button type="button" onclick="retryAllFailedSyncs()" title="重试所有未同步成功的已审核申请" style="border:1px solid #ddd6fe;padding:7px 12px;border-radius:8px;background:#fff;color:#6d28d9;cursor:pointer;">重试全部失败</button>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px;">
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;text-align:center;"><div id="appsStatTotal" style="font-size:1.5em;font-weight:700;color:#334155;">-</div><div style="font-size:0.85em;color:#64748b;">全部</div></div>
                        <div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:14px;text-align:center;"><div id="appsStatPending" style="font-size:1.5em;font-weight:700;color:#ca8a04;">-</div><div style="font-size:0.85em;color:#a16207;">待审核</div></div>
                        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px;text-align:center;"><div id="appsStatNeedInfo" style="font-size:1.5em;font-weight:700;color:#2563eb;">-</div><div style="font-size:0.85em;color:#1d4ed8;">需补充</div></div>
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;text-align:center;"><div id="appsStatApproved" style="font-size:1.5em;font-weight:700;color:#16a34a;">-</div><div style="font-size:0.85em;color:#15803d;">已通过</div></div>
                        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;text-align:center;"><div id="appsStatRejected" style="font-size:1.5em;font-weight:700;color:#dc2626;">-</div><div style="font-size:0.85em;color:#b91c1c;">已拒绝</div></div>
                        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px;text-align:center;"><div id="appsStatUnsynced" style="font-size:1.5em;font-weight:700;color:#2563eb;">-</div><div style="font-size:0.85em;color:#1d4ed8;">未同步</div></div>
                    </div>
                    <div id="applicationsList" style="display:grid;gap:12px;"></div>
                    <div id="applicationsPagination" style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:16px;"></div>
                </div>

                <div class="form-section">
                    <details class="application-settings-panel">
                        <summary class="application-settings-summary">
                            <span>申请与通知配置</span>
                            <span>低频设置，点击展开</span>
                        </summary>
                    <form method="POST" action="save.php" data-ajax="true" class="application-settings-form">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="tab" value="application_settings">
                        <div class="settings-panel-head">
                            <div>
                                <h4>配置详情</h4>
                                <p>下方是低频配置项；0 表示不限制，通知邮箱使用消息通知里的 SMTP 接收邮箱</p>
                            </div>
                            <button type="submit" class="btn-save small">保存配置</button>
                        </div>
                        <div class="application-settings-grid">
                            <div class="settings-card settings-card-toggles">
                                <div class="settings-card-title">通知与自动化</div>
                                <label class="settings-toggle-row">
                                    <span>新申请邮件通知</span>
                                    <label class="switch"><input class="toggle" type="checkbox" name="application_notify_enabled" value="1" <?= array_key_exists('application_notify_enabled', $settings) ? (!empty($settings['application_notify_enabled']) ? 'checked' : '') : 'checked' ?>><span class="slider"></span></label>
                                </label>
                                <label class="settings-toggle-row">
                                    <span>入服申请免打扰</span>
                                    <label class="switch"><input class="toggle" type="checkbox" name="application_dnd_mode" value="1" <?= !empty($settings['application_dnd_mode']) ? 'checked' : '' ?>><span class="slider"></span></label>
                                </label>
                                <label class="settings-toggle-row">
                                    <span>通过后发送入服指南</span>
                                    <label class="switch"><input class="toggle" type="checkbox" name="join_guide_enabled" value="1" <?= array_key_exists('join_guide_enabled', $settings) ? (!empty($settings['join_guide_enabled']) ? 'checked' : '') : 'checked' ?>><span class="slider"></span></label>
                                </label>
                                <label class="settings-toggle-row">
                                    <span>审核结果发送邮件</span>
                                    <label class="switch"><input class="toggle" type="checkbox" name="application_review_mail_enabled" value="1" <?= !empty($settings['application_review_mail_enabled']) ? 'checked' : '' ?>><span class="slider"></span></label>
                                </label>
                            </div>
                            <div class="settings-card">
                                <div class="settings-card-title">提交限制</div>
                                <div class="compact-field-grid">
                                    <div class="form-group">
                                        <label>冷却时间（小时）</label>
                                        <input type="number" min="0" max="8760" name="application_cooldown_hours" value="<?= e((string)($settings['application_cooldown_hours'] ?? 0)) ?>" class="form-input" placeholder="例如 24">
                                    </div>
                                    <div class="form-group">
                                        <label>最多提交次数</label>
                                        <input type="number" min="0" max="999" name="application_max_submissions" value="<?= e((string)($settings['application_max_submissions'] ?? 0)) ?>" class="form-input" placeholder="例如 3">
                                    </div>
                                </div>
                            </div>
                            <div class="settings-card settings-card-wide">
                                <div class="settings-card-title">入服指南内容</div>
                                <div class="guide-field-grid">
                                    <div class="form-group"><label>服务器 IP</label><input type="text" name="join_guide_server_ip" value="<?= e((string)($settings['join_guide_server_ip'] ?? '')) ?>" class="form-input" placeholder="play.example.com"></div>
                                    <div class="form-group"><label>推荐版本</label><input type="text" name="join_guide_version" value="<?= e((string)($settings['join_guide_version'] ?? '')) ?>" class="form-input" placeholder="1.20.1"></div>
                                    <div class="form-group"><label>交流群</label><input type="text" name="join_guide_group" value="<?= e((string)($settings['join_guide_group'] ?? '')) ?>" class="form-input" placeholder="QQ群 / Discord 链接"></div>
                                    <div class="form-group"><label>资源包链接</label><input type="text" name="join_guide_resource_pack" value="<?= e((string)($settings['join_guide_resource_pack'] ?? '')) ?>" class="form-input"></div>
                                    <div class="form-group"><label>新人教程链接</label><input type="text" name="join_guide_tutorial" value="<?= e((string)($settings['join_guide_tutorial'] ?? '')) ?>" class="form-input"></div>
                                    <div class="form-group"><label>规则页面链接</label><input type="text" name="join_guide_rules" value="<?= e((string)($settings['join_guide_rules'] ?? '')) ?>" class="form-input"></div>
                                    <div class="form-group guide-contact-field"><label>管理员联系方式</label><input type="text" name="join_guide_contact" value="<?= e((string)($settings['join_guide_contact'] ?? '')) ?>" class="form-input"></div>
                                </div>
                            </div>
                            <div class="settings-card settings-card-wide rcon-settings-card">
                                <details class="rcon-guide">
                                    <summary style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:12px;font-weight:800;color:var(--text-main);">
                                        <span>白名单自动同步向导</span>
                                        <span style="font-size:0.82rem;color:var(--text-muted);font-weight:500;">可选功能，点击展开</span>
                                    </summary>
                                    <div style="margin-top:12px;">
                                        <input type="hidden" name="whitelist_sync_mode" value="rcon">
                                        <div class="rcon-guide-main">
                                            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:12px;">
                                                <strong style="display:block;color:#c2410c;margin-bottom:8px;">服务器只要这样设置</strong>
                                                <div style="background:#fff;border:1px solid #ffedd5;border-radius:10px;padding:10px;font-family:Consolas,monospace;font-size:0.84rem;line-height:1.7;color:#7c2d12;">
                                                    enable-rcon=true<br>
                                                    rcon.port=25575<br>
                                                    rcon.password=自己设置一个密码
                                                </div>
                                                <p style="margin:8px 0 0;color:#9a3412;font-size:0.84rem;line-height:1.6;">保存后重启服务器，并仅向网站服务器 IP 放行这个端口，不要直接暴露到公网。</p>
                                            </div>
                                            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:12px;">
                                                <div class="settings-toggle-row" style="margin:0 0 10px;padding:0;border:none;">
                                                    <strong style="color:#15803d;">审核后自动执行命令</strong>
                                                    <label class="switch"><input class="toggle" type="checkbox" name="whitelist_sync_enabled" value="1" <?= !empty($settings['whitelist_sync_enabled']) ? 'checked' : '' ?>><span class="slider"></span></label>
                                                </div>
                                                <div class="rcon-connect-grid">
                                                    <div class="form-group" style="margin:0;"><label>服务器地址</label><input type="text" id="rconHostInput" name="rcon_host" value="<?= e((string)($settings['rcon_host'] ?? '')) ?>" class="form-input" placeholder="IP 或域名" autocomplete="off"></div>
                                                    <div class="form-group" style="margin:0;"><label>端口</label><input type="number" id="rconPortInput" min="1" max="65535" name="rcon_port" value="<?= e((string)($settings['rcon_port'] ?? 25575)) ?>" class="form-input"></div>
                                                    <div class="form-group" style="margin:0;">
                                                        <label>密码</label>
                                                        <div style="position:relative;">
                                                            <input type="password" id="rconPasswordInput" name="rcon_password" value="" class="form-input" autocomplete="new-password" style="padding-right:64px;" placeholder="<?= !empty($settings['rcon_password']) ? '已保存，留空不改' : 'RCON 密码' ?>">
                                                            <button type="button" onclick="toggleRconPasswordVisibility()" title="显示/隐藏" style="position:absolute;right:30px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;font-size:14px;padding:4px;">👁</button>
                                                            <input type="hidden" name="rcon_password_clear" id="rconPasswordClear" value="">
                                                            <?php if (!empty($settings['rcon_password'])): ?>
                                                            <button type="button" onclick="clearRconPassword()" title="清除已保存的密码" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#dc2626;font-size:14px;padding:4px;">✕</button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small id="rconPasswordHint" style="display:none;color:#dc2626;margin-top:4px;font-size:0.78rem;">保存后将清除已保存的 RCON 密码</small>
                                                    </div>
                                                    <button type="button" id="rconTestButton" onclick="testRconConnection()" class="btn-secondary" style="height:38px;white-space:nowrap;">测试连接</button>
                                                </div>
                                                <div id="rconTestResult" class="rcon-test-result" role="status" aria-live="polite"></div>
                                                <p style="margin:8px 0 0;color:#64748b;font-size:0.84rem;line-height:1.6;">可直接测试当前输入，无需先保存。不开自动同步也能用，审核后仍可一键复制命令。</p>
                                            </div>
                                        </div>
                                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:12px;">
                                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;">
                                                <strong style="color:#334155;">自定义命令模板</strong>
                                                <span style="color:#94a3b8;font-size:0.82rem;">不同白名单插件改这里即可</span>
                                            </div>
                                            <div class="rcon-command-grid">
                                                <div class="form-group" style="margin:0;"><label>通过时执行</label><input type="text" id="whitelistCommandTemplateInput" name="whitelist_command_template" value="<?= e((string)($settings['whitelist_command_template'] ?? '/vmc approve {mc_name}')) ?>" class="form-input rcon-template-input" data-template-target="approve" placeholder="/whitelist add {mc_name}"></div>
                                                <div class="form-group" style="margin:0;"><label>拒绝时执行</label><input type="text" id="whitelistRejectTemplateInput" name="whitelist_reject_command_template" value="<?= e((string)($settings['whitelist_reject_command_template'] ?? '/vmc reject {mc_name} {reason}')) ?>" class="form-input rcon-template-input" data-template-target="reject" placeholder="/vmc reject {mc_name} {reason}"></div>
                                                <div class="form-group" style="margin:0;"><label>超时秒数</label><input type="number" id="rconTimeoutInput" min="1" max="60" name="rcon_timeout" value="<?= e((string)($settings['rcon_timeout'] ?? 5)) ?>" class="form-input"></div>
                                            </div>
                                            <div style="margin-top:8px;font-size:0.82rem;color:#64748b;">点击下方占位符插入到光标位置（在哪个模板里点上次聚焦哪条就插哪条）：</div>
                                            <div class="rcon-placeholder-grid" id="rconPlaceholderGrid" style="margin-top:6px;">
                                                <span data-placeholder="{mc_name}" style="cursor:pointer;"><code>{mc_name}</code> 游戏 ID</span>
                                                <span data-placeholder="{reason}" style="cursor:pointer;"><code>{reason}</code> 审核备注</span>
                                                <span data-placeholder="{username}" style="cursor:pointer;"><code>{username}</code> 网站用户名</span>
                                                <span data-placeholder="{email}" style="cursor:pointer;"><code>{email}</code> 用户邮箱</span>
                                                <span data-placeholder="{app_id}" style="cursor:pointer;"><code>{app_id}</code> 申请 ID</span>
                                                <span data-placeholder="{user_id}" style="cursor:pointer;"><code>{user_id}</code> 用户 ID</span>
                                                <span data-placeholder="{source}" style="cursor:pointer;"><code>{source}</code> 来源</span>
                                                <span data-placeholder="{age_range}" style="cursor:pointer;"><code>{age_range}</code> 年龄段</span>
                                            </div>
                                            <div style="margin-top:10px;background:#0f172a;border-radius:10px;padding:10px 12px;font-family:Consolas,monospace;font-size:0.84rem;color:#e2e8f0;line-height:1.7;">
                                                <div style="color:#94a3b8;font-size:0.78rem;margin-bottom:4px;">实时预览（用示例数据替换占位符）</div>
                                                <div>通过时：<span id="rconPreviewApprove" style="color:#86efac;">-</span></div>
                                                <div>拒绝时：<span id="rconPreviewReject" style="color:#fda4af;">-</span></div>
                                            </div>
                                            <p style="margin:8px 0 0;color:#94a3b8;font-size:0.82rem;">示例：<code>/whitelist add {mc_name}</code>、<code>/easywl approve {mc_name}</code>、<code>/lp user {mc_name} parent add default</code>、<code>/vmc reject {mc_name} {reason}</code></p>
                                            <!-- RCON 预览脚本已外提到 admin/js/rcon-preview.js -->
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </form>
                    </details>
                </div>
            </div>

            <!-- ===== 商城：收益概览 ===== -->
            <div id="tab-shop_revenue" class="tab-pane shop-admin" style="display: <?= $currentTab === 'shop_revenue' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">收益概览
                        <span class="shop-title-actions">
                            <button type="button" onclick="shopLoadDashboard()" class="btn-secondary">刷新</button>
                        </span>
                    </h3>
                    <div id="shopDashboardStats" class="shop-metrics"></div>
                    <div class="shop-dashboard-grid">
                        <div class="shop-panel">
                            <h4 class="shop-panel-title">最近 14 天订单 / 收入趋势</h4>
                            <div id="shopTrendChart" class="shop-trend-chart"></div>
                        </div>
                        <div class="shop-panel">
                            <h4 class="shop-panel-title">销量 Top 商品</h4>
                            <div id="shopTopProducts" class="shop-list"></div>
                        </div>
                    </div>
                    <div class="shop-panel">
                        <h4 class="shop-panel-title">最近订单</h4>
                        <div id="shopRecentOrders" class="shop-list"></div>
                    </div>
                </div>
            </div>

            <!-- ===== 商城：商品管理 ===== -->
            <div id="tab-shop_products" class="tab-pane shop-admin" style="display: <?= $currentTab === 'shop_products' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">商品管理
                        <span class="shop-title-actions">
                            <button type="button" onclick="shopOpenCategoryManager()" class="btn-secondary">分类管理</button>
                            <button type="button" onclick="shopOpenProductEditor()" class="btn-save">+ 新增商品</button>
                        </span>
                    </h3>
                    <div class="shop-toolbar">
                        <input type="text" id="shopProductSearch" class="form-input" placeholder="搜索商品名称 / 副标题...">
                        <select id="shopProductCategoryFilter" class="form-input"><option value="">全部分类</option></select>
                        <select id="shopProductActiveFilter" class="form-input">
                            <option value="">全部状态</option>
                            <option value="1">已上架</option>
                            <option value="0">已下架</option>
                        </select>
                        <label class="shop-check"><input type="checkbox" id="shopProductLowStockFilter"> 仅低库存</label>
                        <button type="button" onclick="shopLoadProducts(1)" class="btn-save">查询</button>
                    </div>
                    <div id="shopProductBatchBar" class="shop-batch-bar" style="display:none;">
                        <span class="shop-batch-count">已选 0 件</span>
                        <button type="button" onclick="shopBatchSelectAll()" class="shop-btn shop-btn--muted">全选/取消</button>
                        <button type="button" onclick="shopBatchAction('activate')" class="shop-btn shop-btn--primary">批量上架</button>
                        <button type="button" onclick="shopBatchAction('deactivate')" class="shop-btn shop-btn--danger">批量下架</button>
                    </div>
                    <div id="shopProductsList" class="shop-grid-list"></div>
                    <div id="shopProductsPagination" class="shop-pagination"></div>
                </div>
            </div>

            <!-- ===== 商城：订单管理 ===== -->
            <div id="tab-shop_orders" class="tab-pane shop-admin" style="display: <?= $currentTab === 'shop_orders' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">订单管理</h3>
                    <div class="shop-toolbar" style="grid-template-columns: minmax(180px,1fr) minmax(120px,auto) auto auto auto;">
                        <input type="text" id="shopOrderSearch" class="form-input" placeholder="搜索订单号 / 用户名 / 邮箱 / MC ID...">
                        <select id="shopOrderStatusFilter" class="form-input">
                            <option value="">全部状态</option>
                            <option value="pending_payment">待支付</option>
                            <option value="paid">已支付</option>
                            <option value="shipped">已发货</option>
                            <option value="completed">已完成</option>
                            <option value="cancelled">已取消</option>
                            <option value="refunded">已退款</option>
                        </select>
                        <input type="date" id="shopOrderDateFrom" class="form-input" title="起始日期">
                        <input type="date" id="shopOrderDateTo" class="form-input" title="截止日期">
                        <button type="button" onclick="shopLoadOrders(1)" class="btn-save">查询</button>
                    </div>
                    <div id="shopOrdersList" class="shop-grid-list"></div>
                    <div id="shopOrdersPagination" class="shop-pagination"></div>
                </div>
            </div>

            <!-- ===== 商城：库存管理 ===== -->
            <div id="tab-shop_inventory" class="tab-pane shop-admin" style="display: <?= $currentTab === 'shop_inventory' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">库存管理
                        <span style="margin-left:auto;font-size:.82em;font-weight:400;color:var(--text-muted);">库存 ≤ <?= SHOP_LOW_STOCK_THRESHOLD ?> 视为低库存</span>
                    </h3>

                    <!-- 库存概览统计 -->
                    <div id="shopInventoryStats" class="inv-stats">
                        <div class="inv-stat">
                            <span class="inv-stat__label">商品总数</span>
                            <span class="inv-stat__value" id="invStatTotal">—</span>
                        </div>
                        <div class="inv-stat inv-stat--amber">
                            <span class="inv-stat__label">低库存</span>
                            <span class="inv-stat__value" id="invStatLow">—</span>
                        </div>
                        <div class="inv-stat inv-stat--red">
                            <span class="inv-stat__label">已售罄</span>
                            <span class="inv-stat__value" id="invStatOut">—</span>
                        </div>
                    </div>

                    <div class="inv-toolbar">
                        <div class="inv-search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" id="shopInventorySearch" class="inv-search__input" placeholder="搜索商品名称..." onkeydown="if(event.key==='Enter'){shopLoadInventory(1);return false;}">
                        </div>
                        <label class="inv-filter-chip"><input type="checkbox" id="shopInventoryLowOnly" checked> 仅显示低库存</label>
                        <button type="button" onclick="shopLoadInventory(1)" class="btn-save">查询</button>
                    </div>

                    <div id="shopInventoryList" class="inv-cards"></div>
                    <div id="shopInventoryPagination" class="shop-pagination"></div>
                </div>
            </div>

            <!-- ===== 商城：发货链路 ===== -->
            <div id="tab-shop_delivery" class="tab-pane shop-admin" style="display: <?= $currentTab === 'shop_delivery' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <?php
                        // 推断 PHP CLI 二进制路径（宝塔典型路径 /www/server/php/<ver>/bin/php），失败则回退 php
                        $shopCronPhpBin = 'php';
                        $shopCronPhpVerKey = str_replace('.', '', PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION);
                        $shopCronBtPath = '/www/server/php/' . $shopCronPhpVerKey . '/bin/php';
                        if (@is_executable($shopCronBtPath)) {
                            $shopCronPhpBin = $shopCronBtPath;
                        } elseif (defined('PHP_BINDIR') && @is_executable(PHP_BINDIR . '/php')) {
                            $shopCronPhpBin = PHP_BINDIR . '/php';
                        }
                        $shopCronScript = __DIR__ . '/shop_delivery_cron.php';
                        $shopCronShell  = "#!/bin/bash\n" . $shopCronPhpBin . ' ' . $shopCronScript;
                    ?>
                    <h3 class="section-title">RCON 发货链路
                        <span class="shop-title-actions">
                            <button type="button" onclick="shopDeliveryTestRcon()" class="btn-secondary">测试 RCON</button>
                            <button type="button" onclick="shopDeliveryRunNow(this)" class="btn-save">立即处理队列</button>
                        </span>
                    </h3>

                    <!-- 概览 -->
                    <div id="shopDeliveryStats" class="shop-metrics"></div>

                    <!-- 待发队列（日常使用） -->
                    <div class="shop-toolbar shop-toolbar--compact">
                        <input type="text" id="shopDeliveryKeyword" class="form-input" placeholder="按 MC ID / 商品名 / 命令搜索...">
                        <select id="shopDeliveryStatusFilter" class="form-input">
                            <option value="pending">待发送</option>
                            <option value="">全部</option>
                            <option value="success">已发送</option>
                            <option value="failed">失败</option>
                            <option value="cancelled">已取消</option>
                        </select>
                        <button type="button" onclick="shopDeliveryLoadQueue(1)" class="btn-save">查询</button>
                    </div>
                    <div class="shop-batch-bar" style="margin-bottom:10px;">
                        <span style="font-size:.84rem;color:var(--text-secondary);">批量操作：</span>
                        <button type="button" onclick="shopDeliveryBatchRetryFailed()" class="shop-btn shop-btn--primary">重试全部失败</button>
                        <button type="button" onclick="shopDeliveryBatchCancelPending()" class="shop-btn shop-btn--danger">取消全部待发</button>
                    </div>
                    <div id="shopDeliveryQueueList" class="shop-grid-list"></div>
                    <div id="shopDeliveryQueuePagination" class="shop-pagination"></div>

                    <!-- 发货链路设置（折叠，设置一次即可） -->
                    <details class="shop-collapse">
                        <summary>发货链路设置 <span class="shop-summary-note">重试 / 自动发货 / 超时取消</span></summary>
                        <div class="shop-collapse-body">
                            <form id="shopDeliverySettingsForm" onsubmit="return shopDeliverySaveSettings(event)">
                                <div class="shop-toggle-row">
                                    <label class="shop-toggle-card">
                                        <input type="checkbox" name="shop_rcon_delivery_enabled" id="shopDeliveryEnabled">
                                        <span class="shop-toggle-switch"></span>
                                        <span class="shop-toggle-text">
                                            <b>启用 RCON 自动发货</b>
                                            <small>订单已支付后自动通过 RCON 执行发货命令</small>
                                        </span>
                                    </label>
                                    <label class="shop-toggle-card">
                                        <input type="checkbox" name="shop_delivery_auto_ship" id="shopDeliveryAutoShip" checked>
                                        <span class="shop-toggle-switch"></span>
                                        <span class="shop-toggle-text">
                                            <b>自动标记「已发货」</b>
                                            <small>全部命令执行成功后，把订单状态推进为已发货</small>
                                        </span>
                                    </label>
                                </div>
                                <div class="shop-form-grid" style="margin-top:14px;">
                                    <div class="shop-field"><label>最大重试次数</label><input type="number" name="shop_delivery_max_attempts" id="shopDeliveryMaxAttempts" class="form-input" min="1" max="999" value="30"></div>
                                    <div class="shop-field"><label>重试间隔（秒）</label><input type="number" name="shop_delivery_retry_seconds" id="shopDeliveryRetrySeconds" class="form-input" min="10" max="86400" value="60"></div>
                                    <div class="shop-field"><label>待支付订单超时取消（分钟）</label><input type="number" name="shop_order_expire_minutes" id="shopOrderExpireMinutes" class="form-input" min="0" max="44640" value="0" placeholder="0 = 不自动取消"><span class="shop-field-note">0 = 关闭；在线支付建议 30~60，手动对账 120~1440，需 Cron 已配置。</span></div>
                                    <div class="shop-field shop-field--full"><label>Cron Token <button type="button" onclick="shopDeliveryRegenToken()" class="shop-link-btn">生成新 Token</button></label><input type="text" name="shop_delivery_cron_token" id="shopDeliveryCronToken" class="form-input shop-mono" placeholder="留空 = 自动生成"></div>
                                </div>
                                <div class="shop-form-actions">
                                    <button type="submit" class="btn-save">保存设置</button>
                                </div>
                                <p class="shop-field-note">RCON 连接配置（host/port/password/timeout）在「入服申请」标签页的 <b>RCON 设置</b> 区域，所有 RCON 功能共用同一连接。</p>
                            </form>
                        </div>
                    </details>

                    <!-- 定时任务部署（折叠，部署一次即可） -->
                    <details class="shop-collapse">
                        <summary>定时任务部署 <span class="shop-summary-note">必须配置一次，否则无法自动补发</span></summary>
                        <div class="shop-collapse-body">
                            <p class="shop-note-text" style="margin:0 0 8px;">订单进入「已支付」后立即尝试 RCON 发货；失败或玩家不在线则进入队列，由 Cron 反复补发。</p>
                            <div class="shop-notice-head" style="margin-bottom:8px;">
                                <div>宝塔 → 计划任务 → Shell 脚本，执行周期每 <b>1 分钟</b></div>
                                <button type="button" onclick="shopDeliveryCopyCron(this)" class="btn-secondary">复制脚本</button>
                            </div>
                            <pre id="shopDeliveryCronScript" class="shop-code-box"><?= htmlspecialchars($shopCronShell) ?></pre>
                            <div class="shop-field-note" style="margin-top:8px;line-height:1.8;">
                                <div><b>Linux crontab</b>：<code>* * * * * <?= htmlspecialchars($shopCronPhpBin . ' ' . $shopCronScript) ?></code></div>
                                <div><b>Windows 计划任务</b>：每分钟运行 <code>php.exe shop_delivery_cron.php</code></div>
                                <div><b>HTTP 触发</b>（需带 Token）：<code>GET /admin/shop_delivery_cron.php?token=YOUR_TOKEN</code></div>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <!-- ===== 商城：支付设置 ===== -->
            <div id="tab-shop_payments" class="tab-pane shop-admin" style="display: <?= $currentTab === 'shop_payments' ? 'block' : 'none' ?>">
                <?php
                $payCfg    = function_exists('shopPaymentSettings') ? shopPaymentSettings(false) : [];
                $payEnabled = !empty($payCfg['enabled']);
                $payKeySet = !empty($payCfg['key_set']);
                $payType   = (string)($payCfg['type'] ?? 'alipay');
                ?>

                <!-- 接入配置 -->
                <form id="shopPaymentSettingsForm" onsubmit="return shopPaymentSaveSettings(event)" class="pay-card pay-config-form">
                    <div class="pay-card__header">
                        <div class="pay-card__header-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        </div>
                        <div class="pay-card__header-text">
                            <div class="pay-card__title">支付设置</div>
                            <div class="pay-card__subtitle">配置易支付商户参数，启用后用户可在商城下单时在线付款。</div>
                        </div>
                    </div>
                    <div class="pay-card__body">

                        <!-- 安全提示 -->
                        <div class="pay-section">
                            <div class="pay-tip" style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border-radius:8px;background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.35);color:#b45309;font-size:13px;line-height:1.5;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                                <span>易支付平台跑路狗很多，推荐自己搭建平台使用！</span>
                            </div>
                        </div>

                        <!-- 启用开关 -->
                        <div class="pay-section">
                            <div class="pay-block--toggle">
                                <div class="pay-toggle-row">
                                    <div class="pay-toggle-text">
                                        <div class="pay-toggle-label">启用在线收款</div>
                                        <div class="pay-toggle-desc">启用后用户可在商城下单时选择在线支付</div>
                                    </div>
                                    <label class="pay-switch">
                                        <input type="checkbox" id="shopPaymentEnabled" <?= $payEnabled ? 'checked' : '' ?>>
                                        <span class="pay-switch-track"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 凭证 -->
                        <div class="pay-section">
                            <div class="pay-section__label"><span class="pay-section__label-tag"></span>商户凭证</div>
                            <div class="pay-credentials-grid">
                                <div class="pay-field-group pay-full-width">
                                    <label class="pay-field-label" for="shopPaymentBaseUrl">支付网关地址（易支付平台域名）</label>
                                    <input type="url" id="shopPaymentBaseUrl" class="form-input" value="<?= e((string)($payCfg['base_url'] ?? 'https://mc.example.com')) ?>" placeholder="https://mc.example.com">
                                    <div class="pay-field-hint">填写易支付服务商域名，无需带 /submit.php 后缀</div>
                                    <label class="pay-field-label" for="sitePublicUrl" style="margin-top:10px;">站点公开地址（你自己网站的域名）</label>
                                    <input type="url" id="sitePublicUrl" class="form-input" value="<?= e((string)(loadSettings()['site_public_url'] ?? '')) ?>" placeholder="https://你的网站域名">
                                    <div class="pay-field-hint" style="color:#dc2626;">必须填你自己的网站域名（https:// 开头，不带路径）。异步通知与支付回跳都会指向它——填成易支付域名会导致回调 404、付款后无法到账。</div>
                                </div>
                                <div class="pay-field-group">
                                    <label class="pay-field-label" for="shopPaymentPid">商户 ID（PID）</label>
                                    <input type="text" id="shopPaymentPid" class="form-input" value="<?= e((string)($payCfg['pid'] ?? '')) ?>" placeholder="例如：1000">
                                </div>
                                <div class="pay-field-group">
                                    <div class="pay-key-header">
                                        <label class="pay-field-label" for="shopPaymentKey">
                                            商户密钥（KEY）
                                            <span class="pay-key-badge <?= $payKeySet ? 'pay-key-badge--set' : 'pay-key-badge--unset' ?>"><?= $payKeySet ? '已配置' : '未配置' ?></span>
                                        </label>
                                        <label class="pay-key-inline-actions">
                                            <input type="checkbox" id="shopPaymentClearKey">
                                            <span>清空密钥</span>
                                        </label>
                                    </div>
                                    <div class="pay-key-wrap">
                                        <input type="password" id="shopPaymentKey" class="form-input pay-key-input" value="" placeholder="<?= $payKeySet ? '留空保持不变' : '输入商户密钥' ?>" autocomplete="new-password">
                                        <button type="button" class="pay-eye-btn" onclick="shopPayToggleKeyVisible(this)" title="显示/隐藏密钥">
                                            <svg class="pay-eye-show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <svg class="pay-eye-hide" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                        </button>
                                    </div>
                                    <div class="pay-field-hint">仅本次保存生效，留空则保留原密钥</div>
                                </div>
                                <div class="pay-field-group pay-full-width">
                                    <label class="pay-field-label" for="shopPaymentSitename">站点名称（显示在支付页）</label>
                                    <input type="text" id="shopPaymentSitename" class="form-input" maxlength="80" value="<?= e((string)($payCfg['sitename'] ?? 'FoxMC 商城')) ?>">
                                </div>
                            </div>
                        </div>

                        <!-- 支付方式 -->
                        <div class="pay-section">
                            <div class="pay-section__label"><span class="pay-section__label-tag"></span>默认支付方式</div>
                            <div class="pay-type-tabs">
                                <label class="pay-type-tab pay-default-option <?= $payType === 'alipay' ? 'is-active' : '' ?>">
                                    <input type="radio" name="_payTypeRadio" value="alipay" <?= $payType === 'alipay' ? 'checked' : '' ?> onchange="shopPayTypeChanged(this)">
                                    <span class="pay-option-dot pay-option-dot--alipay"></span>
                                    <span class="pay-option-main">支付宝</span>
                                </label>
                                <label class="pay-type-tab pay-default-option <?= $payType === 'wxpay' ? 'is-active' : '' ?>">
                                    <input type="radio" name="_payTypeRadio" value="wxpay" <?= $payType === 'wxpay' ? 'checked' : '' ?> onchange="shopPayTypeChanged(this)">
                                    <span class="pay-option-dot pay-option-dot--wxpay"></span>
                                    <span class="pay-option-main">微信支付</span>
                                </label>
                                <label class="pay-type-tab pay-default-option <?= $payType === 'qqpay' ? 'is-active' : '' ?>">
                                    <input type="radio" name="_payTypeRadio" value="qqpay" <?= $payType === 'qqpay' ? 'checked' : '' ?> onchange="shopPayTypeChanged(this)">
                                    <span class="pay-option-dot pay-option-dot--qqpay"></span>
                                    <span class="pay-option-main">QQ 支付</span>
                                </label>
                            </div>
                            <select id="shopPaymentType" style="display:none;">
                                <option value="alipay" <?= $payType === 'alipay' ? 'selected' : '' ?>>支付宝</option>
                                <option value="wxpay"  <?= $payType === 'wxpay'  ? 'selected' : '' ?>>微信支付</option>
                                <option value="qqpay"  <?= $payType === 'qqpay'  ? 'selected' : '' ?>>QQ支付</option>
                            </select>
                            <div class="pay-section__hint" style="margin-top:8px;">用户结算时默认勾选此方式</div>
                        </div>
                    </div>

                    <div class="pay-card__footer">
                        <button type="submit" class="btn-save pay-action-btn" id="shopPaySaveBtn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            保存设置
                        </button>
                    </div>
                </form>

                <!-- 回调地址 -->
                <div class="pay-card">
                    <div class="pay-card__header">
                        <div class="pay-card__header-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        </div>
                        <div class="pay-card__header-text">
                            <div class="pay-card__title">回调地址</div>
                            <div class="pay-card__subtitle">将下面两个地址填入易支付平台后台对应位置。</div>
                        </div>
                    </div>
                    <div class="pay-card__body">
                        <div class="pay-url-grid">
                            <div class="pay-url-card">
                                <div class="pay-url-card-head">
                                    <div class="pay-url-meta">
                                        <span class="pay-badge-method pay-badge-method--post">POST</span>
                                        <div>
                                            <div class="pay-url-name">异步通知地址</div>
                                            <div class="pay-url-key">notify_url</div>
                                        </div>
                                    </div>
                                    <button type="button" class="pay-copy-btn" onclick="shopPayCopyUrl('shopPaymentNotifyUrl', this)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        复制
                                    </button>
                                </div>
                                <div id="shopPaymentNotifyUrl" class="pay-url-value"><?= e(function_exists('shopPaymentNotifyUrl') ? shopPaymentNotifyUrl() : '') ?></div>
                            </div>
                            <div class="pay-url-card">
                                <div class="pay-url-card-head">
                                    <div class="pay-url-meta">
                                        <span class="pay-badge-method pay-badge-method--get">GET</span>
                                        <div>
                                            <div class="pay-url-name">同步返回地址</div>
                                            <div class="pay-url-key">return_url</div>
                                        </div>
                                    </div>
                                    <button type="button" class="pay-copy-btn" onclick="shopPayCopyUrl('shopPaymentReturnUrl', this)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        复制
                                    </button>
                                </div>
                                <div id="shopPaymentReturnUrl" class="pay-url-value"><?= e(function_exists('shopPaymentReturnUrl') ? shopPaymentReturnUrl() : '') ?></div>
                            </div>
                        </div>
                        <div style="margin-top:16px;">
                            <div style="font-size:.85em;font-weight:600;color:#334155;margin-bottom:8px;">最近回调 / 补单记录</div>
                            <div id="shopPaymentNotifyLog" style="border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;padding:4px 12px;font-size:.82em;color:#475569;">
                                <div style="padding:10px 0;color:#94a3b8;">加载中…</div>
                            </div>
                            <div class="pay-field-hint" style="margin-top:6px;">若支付后迟迟不到账：此处无任何记录说明网关回调未到达本站（检查站点公开地址 / WAF / 白名单）；有失败记录则按失败原因排查（验签失败多为商户密钥不符）。</div>
                        </div>
                    </div>
                </div>

            </div>


            <!-- ===== 商城：商品编辑器弹窗 ===== -->
            <div id="shopProductModal" class="modal-overlay" style="display:none;">
                <div class="modal-content" style="max-width:680px;">
                    <div class="modal-header">
                        <h3 id="shopProductModalTitle">新增商品</h3>
                        <button type="button" class="close-modal" onclick="shopCloseProductEditor()">×</button>
                    </div>
                    <form id="shopProductForm" onsubmit="return shopSubmitProduct(event)">
                        <input type="hidden" name="id" id="shopProductId" value="">
                        <div class="form-grid-2">
                            <div class="form-group" style="grid-column:1/-1;margin:0;"><label>商品名称 *</label><input type="text" name="name" id="shopProductName" class="form-input" maxlength="120" required></div>
                            <div class="form-group" style="grid-column:1/-1;margin:0;"><label>副标题 / 卖点</label><input type="text" name="subtitle" id="shopProductSubtitle" class="form-input" maxlength="200" placeholder="例如：限时 7 折，专属称号"></div>
                            <div class="form-group" style="margin:0;"><label>分类</label><select name="category_id" id="shopProductCategory" class="form-input"><option value="">未分类</option></select></div>
                            <div class="form-group" style="margin:0;"><label>排序权重</label><input type="number" name="sort_order" id="shopProductSort" class="form-input" value="0"></div>
                            <div class="form-group" style="margin:0;"><label>售价（元）*</label><input type="number" name="price" id="shopProductPrice" class="form-input" step="0.01" min="0.01" required></div>
                            <div class="form-group" style="margin:0;"><label>原价（可选）</label><input type="number" name="original_price" id="shopProductOriginalPrice" class="form-input" step="0.01" min="0"></div>
                            <div class="form-group" style="margin:0;"><label>库存</label><input type="number" name="stock" id="shopProductStock" class="form-input" min="0" value="0"></div>
                            <div class="form-group" style="margin:0;"><label>最小购买数量</label><input type="number" name="min_qty" id="shopProductMinQty" class="form-input" min="1" step="1" value="1"></div>
                            <div class="form-group" style="margin:0;"><label>最大购买数量（0=不限）</label><input type="number" name="max_qty" id="shopProductMaxQty" class="form-input" min="0" step="1" value="0" placeholder="0 表示仅受库存限制"></div>
                            <div class="form-group" style="grid-column:1/-1;margin:0;">
                                <label>封面图（可选）</label>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="text" name="cover_image" id="shopProductCover" class="form-input" placeholder="assets/images/foo.png 或 https://..." oninput="shopUpdateCoverPreview(this.value)" style="flex:1;min-width:0;">
                                    <button type="button" id="shopProductCoverUploadBtn" onclick="shopChooseProductCover()" style="flex-shrink:0;display:inline-flex;align-items:center;gap:5px;padding:0 14px;height:40px;border:1.5px solid #16a34a;border-radius:8px;color:#16a34a;background:#fff;cursor:pointer;font-size:.88em;font-weight:500;white-space:nowrap;transition:background .15s;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='#fff'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        上传图片
                                    </button>
                                    <input type="file" id="shopProductCoverFile" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="shopUploadProductCover(this)">
                                </div>
                                <div id="shopProductCoverPreviewWrap" style="display:none;margin-top:8px;">
                                    <img id="shopProductCoverPreview" src="" alt="封面预览" style="max-height:90px;max-width:180px;border-radius:6px;border:1px solid #e2e8f0;object-fit:cover;">
                                    <button type="button" onclick="shopClearCover()" style="margin-left:8px;border:none;background:none;color:#94a3b8;cursor:pointer;font-size:.82em;vertical-align:middle;">✕ 移除</button>
                                </div>
                                <div id="shopProductCoverUploadHint" style="font-size:.76em;color:#94a3b8;margin-top:4px;">JPG / PNG / GIF / WebP，上限 5 MB</div>
                            </div>
                            <div class="form-group" style="grid-column:1/-1;margin:0;"><label>商品介绍</label><textarea name="description" id="shopProductDescription" class="form-input" rows="3" maxlength="8000" placeholder="支持纯文本，可换行"></textarea><?php if (!empty($settings['ai_content_enabled'])): ?><button type="button" class="btn-secondary" style="margin-top:6px;" onclick="aiGenerateInto('shopProductDescription','product')">✨ AI 生成描述</button><?php endif; ?></div>
                            <div class="form-group" style="grid-column:1/-1;margin:0;"><label>发货说明（可选）</label><textarea name="delivery_note" id="shopProductDeliveryNote" class="form-input" rows="2" maxlength="500" placeholder="例如：管理员审核后将于 24 小时内通过游戏指令发放"></textarea></div>
                            <div class="form-group" style="grid-column:1/-1;margin:0;"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;"><input type="checkbox" name="is_active" id="shopProductActive" checked> 上架（用户可见可购买）</label></div>
                            <details class="shop-rcon-delivery" style="grid-column:1/-1;border:1px solid #bbf7d0;border-radius:8px;padding:0;">
                                <summary style="cursor:pointer;font-weight:600;color:#166534;font-size:.88em;padding:9px 12px;list-style:none;display:flex;align-items:center;gap:6px;">
                                    <span style="font-size:1em;">▶</span> RCON 自动发货
                                    <span style="font-weight:400;color:#6b7280;font-size:.85em;margin-left:4px;">— 不填则人工处理，新手直接用下方生成器</span>
                                </summary>
                                <div style="padding:10px 12px 12px;border-top:1px solid #dcfce7;display:grid;gap:12px;">

                                    <!-- 步骤一：指令生成器 -->
                                    <div>
                                        <div style="font-size:.82em;font-weight:600;color:#374151;margin-bottom:6px;">① 选择发货类型，点「添加」生成指令</div>
                                        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px;" id="shopBuilderTypeTabs">
                                            <button type="button" class="shop-builder-tab is-active" data-btype="give"   onclick="shopBuilderSetType('give')"   style="border:1px solid #86efac;background:#dcfce7;border-radius:5px;padding:5px 11px;font-size:.8em;cursor:pointer;color:#15803d;font-weight:600;">🎁 发物品</button>
                                            <button type="button" class="shop-builder-tab"          data-btype="money"  onclick="shopBuilderSetType('money')"  style="border:1px solid #d1d5db;background:#fff;border-radius:5px;padding:5px 11px;font-size:.8em;cursor:pointer;color:#374151;">💰 发游戏币</button>
                                            <button type="button" class="shop-builder-tab"          data-btype="lp"     onclick="shopBuilderSetType('lp')"     style="border:1px solid #d1d5db;background:#fff;border-radius:5px;padding:5px 11px;font-size:.8em;cursor:pointer;color:#374151;">👑 发会员组</button>
                                            <button type="button" class="shop-builder-tab"          data-btype="custom" onclick="shopBuilderSetType('custom')" style="border:1px solid #d1d5db;background:#fff;border-radius:5px;padding:5px 11px;font-size:.8em;cursor:pointer;color:#374151;">🛠 自定义</button>
                                        </div>

                                        <!-- 发物品 -->
                                        <div class="shop-builder-panel" data-bpanel="give" style="display:grid;gap:7px;">
                                            <div>
                                                <label style="display:block;margin-bottom:3px;font-size:.8em;color:#6b7280;">物品（下拉选或手填 ID）</label>
                                                <input list="shopItemPresetList" id="shopBuilderItemId" class="form-input" placeholder="例如 钻石 或 minecraft:diamond" oninput="shopBuilderSyncItemId()">
                                                <datalist id="shopItemPresetList"></datalist>
                                            </div>
                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;">
                                                <div>
                                                    <label style="display:block;margin-bottom:3px;font-size:.8em;color:#6b7280;">数量</label>
                                                    <select id="shopBuilderQtyMode" class="form-input" onchange="shopBuilderQtyModeChanged()">
                                                        <option value="follow">买几个发几个</option>
                                                        <option value="fixed">固定数量</option>
                                                    </select>
                                                </div>
                                                <div id="shopBuilderFixedQtyWrap" style="display:none;">
                                                    <label style="display:block;margin-bottom:3px;font-size:.8em;color:#6b7280;">固定个数</label>
                                                    <input type="number" id="shopBuilderFixedQty" class="form-input" min="1" value="1">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 发游戏币 -->
                                        <div class="shop-builder-panel" data-bpanel="money" style="display:none;gap:7px;">
                                            <div>
                                                <label style="display:block;margin-bottom:3px;font-size:.8em;color:#6b7280;">经济插件（不确定用默认）</label>
                                                <select id="shopBuilderEcoCmd" class="form-input">
                                                    <option value="eco give">eco give（EssentialsX / 通用）</option>
                                                    <option value="money give">money give</option>
                                                    <option value="points give">points give（PlayerPoints）</option>
                                                </select>
                                            </div>
                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;">
                                                <div>
                                                    <label style="display:block;margin-bottom:3px;font-size:.8em;color:#6b7280;">金额</label>
                                                    <select id="shopBuilderMoneyMode" class="form-input" onchange="shopBuilderMoneyModeChanged()">
                                                        <option value="follow">跟随购买数量</option>
                                                        <option value="fixed">固定金额</option>
                                                    </select>
                                                </div>
                                                <div id="shopBuilderFixedMoneyWrap" style="display:none;">
                                                    <label style="display:block;margin-bottom:3px;font-size:.8em;color:#6b7280;">固定金额</label>
                                                    <input type="number" id="shopBuilderFixedMoney" class="form-input" min="1" value="100">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 发会员组 -->
                                        <div class="shop-builder-panel" data-bpanel="lp" style="display:none;gap:7px;">
                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;">
                                                <div>
                                                    <label style="display:block;margin-bottom:3px;font-size:.8em;color:#6b7280;">权限组名（如 vip）</label>
                                                    <input type="text" id="shopBuilderLpGroup" class="form-input" placeholder="vip" value="vip">
                                                </div>
                                                <div>
                                                    <label style="display:block;margin-bottom:3px;font-size:.8em;color:#6b7280;">有效期</label>
                                                    <select id="shopBuilderLpDuration" class="form-input">
                                                        <option value="permanent">永久</option>
                                                        <option value="30d">30 天</option>
                                                        <option value="90d">90 天</option>
                                                        <option value="365d">365 天</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div style="font-size:.76em;color:#9ca3af;">需安装 LuckPerms 插件</div>
                                        </div>

                                        <!-- 自定义 -->
                                        <div class="shop-builder-panel" data-bpanel="custom" style="display:none;gap:7px;">
                                            <div>
                                                <label style="display:block;margin-bottom:3px;font-size:.8em;color:#6b7280;">指令（无需加 <code>/</code>，玩家ID写 <code>{mc_name}</code>）</label>
                                                <input type="text" id="shopBuilderCustomCmd" class="form-input" placeholder="例如 crate give {mc_name} vipkey {qty}" style="font-family:Consolas,monospace;">
                                            </div>
                                        </div>

                                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:8px;">
                                            <button type="button" onclick="shopBuilderAddCommand()" style="border:none;background:#16a34a;color:#fff;border-radius:5px;padding:6px 14px;font-size:.82em;cursor:pointer;font-weight:600;">＋ 添加到指令框</button>
                                            <?php if (!empty($settings['ai_content_enabled'])): ?><button type="button" id="shopDeliveryAiBtn" onclick="shopGenerateDeliveryWithAI()" style="border:1px solid #a855f7;background:#faf5ff;color:#7e22ce;border-radius:5px;padding:5px 11px;font-size:.8em;cursor:pointer;font-weight:600;">✨ AI 生成/优化指令</button><?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 步骤二：指令框 -->
                                    <div>
                                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                            <span style="font-size:.82em;font-weight:600;color:#374151;">② 发货指令（每行一条，可手动修改）</span>
                                            <button type="button" onclick="shopApplyDeliveryTemplate('clear')" style="border:1px solid #fca5a5;background:none;border-radius:4px;padding:2px 8px;font-size:.75em;cursor:pointer;color:#b91c1c;">清空</button>
                                        </div>
                                        <textarea name="delivery_commands" id="shopProductDeliveryCommands" class="form-input" rows="3" maxlength="4000" placeholder="留空 = 不自动发货" style="font-family:Consolas,monospace;font-size:.85em;" oninput="shopRenderDeliveryPreview()"></textarea>
                                    </div>

                                    <!-- 占位符快速插入 -->
                                    <div>
                                        <div style="font-size:.8em;color:#6b7280;margin-bottom:5px;">点击标签可插入到光标处：</div>
                                        <div id="shopDeliveryPlaceholderGrid" style="display:flex;flex-wrap:wrap;gap:5px;">
                                            <span class="shop-ph-chip" data-placeholder="{mc_name}" style="cursor:pointer;border:1px solid #86efac;background:#f0fdf4;color:#15803d;border-radius:4px;padding:3px 8px;font-size:.78em;font-family:Consolas,monospace;">{mc_name} 游戏ID</span>
                                            <span class="shop-ph-chip" data-placeholder="{qty}"     style="cursor:pointer;border:1px solid #86efac;background:#f0fdf4;color:#15803d;border-radius:4px;padding:3px 8px;font-size:.78em;font-family:Consolas,monospace;">{qty} 数量</span>
                                            <span class="shop-ph-chip" data-placeholder="{product}" style="cursor:pointer;border:1px solid #86efac;background:#f0fdf4;color:#15803d;border-radius:4px;padding:3px 8px;font-size:.78em;font-family:Consolas,monospace;">{product} 商品名</span>
                                            <span class="shop-ph-chip" data-placeholder="{user}"    style="cursor:pointer;border:1px solid #86efac;background:#f0fdf4;color:#15803d;border-radius:4px;padding:3px 8px;font-size:.78em;font-family:Consolas,monospace;">{user} 用户名</span>
                                            <span class="shop-ph-chip" data-placeholder="{order_no}" style="cursor:pointer;border:1px solid #86efac;background:#f0fdf4;color:#15803d;border-radius:4px;padding:3px 8px;font-size:.78em;font-family:Consolas,monospace;">{order_no} 订单号</span>
                                        </div>
                                    </div>

                                    <!-- 实时预览 -->
                                    <div>
                                        <div style="font-size:.8em;color:#6b7280;margin-bottom:4px;">预览（Steve 买 2 个）：</div>
                                        <pre id="shopDeliveryPreview" style="margin:0;background:#052e16;color:#86efac;border-radius:6px;padding:8px 10px;font-size:.8em;line-height:1.6;white-space:pre-wrap;word-break:break-all;min-height:1.6em;">(留空 = 不自动发货)</pre>
                                    </div>

                                    <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.85em;color:#374151;">
                                        <input type="checkbox" name="require_online" id="shopProductRequireOnline" checked>
                                        玩家在线时才发放（发物品建议勾选）
                                    </label>
                                </div>
                            </details>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="shopCloseProductEditor()">取消</button>
                            <button type="submit" class="btn-save">保存商品</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ===== 商城：分类管理弹窗 ===== -->
            <div id="shopCategoryModal" class="modal-overlay" style="display:none;">
                <div class="modal-content" style="max-width:560px;">
                    <div class="modal-header">
                        <h3>分类管理</h3>
                        <button type="button" class="close-modal" onclick="shopCloseCategoryManager()">×</button>
                    </div>
                    <div id="shopCategoriesList" style="display:grid;gap:8px;margin-bottom:14px;"></div>
                    <form id="shopCategoryForm" onsubmit="return shopSubmitCategory(event)" style="display:grid;grid-template-columns:2fr 1fr auto auto;gap:8px;align-items:end;">
                        <input type="hidden" name="id" id="shopCategoryId" value="">
                        <div class="form-group" style="margin:0;"><label style="font-size:.84em;">分类名称</label><input type="text" name="name" id="shopCategoryName" class="form-input" maxlength="80" required></div>
                        <div class="form-group" style="margin:0;"><label style="font-size:.84em;">排序</label><input type="number" name="sort_order" id="shopCategorySort" class="form-input" value="0"></div>
                        <label style="display:flex;align-items:center;gap:4px;font-size:.86em;color:#475569;"><input type="checkbox" name="is_active" id="shopCategoryActive" checked> 启用</label>
                        <button type="submit" class="btn-save" style="padding:8px 14px;">保存</button>
                    </form>
                </div>
            </div>

            <!-- ===== 商城：订单详情弹窗 ===== -->
            <div id="shopOrderModal" class="modal-overlay" style="display:none;">
                <div class="modal-content" style="max-width:760px;">
                    <div class="modal-header">
                        <h3 id="shopOrderModalTitle">订单详情</h3>
                        <button type="button" class="close-modal" onclick="shopCloseOrderModal()">×</button>
                    </div>
                    <div id="shopOrderModalBody"></div>
                </div>
            </div>

            <!-- ===== 商城：库存调整弹窗 ===== -->
            <div id="shopStockModal" class="modal-overlay" style="display:none;">
                <div class="modal-content" style="max-width:480px;">
                    <div class="modal-header">
                        <h3>调整库存</h3>
                        <button type="button" class="close-modal" onclick="shopCloseStockModal()">×</button>
                    </div>
                    <form id="shopStockForm" onsubmit="return shopSubmitStock(event)">
                        <input type="hidden" name="id" id="shopStockProductId" value="">
                        <div class="form-group"><label>商品</label><div id="shopStockProductName" style="padding:8px 12px;background:#f8fafc;border-radius:8px;color:#0f172a;"></div></div>
                        <div class="form-group"><label>当前库存</label><div id="shopStockCurrent" style="padding:8px 12px;background:#f8fafc;border-radius:8px;color:#0f172a;font-weight:700;"></div></div>
                        <div class="form-group"><label>调整数量（正数为入库，负数为出库）*</label><input type="number" name="delta" id="shopStockDelta" class="form-input" required></div>
                        <div class="form-group"><label>备注（可选）</label><input type="text" name="reason" class="form-input" maxlength="200" placeholder="例如：补货 / 损耗 / 兑换码补发"></div>
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="shopCloseStockModal()">取消</button>
                            <button type="submit" class="btn-save">提交</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 用户详情弹窗 -->
            <div id="userDetailModal" class="user-detail-modal" onclick="if(event.target===this)closeUserDetail()">
                <div class="user-detail-modal-inner" onclick="event.stopPropagation()">
                    <div class="user-detail-modal-head">
                        <h3>用户详情</h3>
                        <button onclick="closeUserDetail()" aria-label="关闭" class="user-detail-modal-close">&times;</button>
                    </div>
                    <div id="userDetailContent" class="user-detail-modal-body"></div>
                </div>
            </div>

            <!-- 创建用户弹窗 -->
            <div id="createUserModal" class="user-detail-modal" onclick="if(event.target===this)closeCreateUserModal()">
                <div class="user-detail-modal-inner" style="max-width:560px;" onclick="event.stopPropagation()">
                    <div class="user-detail-modal-head">
                        <h3>添加用户</h3>
                        <button type="button" onclick="closeCreateUserModal()" aria-label="关闭" class="user-detail-modal-close">&times;</button>
                    </div>
                    <div class="user-detail-modal-body">
                        <form id="createUserForm" onsubmit="submitCreateUser(event)">
                            <div class="form-group">
                                <label>用户名 <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="createUserUsername" class="form-input" maxlength="32" placeholder="2-32 位字母、数字、下划线或中文" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label>邮箱 <span style="color:#dc2626;">*</span></label>
                                <input type="email" id="createUserEmail" class="form-input" maxlength="120" placeholder="用于登录和接收通知" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label>密码 <span style="color:#dc2626;">*</span></label>
                                <input type="password" id="createUserPassword" class="form-input" maxlength="6" placeholder="必须为 6 位字符" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label>确认密码 <span style="color:#dc2626;">*</span></label>
                                <input type="password" id="createUserConfirm" class="form-input" placeholder="再次输入密码" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label>用户角色 <span style="color:#dc2626;">*</span></label>
                                <select id="createUserRole" class="form-input">
                                    <option value="user">普通用户</option>
                                    <option value="staff">工作人员（可使用用户中心工作台）</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Minecraft 游戏 ID <span style="color:#94a3b8;font-weight:400;">（选填）</span></label>
                                <input type="text" id="createUserMcName" class="form-input" maxlength="32" placeholder="2-32 位字母、数字或下划线" autocomplete="off">
                            </div>
                            <div id="createUserError" style="display:none;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:0.88em;line-height:1.6;"></div>
                            <div style="display:flex;justify-content:flex-end;gap:10px;">
                                <button type="button" onclick="closeCreateUserModal()" style="background:#f1f5f9;color:#334155;border:none;padding:10px 20px;border-radius:10px;font-weight:600;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">取消</button>
                                <button type="submit" id="createUserSubmitBtn" class="btn-save" style="display:inline-flex;align-items:center;gap:6px;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    创建用户
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-community" class="tab-pane" style="display: <?= $currentTab === 'community' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="community">
                    <div class="form-section">
                        <h3 class="section-title">社区链接板块</h3>
                        <div class="form-row">
                            <div class="form-group"><label>板块标题</label><input type="text" name="community[title]" value="<?= e($content['community']['title'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>板块副标题</label><input type="text" name="community[subtitle]" value="<?= e($content['community']['subtitle'] ?? '') ?>" class="form-input"></div>
                        </div>
                        <div class="form-group">
                            <label>板块背景图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['community']['bg_image'])): ?><img <?= $imgAttr($content['community']['bg_image'], 'community') ?> class="preview-img" alt=""><?php endif; ?>
                                <input type="file" name="community_bg_image" accept="image/*" class="form-file">
                                <input type="hidden" name="community[bg_image]" value="<?= e($content['community']['bg_image'] ?? '') ?>">
                                <span class="file-hint">留空则保持当前图片不变</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <h3 class="section-title">QQ群</h3>
                        <div class="form-group"><label>标题</label><input type="text" name="community[qq_text]" value="<?= e($content['community']['qq_text'] ?? '') ?>" class="form-input"></div>
                        <div class="form-group"><label>描述</label><input type="text" name="community[qq_desc]" value="<?= e($content['community']['qq_desc'] ?? '') ?>" class="form-input"></div>
                        <div class="form-group">
                            <label>二维码图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['community']['qq_qr'])): ?>
                                    <img <?= $imgAttr($content['community']['qq_qr'], 'community') ?> class="preview-img small" alt="">
                                <?php endif; ?>
                                <input type="file" name="community_qq_qr" accept="image/*" class="form-file">
                                <input type="hidden" name="community[qq_qr]" value="<?= e($content['community']['qq_qr'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group"><label>加群链接</label><input type="text" name="community[qq_link]" value="<?= e($content['community']['qq_link'] ?? '') ?>" class="form-input"></div>
                    </div>
                    <div class="form-section">
                        <h3 class="section-title">微信群</h3>
                        <div class="form-group"><label>标题</label><input type="text" name="community[wechat_text]" value="<?= e($content['community']['wechat_text'] ?? '') ?>" class="form-input"></div>
                        <div class="form-group"><label>描述</label><input type="text" name="community[wechat_desc]" value="<?= e($content['community']['wechat_desc'] ?? '') ?>" class="form-input"></div>
                        <div class="form-group">
                            <label>二维码图片</label>
                            <div class="image-upload-group">
                                <?php if (!empty($content['community']['wechat_qr'])): ?>
                                    <img <?= $imgAttr($content['community']['wechat_qr'], 'community') ?> class="preview-img small" alt="">
                                <?php endif; ?>
                                <input type="file" name="community_wechat_qr" accept="image/*" class="form-file">
                                <input type="hidden" name="community[wechat_qr]" value="<?= e($content['community']['wechat_qr'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group"><label>加群链接</label><input type="text" name="community[wechat_link]" value="<?= e($content['community']['wechat_link'] ?? '') ?>" class="form-input"></div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div id="tab-community" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <?php
                $aiProvider = in_array((string)($settings['ai_provider'] ?? 'zhipu'), ['zhipu', 'xiaomi_mimo'], true)
                    ? (string)($settings['ai_provider'] ?? 'zhipu')
                    : 'zhipu';
                $aiProviderKeys = is_array($settings['ai_api_keys'] ?? null) ? $settings['ai_api_keys'] : [];
                $aiKeyConfigured = !empty($aiProviderKeys[$aiProvider])
                    || ($aiProvider === 'zhipu' && !empty($settings['ai_api_key']));
                $aiIsMimo = $aiProvider === 'xiaomi_mimo';
                $aiModels = $aiIsMimo ? [
                    'mimo-v2.5-pro' => 'MiMo-V2.5-Pro',
                    'mimo-v2.5'     => 'MiMo-V2.5',
                ] : [
                    'glm-5.2'      => 'GLM-5.2',
                    'glm-4-air'    => 'GLM-4-Air',
                    'glm-4-flashx' => 'GLM-4-FlashX',
                ];
                $aiVisionModels = $aiIsMimo ? [
                    'mimo-v2.5' => 'MiMo-V2.5',
                ] : [
                    'glm-4.6v' => 'GLM-4.6V',
                    'glm-4.1v-thinking-flashx' => 'GLM-4.1V-Thinking-FlashX',
                ];
                $aiDefaultModel = $aiIsMimo ? 'mimo-v2.5-pro' : 'glm-4-flashx';
                $aiCurrentModel = (string)($settings['ai_model'] ?? $aiDefaultModel);
                if (!isset($aiModels[$aiCurrentModel])) {
                    $aiCurrentModel = $aiDefaultModel;
                }
            ?>
            <?php
                $aiChatOn   = !empty($settings['ai_chat_enabled']);
                $aiTicketOn = !empty($settings['ai_ticket_enabled']) || !empty($settings['ai_ticket_auto_reply']);
                $aiAppOn    = !empty($settings['ai_application_review_enabled']) || !empty($settings['ai_application_auto_review_enabled']) || !empty($settings['application_ai_auto_review_enabled']);
                $aiContentOn= !empty($settings['ai_content_enabled']);
                $aiBadge = function (bool $on): string { return $on ? '<span class="ai-badge">已启用</span>' : ''; };
                $aiUsage = function_exists('aiUsageSummary') && isUserSystemInstalled() ? aiUsageSummary(7) : ['calls' => 0, 'success' => 0, 'errors' => 0, 'cancelled' => 0, 'avg_latency_ms' => 0, 'total_tokens' => 0];
            ?>
            <div id="tab-ai_settings" class="tab-pane" style="display: <?= $currentTab === 'ai_settings' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <div class="ai-hero">
                        <div class="ai-hero-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 9h6v6H9z"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>
                        </div>
                        <div class="ai-hero-text">
                            <h3>AI 配置</h3>
                            <p>智谱 BigModel / Xiaomi MiMo · OpenAI Chat Completions 兼容</p>
                        </div>
                    </div>

                    <div class="ai-tip">
                        <div class="ai-tip-title">接入说明</div>
                        <div>• 支持智谱 GLM 与 Xiaomi MiMo，供应商切换后请填写对应平台的 API Key。</div>
                        <div>• API Key 在服务器端 <b>加密存储</b>，不会下发到前台页面。</div>
                        <div>• 默认端点兼容 OpenAI 风格 <code>/chat/completions</code>，支持流式输出。</div>
                        <div id="aiProviderCapabilityHint"><?= $aiIsMimo ? '• MiMo 模式支持原生联网搜索、深度思考与 MiMo-V2.5 工单图片分析；智谱知识库、内容审核和文档解析会停用。' : '• 智谱模式支持现有知识库、内容审核、联网搜索和文件解析能力。' ?></div>
                    </div>

                    <form method="POST" action="save.php" data-ajax="true" class="ai-form">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="tab" value="ai_settings">

                        <label class="ai-toggle ai-master">
                            <input type="checkbox" name="ai_enabled" value="1" <?= !empty($settings['ai_enabled']) ? 'checked' : '' ?>>
                            <span class="ai-toggle-text">启用 AI 功能总开关<small>关闭后所有 AI 能力停用</small></span>
                        </label>

                        <div class="ai-tip">
                            <div class="ai-tip-title">异步任务 Cron</div>
                            <div>建议每分钟通过 CLI 执行：<code>php <?= e(ADMIN_DIR . '/ai_cron.php') ?></code></div>
                            <div>HTTP 触发 Token：<code><?= e((string)($settings['ai_cron_token'] ?? '保存一次 AI 设置后自动生成')) ?></code></div>
                        </div>

                        <div class="ai-stats">
                            <div class="ai-stat">
                                <span class="ai-stat-label">近 7 天调用</span>
                                <span class="ai-stat-value"><?= number_format((int)$aiUsage['calls']) ?></span>
                            </div>
                            <div class="ai-stat">
                                <span class="ai-stat-label">成功 / 错误 / 取消</span>
                                <span class="ai-stat-value ai-stat-split"><?= number_format((int)$aiUsage['success']) ?> <i>/</i> <?= number_format((int)$aiUsage['errors']) ?> <i>/</i> <?= number_format((int)$aiUsage['cancelled']) ?></span>
                            </div>
                            <div class="ai-stat">
                                <span class="ai-stat-label">平均响应延迟</span>
                                <span class="ai-stat-value"><?= number_format((int)$aiUsage['avg_latency_ms']) ?> <em>ms</em></span>
                            </div>
                            <div class="ai-stat">
                                <span class="ai-stat-label">Token 总量</span>
                                <span class="ai-stat-value"><?= number_format((int)$aiUsage['total_tokens']) ?></span>
                            </div>
                        </div>

                        <!-- ===== 基础连接 ===== -->
                        <details class="ai-group" open>
                            <summary>
                                <span class="ai-group-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></span>
                                <span class="ai-group-titles"><span class="t">基础连接</span><span class="d">API 端点、模型、密钥与采样参数</span></span>
                                <span class="ai-group-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                            </summary>
                            <div class="ai-group-body">

                                <!-- 供应商 & 端点 & 默认模型 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">供应商与端点</span>
                                    </div>
                                    <div class="compact-field-grid">
                                        <div class="form-group">
                                            <label>API 供应商</label>
                                            <select name="ai_provider" id="aiProviderSelect" class="form-input">
                                                <option value="zhipu" <?= $aiProvider === 'zhipu' ? 'selected' : '' ?>>智谱 BigModel</option>
                                                <option value="xiaomi_mimo" <?= $aiProvider === 'xiaomi_mimo' ? 'selected' : '' ?>>Xiaomi MiMo</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>API 端点 Base URL</label>
                                            <input type="text" name="ai_base_url" id="aiBaseUrlInput" value="<?= e((string)($settings['ai_base_url'] ?? ($aiIsMimo ? 'https://api.xiaomimimo.com/v1' : 'https://open.bigmodel.cn/api/paas/v4'))) ?>" class="form-input" placeholder="<?= $aiIsMimo ? 'https://api.xiaomimimo.com/v1' : 'https://open.bigmodel.cn/api/paas/v4' ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>默认模型</label>
                                            <select name="ai_model" class="form-input" data-ai-model-select="text" data-allow-follow="0">
                                                <?php foreach ($aiModels as $mv => $ml): ?>
                                                <option value="<?= e($mv) ?>" <?= $aiCurrentModel === $mv ? 'selected' : '' ?>><?= e($ml) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- API Key -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">API 密钥</span>
                                        <?php if ($aiKeyConfigured): ?><span class="ai-badge">已配置</span><?php endif; ?>
                                    </div>
                                    <div class="ai-prompt-card">
                                        <label>API Key</label>
                                        <input type="password" name="ai_api_key" class="form-input" autocomplete="new-password" placeholder="<?= $aiKeyConfigured ? '已配置 — 留空则保持不变' : '粘贴对应供应商的 API Key' ?>">
                                        <?php if ($aiKeyConfigured): ?>
                                        <div class="ai-field-note" style="margin-top:10px;">
                                            <label style="display:inline-flex;align-items:center;gap:6px;color:#dc2626;cursor:pointer;font-size:.82rem;">
                                                <input type="checkbox" name="ai_api_key_clear" value="1"> 清除已保存的 API Key
                                            </label>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- 采样参数 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">采样参数</span>
                                        <span class="ai-section-desc">影响全局默认输出风格，各子功能可单独覆盖</span>
                                    </div>
                                    <div class="compact-field-grid">
                                        <div class="form-group">
                                            <label>采样温度 <span id="aiTemperatureRange" style="font-weight:400;color:var(--text-muted);font-size:.85em;">(0 ~ <?= $aiIsMimo ? '1.5' : '1' ?>)</span></label>
                                            <input type="number" step="0.1" min="0" max="<?= $aiIsMimo ? '1.5' : '1' ?>" id="aiTemperatureInput" name="ai_temperature" value="<?= e((string)($settings['ai_temperature'] ?? ($aiIsMimo ? 1.0 : 0.7))) ?>" class="form-input">
                                            <div class="ai-field-note">值越高回答越发散，越低越保守准确</div>
                                        </div>
                                        <div class="form-group">
                                            <label>单次回复最大 tokens</label>
                                            <input type="number" min="1" max="<?= $aiIsMimo ? '131072' : '8192' ?>" id="aiMaxTokensInput" name="ai_max_tokens" value="<?= e((string)($settings['ai_max_tokens'] ?? 1024)) ?>" class="form-input">
                                            <div class="ai-field-note">超出限制时回复会被截断</div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </details>

                        <!-- ===== 智能客服 ===== -->
                        <details class="ai-group">
                            <summary>
                                <span class="ai-group-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                                <span class="ai-group-titles"><span class="t">智能客服（用户端）</span><span class="d">对话入口、模型、提示词与能力开关</span></span>
                                <?= $aiBadge($aiChatOn) ?>
                                <span class="ai-group-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                            </summary>
                            <div class="ai-group-body">

                                <!-- 基础设置 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">基础设置</span>
                                    </div>
                                    <label class="ai-toggle ai-master">
                                        <input type="checkbox" name="ai_chat_enabled" value="1" <?= $aiChatOn ? 'checked' : '' ?>>
                                        <span class="ai-toggle-text">在用户中心开放「智能客服」对话入口<small>关闭后用户端不显示客服助手</small></span>
                                    </label>
                                    <div class="compact-field-grid" style="margin-top:14px;">
                                        <div class="form-group">
                                            <label>智能客服模型</label>
                                            <select name="ai_chat_model" class="form-input" data-ai-model-select="text" data-allow-follow="1">
                                                <?php $aiChatModel = (string)($settings['ai_chat_model'] ?? ''); ?>
                                                <option value="" <?= $aiChatModel === '' ? 'selected' : '' ?>>跟随默认模型（<?= e($aiModels[$aiCurrentModel] ?? $aiCurrentModel) ?>）</option>
                                                <?php foreach ($aiModels as $mv => $ml): ?>
                                                <option value="<?= e($mv) ?>" <?= $aiChatModel === $mv ? 'selected' : '' ?>><?= e($ml) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>每用户每日对话上限 <span style="font-weight:400;color:var(--text-muted);font-size:.85em;">（0 = 不限）</span></label>
                                            <input type="number" min="0" max="9999" name="ai_chat_daily_limit" value="<?= e((string)($settings['ai_chat_daily_limit'] ?? 50)) ?>" class="form-input">
                                        </div>
                                    </div>
                                    <div class="ai-prompt-card" style="margin-top:14px;">
                                        <label>系统提示词（System Prompt）</label>
                                        <textarea name="ai_chat_system_prompt" rows="4" class="form-input" placeholder="定义客服角色、服务器信息、回答风格…"><?= e((string)($settings['ai_chat_system_prompt'] ?? '')) ?></textarea>
                                    </div>
                                    <div class="form-group" style="margin-top:12px;">
                                        <label>欢迎语</label>
                                        <input type="text" name="ai_chat_welcome" value="<?= e((string)($settings['ai_chat_welcome'] ?? '')) ?>" class="form-input" placeholder="用户首次打开客服时显示的问候语">
                                    </div>
                                </div>

                                <!-- 知识库 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">知识库</span>
                                        <span class="ai-section-desc">仅智谱 BigModel 支持</span>
                                    </div>
                                    <div class="ai-feature-card" data-zhipu-only-feature="knowledge">
                                        <label class="ai-toggle">
                                            <input type="checkbox" name="ai_knowledge_enabled" value="1" <?= !empty($settings['ai_knowledge_enabled']) ? 'checked' : '' ?> <?= $aiIsMimo ? 'disabled' : '' ?>>
                                            <span class="ai-toggle-text">启用服务器知识库检索增强<small><?= $aiIsMimo ? 'Xiaomi MiMo 模式暂不支持' : '基于知识库检索增强回答准确度' ?></small></span>
                                        </label>
                                        <div class="ai-sub">
                                            <label>知识库 ID <span style="font-weight:400;color:var(--text-muted);font-size:.9em;">（多个以逗号分隔）</span></label>
                                            <input type="text" name="ai_knowledge_ids" value="<?= e((string)($settings['ai_knowledge_ids'] ?? '')) ?>" class="form-input" placeholder="例：kb_abc123, kb_def456">
                                        </div>
                                        <div class="ai-sub">
                                            <label>召回数量 / 最低相似度</label>
                                            <div class="compact-subgrid">
                                                <input type="number" min="1" max="20" name="ai_knowledge_top_k" value="<?= e((string)($settings['ai_knowledge_top_k'] ?? 6)) ?>" class="form-input" placeholder="召回数">
                                                <input type="number" min="0" max="1" step="0.05" name="ai_knowledge_threshold" value="<?= e((string)($settings['ai_knowledge_threshold'] ?? 0.2)) ?>" class="form-input" placeholder="相似度阈值">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 扩展能力 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">扩展能力</span>
                                        <span class="ai-section-desc">深度思考与联网搜索</span>
                                    </div>
                                    <div class="ai-feature-row">
                                        <div class="ai-feature-card">
                                            <label class="ai-toggle">
                                                <input type="checkbox" name="ai_thinking_enabled" value="1" <?= !empty($settings['ai_thinking_enabled']) ? 'checked' : '' ?>>
                                                <span class="ai-toggle-text">允许深度思考<small>先推理再回答，耗时更长</small></span>
                                            </label>
                                            <div class="ai-sub">
                                                <label>每日上限 <span style="font-weight:400;color:var(--text-muted);font-size:.9em;">（0 = 不限）</span></label>
                                                <input type="number" min="0" max="9999" name="ai_thinking_daily_limit" value="<?= e((string)($settings['ai_thinking_daily_limit'] ?? 10)) ?>" class="form-input">
                                            </div>
                                        </div>
                                        <div class="ai-feature-card">
                                            <label class="ai-toggle">
                                                <input type="checkbox" name="ai_search_enabled" value="1" <?= !empty($settings['ai_search_enabled']) ? 'checked' : '' ?>>
                                                <span class="ai-toggle-text">允许联网搜索<small>实时检索网络信息</small></span>
                                            </label>
                                            <div class="ai-sub">
                                                <label>每日上限 <span style="font-weight:400;color:var(--text-muted);font-size:.9em;">（0 = 不限）</span></label>
                                                <input type="number" min="0" max="9999" name="ai_search_daily_limit" value="<?= e((string)($settings['ai_search_daily_limit'] ?? 10)) ?>" class="form-input">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 安全审核 & 工作人员辅助 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">安全审核 & 工作人员辅助</span>
                                    </div>
                                    <div class="ai-feature-stack">

                                        <!-- 内容安全审核 -->
                                        <div class="ai-feature-card" data-zhipu-only-feature="moderation">
                                            <div class="ai-feature-card-row">
                                                <label class="ai-toggle">
                                                    <input type="checkbox" name="ai_moderation_enabled" value="1" <?= !empty($settings['ai_moderation_enabled']) ? 'checked' : '' ?> <?= $aiIsMimo ? 'disabled' : '' ?>>
                                                    <span class="ai-toggle-text">内容安全审核<small><?= $aiIsMimo ? 'Xiaomi MiMo 模式暂不支持' : '拦截可疑或违规输入内容' ?></small></span>
                                                </label>
                                                <div class="ai-sub">
                                                    <label>可疑内容（REVIEW）处理方式</label>
                                                    <select name="ai_moderation_review_action" class="form-input">
                                                        <option value="block" <?= ($settings['ai_moderation_review_action'] ?? 'block') === 'block' ? 'selected' : '' ?>>阻止提交</option>
                                                        <option value="allow" <?= ($settings['ai_moderation_review_action'] ?? 'block') === 'allow' ? 'selected' : '' ?>>允许提交并记录</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 工作人员 AI 辅助 -->
                                        <div class="ai-feature-card">
                                            <label class="ai-toggle">
                                                <input type="checkbox" name="ai_staff_enabled" value="1" <?= !empty($settings['ai_staff_enabled']) ? 'checked' : '' ?>>
                                                <span class="ai-toggle-text">工作人员 AI 辅助<small>工作人员可调用 AI 协助处理对话</small></span>
                                            </label>
                                            <div class="ai-sub">
                                                <label>每工作人员每日上限 <span style="font-weight:400;color:var(--text-muted);font-size:.9em;">（0 = 不限）</span></label>
                                                <input type="number" min="0" max="9999" name="ai_staff_daily_limit" value="<?= e((string)($settings['ai_staff_daily_limit'] ?? 50)) ?>" class="form-input">
                                            </div>
                                            <div class="ai-sub">
                                                <label>工作人员提示词 <span style="font-weight:400;color:var(--text-muted);font-size:.9em;">（留空使用内置默认值）</span></label>
                                                <textarea name="ai_staff_system_prompt" rows="5" class="form-input" placeholder="留空则使用内置提示词"><?= e((string)($settings['ai_staff_system_prompt'] ?? '')) ?></textarea>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </details>

                        <!-- ===== 工单 AI 辅助 ===== -->
                        <details class="ai-group">
                            <summary>
                                <span class="ai-group-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                                <span class="ai-group-titles"><span class="t">工单 AI 辅助</span><span class="d">回复草稿、自动回复与附件分析</span></span>
                                <?= $aiBadge($aiTicketOn) ?>
                                <span class="ai-group-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                            </summary>
                            <div class="ai-group-body">

                                <!-- 功能开关 & 模型 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">功能开关与模型</span>
                                    </div>
                                    <label class="ai-toggle ai-master">
                                        <input type="checkbox" name="ai_ticket_enabled" value="1" <?= !empty($settings['ai_ticket_enabled']) ? 'checked' : '' ?>>
                                        <span class="ai-toggle-text">启用工单 AI 回复<small>管理员可一键生成回复草稿</small></span>
                                    </label>
                                    <div class="compact-field-grid" style="margin-top:14px;">
                                        <div class="form-group">
                                            <label>纯文字工单模型</label>
                                            <select name="ai_ticket_text_model" class="form-input" data-ai-model-select="text" data-allow-follow="0">
                                                <?php $aiTicketTextModel = (string)($settings['ai_ticket_text_model'] ?? $aiDefaultModel); ?>
                                                <?php foreach ($aiModels as $mv => $ml): ?>
                                                <option value="<?= e($mv) ?>" <?= $aiTicketTextModel === $mv ? 'selected' : '' ?>><?= e($ml) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>有图片 / 附件工单模型</label>
                                            <select name="ai_ticket_vision_model" class="form-input" data-ai-model-select="vision" data-allow-follow="0">
                                                <?php $aiTicketVisionModel = (string)($settings['ai_ticket_vision_model'] ?? ($aiIsMimo ? 'mimo-v2.5' : 'glm-4.6v')); ?>
                                                <?php foreach ($aiVisionModels as $mv => $ml): ?>
                                                <option value="<?= e($mv) ?>" <?= $aiTicketVisionModel === $mv ? 'selected' : '' ?>><?= e($ml) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- 提示词 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">提示词</span>
                                    </div>
                                    <div class="ai-prompt-card">
                                        <label>回复提示词（System Prompt）</label>
                                        <textarea name="ai_ticket_system_prompt" rows="3" class="form-input" placeholder="留空则使用内置提示词"><?= e((string)($settings['ai_ticket_system_prompt'] ?? '')) ?></textarea>
                                    </div>
                                    <div class="ai-prompt-card" style="margin-top:12px;">
                                        <label>分析提示词 <span style="font-weight:400;color:var(--ai-green-text);font-size:.9em;">（分类 / 摘要 / 附件诊断，留空使用内置默认值）</span></label>
                                        <textarea name="ai_ticket_analysis_system_prompt" rows="3" class="form-input" placeholder="留空则使用内置提示词"><?= e((string)($settings['ai_ticket_analysis_system_prompt'] ?? '')) ?></textarea>
                                    </div>
                                </div>

                                <!-- 自动化 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">自动化</span>
                                    </div>
                                    <div class="ai-warn-bar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        <span>以下操作会以管理员身份直接发送回复并通知用户，请谨慎开启</span>
                                    </div>
                                    <div class="ai-feature-row" style="margin-top:14px;">
                                        <div class="ai-feature-card">
                                            <label class="ai-toggle is-caution">
                                                <input type="checkbox" name="ai_ticket_auto_reply" value="1" <?= !empty($settings['ai_ticket_auto_reply']) ? 'checked' : '' ?>>
                                                <span class="ai-toggle-text">新工单自动 AI 回复<small>直接回复并通知用户</small></span>
                                            </label>
                                        </div>
                                        <div class="ai-feature-card">
                                            <label class="ai-toggle">
                                                <input type="checkbox" name="ai_ticket_analysis_enabled" value="1" <?= !empty($settings['ai_ticket_analysis_enabled']) ? 'checked' : '' ?>>
                                                <span class="ai-toggle-text">自动分类、摘要与附件诊断<small>支持截图及 PDF/TXT/LOG，单文件最大 10MB</small></span>
                                            </label>
                                            <div class="ai-sub">
                                                <label>孤立附件清理天数</label>
                                                <input type="number" min="1" max="365" name="ai_ticket_file_cleanup_days" value="<?= e((string)($settings['ai_ticket_file_cleanup_days'] ?? 7)) ?>" class="form-input">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </details>

                        <!-- ===== 入服申请 AI 审核 ===== -->
                        <details class="ai-group">
                            <summary>
                                <span class="ai-group-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                                <span class="ai-group-titles"><span class="t">入服申请 AI 审核</span><span class="d">审核建议、自动审核与邮件通知</span></span>
                                <?= $aiBadge($aiAppOn) ?>
                                <span class="ai-group-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                            </summary>
                            <div class="ai-group-body">

                                <!-- 基础开关 & 模型 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">功能开关与模型</span>
                                    </div>
                                    <label class="ai-toggle ai-master">
                                        <input type="checkbox" name="ai_application_review_enabled" value="1" <?= (!empty($settings['ai_application_review_enabled']) || !empty($settings['ai_application_auto_review_enabled']) || !empty($settings['application_ai_auto_review_enabled'])) ? 'checked' : '' ?>>
                                        <span class="ai-toggle-text">启用入服申请 AI 审核建议<small>后台一键生成审核备注</small></span>
                                    </label>
                                    <div class="form-group" style="margin-top:14px;">
                                        <label>审核模型</label>
                                        <select name="ai_application_review_model" class="form-input" data-ai-model-select="text" data-allow-follow="1">
                                            <?php $aiAppReviewModel = (string)($settings['ai_application_review_model'] ?? ''); ?>
                                            <option value="" <?= $aiAppReviewModel === '' ? 'selected' : '' ?>>跟随默认模型（<?= e($aiModels[$aiCurrentModel] ?? $aiCurrentModel) ?>）</option>
                                            <?php foreach ($aiModels as $mv => $ml): ?>
                                            <option value="<?= e($mv) ?>" <?= $aiAppReviewModel === $mv ? 'selected' : '' ?>><?= e($ml) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- 自动化 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">自动审核</span>
                                    </div>
                                    <div class="ai-warn-bar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        <span>自动审核会直接写入通过 / 拒绝 / 需补充状态，请确认提示词与阈值后再开启</span>
                                    </div>
                                    <label class="ai-toggle is-caution" style="margin-top:14px;">
                                        <input type="checkbox" name="ai_application_auto_review_enabled" value="1" <?= (!empty($settings['ai_application_auto_review_enabled']) || !empty($settings['application_ai_auto_review_enabled'])) ? 'checked' : '' ?>>
                                        <span class="ai-toggle-text">新申请自动 AI 审核<small>直接写入状态，无需人工确认</small></span>
                                    </label>

                                    <!-- 邮件通知 -->
                                    <div class="ai-feature-row" style="margin-top:14px;">
                                        <div class="ai-feature-card">
                                            <label class="ai-toggle">
                                                <input type="checkbox" name="ai_application_auto_review_mail_approved" value="1" <?= (!empty($settings['ai_application_auto_review_mail_approved']) || !empty($settings['application_ai_auto_review_mail_approved'])) ? 'checked' : '' ?>>
                                                <span class="ai-toggle-text">自动通过时邮件通知管理员</span>
                                            </label>
                                        </div>
                                        <div class="ai-feature-card">
                                            <label class="ai-toggle">
                                                <input type="checkbox" name="ai_application_auto_review_mail_rejected" value="1" <?= (!empty($settings['ai_application_auto_review_mail_rejected']) || !empty($settings['application_ai_auto_review_mail_rejected'])) ? 'checked' : '' ?>>
                                                <span class="ai-toggle-text">自动拒绝 / 需补充时邮件通知管理员</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- 数值参数 -->
                                    <div class="ai-params-grid" style="margin-top:14px;">
                                        <div class="form-group">
                                            <label>不通过转人工次数 <span style="font-weight:400;color:var(--text-muted);font-size:.9em;">（0 = 不限）</span></label>
                                            <input type="number" min="0" max="999" name="ai_application_auto_review_fail_limit" value="<?= e((string)($settings['ai_application_auto_review_fail_limit'] ?? 0)) ?>" class="form-input" placeholder="例如 2">
                                        </div>
                                        <div class="form-group">
                                            <label>审核采样温度 <span style="font-weight:400;color:var(--text-muted);font-size:.9em;">（0~1，建议 0.1~0.2）</span></label>
                                            <input type="number" step="0.05" min="0" max="1" name="ai_application_review_temperature" value="<?= e((string)($settings['ai_application_review_temperature'] ?? 0.15)) ?>" class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>审核最大 tokens</label>
                                            <input type="number" min="1" max="8192" name="ai_application_review_max_tokens" value="<?= e((string)($settings['ai_application_review_max_tokens'] ?? 400)) ?>" class="form-input">
                                        </div>
                                    </div>
                                </div>

                                <!-- 提示词 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">审核提示词</span>
                                    </div>
                                    <div class="ai-prompt-card">
                                        <label>系统提示词（System Prompt）</label>
                                        <textarea name="ai_application_review_system_prompt" rows="5" class="form-input" placeholder="可写入服务器准入标准、拒绝条件、需补充条件等。自动审核时会强制要求 JSON 输出。"><?= e((string)($settings['ai_application_review_system_prompt'] ?? '')) ?></textarea>
                                        <div class="ai-field-note">自动审核时无论此提示词内容如何，系统均会强制追加 JSON 输出格式要求</div>
                                    </div>
                                </div>

                            </div>
                        </details>

                        <!-- ===== 内容生成 ===== -->
                        <details class="ai-group">
                            <summary>
                                <span class="ai-group-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg></span>
                                <span class="ai-group-titles"><span class="t">内容生成</span><span class="d">公告 / 商品管理中的「AI 生成」按钮</span></span>
                                <?= $aiBadge($aiContentOn) ?>
                                <span class="ai-group-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                            </summary>
                            <div class="ai-group-body">

                                <!-- 开关 & 模型 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">功能开关与模型</span>
                                    </div>
                                    <label class="ai-toggle ai-master">
                                        <input type="checkbox" name="ai_content_enabled" value="1" <?= $aiContentOn ? 'checked' : '' ?>>
                                        <span class="ai-toggle-text">在公告管理 / 商品管理中显示「AI 生成」按钮</span>
                                    </label>
                                    <div class="form-group" style="margin-top:14px;">
                                        <label>内容生成模型</label>
                                        <select name="ai_content_model" class="form-input" data-ai-model-select="text" data-allow-follow="1">
                                            <?php $aiContentModel = (string)($settings['ai_content_model'] ?? ''); ?>
                                            <option value="" <?= $aiContentModel === '' ? 'selected' : '' ?>>跟随默认模型（<?= e($aiModels[$aiCurrentModel] ?? $aiCurrentModel) ?>）</option>
                                            <?php foreach ($aiModels as $mv => $ml): ?>
                                            <option value="<?= e($mv) ?>" <?= $aiContentModel === $mv ? 'selected' : '' ?>><?= e($ml) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- 提示词层级 -->
                                <div class="ai-section">
                                    <div class="ai-section-header">
                                        <span class="ai-section-dot"></span>
                                        <span class="ai-section-title">提示词层级</span>
                                        <span class="ai-section-desc">全局提示词填写后将覆盖下方三个子类型</span>
                                    </div>
                                    <div class="ai-prompt-card">
                                        <label>全局提示词 <span style="font-weight:400;color:var(--ai-green-text);font-size:.9em;">（留空则各子类型独立使用各自提示词）</span></label>
                                        <textarea name="ai_content_system_prompt" rows="3" class="form-input" placeholder="填写后将覆盖下方三个子类型的提示词…"><?= e((string)($settings['ai_content_system_prompt'] ?? '')) ?></textarea>
                                        <div class="ai-field-note">优先级：全局提示词 &gt; 子类型提示词 &gt; 内置默认值</div>
                                    </div>

                                    <div class="ai-feature-row" style="margin-top:14px;">
                                        <div class="ai-feature-card">
                                            <div style="font-size:.8rem;font-weight:700;color:var(--ai-green-text);margin-bottom:8px;">公告生成</div>
                                            <div class="ai-sub" style="margin-top:0;">
                                                <textarea name="ai_content_announcement_system_prompt" rows="3" class="form-input" placeholder="留空则使用内置提示词"><?= e((string)($settings['ai_content_announcement_system_prompt'] ?? '')) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="ai-feature-card">
                                            <div style="font-size:.8rem;font-weight:700;color:var(--ai-green-text);margin-bottom:8px;">商品描述生成</div>
                                            <div class="ai-sub" style="margin-top:0;">
                                                <textarea name="ai_content_product_system_prompt" rows="3" class="form-input" placeholder="留空则使用内置提示词"><?= e((string)($settings['ai_content_product_system_prompt'] ?? '')) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="ai-feature-card">
                                            <div style="font-size:.8rem;font-weight:700;color:var(--ai-green-text);margin-bottom:8px;">通用内容生成</div>
                                            <div class="ai-sub" style="margin-top:0;">
                                                <textarea name="ai_content_general_system_prompt" rows="3" class="form-input" placeholder="留空则使用内置提示词"><?= e((string)($settings['ai_content_general_system_prompt'] ?? '')) ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </details>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">保存 AI 设置</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="tab-ai_playground" class="tab-pane" style="display: <?= $currentTab === 'ai_playground' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <div class="ai-hero">
                        <div class="ai-hero-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        </div>
                        <div class="ai-hero-text">
                            <h3>AI 测试台</h3>
                            <p>直接发消息验证当前 AI 配置，回复以打字机流式效果输出</p>
                        </div>
                    </div>
                    <div class="ai-form">
                        <div id="aiPlaygroundOutput" class="ai-play-output">（回复将显示在这里）</div>
                        <div class="ai-play-bar">
                            <input type="text" id="aiPlaygroundInput" class="form-input ai-play-input" placeholder="输入测试问题，例如：你们服务器怎么进？">
                            <button type="button" id="aiPlaygroundSend" class="btn-save">发送</button>
                            <button type="button" id="aiPlaygroundStop" class="btn-secondary" style="display:none;">停止</button>
                        </div>
                        <input type="hidden" id="aiPlaygroundCsrf" value="<?= e($csrf) ?>">
                    </div>
                </div>
            </div>

            <div id="tab-backup" class="tab-pane" style="display: <?= $currentTab === 'backup' ? 'block' : 'none' ?>">
                <div class="form-section">
                    <h3 class="section-title">数据备份与恢复</h3>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 16px;margin-bottom:18px;color:#15803d;font-size:.92em;line-height:1.7;">
                        <div style="font-weight:600;margin-bottom:4px;">📦 备份说明</div>
                        <div>• 每份备份是一个 <code>foxmc-backup-*.zip</code>，含 <code>database.sql</code> + <code>meta.json</code> + <code>manifest.json</code>（含每条目 SHA-256）。</div>
                        <div>• 勾选文件集后会同时备份 <code>admin/uploads/</code>、<code>user/uploads/</code>、<code>png/</code>、<code>egg/</code>、<code>assets/images/</code> 与必要的 <code>admin/data/</code> 配置/状态文件。</div>
                        <div>• 写入采用 <b>原子模式</b>（先写 <code>.tmp</code> 后重命名）+ <b>CRC 自检</b>，避免半文件污染列表。</div>
                        <div>• 恢复前会强制做 <b>逐条目 SHA-256 校验</b>，损坏或被篡改的文件将被拒绝以保护现有数据。</div>
                        <div>• 服务器仅保留最近 <?= (int)BACKUP_KEEP_LATEST ?> 份备份，旧备份将自动清理。建议同时下载到本地另存。</div>
                        <div>• 恢复操作会 <b style="color:#b91c1c;">覆盖当前数据</b>，恢复前系统会自动生成一份"恢复前快照"。</div>
                    </div>

                    <h3 class="section-title" style="margin-top:8px;">立即创建备份</h3>
                    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;margin-bottom:24px;">
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;">
                            <input type="checkbox" id="backupIncludeUploads">
                            <span>同时打包站点文件集（上传文件、用户头像、图片资源、必要配置/状态文件，备份体积会变大）</span>
                        </label>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="button" id="backupCreateBtn" class="btn-save">创建备份并保存到服务器</button>
                            <button type="button" id="backupCreateAndDownloadBtn" class="btn-secondary">创建并下载到本地</button>
                            <span id="backupCreateHint" style="color:#64748b;font-size:.88em;align-self:center;"></span>
                        </div>
                    </div>

                    <h3 class="section-title">本地备份列表</h3>
                    <div style="overflow-x:auto;">
                        <table id="backupListTable" style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                            <thead style="background:#f8fafc;">
                                <tr style="text-align:left;color:#475569;font-size:.88em;">
                                    <th style="padding:10px 12px;">文件名</th>
                                    <th style="padding:10px 12px;">导出时间</th>
                                    <th style="padding:10px 12px;">大小</th>
                                    <th style="padding:10px 12px;">表 / 行数</th>
                                    <th style="padding:10px 12px;">状态</th>
                                    <th style="padding:10px 12px;text-align:right;">操作</th>
                                </tr>
                            </thead>
                            <tbody id="backupListBody">
                                <tr><td colspan="6" style="padding:24px;text-align:center;color:#94a3b8;">加载中...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="section-title" style="margin-top:24px;">从备份文件恢复（上传 zip）</h3>
                    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
                        <div class="form-group">
                            <label>选择 .zip 备份文件</label>
                            <input type="file" id="backupUploadFile" accept=".zip,application/zip" class="form-input">
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;">
                            <input type="checkbox" id="backupUploadRestoreUploads">
                            <span>同时还原站点文件集（如果备份中包含）</span>
                        </label>
                        <button type="button" id="backupUploadRestoreBtn" class="btn-secondary" style="background:#dc2626;color:#fff;border-color:#dc2626;">上传并恢复</button>
                        <span style="color:#94a3b8;font-size:.85em;margin-left:10px;">将要求验证管理员密码</span>
                    </div>

                    <h3 class="section-title" style="margin-top:24px;">数据库修复 / 优化</h3>
                    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;margin-bottom:14px;color:#92400e;font-size:.92em;line-height:1.7;">
                        <div style="font-weight:600;margin-bottom:4px;">🛠 适用场景</div>
                        <div>• 把<b>旧版本 / 老数据库</b>导入新站后出现的小问题（缺字段、缺表、缺索引、字符集乱码等），可一键自动排查修复。</div>
                        <div>• 修复过程会<b>逐项独立执行，单步出错自动跳过</b>不影响其它步骤，并返回详细报告。</div>
                        <div>• 执行前会自动生成一份 <code>pre-repair</code> 快照，可随时回滚。</div>
                    </div>
                    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
                        <div style="font-weight:600;margin-bottom:10px;color:#334155;">修复项</div>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;">
                            <input type="checkbox" id="repairCheckRepair" checked>
                            <span>检查并修复损坏的表（CHECK / REPAIR）</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;">
                            <input type="checkbox" id="repairNormalizeCharset" checked>
                            <span>统一字符集为 <code>utf8mb4</code>（修复旧库 latin1 / utf8 乱码）</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;">
                            <input type="checkbox" id="repairOptimize" checked>
                            <span>优化表、整理碎片回收空间（OPTIMIZE）</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;">
                            <input type="checkbox" id="repairCleanOrphans">
                            <span style="color:#b45309;">清理孤儿数据（删除引用了不存在父记录的脏数据，<b>具破坏性，谨慎勾选</b>）</span>
                        </label>
                        <div style="font-size:.85em;color:#64748b;margin-bottom:12px;">注：补全缺失的表 / 字段 / 索引会<b>始终执行</b>，这是旧库导入问题的核心修复。</div>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                            <button type="button" id="dbRepairBtn" class="btn-save">开始修复 / 优化</button>
                            <span style="color:#94a3b8;font-size:.85em;">将要求验证管理员密码</span>
                        </div>
                        <div id="dbRepairResult" style="margin-top:14px;"></div>
                    </div>
                </div>
            </div>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-footer" class="tab-pane" style="display: <?= $currentTab === 'footer' ? 'block' : 'none' ?>">
                <form method="POST" action="save.php" enctype="multipart/form-data" data-ajax="true">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="tab" value="footer">
                    <div class="form-section">
                        <h3 class="section-title">页脚设置</h3>
                        <div class="form-group"><label>页脚描述</label><textarea name="footer[desc]" class="form-input" rows="3"><?= e($content['footer']['desc'] ?? '') ?></textarea></div>
                        <div class="form-group"><label>版权信息</label><input type="text" name="footer[copyright]" value="<?= e($content['footer']['copyright'] ?? '') ?>" class="form-input"></div>
                    </div>
                    <?php foreach (($content['footer']['friend_links'] ?? []) as $i => $link): ?>
                    <div class="form-section">
                        <h3 class="section-title">友情链接 <?= $i + 1 ?></h3>
                        <div class="form-row">
                            <div class="form-group"><label>名称</label><input type="text" name="footer[friend_links][<?= $i ?>][name]" value="<?= e($link['name'] ?? '') ?>" class="form-input"></div>
                            <div class="form-group"><label>链接</label><input type="text" name="footer[friend_links][<?= $i ?>][url]" value="<?= e($link['url'] ?? '') ?>" class="form-input" placeholder="https://example.com"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-section">
                        <h3 class="section-title">添加新友情链接</h3>
                        <div class="form-row">
                            <div class="form-group"><label>名称</label><input type="text" name="footer_new_link_name" class="form-input" placeholder="输入链接名称..."></div>
                            <div class="form-group"><label>链接</label><input type="text" name="footer_new_link_url" class="form-input" placeholder="https://example.com"></div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">保存更改</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div id="tab-footer" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

            <?php if ($renderSettingsTabs): ?>
            <div id="tab-images" class="tab-pane" style="display: <?= $currentTab === 'images' ? 'block' : 'none' ?>">
                <div class="tab-header">
                    <h2 class="tab-title">图片管理</h2>
                    <p class="tab-subtitle">浏览、上传和管理站点所有图片资源，检测未被引用的孤儿图片</p>
                </div>

                <!-- 工具栏 -->
                <div class="img-toolbar">
                    <div class="img-toolbar-left">
                        <select id="imgDirFilter" class="form-select img-select">
                            <option value="">全部目录</option>
                        </select>
                        <div class="img-search-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="imgSearch" class="form-input img-search" placeholder="搜索文件名…">
                        </div>
                        <button type="button" id="imgOrphanBtn" class="btn-secondary img-orphan-btn" title="只显示未被任何内容引用的孤儿图片">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span id="imgOrphanFilterLabel">只看孤儿</span>
                            <span class="orphan-count"></span>
                        </button>
                        <span id="imgStats" class="img-stats"></span>
                    </div>
                    <div class="img-toolbar-right">
                        <button type="button" id="imgSelectAll" class="btn-secondary">全选当前页</button>
                        <select id="imgUploadDir" class="form-select img-select" title="上传到">
                            <option value="admin_uploads">后台上传 (admin/uploads/)</option>
                            <option value="png">资源图片 (png/)</option>
                            <option value="egg">角色图标 (egg/)</option>
                            <option value="assets_images">站点图标 (assets/images/)</option>
                        </select>
                        <input type="file" id="imgUploadInput" accept="image/jpeg,image/png,image/gif,image/webp" multiple style="display:none;">
                        <button type="button" id="imgUploadBtn" class="btn-save">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
                            上传图片
                        </button>
                    </div>
                </div>

                <!-- 批量操作栏 -->
                <div id="imgSelBar" class="img-sel-bar">
                    <span id="imgSelCount">已选 0 张</span>
                    <button type="button" id="imgBatchDelete" class="btn-danger-sm" disabled>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                        删除所选
                    </button>
                    <button type="button" onclick="imgClearSelection()" class="btn-secondary-sm">取消选择</button>
                </div>

                <!-- 拖拽上传区（空状态引导） -->
                <div id="imgDropzone" class="img-dropzone" style="display:none;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
                    <p>拖拽图片到此处上传，或点击选择文件</p>
                </div>

                <!-- 图片网格 -->
                <div id="imgGrid" class="img-grid"></div>
            </div>
            <?php else: ?>
            <div id="tab-images" class="tab-pane" data-lazy="1" style="display:none;"></div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Lightbox (gallery) -->
    <div id="lightbox" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out;backdrop-filter:blur(4px);" onclick="closeLightbox()">
        <img id="lightboxImg" src="" alt="预览" style="max-width:90%;max-height:90%;object-fit:contain;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,0.5);animation:slideUp 0.3s ease;">
        <button onclick="closeLightbox()" style="position:absolute;top:20px;right:20px;background:rgba(255,255,255,0.2);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:1.5rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
    </div>

    <!-- 图片管理 Lightbox -->
    <div id="imgLightbox" class="img-lb-overlay" style="display:none;" onclick="if(event.target===this)imgCloseLightbox()">
        <div class="img-lb-box">
            <button class="img-lb-close" onclick="imgCloseLightbox()">&times;</button>
            <div class="img-lb-thumb">
                <img class="img-lb-img" src="" alt="预览">
            </div>
            <div class="img-lb-footer">
                <div class="img-lb-url-row">
                    <code class="img-lb-url"></code>
                </div>
                <div class="img-lb-info"></div>
                <div class="img-lb-btns">
                    <button class="img-lb-copy btn-secondary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        复制 URL
                    </button>
                    <button class="img-lb-delete btn-danger-sm">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                        删除
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php
        $iniToBytes = static function (string $val): int {
            $val  = trim($val);
            if ($val === '') return 0;
            $unit = strtolower(substr($val, -1));
            $num  = (int)$val;
            return match ($unit) {
                'g' => $num * 1024 * 1024 * 1024,
                'm' => $num * 1024 * 1024,
                'k' => $num * 1024,
                default => $num,
            };
        };
        $effectiveMax = (int)$MAX_UPLOAD_SIZE;
        foreach (['upload_max_filesize', 'post_max_size'] as $iniKey) {
            $b = $iniToBytes((string)ini_get($iniKey));
            if ($b > 0 && $b < $effectiveMax) $effectiveMax = $b;
        }
        $tipText = sprintf(
            '点击或拖拽图片到此处，支持 JPG / PNG / GIF / WebP，单文件 ≤ %s',
            $effectiveMax >= 1048576
                ? number_format($effectiveMax / 1048576, 1) . 'MB'
                : (number_format($effectiveMax / 1024, 1) . 'KB')
        );
        $aiTicketEnabled = false;
        if (function_exists('aiGetConfig')) {
            $_aiC = aiGetConfig();
            $aiTicketEnabled = aiIsConfigured($_aiC) && !empty($_aiC['ticket_enabled']);
        }
        $panelInitData = [
            'csrf'         => $csrf,
            'tabLabels'    => array_map(fn($t) => $t['label'], $tabs),
            'aiTicketEnabled' => $aiTicketEnabled,
            'lazyConfig'   => [
                'v' => [
                    'richtext' => filemtime(__DIR__ . '/js/richtext.js'),
                    'backup'   => filemtime(__DIR__ . '/js/backup.js'),
                    'rcon'     => filemtime(__DIR__ . '/js/rcon-preview.js'),
                ],
                'currentTab' => $currentTab,
            ],
            'uploadLimits' => [
                'max_bytes' => (int)$effectiveMax,
                'tip'       => $tipText,
                'ico_max_bytes' => (int)$MAX_ICO_SIZE,
                'ico_tip'       => sprintf(
                    '点击或拖拽 ICO 图标到此处，仅支持 .ico 格式，单文件 ≤ %s',
                    $MAX_ICO_SIZE >= 1048576
                        ? number_format($MAX_ICO_SIZE / 1048576, 1) . 'MB'
                        : (number_format($MAX_ICO_SIZE / 1024, 1) . 'KB')
                ),
            ],
        ];
    ?>
    <script id="panel-init-data" type="application/json"><?= json_encode($panelInitData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <script src="js/panel-data.js?v=<?= filemtime(__DIR__.'/js/panel-data.js') ?>"></script>
    <script src="js/lazy-loader.js?v=<?= filemtime(__DIR__.'/js/lazy-loader.js') ?>"></script>
    <script defer src="js/core.js?v=<?= filemtime(__DIR__.'/js/core.js') ?>"></script>
    <script defer src="js/messages.js?v=<?= filemtime(__DIR__.'/js/messages.js') ?>"></script>
    <script defer src="js/admin.js?v=<?= filemtime(__DIR__.'/js/admin.js') ?>"></script>
    <script defer src="js/panel-init.js?v=<?= filemtime(__DIR__.'/js/panel-init.js') ?>"></script>
    <script defer src="js/shop.js?v=<?= filemtime(__DIR__.'/js/shop.js') ?>"></script>
    <script defer src="js/images.js?v=<?= filemtime(__DIR__.'/js/images.js') ?>"></script>
    <script defer src="js/ai.js?v=<?= filemtime(__DIR__.'/js/ai.js') ?>"></script>
</body>
</html>
<?php
// 最终输出广告完整性校验
$__html = ob_get_clean();
if (!verifyAdOutput($__html, $settings)) {
    lockAdmin('广告输出被篡改（注释/隐藏/节点丢失）');
    requireAdIntegrity();
    exit;
}
echo $__html;
?>
