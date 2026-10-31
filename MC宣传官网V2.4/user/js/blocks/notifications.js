/**
 * 站内通知 tab — Element Plus 版。
 * 当前 tab 直接进入时由 PHP 注入 props（preloaded）；从其它 tab 切过来时
 * 首次挂载走 AJAX 拉取第 1 页，加载更多同样走 AJAX。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var TYPE_TAG = {
        system:      { label: '系统通知', type: 'info' },
        application: { label: '申请相关', type: 'warning' },
        review:      { label: '审核结果', type: 'success' }
    };

    var TEMPLATE = ''
        + '<div class="ep-notifs">'

        + '  <el-skeleton v-if="loading && !list.length" :rows="6" animated></el-skeleton>'

        + '  <el-result v-else-if="failed" icon="error" title="通知加载失败" sub-title="请检查网络连接后重试"><template #extra><el-button type="primary" plain @click="load(true)">重新加载</el-button></template></el-result>'

        + '  <div v-else-if="list.length === 0" class="ep-empty">'
        + '    <el-empty description="暂无通知"></el-empty>'
        + '  </div>'

        + '  <div class="ep-notif-list" v-else>'
        + '    <el-card v-for="n in list" :key="n.id"'
        + '             class="ep-notif-item" :class="{ \'is-unread\': !n.is_read }"'
        + '             shadow="hover" @click="toggle(n)">'
        + '      <div class="ep-notif-head">'
        + '        <div class="ep-notif-meta">'
        + '          <el-badge v-if="!n.is_read" is-dot class="ep-unread-dot"></el-badge>'
        + '          <el-tag :type="tagType(n.type)" size="small" effect="light">{{ tagLabel(n.type) }}</el-tag>'
        + '          <el-text class="ep-notif-title">{{ n.title }}</el-text>'
        + '        </div>'
        + '        <el-text type="info" size="small" class="ep-notif-time">{{ n.created_at }}</el-text>'
        + '      </div>'
        + '      <div v-if="n.expanded" class="ep-notif-body">{{ n.content }}</div>'
        + '    </el-card>'
        + '  </div>'

        + '  <div class="ep-notif-footer">'
        + '    <el-button v-if="hasMore" :loading="loading" @click="loadMore" plain>加载更多（共 {{ total }} 条）</el-button>'
        + '    <el-text v-else-if="list.length > 0" type="info" size="small" class="ep-notif-end">已显示全部 {{ total }} 条通知</el-text>'
        + '  </div>'

        + '</div>';

    window.EpBlocks.userNotifications = function (props) {
        var preloaded = !!props.preloaded;
        var initial = (props.notifications || []).map(function (n) {
            return Object.assign({}, n, { expanded: false, is_read: !!n.is_read });
        });
        return {
            template: TEMPLATE,
            data: function () {
                return {
                    list:    initial,
                    total:   Number(props.total) || 0,
                    page:    1,
                    perPage: Number(props.perPage) || 20,
                    loading: false,
                    failed:  false,
                    loaded:  preloaded
                };
            },
            computed: {
                hasMore: function () { return this.list.length < this.total; }
            },
            mounted: function () {
                // 复用既有的 tab 切换钩子（core.js / bootstrap.js 会调用），
                // 覆盖 interactions.js 里旧原生版实现。
                window.ensureUserNotificationsLoaded = this.load;
                // 隐藏的 tab 交给切换钩子触发，避免首屏多一次无用请求。
                if (!this.loaded && this.$el.offsetParent !== null) this.load();
            },
            methods: {
                tagType:  function (t) { return (TYPE_TAG[t] || TYPE_TAG.system).type; },
                tagLabel: function (t) { return (TYPE_TAG[t] || TYPE_TAG.system).label; },
                toggle: function (n) {
                    n.expanded = !n.expanded;
                    if (!n.is_read) {
                        n.is_read = true;
                        this.markRead(n.id);
                    }
                },
                markRead: function (id) {
                    var fd = new FormData();
                    fd.append('csrf', window.userCsrf || '');
                    fd.append('notif_action', 'mark_read');
                    fd.append('id', id);
                    fetch('api/index.php?action=notifications', { method: 'POST', body: fd }).catch(function () {});
                },
                load: function (force) {
                    var self = this;
                    if (self.loading || (self.loaded && !force)) return;
                    self.loading = true;
                    self.failed = false;
                    fetch('api/index.php?action=notifications&notif_action=list&page=1', { credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            self.loading = false;
                            if (data.status !== 'success') { self.failed = true; return; }
                            self.loaded = true;
                            self.page = 1;
                            if (data.total) self.total = data.total;
                            var items = (data.data || []).map(function (n) {
                                return Object.assign({}, n, { expanded: false, is_read: !!n.is_read });
                            });
                            self.list = items;
                        })
                        .catch(function () {
                            self.loading = false;
                            self.failed = true;
                        });
                },
                loadMore: function () {
                    var self = this;
                    self.loading = true;
                    var nextPage = self.page + 1;
                    fetch('api/index.php?action=notifications&notif_action=list&page=' + nextPage)
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            self.loading = false;
                            var items = data.data || [];
                            if (items.length) {
                                self.page = nextPage;
                                items.forEach(function (n) {
                                    self.list.push(Object.assign({}, n, { expanded: false, is_read: !!n.is_read }));
                                });
                            }
                            if (data.total) self.total = data.total;
                        })
                        .catch(function () { self.loading = false; });
                }
            }
        };
    };
}());
