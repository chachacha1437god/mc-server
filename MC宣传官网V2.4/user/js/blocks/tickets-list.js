/**
 * 工单列表区块 — Element Plus 版。
 * 替换原 PHP 服务端渲染 + tickets.js renderUserTickets() 的工单列表部分。
 * 提交工单表单仍由 blocks/tickets.js (userTickets) 负责。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    // 附件渲染（纯字符串，插入到 v-html 里；灯箱由 core.js 事件委托处理）
    function renderAttachmentsHtml(atts) {
        if (!atts || !atts.length) return '';
        var items = atts.map(function (a) {
            var url = a.web_url || '';
            if (!url) return '';
            var name = a.original_name || '附件';
            var size = a.size_label || '';
            if (a.is_image) {
                return '<a class="ticket-attachment ticket-attachment-image" href="' + esc(url) + '" data-lightbox="' + esc(url) + '" title="' + esc(name) + '"><span class="ticket-thumb-frame"><img class="ticket-thumb" src="' + esc(url) + '" alt="' + esc(name) + '" loading="lazy"></span><span class="ticket-thumb-name">' + esc(name) + '</span></a>';
            }
            return '<a class="ticket-attachment" href="' + esc(url) + '" target="_blank" rel="noopener" download>📎 ' + esc(name) + '<span class="ticket-attachment-size">' + esc(size) + '</span></a>';
        }).join('');
        return '<div class="ticket-attachments">' + items + '</div>';
    }

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    var STATUS_LABEL = { open: '待处理', replied: '已回复', closed: '已关闭' };
    var STATUS_TYPE  = { open: 'warning', replied: 'success', closed: 'info' };
    var CAT_LABEL    = { bug: 'Bug', report: '举报', appeal: '申诉', suggestion: '建议', other: '其他' };

    var LIST_TEMPLATE = ''
        + '<div class="ep-ticket-list-wrap">'

        /* ── 标题栏 ── */
        + '  <div class="ep-ticket-list-head">'
        + '    <h2>我的工单</h2>'
        + '    <el-button :icon="\'Refresh\'" circle :loading="loading" title="刷新" @click="load(false)" size="small"></el-button>'
        + '  </div>'

        /* ── 骨架 / 空状态 / 列表 ── */
        + '  <el-skeleton :rows="4" animated v-if="loading && !tickets.length"></el-skeleton>'

        + '  <el-empty description="暂无工单" v-else-if="!loading && !tickets.length"></el-empty>'

        + '  <el-collapse v-else v-model="openIds" class="ep-ticket-collapse">'
        + '    <el-collapse-item v-for="t in tickets" :key="t.id" :name="t.id">'

        /* 折叠面板标题 */
        + '      <template #title>'
        + '        <div class="ep-tc-head">'
        + '          <div class="ep-tc-main">'
        + '            <div class="ep-tc-title">'
        + '              <span class="ep-tc-id">#{{ t.id }}</span>'
        + '              <span class="ep-tc-subject">{{ t.subject }}</span>'
        + '            </div>'
        + '            <div class="ep-tc-meta">'
        + '              <el-tag type="info" size="small" effect="plain">{{ catLabel(t.category) }}</el-tag>'
        + '              <span class="ep-tc-time">{{ t.created_at }}</span>'
        + '            </div>'
        + '          </div>'
        + '          <el-tag :type="statusType(t.status)" size="small" effect="light" round class="ep-tc-status">{{ statusLabel(t.status) }}</el-tag>'
        + '        </div>'
        + '      </template>'

        /* 回复列表 */
        + '      <div class="ep-ticket-thread">'
        + '        <div v-for="(reply, idx) in t.replies" :key="idx"'
        + '             :class="[\'ep-ticket-reply\', reply.author_type === \'admin\' ? \'is-admin\' : \'is-user\']">'
        + '          <div class="ep-reply-meta">'
        + '            <span class="ep-reply-author">{{ reply.author_type === "admin" ? "管理员" : "我" }}</span>'
        + '            <span class="ep-reply-time">{{ reply.created_at }}</span>'
        + '          </div>'
        + '          <div class="ep-reply-body ticket-rich-content" v-html="replyHtml(reply, idx, t)"></div>'
        + '        </div>'
        + '      </div>'

        /* 补充回复表单（未关闭才显示） */
        + '      <div v-if="t.status !== \'closed\'" class="ep-ticket-reply-form">'
        + '        <el-input v-model="replyDrafts[t.id]" type="textarea" :rows="3"'
        + '                  maxlength="5000" show-word-limit placeholder="补充说明…"></el-input>'
        + '        <div class="ep-reply-attach-row">'
        + '          <el-upload :auto-upload="false" multiple :limit="5"'
        + '                     accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.log,.json,.zip,.mp4,.mp3,image/*"'
        + '                     :before-upload="() => false"'
        + '                     :on-change="(f, fl) => onReplyUploadChange(t.id, f, fl)"'
        + '                     :on-remove="(f, fl) => onReplyUploadRemove(t.id, f, fl)"'
        + '                     :on-exceed="onExceed"'
        + '                     :file-list="replyFileLists[t.id] || []">'
        + '            <el-button size="small">添加附件</el-button>'
        + '            <template #tip>'
        + '              <el-text type="info" size="small">最多 5 个，单个 ≤ 10MB</el-text>'
        + '            </template>'
        + '          </el-upload>'
        + '        </div>'
        + '        <div class="ep-ticket-actions">'
        + '          <el-button type="primary" plain size="small" :loading="replyLoading[t.id]"'
        + '                     @click="submitReply(t.id)">提交回复</el-button>'
        + '          <el-button type="danger" plain size="small" :loading="closeLoading[t.id]"'
        + '                     @click="closeTicket(t)">关闭工单</el-button>'
        + '        </div>'
        + '      </div>'

        + '    </el-collapse-item>'
        + '  </el-collapse>'

        /* ── 分页 ── */
        + '  <div v-if="pagination.pages > 1" class="ep-ticket-pagination">'
        + '    <el-pagination background layout="prev, pager, next" small'
        + '      :total="pagination.total" :page-size="pagination.per_page"'
        + '      :current-page="pagination.page"'
        + '      @current-change="onPageChange">'
        + '    </el-pagination>'
        + '  </div>'

        + '</div>';

    window.EpBlocks.userTicketList = function (props) {
        return {
            template: LIST_TEMPLATE,
            data: function () {
                return {
                    loading: true,
                    tickets: [],
                    pagination: { page: 1, pages: 1, per_page: 5, total: 0 },
                    openIds: [],           // el-collapse 展开的工单 id
                    replyDrafts: {},       // { ticketId: string }
                    replyFiles: {},        // { ticketId: File[] }
                    replyFileLists: {},    // { ticketId: UploadUserFile[] } 供 el-upload :file-list 绑定
                    replyFileOver: {},     // 保留， submitReply 需要
                    replyLoading: {},
                    closeLoading: {},      // { ticketId: bool } 关闭工单请求中
                    ticketPollTimer: null,
                    ticketPollBusy: false,
                    ticketPollSignature: '',
                };
            },
            mounted: function () {
                var self = this;
                window._ticketListVue = self;
                if (typeof window.stopUserTicketsPolling === 'function') window.stopUserTicketsPolling();
                self.onTicketVisibilityChange = function () {
                    if (document.hidden) self.stopTicketPolling();
                    else self.startTicketPolling();
                };
                document.addEventListener('visibilitychange', self.onTicketVisibilityChange);
                // 回放 Vue 就绪前就排队的调用，否则正常首次加载
                var q = window._ticketListQueue;
                if (q) {
                    window._ticketListQueue = null;
                    self.load(q.silent, q.page);
                } else {
                    self.load(false);
                }
                self.startTicketPolling();
            },
            beforeUnmount: function () {
                document.removeEventListener('visibilitychange', this.onTicketVisibilityChange);
                this.stopTicketPolling();
                if (window._ticketListVue === this) window._ticketListVue = null;
            },
            methods: {
                statusLabel: function (s) { return STATUS_LABEL[s] || s; },
                statusType:  function (s) { return STATUS_TYPE[s]  || 'info'; },
                catLabel:    function (c) { return CAT_LABEL[c]    || '其他'; },

                isTicketListVisible: function () {
                    var tab = document.getElementById('tab-tickets');
                    return !!(tab && tab.style.display !== 'none');
                },

                ticketSignature: function (tickets) {
                    return (tickets || []).map(function (ticket) {
                        var replies = ticket.replies || [];
                        var last = replies.length ? replies[replies.length - 1] : null;
                        return [ticket.id, ticket.status, replies.length, last && last.id, last && last.created_at].join(':');
                    }).join('|');
                },

                startTicketPolling: function () {
                    var self = this;
                    self.stopTicketPolling();
                    if (document.hidden || !self.isTicketListVisible()) return;
                    var interval = self.openIds.length ? 15000 : 45000;
                    self.ticketPollTimer = window.setTimeout(function pollTickets() {
                        if (document.hidden || !self.isTicketListVisible()) {
                            self.stopTicketPolling();
                            return;
                        }
                        self.pollTickets();
                        interval = self.openIds.length ? 15000 : 45000;
                        self.ticketPollTimer = window.setTimeout(pollTickets, interval);
                    }, interval);
                },

                stopTicketPolling: function () {
                    if (this.ticketPollTimer) {
                        window.clearTimeout(this.ticketPollTimer);
                        this.ticketPollTimer = null;
                    }
                },

                pollTickets: function () {
                    var self = this;
                    if (self.ticketPollBusy || self.loading) return;
                    self.ticketPollBusy = true;
                    fetch('api/index.php?action=ticket_list&page=' + encodeURIComponent(self.pagination.page || 1), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            if (res.status !== 'success') return;
                            var nextTickets = res.tickets || [];
                            var nextSignature = self.ticketSignature(nextTickets);
                            if (nextSignature !== self.ticketPollSignature) {
                                self.tickets = nextTickets;
                                self.ticketPollSignature = nextSignature;
                                if (res.stats && typeof window.updateTicketBadge === 'function') {
                                    window.updateTicketBadge(res.stats.replied || 0);
                                }
                            }
                        })
                        .catch(function () {})
                        .finally(function () {
                            self.ticketPollBusy = false;
                        });
                },

                replyHtml: function (reply, idx, ticket) {
                    var atts = reply.attachments || [];
                    // 兜底：首条 user 回复无独立附件时，取工单级附件
                    if (reply.author_type !== 'admin' && idx === 0 && !atts.length) {
                        atts = ticket.attachments || [];
                    }
                    return (reply.content_html || esc(reply.content || '')) + renderAttachmentsHtml(atts);
                },

                load: function (silent, page) {
                    var self = this;
                    page = page || self.pagination.page || 1;
                    if (!silent) self.loading = true;
                    fetch('api/index.php?action=ticket_list&page=' + encodeURIComponent(page))
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            self.loading = false;
                            if (res.status !== 'success') {
                                if (!silent) ElementPlus.ElMessage.error(res.message || '工单加载失败');
                                return;
                            }
                            self.tickets = res.tickets || [];
                            self.ticketPollSignature = self.ticketSignature(self.tickets);
                            self.pagination = {
                                page:     parseInt(res.page, 10)     || 1,
                                pages:    parseInt(res.pages, 10)    || 1,
                                per_page: parseInt(res.per_page, 10) || 5,
                                total:    parseInt(res.total, 10)    || 0,
                            };
                            if (res.stats && typeof window.updateTicketBadge === 'function') {
                                window.updateTicketBadge(res.stats.replied || 0);
                            }
                            self.startTicketPolling();
                        })
                        .catch(function () {
                            self.loading = false;
                            if (!silent) ElementPlus.ElMessage.error('网络异常，请重试');
                        });
                },

                onPageChange: function (page) { this.load(false, page); },

                /* ── 补充回复 ── */
                onReplyUploadChange: function (tid, uploadFile, uploadFiles) {
                    if (uploadFile.raw && uploadFile.raw.size > 10 * 1024 * 1024) {
                        ElementPlus.ElMessage.warning(uploadFile.name + ' 超过 10MB，已跳过');
                        var idx = uploadFiles.indexOf(uploadFile);
                        if (idx !== -1) uploadFiles.splice(idx, 1);
                        return;
                    }
                    var valid = uploadFiles.filter(function (f) { return f.raw; });
                    this.replyFiles[tid]     = valid.map(function (f) { return f.raw; });
                    this.replyFileLists[tid] = valid;
                },
                onReplyUploadRemove: function (tid, uploadFile, uploadFiles) {
                    var valid = uploadFiles.filter(function (f) { return f.raw; });
                    this.replyFiles[tid]     = valid.map(function (f) { return f.raw; });
                    this.replyFileLists[tid] = valid;
                },
                onExceed: function () {
                    ElementPlus.ElMessage.warning('最多可选择 5 个附件');
                },

                submitReply: function (tid) {
                    var self    = this;
                    var content = (self.replyDrafts[tid] || '').trim();
                    if (!content) {
                        ElementPlus.ElMessage.warning('请填写补充说明');
                        return;
                    }
                    self.replyLoading[tid] = true;
                    var fd = new FormData();
                    fd.append('csrf', window.userCsrf || '');
                    fd.append('ticket_id', tid);
                    fd.append('content', content);
                    (self.replyFiles[tid] || []).forEach(function (f) { fd.append('attachments[]', f); });
                    fetch('api/index.php?action=ticket_reply', { method: 'POST', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            self.replyLoading[tid] = false;
                            if (data.status === 'success') {
                                ElementPlus.ElMessage.success(data.message || '回复已提交');
                                self.replyDrafts[tid] = '';
                                self.replyFiles[tid]  = [];
                                self.replyFileLists[tid] = [];
                                self.load(true);
                            } else {
                                ElementPlus.ElMessage.error(data.message || '提交失败');
                            }
                        })
                        .catch(function () {
                            self.replyLoading[tid] = false;
                            ElementPlus.ElMessage.error('网络异常，请重试');
                        });
                },

                /* ── 主动关闭工单 ── */
                closeTicket: function (t) {
                    var self = this;
                    ElementPlus.ElMessageBox.confirm(
                        '确定要关闭工单 #' + t.id + '「' + t.subject + '」吗？关闭后将无法继续回复。',
                        '关闭工单',
                        { confirmButtonText: '确定关闭', cancelButtonText: '取消', type: 'warning' }
                    ).then(function () {
                        self.closeLoading[t.id] = true;
                        var fd = new FormData();
                        fd.append('csrf', window.userCsrf || '');
                        fd.append('ticket_id', t.id);
                        fetch('api/index.php?action=ticket_close', { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                self.closeLoading[t.id] = false;
                                if (data.status === 'success') {
                                    ElementPlus.ElMessage.success(data.message || '工单已关闭');
                                    self.load(true);
                                } else {
                                    ElementPlus.ElMessage.error(data.message || '关闭失败');
                                }
                            })
                            .catch(function () {
                                self.closeLoading[t.id] = false;
                                ElementPlus.ElMessage.error('网络异常，请重试');
                            });
                    }).catch(function () { /* 用户取消 */ });
                },
            },
        };
    };
}());
