/**
 * 个人资料 tab — Element Plus 版。
 * 头像上传用 el-upload（auto-upload=false），表单字段用 el-form。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var TEMPLATE = ''
        + '<div class="ep-profile">'

        + '  <el-card shadow="never">'
        + '    <template #header><div class="ep-card-head"><div><h2>头像</h2></div></div></template>'
        + '    <div class="ep-avatar-row">'
        + '      <el-avatar :size="80" :src="avatarSrc || undefined" class="ep-avatar-preview">'
        + '        <span class="ep-avatar-initial">{{ initial }}</span>'
        + '      </el-avatar>'
        + '      <div class="ep-avatar-actions">'
        + '        <el-upload :auto-upload="false" accept="image/jpeg,image/png,image/gif,image/webp"'
        + '                   :show-file-list="false" :before-upload="beforeAvatarUpload" :on-change="onAvatarChange">'
        + '          <el-button>选择头像</el-button>'
        + '        </el-upload>'
        + '        <el-text type="info" size="small">支持 JPG/PNG/GIF/WebP，最大 2MB</el-text>'
        + '      </div>'
        + '    </div>'
        + '  </el-card>'

        + '  <el-card shadow="never">'
        + '    <template #header><div class="ep-card-head"><div><h2>基本信息</h2></div></div></template>'
        + '    <el-form :model="form" :rules="rules" ref="formRef" label-position="top">'
        + '      <el-form-item label="用户名">'
        + '        <el-input :model-value="username" disabled></el-input>'
        + '        <el-text type="info" size="small">用户名注册后不可修改</el-text>'
        + '      </el-form-item>'
        + '      <el-form-item label="邮箱" prop="email">'
        + '        <el-input v-model="form.email" type="email"></el-input>'
        + '      </el-form-item>'
        + '      <el-form-item label="Minecraft 游戏 ID" prop="mc_name">'
        + '        <el-input v-model="form.mc_name" maxlength="32" show-word-limit></el-input>'
        + '      </el-form-item>'
        + '    </el-form>'
        + '  </el-card>'

        + '  <el-card shadow="never">'
        + '    <template #header><div class="ep-card-head"><div><h2>联系方式</h2></div></div></template>'
        + '    <el-form :model="form" label-position="top">'
        + '      <el-row :gutter="16">'
        + '        <el-col :xs="24" :sm="12">'
        + '          <el-form-item label="QQ">'
        + '            <el-input v-model="form.contact_qq" maxlength="32" placeholder="选填"></el-input>'
        + '          </el-form-item>'
        + '        </el-col>'
        + '        <el-col :xs="24" :sm="12">'
        + '          <el-form-item label="Discord">'
        + '            <el-input v-model="form.contact_discord" maxlength="64" placeholder="选填"></el-input>'
        + '          </el-form-item>'
        + '        </el-col>'
        + '      </el-row>'
        + '    </el-form>'
        + '  </el-card>'

        + '  <el-card shadow="never">'
        + '    <template #header><div class="ep-card-head"><div><h2>个人介绍</h2></div></div></template>'
        + '    <el-form :model="form" label-position="top">'
        + '      <el-form-item>'
        + '        <el-input v-model="form.bio" type="textarea" :rows="4" maxlength="500" show-word-limit'
        + '                  placeholder="介绍一下你自己..."></el-input>'
        + '      </el-form-item>'
        + '    </el-form>'
        + '  </el-card>'

        + '  <div class="ep-form-actions">'
        + '    <el-button type="primary" :loading="loading" @click="save" style="width:100%;max-width:320px">保存修改</el-button>'
        + '  </div>'

        + '</div>';

    window.EpBlocks.userProfile = function (props) {
        return {
            template: TEMPLATE,
            data: function () {
                return {
                    username:  props.username || '',
                    initial:   props.initial  || '',
                    avatarSrc: props.avatar   || '',
                    avatarObjectUrl: '',
                    avatarFile: null,
                    loading: false,
                    form: {
                        email:            props.email           || '',
                        mc_name:          props.mc_name         || '',
                        contact_qq:       props.contact_qq      || '',
                        contact_discord:  props.contact_discord || '',
                        bio:              props.bio             || ''
                    },
                    rules: {
                        email:   [{ required: true, type: 'email', message: '请输入有效邮箱', trigger: 'blur' }],
                        mc_name: [{ required: true, message: '请填写游戏 ID', trigger: 'blur' }]
                    }
                };
            },
            beforeUnmount: function () {
                this.releaseAvatarObjectUrl();
            },
            methods: {
                releaseAvatarObjectUrl: function () {
                    if (!this.avatarObjectUrl) return;
                    URL.revokeObjectURL(this.avatarObjectUrl);
                    this.avatarObjectUrl = '';
                },
                beforeAvatarUpload: function (file) {
                    // el-upload before-upload 返回 false 阻止自动上传，校验仍在 on-change 里做
                    return false;
                },
                onAvatarChange: function (uploadFile) {
                    var file = uploadFile.raw;
                    if (!file) return;
                    if (file.size > 2 * 1024 * 1024) {
                        ElementPlus.ElMessage.error('头像不能超过 2MB');
                        return;
                    }
                    this.releaseAvatarObjectUrl();
                    this.avatarFile = file;
                    this.avatarObjectUrl = URL.createObjectURL(file);
                    this.avatarSrc = this.avatarObjectUrl;
                },
                save: function () {
                    var self = this;
                    this.$refs.formRef.validate(function (valid) {
                        if (!valid) return;
                        self.loading = true;
                        var fd = new FormData();
                        fd.append('csrf', window.userCsrf || '');
                        fd.append('email',           self.form.email);
                        fd.append('mc_name',         self.form.mc_name);
                        fd.append('contact_qq',      self.form.contact_qq);
                        fd.append('contact_discord', self.form.contact_discord);
                        fd.append('bio',             self.form.bio);
                        if (self.avatarFile) fd.append('avatar', self.avatarFile);
                        fetch('api/index.php?action=profile', { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                self.loading = false;
                                if (data.status === 'success') {
                                    ElementPlus.ElMessage.success(data.message || '资料已更新');
                                    self.avatarFile = null;
                                    if (data.avatar_url) {
                                        self.releaseAvatarObjectUrl();
                                        self.avatarSrc = data.avatar_url;
                                    }
                                } else {
                                    ElementPlus.ElMessage.error(data.message || '保存失败');
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
