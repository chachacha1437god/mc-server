/**
 * 入服申请 tab — Element Plus 版。
 * 状态展示用 el-result，申请表单用 el-form。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var TEMPLATE = ''
        + '<div class="ep-application">'

        /* 状态卡 */
        + '  <el-card v-if="app" class="ep-app-status-card" shadow="never">'
        + '    <el-result :icon="statusIcon" :title="statusTitle" :sub-title="statusSub">'
        + '      <template #extra>'
        + '        <el-descriptions v-if="app.mc_name" :column="1" border size="small" class="ep-app-desc">'
        + '          <el-descriptions-item label="游戏 ID">{{ app.mc_name }}</el-descriptions-item>'
        + '          <el-descriptions-item label="提交时间">{{ app.created_at }}</el-descriptions-item>'
        + '          <el-descriptions-item v-if="app.reviewed_at" label="审核时间">{{ app.reviewed_at }}</el-descriptions-item>'
        + '        </el-descriptions>'
        + '        <el-alert v-if="app.review_note" :title="\'管理员留言：\' + app.review_note"'
        + '                  type="info" show-icon :closable="false" style="margin-top:12px;text-align:left;"></el-alert>'
        + '      </template>'
        + '    </el-result>'
        + '  </el-card>'

        /* 规则提示 */
        + '  <el-alert v-if="ruleText" :title="ruleText" type="info" show-icon :closable="false"'
        + '             style="margin-bottom:16px;"></el-alert>'

        /* 限制提示 */
        + '  <el-alert v-if="limitMsg" :title="limitMsg" type="error" show-icon :closable="false"'
        + '             style="margin-bottom:16px;"></el-alert>'

        /* 申请表单 */
        + '  <el-card v-if="canSubmit" shadow="never">'
        + '    <template #header>'
        + '      <div class="ep-card-head"><div><h2>{{ app ? \'重新提交申请\' : \'填写申请表\' }}</h2></div></div>'
        + '    </template>'
        + '    <el-form :model="form" :rules="rules" ref="formRef" label-position="top" @submit.prevent="submit">'
        + '      <el-form-item label="Minecraft 游戏 ID" prop="mc_name">'
        + '        <el-input v-model="form.mc_name" maxlength="32" show-word-limit></el-input>'
        + '      </el-form-item>'
        + '      <el-form-item label="年龄段" prop="age_range">'
        + '        <el-select v-model="form.age_range" style="width:100%">'
        + '          <el-option label="13以下" value="13以下"></el-option>'
        + '          <el-option label="13-17"  value="13-17"></el-option>'
        + '          <el-option label="18-24"  value="18-24"></el-option>'
        + '          <el-option label="25-30"  value="25-30"></el-option>'
        + '          <el-option label="30以上" value="30以上"></el-option>'
        + '        </el-select>'
        + '      </el-form-item>'
        + '      <el-form-item label="从哪里知道我们的服务器？">'
        + '        <el-input v-model="form.source" maxlength="100" placeholder="例如：朋友推荐、B站、百度..."></el-input>'
        + '      </el-form-item>'
        + '      <el-form-item label="为什么想加入？" prop="reason">'
        + '        <el-input v-model="form.reason" type="textarea" :rows="5" maxlength="1000" show-word-limit'
        + '                  placeholder="请简要介绍你想加入的原因..."></el-input>'
        + '      </el-form-item>'
        + '      <el-form-item prop="agreed">'
        + '        <el-checkbox v-model="form.agreed">我已阅读并同意遵守服务器规则</el-checkbox>'
        + '      </el-form-item>'
        + '      <el-form-item>'
        + '        <el-button type="primary" native-type="submit" :loading="loading" style="width:100%">'
        + '          {{ app ? \'重新提交申请\' : \'提交申请\' }}'
        + '        </el-button>'
        + '      </el-form-item>'
        + '    </el-form>'
        + '  </el-card>'

        + '</div>';

    var STATUS_MAP = {
        pending:        { icon: 'warning', title: '申请待审核中', sub: '管理员会尽快审核，请耐心等待。审核结果将通过站内通知发送。' },
        approved:       { icon: 'success', title: '申请已通过',  sub: '恭喜！你已获得白名单，可以进入服务器游玩了！' },
        rejected:       { icon: 'error',   title: '申请已拒绝',  sub: '你可以修改信息后重新提交申请。' },
        need_more_info: { icon: 'info',    title: '需要补充信息', sub: '请根据管理员留言补充后重新提交。' }
    };

    window.EpBlocks.userApplication = function (props) {
        var app = props.app || null;
        var appStatus = app ? (STATUS_MAP[app.status] || STATUS_MAP.pending) : null;

        var ruleText = '';
        if (props.cooldownHours > 0 || props.maxSubmissions > 0) {
            var parts = [];
            if (props.cooldownHours > 0) parts.push('每次提交后需等待 ' + props.cooldownHours + ' 小时');
            if (props.maxSubmissions > 0) parts.push('最多提交 ' + props.maxSubmissions + ' 次，当前已提交 ' + props.submittedCount + ' 次');
            ruleText = '申请规则：' + parts.join('；');
        }

        return {
            template: TEMPLATE,
            data: function () {
                return {
                    app: app,
                    canSubmit: !!props.canSubmit,
                    limitMsg: props.limitMsg || '',
                    ruleText: ruleText,
                    loading: false,
                    form: {
                        mc_name:   app ? (app.mc_name || '') : (props.mcName || ''),
                        age_range: app ? (app.age_range || '') : '',
                        source:    app ? (app.source || '') : '',
                        reason:    app ? (app.reason || '') : '',
                        agreed:    false
                    },
                    rules: {
                        mc_name:   [{ required: true, message: '请填写游戏 ID', trigger: 'blur' }],
                        age_range: [{ required: true, message: '请选择年龄段', trigger: 'change' }],
                        reason:    [{ required: true, message: '请填写原因', trigger: 'blur' }],
                        agreed:    [{ validator: function (rule, val, cb) {
                            val ? cb() : cb(new Error('请阅读并同意服务器规则'));
                        }, trigger: 'change' }]
                    }
                };
            },
            computed: {
                statusIcon:  function () { return appStatus ? appStatus.icon : 'info'; },
                statusTitle: function () { return appStatus ? appStatus.title : ''; },
                statusSub:   function () { return appStatus ? appStatus.sub : ''; }
            },
            methods: {
                submit: function () {
                    var self = this;
                    this.$refs.formRef.validate(function (valid) {
                        if (!valid) return;
                        self.loading = true;
                        var fd = new FormData();
                        fd.append('csrf',      window.userCsrf || '');
                        fd.append('mc_name',   self.form.mc_name);
                        fd.append('age_range', self.form.age_range);
                        fd.append('source',    self.form.source);
                        fd.append('reason',    self.form.reason);
                        fd.append('agreed_rules', '1');
                        fetch('api/index.php?action=apply', { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                self.loading = false;
                                if (data.status === 'success') {
                                    ElementPlus.ElMessage.success(data.message || '申请已提交，请耐心等待审核');
                                    self.canSubmit = false;
                                    self.app = { status: 'pending', mc_name: self.form.mc_name, created_at: '刚刚' };
                                } else {
                                    ElementPlus.ElMessage.error(data.message || '提交失败');
                                }
                            })
                            .catch(function () {
                                self.loading = false;
                                ElementPlus.ElMessage.error('网络异常，请重试');
                            });
                    });
                }
            }
        };
    };
}());
