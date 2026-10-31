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
        window.dispatchEvent(new CustomEvent('foxmc-theme-change', {
            detail: { theme: theme, preferences: preferences }
        }));
        return theme;
    }

    function savePreferences(preferences) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
        applyTheme();
    }

    function ensureDialog() {
        var dialog = document.getElementById('themePreferencesDialog');
        if (dialog) return dialog;

        dialog = document.createElement('dialog');
        dialog.id = 'themePreferencesDialog';
        dialog.className = 'theme-dialog';
        dialog.innerHTML = ''
            + '<form method="dialog" class="theme-dialog-card">'
            + '<div class="theme-dialog-head"><div><h3>外观设置</h3><p>选择固定主题或按时间自动切换</p></div><button class="theme-icon-btn" value="cancel" aria-label="关闭" title="关闭">&times;</button></div>'
            + '<div class="theme-mode-group" role="radiogroup" aria-label="主题模式">'
            + '<label><input type="radio" name="themeMode" value="light"><span>浅色</span></label>'
            + '<label><input type="radio" name="themeMode" value="dark"><span>深色</span></label>'
            + '<label><input type="radio" name="themeMode" value="auto"><span>自动</span></label>'
            + '</div>'
            + '<div class="theme-schedule">'
            + '<label><span>开始时间</span><input type="time" name="themeStart" required></label>'
            + '<label><span>结束时间</span><input type="time" name="themeEnd" required></label>'
            + '<p>自动模式会在此时间段启用深色；支持跨午夜，例如 19:00 至 07:00。</p>'
            + '</div>'
            + '<div class="theme-dialog-actions"><button class="theme-cancel-btn" value="cancel">取消</button><button type="button" class="theme-save-btn">保存</button></div>'
            + '</form>';
        document.body.appendChild(dialog);

        dialog.querySelectorAll('input[name="themeMode"]').forEach(function (input) {
            input.addEventListener('change', function () {
                dialog.querySelector('.theme-schedule').hidden = input.value !== 'auto';
            });
        });
        dialog.querySelector('.theme-save-btn').addEventListener('click', function () {
            var selected = dialog.querySelector('input[name="themeMode"]:checked');
            savePreferences({
                mode: selected ? selected.value : defaults.mode,
                start: dialog.querySelector('input[name="themeStart"]').value || defaults.start,
                end: dialog.querySelector('input[name="themeEnd"]').value || defaults.end
            });
            dialog.close();
        });
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) dialog.close();
        });
        return dialog;
    }

    function openDialog() {
        var preferences = readPreferences();
        var dialog = ensureDialog();
        var modeInput = dialog.querySelector('input[name="themeMode"][value="' + preferences.mode + '"]');
        if (modeInput) modeInput.checked = true;
        dialog.querySelector('input[name="themeStart"]').value = preferences.start;
        dialog.querySelector('input[name="themeEnd"]').value = preferences.end;
        dialog.querySelector('.theme-schedule').hidden = preferences.mode !== 'auto';
        dialog.showModal();
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