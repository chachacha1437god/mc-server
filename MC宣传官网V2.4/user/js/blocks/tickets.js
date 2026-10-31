/**
 * 工单反馈 tab — Element Plus 版。
 * 工单列表走原有 loadUserTickets() AJAX，此组件只负责"提交工单"表单。
 * 工单列表部分沿用原生 HTML（JS 动态注入），置于 #ep-ticket-list 占位容器内。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var FORM_TEMPLATE = ''
        + '<div class="ep-tickets">'
        + '  <el-card class="ep-ticket-form-card" shadow="never">'
        + '    <template #header>'
        + '      <div class="ep-card-head">'
        + '        <div><h2>提交工单</h2></div>'
        + '        <el-tag v-if="!canCreate" type="warning" effect="light">提交受限</el-tag>'
        + '      </div>'
        + '    </template>'

        + '    <el-alert v-if="limitMsg" :title="limitMsg" type="warning" show-icon :closable="false" style="margin-bottom:16px;"></el-alert>'

        + '    <el-form :model="form" :rules="rules" ref="formRef" label-position="top" @submit.prevent="submit">'
        + '      <el-row :gutter="16">'
        + '        <el-col :xs="24" :sm="12">'
        + '          <el-form-item label="问题类型" prop="category">'
        + '            <el-select v-model="form.category" style="width:100%">'
        + '              <el-option label="Bug 反馈"  value="bug"></el-option>'
        + '              <el-option label="举报玩家"  value="report"></el-option>'
        + '              <el-option label="封禁申诉"  value="appeal"></el-option>'
        + '              <el-option label="建议"      value="suggestion"></el-option>'
        + '              <el-option label="其他"      value="other"></el-option>'
        + '            </el-select>'
        + '          </el-form-item>'
        + '        </el-col>'
        + '        <el-col :xs="24" :sm="12">'
        + '          <el-form-item label="优先级" prop="priority">'
        + '            <el-select v-model="form.priority" style="width:100%">'
        + '              <el-option label="低"   value="low"></el-option>'
        + '              <el-option label="普通" value="normal"></el-option>'
        + '              <el-option label="高"   value="high"></el-option>'
        + '            </el-select>'
        + '          </el-form-item>'
        + '        </el-col>'
        + '      </el-row>'

        + '      <el-form-item label="标题" prop="subject">'
        + '        <el-input v-model="form.subject" maxlength="120" show-word-limit placeholder="简要描述问题"></el-input>'
        + '      </el-form-item>'

        + '      <el-form-item label="详细内容" prop="content">'
        + '        <el-input v-model="form.content" type="textarea" :rows="5" maxlength="5000" show-word-limit'
        + '                  placeholder="请尽量说明时间、地点、相关玩家、复现步骤等信息"></el-input>'
        + '      </el-form-item>'

        + '      <el-form-item label="附件（可选）">'
        + '        <el-upload :auto-upload="false" multiple :limit="5" :file-list="uploadFileList"'
        + '                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.log,.json,.zip,.mp4,.mp3,image/*"'
        + '                   :before-upload="beforeFileUpload" :on-change="onUploadChange" :on-remove="onUploadRemove"'
        + '                   :on-exceed="onExceed">'
        + '          <el-button :icon="\'Paperclip\'">添加附件</el-button>'
        + '          <template #tip>'
        + '            <el-text type="info" size="small">最多 5 个，单个 ≤ 10MB（图片/PDF/文本/压缩包等）</el-text>'
        + '          </template>'
        + '        </el-upload>'
        + '      </el-form-item>'

        + '      <el-form-item>'
        + '        <el-button type="primary" native-type="submit" :loading="loading" :disabled="!canCreate" style="width:100%">'
        + '          提交工单'
        + '        </el-button>'
        + '      </el-form-item>'
        + '    </el-form>'
        + '  </el-card>'
        + '</div>';

    window.EpBlocks.userTickets = function (props) {
        return {
            template: FORM_TEMPLATE,
            data: function () {
                return {
                    canCreate: !!props.canCreate,
                    limitMsg: props.limitMsg || '',
                    loading: false,
                    files: [],
                    uploadFileList: [],
                    form: { category: 'bug', priority: 'normal', subject: '', content: '' },
                    rules: {
                        subject: [{ required: true, message: '请填写标题', trigger: 'blur' },
                                  { max: 120, message: '不超过 120 个字符', trigger: 'blur' }],
                        content: [{ required: true, message: '请填写详细内容', trigger: 'blur' }]
                    }
                };
            },
            methods: {
                beforeFileUpload: function () { return false; }, // 阻止自动上传
                onUploadChange: function (uploadFile, uploadFiles) {
                    if (uploadFile.raw && uploadFile.raw.size > 10 * 1024 * 1024) {
                        ElementPlus.ElMessage.warning(uploadFile.name + ' 超过 10MB，已跳过');
                        // 从列表移除超限文件
                        var idx = uploadFiles.indexOf(uploadFile);
                        if (idx !== -1) uploadFiles.splice(idx, 1);
                        return;
                    }
                    this.files = uploadFiles.filter(function (f) { return f.raw; }).map(function (f) { return f.raw; });
                    this.uploadFileList = uploadFiles.filter(function (f) { return f.raw; });
                },
                onUploadRemove: function (uploadFile, uploadFiles) {
                    this.files = uploadFiles.filter(function (f) { return f.raw; }).map(function (f) { return f.raw; });
                    this.uploadFileList = uploadFiles.filter(function (f) { return f.raw; });
                },
                onExceed: function () {
                    ElementPlus.ElMessage.warning('最多可选择 5 个附件');
                },
                submit: function () {
                    var self = this;
                    this.$refs.formRef.validate(function (valid) {
                        if (!valid) return;
                        self.loading = true;
                        var fd = new FormData();
                        fd.append('csrf', window.userCsrf || '');
                        fd.append('category', self.form.category);
                        fd.append('priority', self.form.priority);
                        fd.append('subject', self.form.subject);
                        fd.append('content', self.form.content);
                        self.files.forEach(function (f) { fd.append('attachments[]', f); });
                        fetch('api/index.php?action=ticket_create', { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                self.loading = false;
                                if (data.status === 'success') {
                                    ElementPlus.ElMessage.success(data.message || '工单已提交');
                                    self.$refs.formRef.resetFields();
                                    self.files = [];
                                    self.uploadFileList = [];
                                    if (typeof window.loadUserTickets === 'function') window.loadUserTickets(true);
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
