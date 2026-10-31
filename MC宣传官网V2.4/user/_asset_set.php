<?php
/**
 * 用户中心资源「模块」唯一映射源。
 *
 * css.php / js.php 通过 ?set= 精确输出某个(些)模块的文件；
 * _assets_css.php / _assets_js.php / lazy-tabs.js 用同一份映射决定要加载哪些模块。
 *
 * 说明：
 *  - 每个模块含 'css' 与 'js' 两个文件列表；
 *  - 'base' 为基础运行时（侧边栏 + 各 tab 共用的交互/桥接），每次都加载；
 *  - 其余模块对应某个 tab，普通用户仅在首次切到该 tab 时懒加载；
 *  - 'staff' 按角色加载（工作人员专属）；
 *  - JS 文件顺序即先后加载顺序，改动前请与原有 js.php 顺序核对。
 */

if (!function_exists('userAssetModules')) {
    function userAssetModules(): array {
        return [
            'base' => [
                'css' => [
                    'css/base.css', 'css/auth.css', 'css/layout.css', 'css/forms.css',
                    'css/richtext.css', 'css/pages.css', 'css/effects.css', 'css/theme.css',
                ],
                'js' => [
                    'js/core.js', 'js/richtext.js', 'js/scroll.js', 'js/tickets.js',
                    'js/interactions.js', 'js/bootstrap.js', 'js/element-bridge.js',
                    'js/blocks/sidebar.js', 'js/lazy-tabs.js',
                ],
            ],
            'panel' => [
                'css' => [],
                'js'  => ['js/blocks/dashboard.js'],
            ],
            'tickets' => [
                'css' => [],
                'js'  => ['js/blocks/tickets.js', 'js/blocks/tickets-list.js'],
            ],
            'announcements' => [
                'css' => [],
                'js'  => ['js/blocks/announcements.js'],
            ],
            'notifications' => [
                'css' => [],
                'js'  => ['js/blocks/notifications.js'],
            ],
            'application' => [
                'css' => [],
                'js'  => ['js/blocks/application.js'],
            ],
            'profile' => [
                'css' => [],
                'js'  => ['js/blocks/profile.js'],
            ],
            'security' => [
                'css' => [],
                'js'  => ['js/blocks/security.js'],
            ],
            'shop' => [
                'css' => [],
                'js'  => ['js/blocks/shop-native.js'],
            ],
            'account_delete' => [
                'css' => [],
                'js'  => ['js/blocks/account-delete.js'],
            ],
            'ai' => [
                'css' => [],
                'js'  => ['js/blocks/ai-chat.js'],
            ],
            'staff' => [
                'css' => ['css/staff.css'],
                'js'  => ['js/blocks/staff.js'],
            ],
        ];
    }
}

/** 模块 KEY -> tab 的映射：panel 顶部的 allowedTabs 与模块名之间的对应。 */
if (!function_exists('userAssetTabModule')) {
    function userAssetTabModule(string $tab): ?string {
        $map = [
            'panel' => 'panel',
            'announcements' => 'announcements',
            'tickets' => 'tickets',
            'ai_assistant' => 'ai',
            'shop' => 'shop',
            'orders' => 'shop',
            'profile' => 'profile',
            'application' => 'application',
            'notifications' => 'notifications',
            'security' => 'security',
            'account_delete' => 'account_delete',
            'staff_panel' => 'staff',
            'staff_messages' => 'staff',
            'staff_announcements' => 'staff',
            'staff_tickets' => 'staff',
            'staff_applications' => 'staff',
        ];
        return $map[$tab] ?? null;
    }
}

/** 按可接受顺序展开一组模块 key 各自的文件列表（去重）。 */
if (!function_exists('userAssetFiles')) {
    function userAssetFiles(string $type, array $keys, ?array $modules = null): array {
        $modules = $modules ?? userAssetModules();
        // 规范输出顺序：base 优先，其余按模块定义顺序。
        $order = array_keys($modules);
        $out = [];
        $seen = [];
        foreach ($order as $key) {
            if (!in_array($key, $keys, true)) continue;
            $list = $modules[$key][$type] ?? [];
            foreach ($list as $f) {
                if (isset($seen[$f])) continue;
                $seen[$f] = true;
                $out[] = $f;
            }
        }
        return $out;
    }
}

/** 返回某类型全部文件（用于全局版本号，任何文件变动都让所有懒加载 URL 失效）。 */
if (!function_exists('userAssetAllFiles')) {
    function userAssetAllFiles(string $type): array {
        return userAssetFiles($type, array_keys(userAssetModules()));
    }
}

/** 计算一组文件的最高修改时间；基线取端点本身（css.php / js.php）的 mtime。 */
if (!function_exists('userAssetMaxMtime')) {
    function userAssetMaxMtime(array $files, ?string $baseDir = null, ?string $endpoint = null): int {
        $baseDir = $baseDir ?? __DIR__;
        $v = (int)@filemtime($baseDir . '/' . ($endpoint ?? 'css.php'));
        foreach ($files as $rel) {
            $t = (int)@filemtime($baseDir . '/' . $rel);
            if ($t > $v) $v = $t;
        }
        return $v;
    }
}

/** 校验并返回合法的 set key 列表（防止任意文件路径注入）。 */
if (!function_exists('userAssetNormalizeKeys')) {
    function userAssetNormalizeKeys(array $keys, ?array $modules = null): array {
        $modules = $modules ?? userAssetModules();
        $known = array_keys($modules);
        $out = [];
        foreach ($keys as $k) {
            $k = trim((string)$k);
            if ($k === '' || in_array($k, $out, true)) continue;
            if (in_array($k, $known, true)) $out[] = $k;
        }
        return $out;
    }
}