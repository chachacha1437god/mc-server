/**
 * 服务器公告 tab — Element Plus 时间线卡片版。
 * 当前 tab 直接进入时由 PHP 注入 props；从其它 tab 切过来时走 AJAX 懒加载。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var LEVEL_TAG = {
        info:    { label: '公告', type: 'info' },
        success: { label: '活动', type: 'success' },
        warning: { label: '维护', type: 'warning' },
        danger:  { label: '紧急', type: 'danger' }
    };

    var TEMPLATE = ''
        + '<div class="ep-announcements">'
        + '  <el-card shadow="never" class="ep-ann-shell">'
        + '    <template #header><div class="ep-card-head"><div><h2>服务器公告</h2><p>查看服务器活动、维护与重要通知</p></div><el-button circle title="刷新" :loading="loading" @click="load(true)"><el-icon><Refresh></Refresh></el-icon></el-button></div></template>'
        + '    <el-skeleton v-if="loading && !list.length" :rows="6" animated></el-skeleton>'
        + '    <el-result v-else-if="failed" icon="error" title="公告加载失败" sub-title="请检查网络连接后重试"><template #extra><el-button type="primary" plain @click="load(true)">重新加载</el-button></template></el-result>'
        + '    <el-empty v-else-if="!list.length" description="暂无公告"></el-empty>'
        + '    <template v-else>'
        + '      <div class="ep-ann-toolbar"><el-text type="info">共 {{ list.length }} 条公告</el-text><el-button text type="primary" @click="toggleAll"><el-icon><component :is="allOpen ? \'ArrowUp\' : \'ArrowDown\'"></component></el-icon>{{ allOpen ? \'全部收起\' : \'全部展开\' }}</el-button></div>'
        + '      <el-timeline class="ep-ann-timeline">'
        + '        <el-timeline-item v-for="a in list" :key="a.id" :timestamp="formatTime(a.publish_at || a.created_at)" placement="top" :type="timelineType(a.level)" :hollow="!isOpen(a.id)">'
        + '          <el-card shadow="never" class="ep-ann-card">'
        + '            <div class="ep-ann-card-head" role="button" tabindex="0" @click="toggle(a.id)" @keydown.enter="toggle(a.id)" @keydown.space.prevent="toggle(a.id)">'
        + '              <div><el-tag :type="tagType(a.level)" size="small" effect="light">{{ tagLabel(a.level) }}</el-tag><h3>{{ a.title }}</h3></div>'
        + '              <el-button text circle :title="isOpen(a.id) ? \'收起\' : \'展开\'" @click.stop="toggle(a.id)"><el-icon><component :is="isOpen(a.id) ? \'ArrowUp\' : \'ArrowDown\'"></component></el-icon></el-button>'
        + '            </div>'
        + '            <el-collapse-transition><div v-show="isOpen(a.id)" class="ep-ann-content"><el-divider></el-divider><el-text>{{ a.content }}</el-text></div></el-collapse-transition>'
        + '          </el-card>'
        + '        </el-timeline-item>'
        + '      </el-timeline>'
        + '    </template>'
        + '  </el-card>'
        + '</div>';

    window.EpBlocks.userAnnouncements = function (props) {
        var preloaded = !!props.preloaded;
        var initial = props.items || [];

        return {
            template: TEMPLATE,
            data: function () {
                return {
                    list:    initial,
                    active:  initial.length ? [String(initial[0].id)] : [],
                    loading: false,
                    failed:  false,
                    loaded:  preloaded,
                    Refresh: ElementPlusIconsVue.Refresh
                };
            },
            computed: {
                allOpen: function () {
                    return this.list.length > 0 && this.active.length === this.list.length;
                }
            },
            mounted: function () {
                // 复用既有的 tab 切换钩子（bootstrap.js / core.js 会调用）。
                window.ensureUserAnnouncementsLoaded = this.load;
                // 隐藏的 tab 交给切换钩子触发，避免首屏多一次无用请求。
                if (!this.loaded && this.$el.offsetParent !== null) this.load();
            },
            methods: {
                tagKey:   function (l) { return LEVEL_TAG[l] ? l : 'info'; },
                tagType:  function (l) { return (LEVEL_TAG[l] || LEVEL_TAG.info).type; },
                tagLabel: function (l) { return (LEVEL_TAG[l] || LEVEL_TAG.info).label; },
                timelineType: function (l) { return l === 'danger' ? 'danger' : this.tagType(l); },
                formatTime: function (value) { return value ? String(value).replace('T', ' ').slice(0, 16) : '-'; },
                isOpen: function (id) { return this.active.indexOf(String(id)) >= 0; },
                toggle: function (id) {
                    var key = String(id);
                    var index = this.active.indexOf(key);
                    if (index >= 0) this.active.splice(index, 1);
                    else this.active.push(key);
                },
                toggleAll: function () {
                    this.active = this.allOpen ? [] : this.list.map(function (a) { return String(a.id); });
                },
                load: function (force) {
                    var self = this;
                    if (self.loading || (self.loaded && !force)) return;
                    self.loading = true;
                    self.failed = false;
                    fetch('api/index.php?action=announcements', { credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            self.loading = false;
                            if (res.status !== 'success') { self.failed = true; return; }
                            self.loaded = true;
                            self.list = res.items || [];
                            self.active = self.list.length ? [String(self.list[0].id)] : [];
                        })
                        .catch(function () {
                            self.loading = false;
                            self.failed = true;
                        });
                }
            }
        };
    };
}());
