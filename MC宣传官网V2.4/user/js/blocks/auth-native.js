(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var API = 'api/index.php';
    var switching = false;

    function post(action, data) {
        var form = new FormData();
        Object.keys(data || {}).forEach(function (key) { form.append(key, data[key] == null ? '' : data[key]); });
        return fetch(API + '?action=' + encodeURIComponent(action), { method: 'POST', body: form, credentials: 'same-origin' }).then(function (response) {
            return response.text().then(function (text) {
                try { return JSON.parse(text); }
                catch (error) { throw new Error(text ? text.slice(0, 160) : '服务器未返回有效数据'); }
            });
        });
    }

    function notify(text, type) {
        ElementPlus.ElMessage({ message: text, type: type || 'info' });
    }

    function switchAuth(path, pushState) {
        if (switching) return Promise.resolve(false);
        var root = document.querySelector('[data-ep-app^="auth"]');
        if (!root || !window.EpBridge || typeof window.EpBridge.replace !== 'function') {
            window.location.href = path;
            return Promise.resolve(false);
        }
        switching = true;
        root.classList.add('is-switching');
        var target = new URL(path, window.location.href);
        return fetch(target.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) {
                if (!response.ok) throw new Error('页面加载失败');
                return response.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var next = doc.querySelector('[data-ep-app^="auth"]');
                if (!next) throw new Error('认证页面暂时不可用');
                var name = next.getAttribute('data-ep-app');
                var props = {};
                try { props = JSON.parse(next.getAttribute('data-ep-props') || '{}'); }
                catch (error) { throw new Error('页面数据解析失败'); }
                window.EpBridge.replace(root, name, props);
                document.title = doc.title || document.title;
                if (pushState !== false) history.pushState({ auth: name }, '', target.pathname + target.search);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                requestAnimationFrame(function () { root.classList.remove('is-switching'); root.classList.add('is-entering'); requestAnimationFrame(function () { root.classList.remove('is-entering'); }); });
                return true;
            })
            .catch(function (error) {
                root.classList.remove('is-switching');
                notify(error.message || '页面切换失败', 'error');
                return false;
            })
            .finally(function () { switching = false; });
    }

    window.addEventListener('popstate', function () {
        if (/\/(login|register|forgot_password)\.php$/.test(window.location.pathname)) {
            switchAuth(window.location.pathname + window.location.search, false);
        }
    });

    function noticeHtml() {
        return '<el-alert v-for="(item, index) in notices" :key="index" :type="item.type" :title="item.text" show-icon :closable="false"></el-alert>';
    }

    function passwordStrength(password) {
        if (!password) return { percentage: 0, status: '', label: '' };
        var score = 0;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        if (score <= 0) return { percentage: 20, status: 'exception', label: '弱' };
        if (score <= 1) return { percentage: 50, status: 'warning', label: '中' };
        if (score <= 2) return { percentage: 75, status: '', label: '强' };
        return { percentage: 100, status: 'success', label: '非常强' };
    }

    function startCountdown(vm, seconds) {
        vm.cooldown = Number(seconds) || 60;
        if (vm._timer) clearInterval(vm._timer);
        vm._timer = setInterval(function () {
            vm.cooldown--;
            if (vm.cooldown <= 0) {
                vm.cooldown = 0;
                clearInterval(vm._timer);
                vm._timer = null;
            }
        }, 1000);
    }

    var LOGIN_TEMPLATE = ''
        + '<main class="ep-auth-shell ep-auth-shell-login">'
        + '  <el-card shadow="never" class="ep-auth-card">'
        + '    <template #header><div class="ep-auth-card-head"><div><h2>登录用户中心</h2><p>使用用户名或注册邮箱登录</p></div></div></template>'
        + '    <div class="ep-auth-notices">' + noticeHtml() + '</div>'
        + '    <el-form ref="formRef" :model="form" :rules="rules" label-position="top" :disabled="!available" @submit.prevent="submit">'
        + '      <el-form-item label="用户名 / 邮箱" prop="login"><el-input v-model.trim="form.login" maxlength="120" autocomplete="username" placeholder="输入用户名或邮箱"></el-input></el-form-item>'
        + '      <el-form-item label="密码" prop="password"><el-input v-model="form.password" type="password" show-password autocomplete="current-password" placeholder="输入密码"></el-input></el-form-item>'
        + '      <el-form-item v-if="simpleCaptchaHtml" label="数字验证码" prop="simpleCaptcha"><div class="ep-auth-captcha"><el-button class="ep-auth-captcha-image" title="点击换一张" @click="refreshCaptcha"><span v-html="simpleCaptchaHtml"></span></el-button><el-button :disabled="refreshLeft > 0" @click="refreshCaptcha">{{ refreshLeft > 0 ? refreshLeft + \'s\' : \'换一张\' }}</el-button><el-input v-model.trim="form.simpleCaptcha" maxlength="4" inputmode="numeric" placeholder="输入图中的 4 位数字"></el-input></div></el-form-item>'
        + '      <el-form-item v-if="mathCaptcha" label="人机校验" prop="captcha"><div class="ep-auth-math"><el-tag type="success" effect="plain" size="large">{{ mathCaptcha }} = ?</el-tag><el-input v-model.trim="form.captcha" inputmode="numeric" placeholder="输入计算结果"></el-input></div></el-form-item>'
        + '      <div class="ep-auth-form-links"><el-link href="forgot_password.php" type="primary" :underline="false" @click.prevent="go(\'forgot_password.php\')">忘记密码？</el-link></div>'
        + '      <el-button type="primary" native-type="submit" :loading="loading" :disabled="!available" class="ep-auth-submit">登录</el-button>'
        + '    </el-form>'
        + '    <div class="ep-auth-footer">还没有账号？<el-link href="register.php" type="primary" :underline="false" :disabled="!available" @click.prevent="go(\'register.php\')">立即注册</el-link></div>'
        + '  </el-card>'
        + '</main>';

    window.EpBlocks.authLogin = function (props) {
        return {
            template: LOGIN_TEMPLATE,
            data: function () {
                return {
                    available: !!props.available,
                    notices: props.notices || [],
                    simpleCaptchaHtml: props.simpleCaptchaHtml || '',
                    mathCaptcha: props.mathCaptcha || '',
                    loading: false,
                    refreshLeft: 0,
                    _timer: null,
                    form: { login: props.oldLogin || '', password: '', simpleCaptcha: '', captcha: '', website_url: '', contact_phone: '' },
                    rules: {
                        login: [{ required: true, message: '请输入用户名或邮箱', trigger: 'blur' }],
                        password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
                        simpleCaptcha: [{ required: true, message: '请输入数字验证码', trigger: 'blur' }, { pattern: /^\d{4}$/, message: '请输入 4 位数字', trigger: 'blur' }],
                        captcha: [{ required: true, message: '请输入计算结果', trigger: 'blur' }]
                    }
                };
            },
            mounted: function () {
                var self = this;
                var until = 0;
                try { until = Number(sessionStorage.getItem('userLgCaptchaCooldownUntil')) || 0; } catch (ignore) {}
                if (until <= Date.now()) return;
                self.refreshLeft = Math.ceil((until - Date.now()) / 1000);
                self._timer = setInterval(function () {
                    self.refreshLeft = Math.max(0, Math.ceil((until - Date.now()) / 1000));
                    if (self.refreshLeft <= 0) { clearInterval(self._timer); self._timer = null; }
                }, 500);
            },
            beforeUnmount: function () { if (this._timer) clearInterval(this._timer); },
            methods: {
                refreshCaptcha: function () {
                    var self = this;
                    if (self.refreshLeft > 0) return;
                    self.refreshLeft = 5;
                    try { sessionStorage.setItem('userLgCaptchaCooldownUntil', String(Date.now() + 5000)); } catch (ignore) {}
                    switchAuth('login.php', false);
                },
                go: function (path) { switchAuth(path, true); },
                submit: function () {
                    var self = this;
                    self.$refs.formRef.validate(function (valid) {
                        if (!valid || self.loading) return;
                        self.loading = true;
                        post('login', { csrf: props.csrf || '', login: self.form.login, password: self.form.password, simple_captcha: self.form.simpleCaptcha, captcha: self.form.captcha, website_url: self.form.website_url, contact_phone: self.form.contact_phone }).then(function (result) {
                            if (result.status !== 'success') throw Object.assign(new Error(result.message || '登录失败'), { reload: !!result.reload });
                            notify(result.message || '登录成功', 'success');
                            window.location.href = result.redirect || 'panel.php';
                        }).catch(function (error) {
                            notify(error.message || '登录失败', 'error');
                            if (error.reload) setTimeout(function () { location.reload(); }, 800);
                        }).finally(function () { self.loading = false; });
                    });
                }
            }
        };
    };

    var REGISTER_TEMPLATE = ''
        + '<main class="ep-auth-shell ep-auth-shell-register">'
        + '  <el-card shadow="never" class="ep-auth-card ep-auth-card-wide">'
        + '    <template #header><div class="ep-auth-card-head"><div><h2>注册用户中心</h2><p>请填写真实有效的邮箱和游戏 ID</p></div></div></template>'
        + '    <div class="ep-auth-notices">' + noticeHtml() + '</div>'
        + '    <el-form ref="formRef" :model="form" :rules="rules" label-position="top" :disabled="!available" @submit.prevent="submit">'
        + '      <el-row :gutter="14"><el-col :xs="24" :sm="12"><el-form-item label="用户名" prop="username"><el-input v-model.trim="form.username" maxlength="32" show-word-limit placeholder="2-32 个字符"></el-input></el-form-item></el-col><el-col :xs="24" :sm="12"><el-form-item label="Minecraft 游戏 ID" prop="mcName"><el-input v-model.trim="form.mcName" maxlength="32" placeholder="你的游戏内名称"></el-input></el-form-item></el-col></el-row>'
        + '      <el-form-item label="邮箱" prop="email"><div class="ep-auth-code-row"><el-input v-model.trim="form.email" type="email" maxlength="120" placeholder="your@email.com"></el-input><el-button :loading="sending" :disabled="cooldown > 0" @click="sendCode">{{ cooldown > 0 ? cooldown + \' 秒后重发\' : \'发送验证码\' }}</el-button></div></el-form-item>'
        + '      <el-form-item label="邮箱验证码" prop="emailCode"><el-input v-model.trim="form.emailCode" maxlength="6" inputmode="numeric" placeholder="输入邮件中的 6 位验证码"></el-input></el-form-item>'
        + '      <el-row :gutter="14"><el-col :xs="24" :sm="12"><el-form-item label="密码" prop="password"><el-input v-model="form.password" type="password" show-password maxlength="6" autocomplete="new-password" placeholder="必须为 6 位字符"></el-input><div v-if="form.password" class="ep-auth-strength"><el-progress :percentage="strength.percentage" :status="strength.status" :show-text="false" :stroke-width="5"></el-progress><el-text size="small">强度：{{ strength.label }}</el-text></div></el-form-item></el-col><el-col :xs="24" :sm="12"><el-form-item label="确认密码" prop="confirm"><el-input v-model="form.confirm" type="password" show-password maxlength="6" autocomplete="new-password" placeholder="再次输入密码"></el-input></el-form-item></el-col></el-row>'
        + '      <el-button type="primary" native-type="submit" :loading="loading" :disabled="!available" class="ep-auth-submit">创建账号</el-button>'
        + '    </el-form><div class="ep-auth-footer">已有账号？<el-link href="login.php" type="primary" :underline="false" @click.prevent="go(\'login.php\')">立即登录</el-link></div>'
        + '  </el-card>'
        + '</main>';

    window.EpBlocks.authRegister = function (props) {
        var old = props.old || {};
        return {
            template: REGISTER_TEMPLATE,
            data: function () {
                var self = this;
                return {
                    available: !!props.available, notices: props.notices || [], loading: false, sending: false, cooldown: 0, _timer: null,
                    form: { username: old.username || '', mcName: old.mc_name || '', email: old.email || '', emailCode: '', password: '', confirm: '' },
                    rules: {
                        username: [{ required: true, message: '请输入用户名', trigger: 'blur' }, { min: 2, max: 32, message: '长度需为 2-32 个字符', trigger: 'blur' }, { pattern: /^[a-zA-Z0-9_\u4e00-\u9fa5]+$/, message: '只允许字母、数字、下划线和中文', trigger: 'blur' }],
                        mcName: [{ required: true, message: '请输入 Minecraft 游戏 ID', trigger: 'blur' }],
                        email: [{ required: true, message: '请输入邮箱', trigger: 'blur' }, { type: 'email', message: '邮箱格式不正确', trigger: 'blur' }],
                        emailCode: [{ required: true, message: '请输入邮箱验证码', trigger: 'blur' }, { pattern: /^\d{6}$/, message: '请输入 6 位数字', trigger: 'blur' }],
                        password: [{ required: true, message: '请输入密码', trigger: 'blur' }, { min: 6, max: 6, message: '密码必须为 6 位字符', trigger: 'blur' }],
                        confirm: [{ required: true, message: '请再次输入密码', trigger: 'blur' }, { validator: function (rule, value, callback) { value === self.form.password ? callback() : callback(new Error('两次密码不一致')); }, trigger: 'blur' }]
                    }
                };
            },
            computed: { strength: function () { return passwordStrength(this.form.password); } },
            beforeUnmount: function () { if (this._timer) clearInterval(this._timer); },
            methods: {
                go: function (path) { switchAuth(path, true); },
                sendCode: function () { var self = this; self.$refs.formRef.validateField('email', function (valid) { if (!valid || self.sending) return; self.sending = true; post('send_register_code', { csrf: props.csrf || '', email: self.form.email }).then(function (result) { if (result.status !== 'success') throw Object.assign(new Error(result.message || '发送失败'), { cooldown: result.cooldown }); notify(result.message || '验证码已发送', 'success'); startCountdown(self, result.cooldown); }).catch(function (error) { notify(error.message || '发送失败', 'error'); if (error.cooldown) startCountdown(self, error.cooldown); }).finally(function () { self.sending = false; }); }); },
                submit: function () { var self = this; self.$refs.formRef.validate(function (valid) { if (!valid || self.loading) return; self.loading = true; post('register', { csrf: props.csrf || '', username: self.form.username, mc_name: self.form.mcName, email: self.form.email, email_code: self.form.emailCode, password: self.form.password, password_confirm: self.form.confirm }).then(function (result) { if (result.status !== 'success') throw new Error(result.message || '注册失败'); notify(result.message || '注册成功', 'success'); window.location.href = result.redirect || 'panel.php'; }).catch(function (error) { notify(error.message || '注册失败', 'error'); }).finally(function () { self.loading = false; }); }); }
            }
        };
    };

    var RESET_TEMPLATE = ''
        + '<main class="ep-auth-shell ep-auth-shell-reset">'
        + '  <el-card shadow="never" class="ep-auth-card">'
        + '    <template #header><div class="ep-auth-card-head"><div><h2>重置密码</h2><p>完成邮箱验证后立即生效</p></div></div></template>'
        + '    <div class="ep-auth-notices">' + noticeHtml() + '</div>'
        + '    <el-form ref="formRef" :model="form" :rules="rules" label-position="top" :disabled="!available" @submit.prevent="submit">'
        + '      <el-form-item label="注册邮箱" prop="email"><div class="ep-auth-code-row"><el-input v-model.trim="form.email" type="email" maxlength="120" placeholder="your@email.com"></el-input><el-button :loading="sending" :disabled="cooldown > 0" @click="sendCode">{{ cooldown > 0 ? cooldown + \' 秒后重发\' : \'发送验证码\' }}</el-button></div></el-form-item>'
        + '      <el-form-item label="邮箱验证码" prop="emailCode"><el-input v-model.trim="form.emailCode" maxlength="6" inputmode="numeric" placeholder="输入邮件中的 6 位验证码"></el-input></el-form-item>'
        + '      <el-form-item label="新密码" prop="password"><el-input v-model="form.password" type="password" show-password maxlength="6" autocomplete="new-password" placeholder="必须为 6 位字符"></el-input><div v-if="form.password" class="ep-auth-strength"><el-progress :percentage="strength.percentage" :status="strength.status" :show-text="false" :stroke-width="5"></el-progress><el-text size="small">强度：{{ strength.label }}</el-text></div></el-form-item>'
        + '      <el-form-item label="确认新密码" prop="confirm"><el-input v-model="form.confirm" type="password" show-password autocomplete="new-password" placeholder="再次输入新密码"></el-input></el-form-item>'
        + '      <el-button type="primary" native-type="submit" :loading="loading" :disabled="!available" class="ep-auth-submit">确认重置</el-button>'
        + '    </el-form><div class="ep-auth-footer">想起密码了？<el-link href="login.php" type="primary" :underline="false" @click.prevent="go(\'login.php\')">返回登录</el-link></div>'
        + '  </el-card>'
        + '</main>';

    window.EpBlocks.authReset = function (props) {
        return {
            template: RESET_TEMPLATE,
            data: function () {
                var self = this;
                return {
                    available: !!props.available, notices: props.notices || [], loading: false, sending: false, cooldown: 0, _timer: null,
                    form: { email: props.oldEmail || '', emailCode: '', password: '', confirm: '' },
                    rules: {
                        email: [{ required: true, message: '请输入注册邮箱', trigger: 'blur' }, { type: 'email', message: '邮箱格式不正确', trigger: 'blur' }],
                        emailCode: [{ required: true, message: '请输入邮箱验证码', trigger: 'blur' }, { pattern: /^\d{6}$/, message: '请输入 6 位数字', trigger: 'blur' }],
                        password: [{ required: true, message: '请输入新密码', trigger: 'blur' }, { min: 6, max: 6, message: '密码必须为 6 位字符', trigger: 'blur' }],
                        confirm: [{ required: true, message: '请再次输入新密码', trigger: 'blur' }, { validator: function (rule, value, callback) { value === self.form.password ? callback() : callback(new Error('两次密码不一致')); }, trigger: 'blur' }]
                    }
                };
            },
            computed: { strength: function () { return passwordStrength(this.form.password); } },
            beforeUnmount: function () { if (this._timer) clearInterval(this._timer); },
            methods: {
                go: function (path) { switchAuth(path, true); },
                sendCode: function () { var self = this; self.$refs.formRef.validateField('email', function (valid) { if (!valid || self.sending) return; self.sending = true; post('send_reset_code', { csrf: props.csrf || '', email: self.form.email }).then(function (result) { if (result.status !== 'success') throw Object.assign(new Error(result.message || '发送失败'), { cooldown: result.cooldown }); notify(result.message || '验证码已发送', 'success'); startCountdown(self, result.cooldown); }).catch(function (error) { notify(error.message || '发送失败', 'error'); if (error.cooldown) startCountdown(self, error.cooldown); }).finally(function () { self.sending = false; }); }); },
                submit: function () { var self = this; self.$refs.formRef.validate(function (valid) { if (!valid || self.loading) return; self.loading = true; post('reset_password', { csrf: props.csrf || '', email: self.form.email, email_code: self.form.emailCode, password: self.form.password, password_confirm: self.form.confirm }).then(function (result) { if (result.status !== 'success') throw new Error(result.message || '重置失败'); notify(result.message || '密码已重置', 'success'); window.location.href = result.redirect || 'login.php?msg=reset'; }).catch(function (error) { notify(error.message || '重置失败', 'error'); }).finally(function () { self.loading = false; }); }); }
            }
        };
    };
}());