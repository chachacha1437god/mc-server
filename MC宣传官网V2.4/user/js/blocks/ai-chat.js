/**
 * 智能客服 tab — Element Plus 版。
 * SSE 流式协议与 ai_chat.php 保持一致：delta / reasoning / search_start /
 * search_done / sources / done / error。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var CONVERSATION_KEY = 'foxmc_ai_conversation_id';

    function readConversationId() {
        try { return sessionStorage.getItem(CONVERSATION_KEY) || ''; } catch (e) { return ''; }
    }
    function writeConversationId(id) {
        try { id ? sessionStorage.setItem(CONVERSATION_KEY, id) : sessionStorage.removeItem(CONVERSATION_KEY); } catch (e) {}
    }

    /* ── SSE 流式请求 ── */
    function streamChat(payload, handlers) {
        var controller = new AbortController();
        var settled = false;

        function done(data)  { if (!settled) { settled = true; handlers.onDone && handlers.onDone(data || {}); } }
        function fail(m, d)  { if (!settled) { settled = true; handlers.onError && handlers.onError(m, d || {}); } }

        var body = new FormData();
        Object.keys(payload).forEach(function (k) { body.append(k, payload[k]); });

        fetch('ai_chat.php', { method: 'POST', body: body, signal: controller.signal })
            .then(function (resp) {
                var ct = resp.headers.get('Content-Type') || '';
                if (!resp.ok || ct.indexOf('text/event-stream') === -1) {
                    return resp.text().then(function (t) {
                        var msg = '请求失败', data = null;
                        try { data = JSON.parse(t); msg = data.message || msg; } catch (e) {}
                        fail(msg, data);
                        throw new Error('non-sse');
                    });
                }
                var reader = resp.body.getReader();
                var decoder = new TextDecoder('utf-8');
                var buffer = '';
                function pump() {
                    return reader.read().then(function (res) {
                        if (res.done) { fail('连接提前结束，请重试'); return; }
                        buffer += decoder.decode(res.value, { stream: true });
                        var frames = buffer.split('\n\n');
                        buffer = frames.pop();
                        frames.forEach(function (frame) {
                            var event = 'message', dataStr = '';
                            frame.split('\n').forEach(function (line) {
                                if (line.indexOf('event:') === 0) event = line.slice(6).trim();
                                else if (line.indexOf('data:') === 0) dataStr += line.slice(5).trim();
                            });
                            if (!dataStr) return;
                            var data;
                            try { data = JSON.parse(dataStr); } catch (e) { return; }
                            switch (event) {
                                case 'delta':        handlers.onDelta       && handlers.onDelta(data.text || ''); break;
                                case 'reasoning':    handlers.onReasoning   && handlers.onReasoning(data.text || ''); break;
                                case 'search_done':  handlers.onSearchDone  && handlers.onSearchDone(data.query || ''); break;
                                case 'notice':       handlers.onNotice      && handlers.onNotice(data); break;
                                case 'sources':      handlers.onSources     && handlers.onSources(data.items || []); break;
                                case 'done':         done(data); break;
                                case 'error':        fail(data.message || '请求失败', data); break;
                            }
                        });
                        return pump();
                    });
                }
                return pump();
            })
            .catch(function (err) {
                if (err && (err.name === 'AbortError' || err.message === 'non-sse')) return;
                fail('网络错误，请重试');
            });

        return { abort: function () { settled = true; controller.abort(); } };
    }

    function fmtCountdown(s) {
        s = Math.max(0, parseInt(s || 0, 10));
        var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
        if (h > 0) return h + '小时' + String(m).padStart(2, '0') + '分';
        if (m > 0) return m + '分' + String(sec).padStart(2, '0') + '秒';
        return sec + '秒';
    }

    var CHIPS = [
        { label: '入服申请流程', q: '入服申请流程是什么？' },
        { label: '服务器规则',   q: '服务器有哪些规则？' },
        { label: '商城问题',     q: '商城购买有什么问题？' },
        { label: '提交工单',     q: '我想提交工单反馈问题，应该怎么操作？' }
    ];

    var TEMPLATE = ''
        + '<div class="ep-ai">'

        /* ── 顶栏 ── */
        + '  <div class="ep-ai-topbar">'
        + '    <el-avatar :size="38" :src="avatar" class="ep-ai-avatar"></el-avatar>'
        + '    <div class="ep-ai-agent">'
        + '      <span class="ep-ai-agent-name">在线客服</span>'
        + '      <span class="ep-ai-agent-status"><i class="ep-ai-dot"></i>在线</span>'
        + '    </div>'
        + '    <el-tag v-if="thinkOn" type="primary" size="small" effect="plain" round>深度思考</el-tag>'
        + '    <el-tag v-if="searchOn" type="success" size="small" effect="plain" round>联网搜索</el-tag>'
        + '    <span class="ep-ai-spacer"></span>'
        + '    <el-tag v-if="quotaText" :type="quotaExhausted ? \'warning\' : \'info\'" size="small" effect="light">'
        + '      {{ quotaText }}'
        + '    </el-tag>'
        + '    <el-button size="small" plain round :disabled="busy" @click="resetConversation">新对话</el-button>'
        + '  </div>'

        /* ── 消息区 ── */
        + '  <div ref="scroller" class="ep-ai-messages">'
        + '    <div v-for="(m, i) in messages" :key="i" class="ep-ai-msg" :class="\'is-\' + m.role">'

        + '      <div v-if="m.role === \'assistant\'" class="ep-ai-msg-meta">'
        + '        <el-avatar :size="20" :src="avatar"></el-avatar>'
        + '        <span>在线客服</span>'
        + '      </div>'

        /* 思考区块 */
        + '      <div v-if="m.reasoning" class="ep-ai-block is-think">'
        + '        <button type="button" class="ep-ai-block-head" @click="m.reasoningOpen = !m.reasoningOpen">'
        + '          <span>{{ m.reasoningDone ? "已深度思考" : "思考中…" }}</span>'
        + '          <el-icon class="ep-ai-chevron" :class="{ \'is-open\': m.reasoningOpen }"><ArrowRight /></el-icon>'
        + '        </button>'
        + '        <el-collapse-transition>'
        + '          <div v-show="m.reasoningOpen" class="ep-ai-block-body">{{ m.reasoning }}</div>'
        + '        </el-collapse-transition>'
        + '      </div>'

        /* 联网区块 */
        + '      <div v-if="m.searching || m.searchQuery || m.notice" class="ep-ai-block is-search">'
        + '        <div class="ep-ai-block-head is-static">'
        + '          <span>{{ m.searchDone ? "已联网搜索" : "正在联网搜索…" }}</span>'
        + '        </div>'
        + '        <div v-if="m.searchQuery" class="ep-ai-block-body">{{ m.searchQuery }}</div>'
        + '        <div v-if="m.notice" class="ep-ai-block-body">{{ m.notice }}</div>'
        + '      </div>'

        /* 气泡 */
        + '      <div class="ep-ai-bubble" :class="{ \'is-typing\': m.typing }">{{ m.content }}</div>'

        /* 参考来源 */
        + '      <div v-if="m.sources && m.sources.length" class="ep-ai-sources">'
        + '        <span class="ep-ai-sources-label">参考来源</span>'
        + '        <a v-for="(s, si) in m.sources" :key="si" :href="s.url"'
        + '           class="ep-ai-source" target="_blank" rel="noopener noreferrer">{{ sourceLabel(s) }}</a>'
        + '      </div>'

        + '    </div>'
        + '  </div>'

        /* ── 快捷问题 ── */
        + '  <div v-if="showChips" class="ep-ai-chips">'
        + '    <span class="ep-ai-chips-label">常见问题</span>'
        + '    <el-tag v-for="c in chips" :key="c.q" class="ep-ai-chip" size="large"'
        + '            type="success" effect="plain" round @click="pickChip(c.q)">{{ c.label }}</el-tag>'
        + '  </div>'

        /* ── 输入区 ── */
        + '  <div class="ep-ai-input">'
        + '    <el-input v-model="draft" type="textarea" :autosize="{ minRows: 1, maxRows: 5 }"'
        + '              maxlength="2000" resize="none" :disabled="busy"'
        + '              placeholder="向我提问，我可以回答关于服务器的各类问题…"'
        + '              @keydown.enter.exact.prevent="send"></el-input>'
        + '    <div class="ep-ai-actions">'
        + '      <el-button v-if="canThink" size="small" round :type="thinkOn ? \'primary\' : \'\'"'
        + '                 :plain="!thinkOn" @click="thinkOn = !thinkOn">深度思考</el-button>'
        + '      <el-button v-if="canSearch" size="small" round :type="searchOn ? \'success\' : \'\'"'
        + '                 :plain="!searchOn" @click="searchOn = !searchOn">联网</el-button>'
        + '      <span class="ep-ai-spacer"></span>'
        + '      <el-button v-if="busy" type="danger" plain size="small" round @click="stop">停止</el-button>'
        + '      <el-button type="primary" round :disabled="busy || !draft.trim()" @click="send">发送</el-button>'
        + '    </div>'
        + '  </div>'

        + '</div>';

    window.EpBlocks.userAiChat = function (props) {
        return {
            template: TEMPLATE,
            data: function () {
                return {
                    avatar:  props.avatar || '',
                    welcome: props.welcome || '',
                    csrf:    props.csrf || window.userCsrf || '',
                    chips:   CHIPS,
                    messages: [],
                    draft:   '',
                    busy:    false,
                    thinkOn:  false,
                    searchOn: false,
                    canThink:  !!props.thinkingEnabled,
                    canSearch: !!props.searchEnabled,
                    conversationId: '',
                    quotaRemaining: Number(props.quotaRemaining != null ? props.quotaRemaining : -1),
                    quotaLimit:     Number(props.quotaLimit || 0),
                    quotaReset:     Number(props.quotaReset || 0),
                    started: false,
                    stream:  null,
                    timer:   null,
                    loaded:  false
                };
            },
            computed: {
                showChips: function () { return !this.started && !this.busy; },
                quotaExhausted: function () { return this.quotaLimit > 0 && this.quotaRemaining <= 0; },
                quotaText: function () {
                    if (this.quotaLimit <= 0) return '今日次数：不限';
                    if (this.quotaRemaining > 0) return '剩余 ' + this.quotaRemaining + ' / ' + this.quotaLimit;
                    return '已用完，' + fmtCountdown(this.quotaReset) + '后重置';
                }
            },
            mounted: function () {
                this.resetMessages();

                // 复用旧的 tab 切换钩子：切到客服 tab 时再拉历史。
                var self = this;
                var prev = window.switchUserTab;
                window.switchUserTab = function (tabKey) {
                    if (typeof prev === 'function') prev(tabKey);
                    if (tabKey === 'ai_assistant') self.loadHistory();
                };
                if (this.$el.offsetParent !== null) this.loadHistory();
            },
            beforeUnmount: function () {
                if (this.timer) clearInterval(this.timer);
                if (this.stream) this.stream.abort();
            },
            methods: {
                sourceLabel: function (s) {
                    var host = '';
                    try { host = new URL(s.url).hostname; } catch (e) {}
                    return (s.title || s.url) + (host ? ' · ' + host : '');
                },
                resetMessages: function () {
                    this.messages = this.welcome
                        ? [{ role: 'assistant', content: this.welcome, sources: [] }]
                        : [];
                },
                scrollDown: function () {
                    var el = this.$refs.scroller;
                    if (el) this.$nextTick(function () { el.scrollTop = el.scrollHeight; });
                },
                applyQuota: function (q) {
                    if (!q) return;
                    this.quotaRemaining = Number(q.remaining != null ? q.remaining : -1);
                    this.quotaLimit     = Number(q.limit || 0);
                    this.quotaReset     = Number(q.reset_seconds || 0);
                    this.startCountdown();
                },
                startCountdown: function () {
                    var self = this;
                    if (this.timer) { clearInterval(this.timer); this.timer = null; }
                    if (!this.quotaExhausted || this.quotaReset <= 0) return;
                    this.timer = setInterval(function () {
                        self.quotaReset -= 1;
                        if (self.quotaReset <= 0) { clearInterval(self.timer); self.timer = null; }
                    }, 1000);
                },
                loadHistory: function () {
                    var self = this;
                    if (self.loaded) return;
                    self.loaded = true;
                    self.conversationId = readConversationId();

                    var fd = new FormData();
                    fd.append('action', 'history');
                    fd.append('csrf', self.csrf);
                    fd.append('conversation_id', self.conversationId);

                    fetch('ai_chat.php', { method: 'POST', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data || data.status !== 'success') return;
                            self.conversationId = data.conversation_id || '';
                            writeConversationId(self.conversationId);

                            var items = Array.isArray(data.messages) ? data.messages : [];
                            self.resetMessages();
                            items.forEach(function (m) {
                                self.messages.push({
                                    role:    m.role === 'user' ? 'user' : 'assistant',
                                    content: m.content || '',
                                    sources: m.sources || []
                                });
                            });
                            if (items.length) self.started = true;

                            if (data.capabilities) {
                                self.canThink  = !!data.capabilities.thinking;
                                self.canSearch = !!data.capabilities.search;
                            }
                            self.applyQuota(data.quota);
                            self.scrollDown();
                        })
                        .catch(function () {});
                },
                pickChip: function (q) {
                    this.draft = q;
                    this.send();
                },
                stop: function () {
                    if (this.stream) { this.stream.abort(); this.stream = null; }
                    this.busy = false;
                    var last = this.messages[this.messages.length - 1];
                    if (last && last.role === 'assistant') last.typing = false;
                },
                resetConversation: function () {
                    if (this.stream) { this.stream.abort(); this.stream = null; }
                    writeConversationId('');
                    this.conversationId = '';
                    this.busy = false;
                    this.started = false;
                    this.resetMessages();
                },
                send: function () {
                    var self = this;
                    var msg = self.draft.trim();
                    if (!msg || self.busy) return;

                    self.started = true;
                    self.draft = '';
                    self.busy = true;
                    self.messages.push({ role: 'user', content: msg, sources: [] });

                    var entry = {
                        role: 'assistant',
                        content: '',
                        reasoning: '',
                        reasoningOpen: true,
                        reasoningDone: false,
                        searching: self.searchOn,
                        searchQuery: '',
                        searchDone: false,
                        notice: '',
                        sources: [],
                        typing: true
                    };
                    self.messages.push(entry);
                    self.scrollDown();

                    function finishThinking() {
                        if (entry.reasoning && !entry.reasoningDone) {
                            entry.reasoningDone = true;
                            entry.reasoningOpen = false;
                        }
                    }
                    function finishSearch(query) {
                        if (entry.searching && !entry.searchDone) {
                            entry.searchDone = true;
                            entry.searching = false;
                            if (query) entry.searchQuery = query;
                        }
                    }

                    self.stream = streamChat(
                        {
                            csrf:            self.csrf,
                            message:         msg,
                            conversation_id: self.conversationId,
                            enable_thinking: self.thinkOn  ? '1' : '0',
                            enable_search:   self.searchOn ? '1' : '0'
                        },
                        {
                            onReasoning: function (text) {
                                entry.reasoning += text;
                                self.scrollDown();
                            },
                            onSearchDone: function (query) { finishSearch(query || msg); },
                            onNotice: function (data) {
                                data = data || {};
                                if (data.message) entry.notice = data.message;
                                // 服务器已自动降级（思考与联网互斥），同步关闭本地开关避免反复触发
                                if (data.thinking_disabled) self.thinkOn = false;
                                self.scrollDown();
                            },
                            onDelta: function (text) {
                                finishThinking();
                                finishSearch(msg);
                                entry.typing = false;
                                entry.content += text;
                                self.scrollDown();
                            },
                            onSources: function (items) {
                                entry.sources = (items || []).filter(function (s) {
                                    return s && /^https:\/\//i.test(s.url || '');
                                }).slice(0, 8);
                            },
                            onDone: function (data) {
                                self.stream = null;
                                self.busy = false;
                                entry.typing = false;
                                finishThinking();
                                finishSearch(msg);

                                if (data.conversation_id) {
                                    self.conversationId = data.conversation_id;
                                    writeConversationId(self.conversationId);
                                }
                                if (data.finish_reason === 'length') {
                                    entry.content += '\n\n（回答达到长度上限，内容可能未完整结束）';
                                } else if (data.finish_reason === 'sensitive') {
                                    entry.content += '\n\n（回答因内容安全审核提前结束）';
                                }
                                if (!entry.content) entry.content = '（未返回内容）';
                                self.applyQuota(data.quota);
                                self.scrollDown();
                            },
                            onError: function (message, data) {
                                self.stream = null;
                                self.busy = false;
                                entry.typing = false;
                                finishThinking();
                                finishSearch(msg);
                                entry.content += (entry.content ? '\n\n' : '') + '⚠ ' + message;
                                self.applyQuota(data && data.quota);
                                self.scrollDown();
                            }
                        }
                    );
                }
            }
        };
    };
}());
