/**
 * 用户中心 Tab 资源懒加载。
 *
 * 位于 base 基础包中。首屏只下发「base + 当前 tab + 角色( staff )」，
 * 当用户首次切换到尚未加载的 tab 时，这里负责：
 *   1. 按需向 js.php/css.php 请求该 tab 对应的模块；
 *   2. 加载完成后调用 EpBridge.mountAll 挂载该 tab 的 Vue 容器；
 *   3. 执行该 tab 的数据加载回调。
 *
 * 依赖 _assets_js.php 注入的全局清单：window.userAssetLoadedSets / userAssetVersions / userAssetCssSets。
 */
(function () {
    'use strict';

    // 缺少清单则退化为无操作（不影响旧资源引用）。
    if (!window.userAssetVersions || !window.userAssetLoadedSets) return;

    var V = window.userAssetVersions;

    // tabKey -> 模块集合。模块名与 _asset_set.php 中定义的 key 一一对应。
    var MODULE_TAB_MAP = {
        panel: ['panel'],
        announcements: ['announcements'],
        tickets: ['tickets'],
        ai_assistant: ['ai'],
        shop: ['shop'],
        orders: ['shop'],
        profile: ['profile'],
        application: ['application'],
        notifications: ['notifications'],
        security: ['security'],
        account_delete: ['account_delete'],
        staff_panel: ['staff'],
        staff_messages: ['staff'],
        staff_announcements: ['staff'],
        staff_tickets: ['staff'],
        staff_applications: ['staff']
    };
    var CSS_SETS = window.userAssetCssSets || [];

    var loaded = {};   // 已请求过的模块
    var inflight = {}; // set -> [回调]

    // 首屏已随页面一起加载的模块，直接标记为已加载，避免重复请求。
    (window.userAssetLoadedSets || []).forEach(function (s) { loaded[s] = true; });

    function loadScript(src, done) {
        var s = document.createElement('script');
        s.src = src;
        s.async = false;
        s.onload = function () { done(); };
        s.onerror = function () { done(); };
        document.body.appendChild(s);
    }

    function loadCss(src) {
        if (document.querySelector('link[href="' + src + '"]')) return;
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = src;
        document.head.appendChild(l);
    }

    function remount() {
        if (window.EpBridge && typeof window.EpBridge.mountAll === 'function') {
            try { window.EpBridge.mountAll(document); } catch (e) { console.error(e); }
        }
    }

    function finish(set) {
        remount();
        var q = inflight[set] || [];
        inflight[set] = undefined;
        q.forEach(function (cb) { try { cb(); } catch (e) {} });
    }

    // 拉取单个模块（JS 必带；CSS 仅对声明了 css 的模块下发）
    function loadSet(set, done) {
        if (loaded[set]) { done(); return; }
        if (inflight[set]) { inflight[set].push(done); return; }

        loaded[set] = true;
        inflight[set] = [done];

        var cssUrl = (CSS_SETS.indexOf(set) >= 0)
            ? 'css.php?set=' + encodeURIComponent(set) + '&v=' + V.css : null;
        if (cssUrl) loadCss(cssUrl);

        loadScript('js.php?set=' + encodeURIComponent(set) + '&v=' + V.js, function () {
            finish(set);
        });
    }

    /**
     * 确保 tabKey 对应的所有模块已就绪（脚本已加载、Vue 容器已挂载），
     * 然后回调。
     */
    window.ensureUserTabLoaded = function (tabKey, cb) {
        cb = cb || function () {};
        var sets = MODULE_TAB_MAP[tabKey] || [];
        if (!document.getElementById('tab-' + tabKey) || !sets.length) {
            return cb();
        }
        var remaining = sets.length;
        var waiting = false;
        sets.forEach(function (set) {
            if (loaded[set] && !inflight[set]) {
                remaining -= 1;
                return;
            }
            waiting = true;
            loadSet(set, function () { remaining -= 1; if (remaining <= 0) cb(); });
        });
        if (!waiting && remaining <= 0) cb();
    };
}());