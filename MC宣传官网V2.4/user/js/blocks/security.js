/**
 * 修改密码区块 — Element Plus 版。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var TEMPLATE = ''
        + '<div class="ep-security">'
        + '  <el-row :gutter="18" class="ep-security-layout">'
        + '    <el-col :xs="24" :lg="9">'
        + '      <el-card shadow="never" class="ep-security-guide">'
        + '        <div class="ep-security-guide-icon"><el-icon :size="28"><Lock></Lock></el-icon></div>'
        + '        <h2>保护你的账号</h2>'
        + '        <p>定期更新密码，并避免在其他网站重复使用相同密码。</p>'
        + '        <el-divider></el-divider>'
        + '        <el-descriptions :column="1" size="small">'
        + '          <el-descriptions-item label="固定长度"><el-tag type="success" effect="plain">6 位字符</el-tag></el-descriptions-item>'
        + '          <el-descriptions-item label="推荐组合">大小写字母、数字和符号</el-descriptions-item>'
        + '          <el-descriptions-item label="修改结果">成功后立即使用新密码</el-descriptions-item>'
        + '        </el-descriptions>'
        + '        <el-alert type="info" :closable="false" show-icon title="请勿向任何人透露密码，工作人员不会索要你的登录密码。"></el-alert>'
        + '      </el-card>'
        + '    </el-col>'
        + '    <el-col :xs="24" :lg="15">'
        + '  <el-card shadow="never" class="ep-security-form-card">'
        + '    <template #header>'
        + '      <div class="ep-card-head"><div><h2>修改密码</h2><p>验证当前密码后设置新的登录密码</p></div><el-tag type="success" effect="plain"><el-icon><CircleCheck></CircleCheck></el-icon>安全连接</el-tag></div>'
        + '    </template>'

        + '    <el-alert v-if="msg.text" :title="msg.text" :type="msg.type" show-icon :closable="false" style="margin-bottom:20px"></el-alert>'

        + '    <el-form :model="form" :rules="rules" ref="formRef" label-position="top" @submit.prevent="submit">'

        + '      <el-form-item label="当前密码" prop="old_password">'
        + '        <el-input v-model="form.old_password" type="password" show-password'
        + '                  placeholder="输入当前密码" autocomplete="current-password"></el-input>'
        + '      </el-form-item>'

        + '      <el-form-item label="新密码" prop="new_password">'
        + '        <el-input v-model="form.new_password" type="password" show-password maxlength="6"'
        + '                  placeholder="必须为 6 位字符" autocomplete="new-password"></el-input>'
        + '        <el-space v-if="form.new_password" class="ep-security-strength" fill style="width:100%">'
        + '          <el-progress :percentage="strength.pct" :status="strength.status"'
        + '                       :stroke-width="5" :show-text="false" style="flex:1"></el-progress>'
        + '          <el-text :type="strength.status === \'success\' ? \'success\' : strength.status === \'warning\' ? \'warning\' : strength.status === \'exception\' ? \'danger\' : \'primary\'" size="small" class="ep-security-strength-label">{{ strength.label }}</el-text>'
        + '        </el-space>'
        + '      </el-form-item>'

        + '      <el-form-item label="确认新密码" prop="new_password_confirm">'
        + '        <el-input v-model="form.new_password_confirm" type="password" show-password'
        + '                  placeholder="再次输入新密码" autocomplete="new-password"></el-input>'
        + '      </el-form-item>'

        + '      <el-form-item style="margin-bottom:0">'
        + '        <el-button type="primary" native-type="submit" :loading="loading" class="ep-security-submit">'
        + '          <el-icon><Key></Key></el-icon>修改密码'
        + '        </el-button>'
        + '      </el-form-item>'

        + '    </el-form>'
        + '  </el-card>'
        + '    </el-col>'
        + '  </el-row>'
        + '</div>';

    window.EpBlocks.userSecurity = function (props) {
        return {
            template: TEMPLATE,
            data: function () {
                var self = this;
                return {
                    loading: false,
                    msg: { text: '', type: 'success' },
                    form: { old_password: '', new_password: '', new_password_confirm: '' },
                    rules: {
                        old_password: [{ required: true, message: '请输入当前密码', trigger: 'blur' }],
                        new_password: [
                            { required: true, message: '请输入新密码', trigger: 'blur' },
                            { min: 6, max: 6, message: '密码必须为 6 位字符', trigger: 'blur' }
                        ],
                        new_password_confirm: [
                            { required: true, message: '请再次输入新密码', trigger: 'blur' },
                            {
                                validator: function (rule, value, callback) {
                                    if (value !== self.form.new_password) {
                                        callback(new Error('两次密码不一致'));
                                    } else {
                                        callback();
                                    }
                                },
                                trigger: 'blur'
                            }
                        ]
                    }
                };
            },
            computed: {
                strength: function () {
                    var p = this.form.new_password;
                    if (!p) return { pct: 0, status: '', label: '' };
                    var score = 0;
                    if (/[A-Z]/.test(p)) score++;
                    if (/[0-9]/.test(p)) score++;
                    if (/[^A-Za-z0-9]/.test(p)) score++;
                    if (score <= 0) return { pct: 20,  status: 'exception', label: '弱' };
                    if (score <= 1) return { pct: 50,  status: 'warning',   label: '中' };
                    if (score <= 2) return { pct: 75,  status: '',          label: '强' };
                    return               { pct: 100, status: 'success',   label: '非常强' };
                }
            },
            methods: {
                submit: function () {
                    var self = this;
                    this.$refs.formRef.validate(function (valid) {
                        if (!valid) return;
                        self.loading = true;
                        self.msg = { text: '', type: 'success' };
                        var fd = new FormData();
                        fd.append('csrf', window.userCsrf || '');
                        fd.append('old_password', self.form.old_password);
                        fd.append('new_password', self.form.new_password);
                        fd.append('new_password_confirm', self.form.new_password_confirm);
                        fetch('api/index.php?action=security', { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                self.loading = false;
                                if (data.status === 'success') {
                                    self.msg = { text: data.message || '密码已修改', type: 'success' };
                                    self.$refs.formRef.resetFields();
                                } else {
                                    self.msg = { text: data.message || '修改失败', type: 'error' };
                                }
                            })
                            .catch(function () {
                                self.loading = false;
                                self.msg = { text: '网络异常，请重试', type: 'error' };
                            });
                    });
                }
            }
        };
    };
}());
