/**
 * Element Plus 挂载引导。
 *
 * 用途：把用户中心内的局部区块渐进式改造成 Vue 3 + Element Plus，
 * 与现有原生 JS（core.js / bootstrap.js 等）共存，不接管整页。
 *
 * 约定：容器加 data-ep-app="<名称>"，在 window.EpBlocks 上注册同名工厂函数，
 * 该函数返回 Vue 组件选项对象。挂载时自动注入中文语言包与图标组件。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        } else {
            // defer 脚本执行时 DOM 已就绪，但同一合并包里后续的 block 工厂函数
            // 还没有运行，用 setTimeout 推到下一个宏任务，保证 EpBlocks 全部注册完再挂载。
            setTimeout(fn, 0);
        }
    }

    function registerIcons(app) {
        var icons = window.ElementPlusIconsVue;
        if (!icons) return;
        Object.keys(icons).forEach(function (name) {
            app.component(name, icons[name]);
        });
    }

    function readProps(el) {
        var raw = el.getAttribute('data-ep-props');
        if (!raw) return {};
        try {
            return JSON.parse(raw);
        } catch (error) {
            console.warn('[element-bridge] data-ep-props 解析失败', el, error);
            return {};
        }
    }

    function mountBlock(el, overrideName, overrideProps) {
        if (el.dataset.epMounted === '1' && !overrideName) return;
        var name = overrideName || el.getAttribute('data-ep-app');
        var factory = window.EpBlocks[name];
        if (typeof factory !== 'function') {
            console.warn('[element-bridge] 未注册的区块：' + name);
            return;
        }

        var options = factory(overrideProps || readProps(el), el);
        if (!options) return;

        var app = Vue.createApp(options);
        app.use(ElementPlus, {
            locale: (window.ElementPlusLocaleZhCn || {}),
            zIndex: 3000
        });
        registerIcons(app);
        app.config.errorHandler = function (error) {
            console.error('[element-bridge] 区块 ' + name + ' 运行出错', error);
        };
        var instance = app.mount(el);
        el.__epApp = app;
        el.__epInstance = instance;
        el.dataset.epMounted = '1';
        el.removeAttribute('v-cloak');
    }

    function replaceBlock(el, name, props) {
        if (!el || typeof window.EpBlocks[name] !== 'function') return false;
        if (el.__epApp) el.__epApp.unmount();
        el.innerHTML = '';
        el.dataset.epMounted = '0';
        el.setAttribute('data-ep-app', name);
        el.setAttribute('data-ep-props', JSON.stringify(props || {}));
        mountBlock(el, name, props || {});
        return true;
    }

    function mountAll(root) {
        (root || document).querySelectorAll('[data-ep-app]').forEach(function (el) {
            mountBlock(el);
        });
    }

    ready(function () {
        if (!document.querySelector('[data-ep-app]')) return;
        if (typeof Vue === 'undefined' || typeof ElementPlus === 'undefined') {
            console.warn('[element-bridge] Vue 或 Element Plus 未加载，跳过挂载');
            return;
        }
        mountAll(document);
    });

    window.EpBridge = { mount: mountBlock, mountAll: mountAll, replace: replaceBlock };
}());
