/**
 * 用户首页（panel tab）Element Plus 版本。
 * 数据来自 PHP 注入的 data-ep-props，模板写在 JS 里以规避 DOM 内模板的自闭合标签限制。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var TEMPLATE = ''
        + '<div class="ep-dash">'

        + '  <el-card class="ep-hero" shadow="never">'
        + '    <div class="ep-hero-body">'
        + '      <el-avatar :size="64" :src="user.avatar || undefined" class="ep-hero-avatar">'
        + '        <span class="ep-hero-initial">{{ user.initial }}</span>'
        + '      </el-avatar>'
        + '      <div class="ep-hero-text">'
        + '        <h1>你好，{{ user.username }}</h1>'
        + '        <p>欢迎回到用户中心</p>'
        + '      </div>'
        + '      <div class="ep-hero-tags">'
        + '        <el-tag :type="accountStatus.type" effect="light" round>{{ accountStatus.label }}</el-tag>'
        + '        <el-tag v-if="isStaff" type="primary" effect="plain" round>工作人员</el-tag>'
        + '      </div>'
        + '    </div>'
        + '  </el-card>'

        + '  <el-card v-if="showServerBanner" class="ep-server-banner" shadow="never">'
        + '    <div class="ep-server-banner-body">'
        + '      <div class="ep-server-banner-icon">'
        + '        <el-icon :size="22"><Monitor></Monitor></el-icon>'
        + '      </div>'
        + '      <div class="ep-server-banner-text">'
        + '        <b>服务器 IP</b>'
        + '        <small>你已通过白名单审核，在游戏「多人游戏 → 添加服务器」中输入以下地址即可进入</small>'
        + '      </div>'
        + '      <div class="ep-server-banner-ip">'
        + '        <code>{{ serverIp }}</code>'
        + '        <el-button size="small" type="success" plain round @click="copyServerIp">'
        + '          <el-icon v-if="!copied"><CopyDocument></CopyDocument></el-icon>'
        + '          <el-icon v-else><Select></Select></el-icon>'
        + '          {{ copied ? \'已复制\' : \'复制\' }}'
        + '        </el-button>'
        + '      </div>'
        + '    </div>'
        + '  </el-card>'

        + '  <el-row :gutter="16" class="ep-stat-row">'
        + '    <el-col v-for="item in stats" :key="item.key" :xs="12" :sm="12" :md="6">'
        + '      <el-card class="ep-stat-card" shadow="hover" @click="go(item.href)">'
        + '        <div class="ep-stat-inner">'
        + '          <div class="ep-stat-icon" :class="\'is-\' + item.tone">'
        + '            <el-icon :size="20"><component :is="item.icon"></component></el-icon>'
        + '          </div>'
        + '          <div class="ep-stat-meta">'
        + '            <div class="ep-stat-label">{{ item.label }}</div>'
        + '            <div class="ep-stat-value" :class="{ \'is-small\': item.small }">{{ item.value }}</div>'
        + '          </div>'
        + '        </div>'
        + '      </el-card>'
        + '    </el-col>'
        + '  </el-row>'

        + '  <el-row :gutter="16" class="ep-growth-row">'
        + '    <el-col :xs="24" :lg="12">'
        + '      <el-card class="ep-growth-card" shadow="never">'
        + '        <template #header>'
        + '          <div class="ep-card-head">'
        + '            <div><h2>资料完善度</h2><p>完善资料可帮助管理员更快认识你</p></div>'
        + '            <el-progress type="circle" :width="56" :stroke-width="6" :percentage="profileCompleteness"></el-progress>'
        + '          </div>'
        + '        </template>'
        + '        <div class="ep-check-list">'
        + '          <el-tag v-for="check in profileChecks" :key="check.label"'
        + '                  :type="check.done ? \'success\' : \'info\'"'
        + '                  :effect="check.done ? \'light\' : \'plain\'" round>'
        + '            <el-icon><component :is="check.done ? \'Select\' : \'Minus\'"></component></el-icon>'
        + '            {{ check.label }}'
        + '          </el-tag>'
        + '        </div>'
        + '        <el-button v-if="profileCompleteness < 100" type="primary" plain class="ep-block-btn"'
        + '                   @click="go(\'panel.php?tab=profile\')">继续完善资料</el-button>'
        + '      </el-card>'
        + '    </el-col>'

        + '    <el-col :xs="24" :lg="12">'
        + '      <el-card class="ep-growth-card" shadow="never">'
        + '        <template #header>'
        + '          <div class="ep-card-head">'
        + '            <div><h2>新人任务</h2><p>完成任务，快速熟悉服务器流程</p></div>'
        + '            <el-tag type="success" effect="light" round>{{ newbieDone }} / {{ newbieTasks.length }}</el-tag>'
        + '          </div>'
        + '        </template>'
        + '        <div class="ep-task-list">'
        + '          <el-link v-for="task in newbieTasks" :key="task.label"'
        + '               class="ep-task" :class="{ \'is-done\': task.done }"'
        + '               :underline="false" @click="go(task.href)">'
        + '            <el-icon class="ep-task-mark" :size="18">'
        + '              <component :is="task.done ? \'CircleCheckFilled\' : \'Clock\'"></component>'
        + '            </el-icon>'
        + '            <div class="ep-task-text">'
        + '              <b>{{ task.label }}</b>'
        + '              <small>{{ task.desc }}</small>'
        + '            </div>'
        + '            <el-icon class="ep-task-arrow"><ArrowRight></ArrowRight></el-icon>'
        + '          </el-link>'
        + '        </div>'
        + '      </el-card>'
        + '    </el-col>'
        + '  </el-row>'

        + '  <el-card class="ep-quick-card" shadow="never">'
        + '    <template #header><div class="ep-card-head"><div><h2>快捷入口</h2></div></div></template>'
        + '    <el-row :gutter="16">'
        + '      <el-col v-for="link in quickLinks" :key="link.tab" :xs="12" :sm="12" :md="6">'
        + '        <el-link class="ep-quick" :underline="false"'
        + '             @click="go(\'panel.php?tab=\' + link.tab)">'
        + '          <el-icon class="ep-quick-icon" :size="22"><component :is="link.icon"></component></el-icon>'
        + '          <b>{{ link.title }}</b>'
        + '          <small>{{ link.desc }}</small>'
        + '        </el-link>'
        + '      </el-col>'
        + '    </el-row>'
        + '  </el-card>'

        + '</div>';

    var STATUS_TYPES = {
        active: 'success',
        banned: 'danger'
    };

    var APP_STATUS = {
        pending: { label: '待审核', tone: 'warning' },
        approved: { label: '已通过', tone: 'success' },
        rejected: { label: '已拒绝', tone: 'danger' },
        need_more_info: { label: '需补充信息', tone: 'primary' }
    };

    window.EpBlocks.userDashboard = function (props) {
        var user = props.user || {};
        var appStatus = APP_STATUS[props.applicationStatus] || { label: '未提交', tone: 'warning' };

        return {
            template: TEMPLATE,
            data: function () {
                return {
                    user: user,
                    isStaff: !!props.isStaff,
                    serverIp: (props.serverIp || '').trim(),
                    copied: false,
                    profileCompleteness: Number(props.profileCompleteness) || 0,
                    profileChecks: props.profileChecks || [],
                    newbieTasks: props.newbieTasks || [],
                    stats: [
                        {
                            key: 'account',
                            label: '账号状态',
                            value: props.accountStatusLabel || '未知',
                            icon: 'User',
                            tone: STATUS_TYPES[props.accountStatus] === 'danger' ? 'danger' : 'success',
                            href: 'panel.php?tab=security'
                        },
                        {
                            key: 'application',
                            label: '入服申请',
                            value: appStatus.label,
                            icon: 'Document',
                            tone: appStatus.tone,
                            href: 'panel.php?tab=application'
                        },
                        {
                            key: 'unread',
                            label: '未读通知',
                            value: Number(props.unread) || 0,
                            icon: 'Bell',
                            tone: (Number(props.unread) || 0) > 0 ? 'warning' : 'info',
                            href: 'panel.php?tab=notifications'
                        },
                        {
                            key: 'login',
                            label: '最近登录',
                            value: props.lastLoginAt || '首次登录',
                            icon: 'Clock',
                            tone: 'info',
                            small: true,
                            href: 'panel.php?tab=security'
                        }
                    ],
                    quickLinks: [
                        { tab: 'profile', title: '编辑资料', desc: '修改你的个人信息', icon: 'EditPen' },
                        { tab: 'application', title: '入服申请', desc: '提交或查看白名单申请', icon: 'Tickets' },
                        { tab: 'notifications', title: '站内通知', desc: '查看系统消息和审核结果', icon: 'ChatDotRound' },
                        { tab: 'security', title: '安全中心', desc: '修改密码等安全设置', icon: 'Lock' }
                    ]
                };
            },
            computed: {
                accountStatus: function () {
                    return {
                        type: STATUS_TYPES[props.accountStatus] || 'info',
                        label: props.accountStatusLabel || '未知'
                    };
                },
                newbieDone: function () {
                    return this.newbieTasks.filter(function (task) { return task.done; }).length;
                },
                showServerBanner: function () {
                    // 仅白名单审核已通过且后台配置了服务器 IP 时展示
                    return props.applicationStatus === 'approved' && this.serverIp !== '';
                }
            },
            methods: {
                go: function (href) {
                    if (!href) return;
                    var match = /[?&]tab=([a-z_]+)/.exec(href);
                    if (match && window.switchUserTab) {
                        window.switchUserTab(match[1]);
                        return;
                    }
                    window.location.href = href;
                },
                copyServerIp: function () {
                    var self = this;
                    var text = this.serverIp;
                    if (!text) return;
                    var markCopied = function () {
                        self.copied = true;
                        setTimeout(function () { self.copied = false; }, 2000);
                    };
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(markCopied).catch(function () {
                            self.fallbackCopy(text);
                            markCopied();
                        });
                    } else {
                        self.fallbackCopy(text);
                        markCopied();
                    }
                },
                fallbackCopy: function (text) {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); } catch (e) { /* 忽略 */ }
                    document.body.removeChild(ta);
                }
            }
        };
    };
}());
