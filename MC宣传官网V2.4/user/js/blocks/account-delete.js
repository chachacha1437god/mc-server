/**
 * 注销账号区块 — Element Plus 版（自包含）。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var CONFIRM_WORD = '确认注销';

    var TEMPLATE = ''
        + '<div class="ep-del">'
        + '  <el-alert type="error" show-icon :closable="false" class="ep-del-hero" title="注销账号是永久操作"><template #default>账号及全部相关数据将被永久删除，无法撤销。完成身份验证后仍需经过冷静时间与最终确认。</template></el-alert>'
        + '  <el-row :gutter="18" class="ep-del-layout">'
        + '    <el-col :xs="24" :lg="10">'
        + '      <el-card shadow="never" class="ep-del-impact-card">'
        + '        <template #header><div class="ep-card-head"><div><h2>删除范围</h2><p>注销完成后以下数据无法恢复</p></div><el-tag type="danger" effect="dark">不可撤销</el-tag></div></template>'
        + '        <div class="ep-del-consequences">'
        + '          <div class="ep-del-con-item"><div class="ep-del-con-icon is-danger"><el-icon><User></User></el-icon></div><div><strong>账号资料</strong><span>用户名、邮箱、MC ID 与头像</span></div></div>'
        + '          <div class="ep-del-con-item"><div class="ep-del-con-icon is-warning"><el-icon><Document></Document></el-icon></div><div><strong>申请与通知</strong><span>入服申请、历史快照和站内通知</span></div></div>'
        + '          <div class="ep-del-con-item"><div class="ep-del-con-icon is-info"><el-icon><ChatLineRound></ChatLineRound></el-icon></div><div><strong>工单记录</strong><span>工单、相关回复及上传的附件</span></div></div>'
        + '        </div>'
        + '        <el-divider></el-divider>'
        + '        <el-steps :active="step" finish-status="success" align-center class="ep-del-steps"><el-step title="验证邮箱"></el-step><el-step title="冷静时间"></el-step><el-step title="最终确认"></el-step></el-steps>'
        + '      </el-card>'
        + '    </el-col>'
        + '    <el-col :xs="24" :lg="14">'
        + '  <el-card shadow="never" class="ep-del-verify-card">'
        + '    <template #header><div class="ep-card-head"><h3>身份验证</h3></div></template>'
        + '    <el-form label-position="top" @submit.prevent="openDialog">'
        + '      <el-form-item label="绑定邮箱">'
        + '        <div class="ep-del-email-row"><el-input v-model="form.email" type="email" maxlength="120" placeholder="输入当前账号绑定邮箱"><template #prefix><el-icon><Message></Message></el-icon></template></el-input><el-button :disabled="resendLeft > 0" :loading="sending" @click="sendCode">{{ resendLeft > 0 ? resendLeft + " 秒后重发" : "发送验证码" }}</el-button></div>'
        + '        <el-text type="info" size="small">验证码将发送到当前账号绑定邮箱，10 分钟内有效。</el-text>'
        + '      </el-form-item>'

        + '      <el-row :gutter="16">'
        + '        <el-col :xs="24" :sm="12">'
        + '          <el-form-item label="邮箱验证码">'
        + '            <el-input v-model="form.email_code" maxlength="6" inputmode="numeric" placeholder="6 位验证码"></el-input>'
        + '          </el-form-item>'
        + '        </el-col>'
        + '        <el-col :xs="24" :sm="12">'
        + '          <el-form-item label="当前密码">'
        + '            <el-input v-model="form.password" type="password" show-password placeholder="输入当前登录密码"></el-input>'
        + '          </el-form-item>'
        + '        </el-col>'
        + '      </el-row>'
        + '    </el-form>'

        + '    <div class="ep-del-cooldown">'
        + '      <el-text type="info" size="small" tag="div" style="margin-bottom:8px">{{ cooldownText }}</el-text>'
        + '      <el-progress :percentage="cooldownPct" :status="cooldownLeft <= 0 && codeSent ? \'success\' : undefined"'
        + '                   :stroke-width="6" :show-text="false"></el-progress>'
        + '    </div>'

        + '    <el-button type="danger" native-type="submit" class="ep-del-submit" :disabled="!codeSent">继续注销</el-button>'
        + '  </el-card>'
        + '    </el-col>'
        + '  </el-row>'

        + '  <el-dialog v-model="dialogVisible" title="最终确认" width="min(480px, 94vw)"'
        + '             :close-on-click-modal="false" align-center class="ep-del-dialog">'
        + '    <el-alert type="error" :closable="false" show-icon style="margin-bottom:16px"'
        + '              title="账号注销后将立即退出登录，所有数据永久删除，无法恢复。"></el-alert>'

        + '    <el-checkbox v-model="confirmChecked" class="ep-del-check">'
        + '      我已知晓注销账号会删除账号资料、申请、通知和工单记录，且该操作不可恢复。'
        + '    </el-checkbox>'

        + '    <div class="ep-del-confirm-input">'
        + '      <el-text type="secondary" size="small" tag="div" style="margin-bottom:6px">请输入「{{ confirmWord }}」</el-text>'
        + '      <el-input v-model="confirmText" :placeholder="confirmWord" autocomplete="off"></el-input>'
        + '    </div>'

        + '    <el-text :type="cooldownLeft <= 0 && codeSent ? \'success\' : \'info\'" size="small"'
        + '             tag="div" class="ep-del-modal-cd" :class="{ \'is-done\': cooldownLeft <= 0 && codeSent }">'
        + '      {{ modalCooldownText }}'
        + '    </el-text>'

        + '    <template #footer>'
        + '      <el-button @click="dialogVisible = false">取消</el-button>'
        + '      <el-button type="danger" :disabled="!canSubmit" :loading="submitting" @click="submit">'
        + '        永久注销账号'
        + '      </el-button>'
        + '    </template>'
        + '  </el-dialog>'

        + '</div>';

    window.EpBlocks.userAccountDelete = function (props) {
        return {
            template: TEMPLATE,
            data: function () {
                return {
                    csrf: props.csrf || '',
                    confirmWord: CONFIRM_WORD,
                    form: { email: props.email || '', email_code: '', password: '' },
                    sending: false,
                    submitting: false,
                    codeSent: false,
                    resendLeft: 0,
                    cooldownTotal: 0,
                    cooldownLeft: 0,
                    dialogVisible: false,
                    confirmChecked: false,
                    confirmText: '',
                    _resendTimer: null,
                    _cooldownTimer: null,
                };
            },
            computed: {
                step: function () {
                    if (!this.codeSent) return 0;
                    if (this.cooldownLeft > 0) return 1;
                    return this.canSubmit ? 3 : 2;
                },
                cooldownPct: function () {
                    if (!this.cooldownTotal) return 0;
                    return Math.round((this.cooldownTotal - this.cooldownLeft) / this.cooldownTotal * 100);
                },
                cooldownText: function () {
                    if (!this.codeSent) return '发送验证码后开始冷静倒计时';
                    return this.cooldownLeft > 0
                        ? '冷静时间：还剩 ' + this.cooldownLeft + ' 秒'
                        : '冷静时间已结束，可以继续';
                },
                modalCooldownText: function () {
                    if (!this.codeSent) return '请先发送验证码';
                    return this.cooldownLeft > 0
                        ? '请等待冷静时间结束：还剩 ' + this.cooldownLeft + ' 秒'
                        : '冷静时间已结束，请再次确认后提交';
                },
                canSubmit: function () {
                    return this.confirmChecked
                        && this.confirmText.trim() === CONFIRM_WORD
                        && this.codeSent
                        && this.cooldownLeft <= 0;
                },
            },
            beforeUnmount: function () {
                if (this._resendTimer) clearInterval(this._resendTimer);
                if (this._cooldownTimer) clearInterval(this._cooldownTimer);
            },
            methods: {
                startResend: function (seconds) {
                    var self = this;
                    self.resendLeft = Number(seconds) || 60;
                    if (self._resendTimer) clearInterval(self._resendTimer);
                    self._resendTimer = setInterval(function () {
                        self.resendLeft--;
                        if (self.resendLeft <= 0) {
                            self.resendLeft = 0;
                            clearInterval(self._resendTimer);
                            self._resendTimer = null;
                        }
                    }, 1000);
                },
                startCooldown: function (seconds) {
                    var self = this;
                    self.cooldownTotal = Number(seconds) || 30;
                    self.cooldownLeft = self.cooldownTotal;
                    self.codeSent = true;
                    if (self._cooldownTimer) clearInterval(self._cooldownTimer);
                    self._cooldownTimer = setInterval(function () {
                        self.cooldownLeft--;
                        if (self.cooldownLeft <= 0) {
                            self.cooldownLeft = 0;
                            clearInterval(self._cooldownTimer);
                            self._cooldownTimer = null;
                        }
                    }, 1000);
                },
                sendCode: function () {
                    var self = this;
                    var email = (self.form.email || '').trim();
                    if (!email) { ElementPlus.ElMessage.error('请先填写绑定邮箱'); return; }
                    self.sending = true;
                    var fd = new FormData();
                    fd.append('csrf', self.csrf);
                    fd.append('email', email);
                    fetch('api/index.php?action=send_delete_account_code', { method: 'POST', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            self.sending = false;
                            if (res.status === 'success') {
                                ElementPlus.ElMessage.success(res.message || '注销验证码已发送');
                                self.startResend(res.cooldown || 60);
                                self.startCooldown(res.delete_cooldown || 30);
                            } else {
                                ElementPlus.ElMessage.error(res.message || '验证码发送失败');
                                if (res.cooldown) self.startResend(res.cooldown);
                            }
                        })
                        .catch(function () {
                            self.sending = false;
                            ElementPlus.ElMessage.error('服务器返回异常，请稍后重试');
                        });
                },
                openDialog: function () {
                    if (!this.codeSent) { ElementPlus.ElMessage.error('请先发送验证码'); return; }
                    if (!this.form.email_code.trim()) { ElementPlus.ElMessage.error('请输入邮箱验证码'); return; }
                    if (!this.form.password.trim()) { ElementPlus.ElMessage.error('请输入当前密码'); return; }
                    this.confirmChecked = false;
                    this.confirmText = '';
                    this.dialogVisible = true;
                },
                submit: function () {
                    var self = this;
                    if (!self.canSubmit) return;
                    self.submitting = true;
                    var fd = new FormData();
                    fd.append('csrf', self.csrf);
                    fd.append('email', self.form.email);
                    fd.append('email_code', self.form.email_code);
                    fd.append('password', self.form.password);
                    fd.append('confirm_consequence', '1');
                    fd.append('confirm_text', CONFIRM_WORD);
                    fetch('api/index.php?action=delete_account', { method: 'POST', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            self.submitting = false;
                            if (res.status === 'success') {
                                self.dialogVisible = false;
                                ElementPlus.ElMessage.success(res.message || '账号已注销');
                                setTimeout(function () {
                                    window.location.href = res.redirect || '../index.html';
                                }, 1800);
                            } else {
                                ElementPlus.ElMessage.error(res.message || '注销失败，请重试');
                            }
                        })
                        .catch(function () {
                            self.submitting = false;
                            ElementPlus.ElMessage.error('服务器返回异常，请稍后重试');
                        });
                },
            },
        };
    };
}());
