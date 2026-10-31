var _userTicketsPollTimer = null;
var _userTicketBadgePollTimer = null;
var _userTicketsPollInterval = 30000;
var _userTicketBadgePollInterval = 60000;
var _userTicketCollapsedState = {};
var _userTicketsPage = 1;

function isUserTicketsTabVisible() {
    var tab = document.getElementById('tab-tickets');
    return !!(tab && tab.style.display !== 'none');
}

// 渲染单条回复/工单的附件区
function renderTicketAttachmentsJs(atts) {
    if (!atts || !atts.length) return '';
    var items = atts.map(function(a) {
        var url = a.web_url || '';
        if (!url) return '';
        var name = escHtml(a.original_name || '附件');
        var size = escHtml(a.size_label || '');
        if (a.is_image) {
            return '<a class="ticket-attachment ticket-attachment-image" href="' + escHtml(url) + '" data-lightbox="' + escHtml(url) + '" title="' + name + '"><img src="' + escHtml(url) + '" alt="' + name + '" loading="lazy"></a>';
        }
        return '<a class="ticket-attachment" href="' + escHtml(url) + '" target="_blank" rel="noopener" download><span>📎</span><span>' + name + '</span><span class="ticket-attachment-size">' + size + '</span></a>';
    }).join('');
    return '<div class="ticket-attachments">' + items + '</div>';
}

function renderUserTickets(tickets, pagination) {
    var list = document.getElementById('userTicketsList');
    if (!list) return;
    tickets = tickets || [];
    if (!tickets.length) {
        list.innerHTML = '<div class="empty-state">暂无工单</div>';
        return;
    }
    var statusLabels = { open: '待处理', replied: '已回复', closed: '已关闭' };
    var categoryLabels = { bug: 'Bug', report: '举报', appeal: '申诉', suggestion: '建议', other: '其他' };
    var CHEVRON_DOWN = '<svg class="ticket-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
    var html = tickets.map(function(ticket) {
        var replies = ticket.replies || [];
        var ticketAtts = ticket.attachments || [];
        var collapsed = _userTicketCollapsedState[ticket.id] !== false;
        var replyHtml = replies.map(function(reply, idx) {
            var isAdmin = reply.author_type === 'admin';
            var atts = reply.attachments || [];
            // 兜底：首条 user 回复若无独立附件，取工单级附件（兼容历史数据）
            if (!isAdmin && idx === 0 && !atts.length) atts = ticketAtts;
            return '<div class="ticket-reply ' + (isAdmin ? 'admin' : 'user') + '">' +
                '<div class="ticket-reply-meta"><span class="ticket-reply-author">' + (isAdmin ? '管理员' : '我') + '</span><span class="ticket-reply-time">' + escHtml(reply.created_at || '') + '</span></div>' +
                '<div class="ticket-rich-content">' + (reply.content_html || escHtml(reply.content || '')) + '</div>' +
                renderTicketAttachmentsJs(atts) + '</div>';
        }).join('');
        var replyForm = '';
        if (ticket.status !== 'closed') {
            replyForm =
                '<form method="POST" enctype="multipart/form-data" class="profile-form ticket-reply-form">' +
                    '<input type="hidden" name="csrf" value="' + escHtml(getUserCsrf()) + '">' +
                    '<input type="hidden" name="ticket_id" value="' + escHtml(ticket.id || '') + '">' +
                    '<textarea name="content" rows="3" class="form-input rich-text-source" placeholder="补充说明..."></textarea>' +
                    '<div class="ticket-attach-row">' +
                        '<label class="ticket-attach-btn">' +
                            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>' +
                            '<span>添加附件</span>' +
                            '<input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.log,.json,.zip,.mp4,.mp3,image/*" hidden data-role="ticket-attach">' +
                        '</label>' +
                        '<span class="ticket-attach-hint" data-role="ticket-attach-hint">最多 5 个，单个 ≤ 10MB</span>' +
                    '</div>' +
                    '<button type="submit" class="btn-outline">提交回复</button>' +
                '</form>';
        }
        var statusCls = 'ticket-status-' + (ticket.status || 'open');
        return '<article class="ticket-card ticket-' + escHtml(ticket.status || '') + '">' +
            '<button type="button" id="userTicketToggle' + escHtml(ticket.id || '') + '" class="ticket-toggle-btn' + (collapsed ? '' : ' is-open') + '" onclick="toggleUserTicketCard(' + escHtml(ticket.id || '') + ')" aria-expanded="' + (collapsed ? 'false' : 'true') + '" aria-controls="userTicketBody' + escHtml(ticket.id || '') + '">' +
                '<div class="ticket-toggle-left">' +
                    CHEVRON_DOWN +
                    '<div class="ticket-title"><h3>#' + escHtml(ticket.id || '') + ' ' + escHtml(ticket.subject || '') + '</h3><p><span class="ticket-category">' + escHtml(categoryLabels[ticket.category] || '其他') + '</span><span class="ticket-dot">·</span>' + escHtml(ticket.created_at || '') + '</p></div>' +
                '</div>' +
                '<span class="ticket-status ' + statusCls + '">' + escHtml(statusLabels[ticket.status] || ticket.status || '') + '</span>' +
            '</button>' +
            '<div id="userTicketBody' + escHtml(ticket.id || '') + '" class="ticket-body"' + (collapsed ? ' hidden' : '') + '>' +
                '<div class="ticket-thread">' + replyHtml + '</div>' +
                replyForm +
            '</div>' +
        '</article>';
    }).join('');
    html += renderUserTicketsPagination(pagination || {});
    list.innerHTML = html;
    initUserPageInteractions(list);
    bindTicketAttachInputs(list);
}

function renderUserTicketsPagination(pagination) {
    var page = parseInt(pagination.page, 10) || 1;
    var pages = parseInt(pagination.pages, 10) || 1;
    var perPage = parseInt(pagination.per_page, 10) || 5;
    var total = parseInt(pagination.total, 10) || 0;
    if (pages <= 1) return '';
    var html = '<div class="ticket-pagination"><span>共 ' + total + ' 个工单，每页 ' + perPage + ' 个</span><div>';
    html += '<button type="button" onclick="loadUserTickets(false, ' + (page - 1) + ')" ' + (page <= 1 ? 'disabled' : '') + '>上一页</button>';
    for (var i = 1; i <= pages; i++) {
        html += '<button type="button" class="' + (i === page ? 'active' : '') + '" onclick="loadUserTickets(false, ' + i + ')">' + i + '</button>';
    }
    html += '<button type="button" onclick="loadUserTickets(false, ' + (page + 1) + ')" ' + (page >= pages ? 'disabled' : '') + '>下一页</button>';
    html += '</div></div>';
    return html;
}

function toggleUserTicketCard(id) {
    var body = document.getElementById('userTicketBody' + id);
    var btn = document.getElementById('userTicketToggle' + id);
    if (!body) return;
    var collapsed = !body.hidden;
    body.hidden = collapsed;
    _userTicketCollapsedState[id] = collapsed;
    if (btn) {
        btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        if (collapsed) btn.classList.remove('is-open');
        else btn.classList.add('is-open');
    }
}

function hasUnsavedUserTicketDraft() {
    var list = document.getElementById('userTicketsList');
    if (!list) return false;
    var editors = list.querySelectorAll('.rich-text-area');
    for (var i = 0; i < editors.length; i++) {
        var text = (editors[i].innerText || '').replace(/\u00a0/g, ' ').trim();
        var html = (editors[i].innerHTML || '').replace(/<br\s*\/?>/gi, '').trim();
        if (text !== '' || (html !== '' && html !== '<p></p>')) return true;
    }
    var fileInputs = list.querySelectorAll('input[data-role="ticket-attach"]');
    for (var j = 0; j < fileInputs.length; j++) {
        if (fileInputs[j].files && fileInputs[j].files.length) return true;
    }
    return false;
}

// 把回复表单内的 file input 的"已选数量/总大小"提示同步到 hint
function bindTicketAttachInputs(root) {
    root.querySelectorAll('input[data-role="ticket-attach"]').forEach(function(input) {
        if (input.dataset.hintBound === '1') return;
        input.dataset.hintBound = '1';
        var row = input.closest('.ticket-attach-row');
        var hint = row ? row.querySelector('[data-role="ticket-attach-hint"]') : null;
        input.addEventListener('change', function() {
            if (!hint) return;
            var files = input.files || [];
            if (!files.length) {
                hint.classList.remove('over-limit');
                hint.textContent = '最多 5 个，单个 ≤ 10MB（图片/PDF/文本/压缩包等）';
                return;
            }
            if (files.length > 5) {
                hint.classList.add('over-limit');
                hint.textContent = '最多 5 个附件，已选 ' + files.length + ' 个，请重新选择';
                input.value = '';
                return;
            }
            var maxSize = 10 * 1024 * 1024;
            for (var i = 0; i < files.length; i++) {
                if (files[i].size > maxSize) {
                    hint.classList.add('over-limit');
                    hint.textContent = '存在超过 10MB 的文件，请重新选择';
                    input.value = '';
                    return;
                }
            }
            hint.classList.remove('over-limit');
            hint.textContent = '已选 ' + files.length + ' 个文件';
        });
    });
}

var _ticketListQueue = null; // Vue 就绪前的待执行调用

function loadUserTickets(silent, page) {
    if (window._ticketListVue) {
        window._ticketListVue.load(!!silent, page || undefined);
        return;
    }
    // Vue 尚未挂载，保留最新一次调用，mounted 后回放
    _ticketListQueue = { silent: !!silent, page: page || undefined };
}

function refreshTicketBadge() {
    fetch('api/index.php?action=ticket_badge')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.status === 'success') updateTicketBadge(res.count || 0);
        })
        .catch(function() {});
}

function startUserTicketsPolling() {
    if (window._ticketListVue) return;
    stopUserTicketsPolling();
    if (!isUserTicketsTabVisible() || document.hidden) return;
    _userTicketsPollTimer = setInterval(function() {
        if (!isUserTicketsTabVisible() || document.hidden) {
            stopUserTicketsPolling();
            return;
        }
        if (window._ticketListVue) {
            stopUserTicketsPolling();
            return;
        }
        loadUserTickets(true);
    }, _userTicketsPollInterval);
}

function stopUserTicketsPolling() {
    if (_userTicketsPollTimer) {
        clearInterval(_userTicketsPollTimer);
        _userTicketsPollTimer = null;
    }
}

function startTicketBadgePolling() {
    if (_userTicketBadgePollTimer) return;
    refreshTicketBadge();
    _userTicketBadgePollTimer = setInterval(refreshTicketBadge, _userTicketBadgePollInterval);
}
