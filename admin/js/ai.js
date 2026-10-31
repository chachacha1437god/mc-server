/**
 * 后台 AI 模块前端逻辑
 * - aiStreamChat: 通用流式对话封装（fetch + ReadableStream 解析 SSE）
 * - AI 测试台交互
 * - 工单 AI 回复草稿（由 admin.js 调用 window.aiGenerateTicketDraft）
 */
(function () {
  'use strict';

  /**
   * 发起流式对话。
   * @param {Object} opts
   * @param {string} opts.url        代理端点（默认 ai_proxy.php）
   * @param {Object} opts.payload    POST 字段（含 csrf / message / messages / mode）
   * @param {function(string):void} opts.onDelta  收到增量文本
    * @param {function(string):void} opts.onStatus 收到进度状态
   * @param {function():void}       opts.onDone   完成
   * @param {function(string):void} opts.onError  出错
   * @returns {{abort: function():void}}
   */
  function aiStreamChat(opts) {
    var controller = new AbortController();
    var settled = false;
    function finishDone(data) {
      if (settled) return;
      settled = true;
      if (opts.onDone) opts.onDone(data);
    }
    function finishError(message) {
      if (settled) return;
      settled = true;
      if (opts.onError) opts.onError(message);
    }
    var url = opts.url || 'ai_proxy.php';
    var body = new FormData();
    var payload = opts.payload || {};
    Object.keys(payload).forEach(function (k) {
      body.append(k, payload[k]);
    });

    fetch(url, { method: 'POST', body: body, signal: controller.signal })
      .then(function (resp) {
        var ct = resp.headers.get('Content-Type') || '';
        if (!resp.ok || ct.indexOf('text/event-stream') === -1) {
          // 非 SSE：尝试解析 JSON 错误
          return resp.text().then(function (t) {
            var msg = 'AI 请求失败';
            try { var j = JSON.parse(t); msg = j.message || msg; } catch (e) {}
            finishError(msg);
            throw new Error('non-sse');
          });
        }
        var reader = resp.body.getReader();
        var decoder = new TextDecoder('utf-8');
        var buffer = '';

        function pump() {
          return reader.read().then(function (res) {
            if (res.done) {
              if (!settled) finishError('连接提前结束，请重试');
              return;
            }
            buffer += decoder.decode(res.value, { stream: true });
            // 按 SSE 帧（空行分隔）解析
            var frames = buffer.split('\n\n');
            buffer = frames.pop();
            frames.forEach(function (frame) {
              var event = 'message';
              var dataStr = '';
              frame.split('\n').forEach(function (line) {
                if (line.indexOf('event:') === 0) event = line.slice(6).trim();
                else if (line.indexOf('data:') === 0) dataStr += line.slice(5).trim();
              });
              if (!dataStr) return;
              var data;
              try { data = JSON.parse(dataStr); } catch (e) { return; }
              if (event === 'delta' && data.text) {
                if (opts.onDelta) opts.onDelta(data.text);
              } else if (event === 'status') {
                if (opts.onStatus) opts.onStatus(data.message || 'AI 正在处理...');
              } else if (event === 'done') {
                finishDone(data);
              } else if (event === 'error') {
                finishError(data.message || 'AI 请求失败');
              }
            });
            return pump();
          });
        }
        return pump();
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        if (err && err.message === 'non-sse') return;
        finishError('网络错误，请重试');
      });

    return { abort: function () { settled = true; controller.abort(); } };
  }

  // 暴露给其它脚本（如 admin.js 的工单草稿）
  window.aiStreamChat = aiStreamChat;

  function initProviderSettings() {
    var providerSelect = document.getElementById('aiProviderSelect');
    if (!providerSelect) return;

    var baseUrlInput = document.getElementById('aiBaseUrlInput');
    var temperatureInput = document.getElementById('aiTemperatureInput');
    var temperatureRange = document.getElementById('aiTemperatureRange');
    var maxTokensInput = document.getElementById('aiMaxTokensInput');
    var capabilityHint = document.getElementById('aiProviderCapabilityHint');
    var endpoints = {
      zhipu: 'https://open.bigmodel.cn/api/paas/v4',
      xiaomi_mimo: 'https://api.xiaomimimo.com/v1'
    };
    var models = {
      zhipu: {
        text: [['glm-5.2', 'GLM-5.2'], ['glm-4-air', 'GLM-4-Air'], ['glm-4-flashx', 'GLM-4-FlashX']],
        vision: [['glm-4.6v', 'GLM-4.6V'], ['glm-4.1v-thinking-flashx', 'GLM-4.1V-Thinking-FlashX']]
      },
      xiaomi_mimo: {
        text: [['mimo-v2.5-pro', 'MiMo-V2.5-Pro'], ['mimo-v2.5', 'MiMo-V2.5']],
        vision: [['mimo-v2.5', 'MiMo-V2.5']]
      }
    };

    function applyProviderRestrictions(provider) {
      var isMimo = provider === 'xiaomi_mimo';
      document.querySelectorAll('[data-zhipu-only-feature]').forEach(function (card) {
        var toggle = card.querySelector('input[type="checkbox"]');
        var description = card.querySelector('.ai-toggle-text small');
        if (toggle) {
          if (isMimo) {
            if (!toggle.hasAttribute('data-zhipu-checked')) toggle.setAttribute('data-zhipu-checked', toggle.checked ? '1' : '0');
            toggle.checked = false;
          } else if (toggle.hasAttribute('data-zhipu-checked')) {
            toggle.checked = toggle.getAttribute('data-zhipu-checked') === '1';
            toggle.removeAttribute('data-zhipu-checked');
          }
        }
        card.querySelectorAll('input, select, textarea').forEach(function (control) {
          control.disabled = isMimo;
        });
        card.setAttribute('aria-disabled', isMimo ? 'true' : 'false');
        if (description) {
          description.textContent = isMimo
            ? 'Xiaomi MiMo 模式暂不支持'
            : (card.getAttribute('data-zhipu-only-feature') === 'knowledge' ? '基于知识库检索增强回答' : '拦截可疑或违规内容');
        }
      });
    }

    function rebuildModels(provider) {
      var defaultModel = models[provider].text[0];
      document.querySelectorAll('[data-ai-model-select]').forEach(function (select) {
        var previous = select.value;
        var modelType = select.getAttribute('data-ai-model-select');
        var allowFollow = select.getAttribute('data-allow-follow') === '1';
        select.replaceChildren();
        if (allowFollow) {
          var follow = document.createElement('option');
          follow.value = '';
          follow.textContent = '跟随默认模型（' + defaultModel[1] + '）';
          select.appendChild(follow);
        }
        models[provider][modelType].forEach(function (model) {
          var option = document.createElement('option');
          option.value = model[0];
          option.textContent = model[1];
          select.appendChild(option);
        });
        select.value = Array.from(select.options).some(function (option) { return option.value === previous; })
          ? previous
          : (allowFollow ? '' : models[provider][modelType][0][0]);
      });
    }

    providerSelect.addEventListener('change', function () {
      var provider = providerSelect.value === 'xiaomi_mimo' ? 'xiaomi_mimo' : 'zhipu';
      var otherProvider = provider === 'xiaomi_mimo' ? 'zhipu' : 'xiaomi_mimo';
      if (baseUrlInput && (!baseUrlInput.value.trim() || baseUrlInput.value.trim() === endpoints[otherProvider])) {
        baseUrlInput.value = endpoints[provider];
      }
      if (baseUrlInput) baseUrlInput.placeholder = endpoints[provider];
      if (temperatureInput) {
        temperatureInput.max = provider === 'xiaomi_mimo' ? '1.5' : '1';
        if (Number(temperatureInput.value) > Number(temperatureInput.max)) temperatureInput.value = temperatureInput.max;
      }
      if (temperatureRange) temperatureRange.textContent = '(temperature 0~' + (provider === 'xiaomi_mimo' ? '1.5' : '1') + ')';
      if (maxTokensInput) {
        maxTokensInput.max = provider === 'xiaomi_mimo' ? '131072' : '8192';
        if (Number(maxTokensInput.value) > Number(maxTokensInput.max)) maxTokensInput.value = maxTokensInput.max;
      }
      if (capabilityHint) {
        capabilityHint.textContent = provider === 'xiaomi_mimo'
          ? '• MiMo 模式支持原生联网搜索、深度思考与 MiMo-V2.5 工单图片分析；智谱知识库、内容审核和文档解析会停用。'
          : '• 智谱模式支持现有知识库、内容审核、联网搜索和文件解析能力。';
      }
      rebuildModels(provider);
      applyProviderRestrictions(provider);
    });

    applyProviderRestrictions(providerSelect.value === 'xiaomi_mimo' ? 'xiaomi_mimo' : 'zhipu');
  }

  /**
   * AI 内容生成：弹窗收集要求 -> 调用 user_actions.php?action=ai_generate -> 填入目标字段。
   * @param {string} targetId  目标 textarea/input 的 id
   * @param {string} genType   'announcement' | 'product' | 'general'
   */
  window.aiGenerateInto = function (targetId, genType) {
    var target = document.getElementById(targetId);
    if (!target) return;
    var hint = genType === 'announcement' ? '描述你想发布的公告内容，例如：本周六晚8点举办建筑大赛'
      : (genType === 'product' ? '描述商品卖点，例如：VIP月卡，含专属称号和每日奖励' : '描述你想生成的内容');
    var prompt = window.prompt('请输入生成要求：\n' + hint, '');
    if (prompt === null) return;
    prompt = prompt.trim();
    if (!prompt) return;

    var csrf = (window.adminCsrf || '');
    var fd = new FormData();
    fd.append('action', 'ai_generate');
    fd.append('csrf', csrf);
    fd.append('gen_type', genType || 'general');
    fd.append('prompt', prompt);

    var original = target.value;
    target.value = 'AI 生成中，请稍候...';
    target.disabled = true;

    fetch('user_actions.php', { method: 'POST', body: fd })
      .then(function (r) { return r.text(); })
      .then(function (text) {
        var res;
        try { res = JSON.parse(text); } catch (e) { res = { status: 'error', message: '响应解析失败' }; }
        target.disabled = false;
        if (res.status === 'success' && res.content) {
          target.value = res.content;
          if (window.showToast) window.showToast('AI 已生成，可继续编辑', 'success');
        } else {
          target.value = original;
          if (window.showToast) window.showToast(res.message || 'AI 生成失败', 'error');
          else alert(res.message || 'AI 生成失败');
        }
      })
      .catch(function () {
        target.disabled = false;
        target.value = original;
        if (window.showToast) window.showToast('网络错误，请重试', 'error');
      });
  };

  // ==================== AI 测试台 ====================
  function initPlayground() {
    var input = document.getElementById('aiPlaygroundInput');
    var output = document.getElementById('aiPlaygroundOutput');
    var sendBtn = document.getElementById('aiPlaygroundSend');
    var stopBtn = document.getElementById('aiPlaygroundStop');
    var csrfEl = document.getElementById('aiPlaygroundCsrf');
    if (!input || !output || !sendBtn || !csrfEl) return;

    var current = null;

    function setBusy(busy) {
      sendBtn.disabled = busy;
      input.disabled = busy;
      stopBtn.style.display = busy ? '' : 'none';
    }

    function send() {
      var msg = input.value.trim();
      if (!msg) return;
      output.textContent = '';
      setBusy(true);
      var acc = '';
      current = aiStreamChat({
        url: 'ai_proxy.php',
        payload: { csrf: csrfEl.value, mode: 'chat', message: msg },
        onDelta: function (t) {
          acc += t;
          output.textContent = acc;
        },
        onDone: function () {
          setBusy(false);
          current = null;
          if (!acc) output.textContent = '(AI 未返回内容)';
        },
        onError: function (m) {
          setBusy(false);
          current = null;
          output.textContent = '❌ ' + m;
        }
      });
    }

    sendBtn.addEventListener('click', send);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
      }
    });
    stopBtn.addEventListener('click', function () {
      if (current) current.abort();
      setBusy(false);
      current = null;
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initProviderSettings();
      initPlayground();
    });
  } else {
    initProviderSettings();
    initPlayground();
  }
})();
