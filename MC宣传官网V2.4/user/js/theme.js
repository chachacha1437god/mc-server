(function () {
    'use strict';

    var STORAGE_KEY = 'foxmc-theme-preferences';
    var defaults = { mode: 'light', start: '19:00', end: '07:00' };
    var refreshTimer = null;

    function readPreferences() {
        try {
            var saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            return {
                mode: ['light', 'dark', 'auto'].indexOf(saved.mode) >= 0 ? saved.mode : defaults.mode,
                start: /^\d{2}:\d{2}$/.test(saved.start || '') ? saved.start : defaults.start,
                end: /^\d{2}:\d{2}$/.test(saved.end || '') ? saved.end : defaults.end
            };
        } catch (error) {
            return Object.assign({}, defaults);
        }
    }

    function isDarkTime(start, end, now) {
        var currentMinutes = now.getHours() * 60 + now.getMinutes();
        var startParts = start.split(':').map(Number);
        var endParts = end.split(':').map(Number);
        var startMinutes = startParts[0] * 60 + startParts[1];
        var endMinutes = endParts[0] * 60 + endParts[1];

        if (startMinutes === endMinutes) return true;
        if (startMinutes < endMinutes) {
            return currentMinutes >= startMinutes && currentMinutes < endMinutes;
        }
        return currentMinutes >= startMinutes || currentMinutes < endMinutes;
    }

    function resolveTheme(preferences) {
        if (preferences.mode !== 'auto') return preferences.mode;
        return isDarkTime(preferences.start, preferences.end, new Date()) ? 'dark' : 'light';
    }

    function applyTheme() {
        var preferences = readPreferences();
        var theme = resolveTheme(preferences);
        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;
        // Element Plus 官方暗色变量以 html.dark 为选择器，需与 data-theme 同步
        document.documentElement.classList.toggle('dark', theme === 'dark');
        window.dispatchEvent(new CustomEvent('foxmc-theme-change', {
            detail: { theme: theme, preferences: preferences }
        }));
        return theme;
    }

    function savePreferences(preferences) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
        applyTheme();
    }

    var themeApp = null;

    function ensureThemeApp() {
        if (themeApp) return themeApp;
        if (!window.Vue || !window.ElementPlus) return null;

        var mount = document.createElement('div');
        mount.id = 'themePreferencesApp';
        document.body.appendChild(mount);
        var app = Vue.createApp({
            template: ''
                + '<el-dialog v-model="visible" title="外观设置" width="min(460px, calc(100vw - 32px))" append-to-body class="ep-theme-dialog">'
                + '  <el-form label-position="top">'
                + '    <el-form-item label="主题模式"><el-radio-group v-model="form.mode"><el-radio-button value="light">浅色</el-radio-button><el-radio-button value="dark">深色</el-radio-button><el-radio-button value="auto">自动</el-radio-button></el-radio-group></el-form-item>'
                + '    <template v-if="form.mode === \'auto\'"><el-row :gutter="12"><el-col :span="12"><el-form-item label="开始时间"><el-time-picker v-model="form.start" value-format="HH:mm" format="HH:mm" placeholder="开始时间"></el-time-picker></el-form-item></el-col><el-col :span="12"><el-form-item label="结束时间"><el-time-picker v-model="form.end" value-format="HH:mm" format="HH:mm" placeholder="结束时间"></el-time-picker></el-form-item></el-col></el-row><el-alert title="自动模式会在设定时段启用深色主题，支持跨午夜。" type="info" :closable="false" show-icon></el-alert></template>'
                + '  </el-form>'
                + '  <template #footer><el-button @click="visible=false">取消</el-button><el-button type="primary" @click="save">保存</el-button></template>'
                + '</el-dialog>',
            data: function () {
                return { visible: false, form: Object.assign({}, defaults) };
            },
            methods: {
                open: function () {
                    this.form = readPreferences();
                    this.visible = true;
                },
                save: function () {
                    savePreferences({
                        mode: this.form.mode,
                        start: this.form.start || defaults.start,
                        end: this.form.end || defaults.end
                    });
                    this.visible = false;
                    ElementPlus.ElMessage.success('外观设置已保存');
                }
            }
        });
        app.use(ElementPlus, { locale: window.ElementPlusLocaleZhCn || undefined });
        themeApp = app.mount(mount);
        return themeApp;
    }

    function openDialog() {
        var instance = ensureThemeApp();
        if (instance) instance.open();
    }

    window.FoxmcTheme = { apply: applyTheme, open: openDialog };
    applyTheme();
    refreshTimer = window.setInterval(applyTheme, 60000);
    window.addEventListener('storage', function (event) {
        if (event.key === STORAGE_KEY) applyTheme();
    });
    window.addEventListener('beforeunload', function () {
        window.clearInterval(refreshTimer);
    });
}());