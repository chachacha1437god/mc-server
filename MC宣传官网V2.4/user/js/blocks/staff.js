(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var API = 'api/staff.php';

    function apiGet(action, params) {
        var query = new URLSearchParams(Object.assign({ action: action }, params || {}));
        return fetch(API + '?' + query.toString(), { credentials: 'same-origin' }).then(function (response) {
            return response.json();
        });
    }

    function apiPost(action, data) {
        var form = data instanceof FormData ? data : new FormData();
        if (!(data instanceof FormData)) {
            Object.keys(data || {}).forEach(function (key) {
                form.append(key, data[key]);
            });
        }
        form.set('action', action);
        form.set('csrf', window.userCsrf || '');
        return fetch(API, { method: 'POST', body: form, credentials: 'same-origin' }).then(function (response) {
            return response.json();
        });
    }

    function notify(message, type) {
        ElementPlus.ElMessage({ message: message, type: type || 'info' });
    }

    var OVERVIEW_TEMPLATE = ''
        + '<div class="staff-overview">'
        + '  <el-card shadow="never">'
        + '    <template #header><div class="ep-card-head"><div><h2>工作台概览</h2><p>集中处理需要工作人员跟进的站点事务</p></div><el-button :icon="Refresh" circle title="刷新" :loading="loading" @click="load"></el-button></div></template>'
        + '    <el-alert type="info" :closable="false" show-icon title="所有处理操作都会写入审计日志。"></el-alert>'
        + '    <div class="staff-stats">'
        + '      <el-card v-for="item in stats" :key="item.key" class="staff-stat-card" shadow="hover" @click="go(item.tab)">'
        + '        <el-statistic :value="Number(values[item.key]) || 0"><template #title><div class="staff-stat-title"><el-icon><component :is="item.icon"></component></el-icon>{{ item.label }}</div></template></el-statistic>'
        + '      </el-card>'
        + '    </div>'
        + '  </el-card>'
        + '  <el-card shadow="never" class="staff-notify-settings">'
        + '    <template #header><div class="ep-card-head"><div><h2>工作人员邮箱提醒</h2><p>每位工作人员可设置一个独立的提醒邮箱</p></div><el-tag :type="synced ? \'success\' : \'info\'" effect="plain">{{ synced ? \'已同步\' : \'读取中\' }}</el-tag></div></template>'
        + '    <el-form label-position="top" @submit.prevent="save">'
        + '      <el-form-item label="提醒邮箱"><el-input v-model.trim="form.email" type="email" maxlength="254" placeholder="staff@example.com" clearable><template #prefix><el-icon><Message></Message></el-icon></template></el-input></el-form-item>'
        + '      <div class="staff-notify-options">'
        + '        <div v-for="option in notifyOptions" :key="option.key" class="staff-notify-option"><div><strong>{{ option.label }}</strong><small>{{ option.desc }}</small></div><el-switch v-model="form[option.key]"></el-switch></div>'
        + '      </div>'
        + '      <div class="staff-notify-actions"><el-button type="primary" native-type="submit" :loading="saving"><el-icon><Check></Check></el-icon>保存提醒设置</el-button></div>'
        + '    </el-form>'
        + '  </el-card>'
        + '</div>';

    window.EpBlocks.staffOverview = function () {
        return {
            template: OVERVIEW_TEMPLATE,
            data: function () {
                return {
                    Refresh: ElementPlusIconsVue.Refresh,
                    loading: false,
                    saving: false,
                    synced: false,
                    values: {},
                    form: { email: '', ticket: false, message: false, application: false },
                    stats: [
                        { key: 'unread_messages', label: '未读留言', icon: 'ChatDotRound', tab: 'staff_messages' },
                        { key: 'open_tickets', label: '待处理工单', icon: 'Tickets', tab: 'staff_tickets' },
                        { key: 'pending_applications', label: '待审入服申请', icon: 'DocumentChecked', tab: 'staff_applications' },
                        { key: 'active_announcements', label: '已发布公告', icon: 'Bell', tab: 'staff_announcements' }
                    ],
                    notifyOptions: [
                        { key: 'ticket', label: '新工单', desc: '用户提交新工单时提醒' },
                        { key: 'message', label: '新消息', desc: '官网收到联系留言时提醒' },
                        { key: 'application', label: '新入服申请', desc: '用户提交或重新提交申请时提醒' }
                    ]
                };
            },
            mounted: function () {
                this.load();
            },
            methods: {
                load: function () {
                    var self = this;
                    self.loading = true;
                    apiGet('overview').then(function (result) {
                        if (result.status !== 'success') throw new Error(result.message || '加载失败');
                        var data = result.data || {};
                        self.values = data;
                        self.form.email = data.notify_email || '';
                        self.form.ticket = !!data.notify_ticket_enabled;
                        self.form.message = !!data.notify_message_enabled;
                        self.form.application = !!data.notify_application_enabled;
                        self.synced = true;
                    }).catch(function (error) {
                        notify(error.message || '加载概览失败', 'error');
                    }).finally(function () {
                        self.loading = false;
                    });
                },
                save: function () {
                    var self = this;
                    self.saving = true;
                    apiPost('notification_settings_save', {
                        notify_email: self.form.email,
                        notify_ticket_enabled: self.form.ticket ? '1' : '',
                        notify_message_enabled: self.form.message ? '1' : '',
                        notify_application_enabled: self.form.application ? '1' : ''
                    }).then(function (result) {
                        if (result.status !== 'success') throw new Error(result.message || '保存失败');
                        self.synced = true;
                    }).finally(function () {
                        self.saving = false;
                    });
                },
                go: function (tab) {
                    if (window.switchUserTab) window.switchUserTab(tab);
                }
            }
        };
    };

    function formatTime(value) {
        return value ? String(value).slice(0, 16).replace('T', ' ') : '-';
    }

    function confirmAction(message, type) {
        return ElementPlus.ElMessageBox.confirm(message, '操作确认', {
            confirmButtonText: '确认',
            cancelButtonText: '取消',
            type: type || 'warning'
        });
    }

    function generateDraft(draftType, entityId) {
        return apiPost('ai_draft', { draft_type: draftType, entity_id: entityId }).then(function (result) {
            if (result.status !== 'success') throw new Error(result.message || '生成失败');
            notify(result.message || '草稿已生成', 'success');
            return result.draft || '';
        });
    }

    var MESSAGES_TEMPLATE = ''
        + '<el-card class="staff-manage-card" shadow="never">'
        + '  <template #header><div class="ep-card-head"><div><h2>留言管理</h2><p>查看官网联系留言并通过邮件回复</p></div><el-button :icon="Refresh" circle title="刷新" :loading="loading" @click="load"></el-button></div></template>'
        + '  <div class="staff-toolbar"><el-input v-model="search" placeholder="搜索姓名 / 邮箱 / 主题 / 内容" clearable :prefix-icon="Search" @keyup.enter="applyFilters"></el-input><el-select v-model="filter" @change="applyFilters"><el-option label="全部" value=""></el-option><el-option label="仅未读" value="unread"></el-option><el-option label="未回复" value="unreplied"></el-option><el-option label="已回复" value="replied"></el-option></el-select><el-button type="primary" @click="applyFilters">查询</el-button></div>'
        + '  <el-table :data="rows" v-loading="loading" stripe empty-text="暂无留言" @row-click="open">'
        + '    <el-table-column prop="name" label="留言人" min-width="130"></el-table-column><el-table-column prop="email" label="邮箱" min-width="190" show-overflow-tooltip></el-table-column><el-table-column prop="subject" label="主题" min-width="130"></el-table-column>'
        + '    <el-table-column label="状态" width="150"><template #default="scope"><el-tag :type="Number(scope.row.is_read) ? \'info\' : \'warning\'" size="small">{{ Number(scope.row.is_read) ? \'已读\' : \'未读\' }}</el-tag><el-tag v-if="Number(scope.row.is_replied)" type="success" size="small">已回复</el-tag></template></el-table-column>'
        + '    <el-table-column label="时间" width="150"><template #default="scope">{{ fmt(scope.row.created_at) }}</template></el-table-column><el-table-column label="操作" width="90" fixed="right"><template #default="scope"><el-button link type="primary" @click.stop="open(scope.row)">查看</el-button></template></el-table-column>'
        + '  </el-table><el-pagination v-if="total" class="staff-pagination" background layout="total, prev, pager, next" :total="total" :page-size="20" v-model:current-page="page" @current-change="load"></el-pagination>'
        + '</el-card>'
        + '<el-dialog v-model="visible" title="留言详情" width="min(720px, 94vw)" destroy-on-close>'
        + '  <template v-if="current"><el-descriptions :column="1" border><el-descriptions-item label="留言人">{{ current.name || \'匿名\' }}</el-descriptions-item><el-descriptions-item label="邮箱">{{ current.email }}</el-descriptions-item><el-descriptions-item label="主题">{{ current.subject || \'无主题\' }}</el-descriptions-item><el-descriptions-item label="时间">{{ fmt(current.created_at) }}</el-descriptions-item></el-descriptions><div class="staff-content">{{ current.message }}</div>'
        + '  <el-image v-for="image in current.images || []" :key="image" class="staff-image" :src="\'../\' + String(image).replace(/^\\/+/, \'\')" :preview-src-list="(current.images || []).map(function (item) { return \'../\' + String(item).replace(/^\\/+/, \'\'); })" fit="cover"></el-image>'
        + '  <el-form label-position="top" class="staff-dialog-form"><el-form-item label="邮件回复"><el-input v-model="reply" type="textarea" :rows="5" maxlength="5000" show-word-limit placeholder="回复将通过后台 SMTP 发送"></el-input></el-form-item><el-upload :auto-upload="false" multiple :limit="5" accept="image/jpeg,image/png,image/gif,image/webp" v-model:file-list="files"><el-button :icon="Paperclip">添加图片附件</el-button><template #tip><div class="el-upload__tip">最多 5 张，单张不超过 5MB</div></template></el-upload></el-form></template>'
        + '  <template #footer><el-button v-if="current && !Number(current.is_read)" @click="markRead">标记已读</el-button><el-button v-if="current" type="danger" plain @click="remove">删除</el-button><el-button :disabled="!current" :loading="drafting" @click="draft"><el-icon><MagicStick></MagicStick></el-icon>AI 草稿</el-button><el-button type="primary" :loading="submitting" @click="send">发送回复</el-button></template>'
        + '</el-dialog>';

    window.EpBlocks.staffMessages = function () {
        return {
            template: MESSAGES_TEMPLATE,
            data: function () { return { Refresh: ElementPlusIconsVue.Refresh, Search: ElementPlusIconsVue.Search, Paperclip: ElementPlusIconsVue.Paperclip, rows: [], total: 0, page: 1, search: '', filter: '', loading: false, visible: false, current: null, reply: '', files: [], submitting: false, drafting: false }; },
            mounted: function () { this.load(); },
            methods: {
                fmt: formatTime,
                applyFilters: function () { this.page = 1; this.load(); },
                load: function () { var self = this; self.loading = true; return apiGet('messages_list', { page: self.page, search: self.search, filter: self.filter }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.rows = result.rows || []; self.total = Number(result.total) || 0; }).catch(function (error) { notify(error.message || '留言加载失败', 'error'); }).finally(function () { self.loading = false; }); },
                open: function (row) { this.current = row; this.reply = ''; this.files = []; this.visible = true; },
                draft: function () { var self = this; if (!self.current) return; self.drafting = true; generateDraft('message_reply', self.current.id).then(function (text) { self.reply = text; }).catch(function (error) { notify(error.message, 'error'); }).finally(function () { self.drafting = false; }); },
                markRead: function () { var self = this; apiPost('messages_mark_read', { id: self.current.id }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.current.is_read = 1; notify(result.message, 'success'); self.load(); }).catch(function (error) { notify(error.message, 'error'); }); },
                remove: function () { var self = this; confirmAction('确定删除这条留言？此操作不可撤销。', 'error').then(function () { return apiPost('messages_delete', { id: self.current.id }); }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.visible = false; notify(result.message, 'success'); self.load(); }).catch(function (error) { if (error !== 'cancel' && error !== 'close') notify(error.message || '删除失败', 'error'); }); },
                send: function () { var self = this; if (!self.current || !self.reply.trim()) return notify('请填写回复内容', 'warning'); var form = new FormData(); form.append('id', self.current.id); form.append('reply_content', self.reply.trim()); self.files.forEach(function (item, index) { if (item.raw) form.append('reply_image_' + index, item.raw); }); self.submitting = true; apiPost('messages_reply', form).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.visible = false; notify(result.message, 'success'); self.load(); }).catch(function (error) { notify(error.message || '发送失败', 'error'); }).finally(function () { self.submitting = false; }); }
            }
        };
    };

    var ANNOUNCEMENTS_TEMPLATE = ''
        + '<el-card class="staff-manage-card" shadow="never"><template #header><div class="ep-card-head"><div><h2>公告管理</h2><p>发布和维护站点公告</p></div><div><el-button :icon="Refresh" circle title="刷新" :loading="loading" @click="load"></el-button><el-button type="primary" :icon="Plus" @click="edit()">新建公告</el-button></div></div></template>'
        + '<el-table :data="rows" v-loading="loading" stripe empty-text="暂无公告"><el-table-column prop="title" label="标题" min-width="220" show-overflow-tooltip></el-table-column><el-table-column label="级别" width="90"><template #default="scope"><el-tag :type="levelType(scope.row.level)" size="small">{{ levelLabel(scope.row.level) }}</el-tag></template></el-table-column><el-table-column label="状态" width="150"><template #default="scope"><el-tag :type="Number(scope.row.is_active) ? \'success\' : \'info\'" size="small">{{ Number(scope.row.is_active) ? \'已上线\' : \'已下线\' }}</el-tag><el-tag v-if="Number(scope.row.is_pinned)" type="warning" size="small">置顶</el-tag></template></el-table-column><el-table-column label="发布时间" width="160"><template #default="scope">{{ fmt(scope.row.publish_at || scope.row.created_at) }}</template></el-table-column><el-table-column label="操作" width="130" fixed="right"><template #default="scope"><el-button link type="primary" @click="edit(scope.row)">编辑</el-button><el-button link type="danger" @click="remove(scope.row)">删除</el-button></template></el-table-column></el-table></el-card>'
        + '<el-dialog v-model="visible" :title="form.id ? \'编辑公告\' : \'新建公告\'" width="min(760px, 94vw)" destroy-on-close><el-form label-position="top"><el-form-item label="标题" required><el-input v-model="form.title" maxlength="120" show-word-limit></el-input></el-form-item><el-form-item label="内容" required><el-input v-model="form.content" type="textarea" :rows="6" maxlength="5000" show-word-limit></el-input></el-form-item><el-row :gutter="12"><el-col :xs="24" :sm="8"><el-form-item label="级别"><el-select v-model="form.level"><el-option v-for="item in levels" :key="item.value" :label="item.label" :value="item.value"></el-option></el-select></el-form-item></el-col><el-col :xs="24" :sm="8"><el-form-item label="定时发布"><el-date-picker v-model="form.publish_at" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="可选"></el-date-picker></el-form-item></el-col><el-col :xs="24" :sm="8"><el-form-item label="有效时间"><el-date-picker v-model="dateRange" type="datetimerange" value-format="YYYY-MM-DD HH:mm:ss" start-placeholder="开始" end-placeholder="结束"></el-date-picker></el-form-item></el-col></el-row><div class="staff-switch-grid"><div v-for="option in announcementOptions" :key="option.key"><span>{{ option.label }}</span><el-switch v-model="form[option.key]"></el-switch></div></div></el-form><template #footer><el-button @click="visible=false">取消</el-button><el-button type="primary" :loading="saving" @click="save">保存</el-button></template></el-dialog>';

    window.EpBlocks.staffAnnouncements = function () {
        function blank() { return { id: '', title: '', content: '', level: 'info', publish_at: '', start_at: '', end_at: '', is_active: true, is_pinned: false, show_in_home: false, show_in_user_center: true, show_as_popup: false }; }
        return {
            template: ANNOUNCEMENTS_TEMPLATE,
            data: function () { return { Refresh: ElementPlusIconsVue.Refresh, Plus: ElementPlusIconsVue.Plus, rows: [], loading: false, saving: false, visible: false, form: blank(), dateRange: [], levels: [{ value: 'info', label: '普通' }, { value: 'success', label: '活动' }, { value: 'warning', label: '维护' }, { value: 'danger', label: '紧急' }], announcementOptions: [{ key: 'is_active', label: '上线显示' }, { key: 'is_pinned', label: '置顶' }, { key: 'show_in_home', label: '首页显示' }, { key: 'show_in_user_center', label: '用户中心显示' }, { key: 'show_as_popup', label: '弹窗提醒' }] }; },
            mounted: function () { this.load(); },
            methods: {
                fmt: formatTime,
                levelLabel: function (level) { return { info: '普通', success: '活动', warning: '维护', danger: '紧急' }[level] || level; },
                levelType: function (level) { return { info: 'primary', success: 'success', warning: 'warning', danger: 'danger' }[level] || 'info'; },
                load: function () { var self = this; self.loading = true; apiGet('announcements_list').then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.rows = result.rows || []; }).catch(function (error) { notify(error.message || '公告加载失败', 'error'); }).finally(function () { self.loading = false; }); },
                edit: function (row) { this.form = row ? Object.assign(blank(), row, { is_active: !!Number(row.is_active), is_pinned: !!Number(row.is_pinned), show_in_home: !!Number(row.show_in_home), show_in_user_center: !!Number(row.show_in_user_center), show_as_popup: !!Number(row.show_as_popup) }) : blank(); this.dateRange = this.form.start_at || this.form.end_at ? [this.form.start_at || '', this.form.end_at || ''] : []; this.visible = true; },
                save: function () { var self = this; if (!self.form.title.trim() || !self.form.content.trim()) return notify('请填写标题和内容', 'warning'); self.form.start_at = self.dateRange[0] || ''; self.form.end_at = self.dateRange[1] || ''; var data = {}; Object.keys(self.form).forEach(function (key) { data[key] = typeof self.form[key] === 'boolean' ? (self.form[key] ? '1' : '') : (self.form[key] || ''); }); self.saving = true; apiPost('announcements_save', data).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.visible = false; notify(result.message, 'success'); self.load(); }).catch(function (error) { notify(error.message || '保存失败', 'error'); }).finally(function () { self.saving = false; }); },
                remove: function (row) { var self = this; confirmAction('确定删除公告“' + row.title + '”？', 'error').then(function () { return apiPost('announcements_delete', { id: row.id }); }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); notify(result.message, 'success'); self.load(); }).catch(function (error) { if (error !== 'cancel' && error !== 'close') notify(error.message || '删除失败', 'error'); }); }
            }
        };
    };

    var TICKETS_TEMPLATE = ''
        + '<el-card class="staff-manage-card" shadow="never"><template #header><div class="ep-card-head"><div><h2>工单管理</h2><p>查看用户问题、回复并更新处理状态</p></div><el-button :icon="Refresh" circle title="刷新" :loading="loading" @click="load"></el-button></div></template><div class="staff-toolbar"><el-select v-model="filter" @change="applyFilter"><el-option label="全部" value=""></el-option><el-option label="处理中" value="open"></el-option><el-option label="已回复" value="replied"></el-option><el-option label="已关闭" value="closed"></el-option></el-select></div><el-table :data="rows" v-loading="loading" stripe empty-text="暂无工单" @row-click="open"><el-table-column prop="id" label="#" width="70"></el-table-column><el-table-column prop="subject" label="主题" min-width="220" show-overflow-tooltip></el-table-column><el-table-column prop="username" label="提交人" min-width="120"></el-table-column><el-table-column prop="category" label="分类" width="90"></el-table-column><el-table-column label="状态" width="100"><template #default="scope"><el-tag :type="ticketType(scope.row.status)" size="small">{{ ticketLabel(scope.row.status) }}</el-tag></template></el-table-column><el-table-column label="更新时间" width="160"><template #default="scope">{{ fmt(scope.row.updated_at || scope.row.created_at) }}</template></el-table-column><el-table-column label="操作" width="90" fixed="right"><template #default="scope"><el-button link type="primary" @click.stop="open(scope.row)">处理</el-button></template></el-table-column></el-table><el-pagination v-if="total" class="staff-pagination" background layout="total, prev, pager, next" :total="total" :page-size="30" v-model:current-page="page" @current-change="load"></el-pagination></el-card>'
        + '<el-dialog v-model="visible" :title="detail ? \'工单 #\' + detail.id + \' · \' + detail.subject : \'工单详情\'" width="min(820px, 95vw)" destroy-on-close><div v-loading="detailLoading"><template v-if="detail"><el-descriptions :column="2" border><el-descriptions-item label="提交人">{{ detail.username || \'-\' }}</el-descriptions-item><el-descriptions-item label="邮箱">{{ detail.email || \'-\' }}</el-descriptions-item><el-descriptions-item label="分类">{{ detail.category }}</el-descriptions-item><el-descriptions-item label="状态"><el-tag :type="ticketType(detail.status)">{{ ticketLabel(detail.status) }}</el-tag></el-descriptions-item></el-descriptions><div class="staff-conversation"><div v-for="item in replies" :key="item.id" class="staff-message" :class="{ \'is-admin\': item.author_type === \'admin\' }"><div class="staff-message-meta">{{ item.author_type === \'admin\' ? \'管理员/工作人员\' : \'用户\' }} · {{ fmt(item.created_at) }}</div><div v-html="item.content_html || item.content"></div><div v-if="item.attachments && item.attachments.length" class="staff-attachments"><a v-for="attachment in item.attachments" :key="attachment.id || attachment.web_url" :href="attachment.web_url" target="_blank" rel="noopener">{{ attachment.original_name || \'附件\' }}</a></div></div></div><el-form v-if="detail.status !== \'closed\'" label-position="top"><el-form-item label="回复内容"><el-input v-model="reply" type="textarea" :rows="5" maxlength="5000" show-word-limit></el-input></el-form-item><el-upload :auto-upload="false" multiple :limit="5" v-model:file-list="files"><el-button :icon="Paperclip">添加附件</el-button><template #tip><div class="el-upload__tip">最多 5 个，单个不超过 10MB</div></template></el-upload></el-form></template></div><template #footer><el-button v-if="detail && detail.status !== \'closed\'" :disabled="aiDisabled" :loading="drafting" @click="draft"><el-icon><MagicStick></MagicStick></el-icon>{{ aiDisabled ? \'AI 自动处理已开启\' : \'AI 草稿\' }}</el-button><el-button v-if="detail && detail.status === \'closed\'" @click="setStatus(\'open\')">重新打开</el-button><el-button v-if="detail && detail.status !== \'closed\'" @click="setStatus(\'closed\')">关闭工单</el-button><el-button v-if="detail && detail.status !== \'closed\'" type="primary" :loading="submitting" @click="send">发送回复</el-button></template></el-dialog>';

    window.EpBlocks.staffTickets = function () {
        return {
            template: TICKETS_TEMPLATE,
            data: function () { return { Refresh: ElementPlusIconsVue.Refresh, Paperclip: ElementPlusIconsVue.Paperclip, rows: [], total: 0, page: 1, filter: '', loading: false, visible: false, detailLoading: false, detail: null, replies: [], reply: '', files: [], submitting: false, drafting: false, aiDisabled: !!window.staffAiTicketDraftDisabled }; },
            mounted: function () { this.load(); },
            updated: function () {
                this.$nextTick(function () {
                    document.querySelectorAll('.staff-attachments a').forEach(function (link) {
                        if (link.dataset.thumbReady === '1') return;
                        var url = link.getAttribute('href') || '';
                        if (!/\.(?:jpe?g|png|gif|webp)(?:[?#]|$)/i.test(url)) return;
                        var name = link.textContent.trim() || '图片附件';
                        link.dataset.thumbReady = '1';
                        link.dataset.lightbox = url;
                        link.classList.add('staff-attachment-image');
                        link.innerHTML = '<img src="' + url.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" alt="' + name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;') + '" loading="lazy"><span>' + name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>';
                        link.removeAttribute('target');
                        link.removeAttribute('download');
                    });
                });
            },
            methods: {
                fmt: formatTime,
                ticketLabel: function (status) { return { open: '处理中', replied: '已回复', closed: '已关闭' }[status] || status; },
                ticketType: function (status) { return { open: 'warning', replied: 'primary', closed: 'info' }[status] || 'info'; },
                applyFilter: function () { this.page = 1; this.load(); },
                load: function () { var self = this; self.loading = true; apiGet('tickets_list', { page: self.page, status: self.filter }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.rows = result.rows || []; self.total = Number(result.total) || 0; }).catch(function (error) { notify(error.message || '工单加载失败', 'error'); }).finally(function () { self.loading = false; }); },
                open: function (row) { var self = this; self.visible = true; self.detailLoading = true; self.detail = null; self.reply = ''; self.files = []; apiGet('tickets_get', { id: row.id }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.detail = result.ticket; self.replies = result.replies || []; }).catch(function (error) { notify(error.message || '详情加载失败', 'error'); self.visible = false; }).finally(function () { self.detailLoading = false; }); },
                draft: function () { var self = this; if (!self.detail || self.aiDisabled) return; self.drafting = true; generateDraft('ticket_reply', self.detail.id).then(function (text) { self.reply = text; }).catch(function (error) { notify(error.message, 'error'); }).finally(function () { self.drafting = false; }); },
                send: function () { var self = this; if (!self.detail) return; if (!self.reply.trim() && !self.files.length) return notify('请填写回复内容或添加附件', 'warning'); var form = new FormData(); form.append('ticket_id', self.detail.id); form.append('content', self.reply.trim()); self.files.forEach(function (item) { if (item.raw) form.append('attachments[]', item.raw); }); self.submitting = true; apiPost('tickets_reply', form).then(function (result) { if (result.status !== 'success') throw new Error(result.message); notify(result.message, 'success'); self.reply = ''; self.files = []; self.reloadDetail(); self.load(); }).catch(function (error) { notify(error.message || '回复失败', 'error'); }).finally(function () { self.submitting = false; }); },
                reloadDetail: function () { var self = this; if (!self.detail) return; self.detailLoading = true; apiGet('tickets_get', { id: self.detail.id }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.detail = result.ticket; self.replies = result.replies || []; }).catch(function (error) { notify(error.message || '工单刷新失败', 'error'); }).finally(function () { self.detailLoading = false; }); },
                setStatus: function (status) { var self = this; var action = status === 'closed' ? '关闭' : '重新打开'; confirmAction('确定要' + action + '该工单？').then(function () { return apiPost('tickets_update_status', { ticket_id: self.detail.id, status: status }); }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); notify(result.message, 'success'); self.visible = false; self.load(); }).catch(function (error) { if (error !== 'cancel' && error !== 'close') notify(error.message || '状态更新失败', 'error'); }); }
            }
        };
    };

    var APPLICATIONS_TEMPLATE = ''
        + '<el-card class="staff-manage-card" shadow="never"><template #header><div class="ep-card-head"><div><h2>入服申请审核</h2><p>核对申请资料并记录审核结论</p></div><el-button :icon="Refresh" circle title="刷新" :loading="loading" @click="load"></el-button></div></template><div class="staff-toolbar"><el-input v-model="search" placeholder="搜索用户名 / Minecraft ID / 邮箱" clearable :prefix-icon="Search" @keyup.enter="applyFilters"></el-input><el-select v-model="filter" @change="applyFilters"><el-option label="全部" value=""></el-option><el-option label="待审核" value="pending"></el-option><el-option label="已通过" value="approved"></el-option><el-option label="已拒绝" value="rejected"></el-option><el-option label="需补充" value="need_more_info"></el-option></el-select><el-button type="primary" @click="applyFilters">查询</el-button></div><el-table :data="rows" v-loading="loading" stripe empty-text="暂无申请" @row-click="open"><el-table-column prop="username" label="用户名" min-width="120"></el-table-column><el-table-column prop="mc_name" label="Minecraft ID" min-width="150"></el-table-column><el-table-column prop="email" label="邮箱" min-width="190" show-overflow-tooltip></el-table-column><el-table-column label="状态" width="100"><template #default="scope"><el-tag :type="appType(scope.row.status)" size="small">{{ appLabel(scope.row.status) }}</el-tag></template></el-table-column><el-table-column label="提交时间" width="160"><template #default="scope">{{ fmt(scope.row.created_at) }}</template></el-table-column><el-table-column label="操作" width="90" fixed="right"><template #default="scope"><el-button link type="primary" @click.stop="open(scope.row)">审核</el-button></template></el-table-column></el-table><el-pagination v-if="total" class="staff-pagination" background layout="total, prev, pager, next" :total="total" :page-size="20" v-model:current-page="page" @current-change="load"></el-pagination></el-card>'
        + '<el-dialog v-model="visible" title="入服申请详情" width="min(720px, 94vw)" destroy-on-close><div v-loading="detailLoading"><template v-if="detail"><el-descriptions :column="2" border><el-descriptions-item label="用户名">{{ detail.username || \'-\' }}</el-descriptions-item><el-descriptions-item label="邮箱">{{ detail.email || \'-\' }}</el-descriptions-item><el-descriptions-item label="Minecraft ID">{{ detail.mc_name || \'-\' }}</el-descriptions-item><el-descriptions-item label="年龄段">{{ detail.age_range || \'-\' }}</el-descriptions-item><el-descriptions-item label="来源">{{ detail.source || \'-\' }}</el-descriptions-item><el-descriptions-item label="状态"><el-tag :type="appType(detail.status)">{{ appLabel(detail.status) }}</el-tag></el-descriptions-item></el-descriptions><h3 class="staff-section-title">申请原因</h3><div class="staff-content">{{ detail.reason || \'未填写\' }}</div><template v-if="detail.review_note"><h3 class="staff-section-title">历史备注</h3><el-alert :title="detail.review_note" type="warning" :closable="false"></el-alert></template><el-form v-if="canReview" label-position="top" class="staff-dialog-form"><el-form-item label="审核备注"><el-input v-model="note" type="textarea" :rows="4" maxlength="1000" show-word-limit></el-input></el-form-item></el-form></template></div><template #footer><el-button v-if="canReview" :disabled="aiDisabled" :loading="drafting" @click="draft"><el-icon><MagicStick></MagicStick></el-icon>{{ aiDisabled ? \'AI 自动审核已开启\' : \'AI 草稿\' }}</el-button><el-button v-if="canReview" @click="review(\'need_more_info\')">需补充</el-button><el-button v-if="canReview" type="danger" plain @click="review(\'rejected\')">拒绝</el-button><el-button v-if="canReview" type="success" :loading="submitting" @click="review(\'approved\')">通过</el-button></template></el-dialog>';

    window.EpBlocks.staffApplications = function () {
        return {
            template: APPLICATIONS_TEMPLATE,
            data: function () { return { Refresh: ElementPlusIconsVue.Refresh, Search: ElementPlusIconsVue.Search, rows: [], total: 0, page: 1, search: '', filter: '', loading: false, visible: false, detailLoading: false, detail: null, note: '', submitting: false, drafting: false, aiDisabled: !!window.staffAiApplicationDraftDisabled }; },
            computed: { canReview: function () { return this.detail && (this.detail.status === 'pending' || this.detail.status === 'need_more_info'); } },
            mounted: function () { this.load(); },
            methods: {
                fmt: formatTime,
                appLabel: function (status) { return { pending: '待审核', approved: '已通过', rejected: '已拒绝', need_more_info: '需补充' }[status] || status; },
                appType: function (status) { return { pending: 'warning', approved: 'success', rejected: 'danger', need_more_info: 'primary' }[status] || 'info'; },
                applyFilters: function () { this.page = 1; this.load(); },
                load: function () { var self = this; self.loading = true; apiGet('applications_list', { page: self.page, search: self.search, status: self.filter }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.rows = result.rows || []; self.total = Number(result.total) || 0; }).catch(function (error) { notify(error.message || '申请加载失败', 'error'); }).finally(function () { self.loading = false; }); },
                open: function (row) { var self = this; self.visible = true; self.detailLoading = true; self.detail = null; self.note = ''; apiGet('applications_get', { id: row.id }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.detail = result.application; }).catch(function (error) { notify(error.message || '详情加载失败', 'error'); self.visible = false; }).finally(function () { self.detailLoading = false; }); },
                draft: function () { var self = this; if (!self.detail || self.aiDisabled) return; self.drafting = true; generateDraft('application_review', self.detail.id).then(function (text) { self.note = text; }).catch(function (error) { notify(error.message, 'error'); }).finally(function () { self.drafting = false; }); },
                review: function (decision) { var self = this; var label = { approved: '通过', rejected: '拒绝', need_more_info: '需补充' }[decision]; confirmAction('确定将该申请标记为“' + label + '”？通过或拒绝会通知用户并尝试同步白名单。').then(function () { self.submitting = true; return apiPost('applications_review', { app_id: self.detail.id, decision: decision, review_note: self.note }); }).then(function (result) { if (result.status !== 'success') throw new Error(result.message); self.visible = false; notify(result.message, 'success'); self.load(); }).catch(function (error) { if (error !== 'cancel' && error !== 'close') notify(error.message || '审核失败', 'error'); }).finally(function () { self.submitting = false; }); }
            }
        };
    };
}());