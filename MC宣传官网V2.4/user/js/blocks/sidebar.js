/**
 * 侧边栏 — Element Plus el-menu 版本。
 * 挂载点：aside#ep-sidebar，由 _sidebar.php 写入 data-ep-app="userSidebar"。
 */
(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var TEMPLATE = ''
        + '<el-menu class="ep-sidebar-menu"'
        + '         :default-active="activeKey"'
        + '         :collapse="false"'
        + '         background-color="transparent"'
        + '         active-text-color="var(--green-text)">'

        + '  <div class="ep-sidebar-logo">'
        + '    <a href="../index.html">'
        + '      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4H20V20H4V4Z"/><path d="M4 12H20"/><path d="M12 4V20"/></svg>'
        + '      <span>用户中心</span>'
        + '    </a>'
        + '  </div>'

        + '  <el-menu-item v-for="item in items" :key="item.key" :index="item.key"'
        + '                @click="nav(item.key)">'
        + '    <el-icon><component :is="item.icon"></component></el-icon>'
        + '    <template #title>'
        + '      <span>{{ item.label }}</span>'
        + '      <el-badge v-if="item.badge" :value="item.badge" class="ep-nav-badge"></el-badge>'
        + '    </template>'
        + '  </el-menu-item>'

        + '  <template v-if="isStaff">'
        + '    <div class="ep-nav-divider"></div>'
        + '    <div class="ep-nav-group-title">工作台</div>'
        + '    <el-menu-item v-for="item in staffItems" :key="item.key" :index="item.key"'
        + '                  @click="nav(item.key)">'
        + '      <el-icon><component :is="item.icon"></component></el-icon>'
        + '      <template #title><span>{{ item.label }}</span></template>'
        + '    </el-menu-item>'
        + '  </template>'

        + '  <div class="ep-nav-divider"></div>'
        + '  <el-menu-item index="_home" @click="home">'
        + '    <el-icon><TopRight></TopRight></el-icon>'
        + '    <template #title><span>返回首页</span></template>'
        + '  </el-menu-item>'
        + '  <el-menu-item index="_logout" @click="logout">'
        + '    <el-icon><SwitchButton></SwitchButton></el-icon>'
        + '    <template #title><span>退出登录</span></template>'
        + '  </el-menu-item>'

        + '  <el-tag v-if="isBanned" type="danger" effect="light" class="ep-banned-tag">'
        + '    <el-icon><CircleClose></CircleClose></el-icon> 账号已封禁'
        + '  </el-tag>'

        + '</el-menu>';

    window.EpBlocks.userSidebar = function (props) {
        return {
            template: TEMPLATE,
            data: function () {
                return {
                    activeKey: props.currentPage || 'panel',
                    isStaff:   !!props.isStaff,
                    isBanned:  !!props.isBanned,
                    unread:    Number(props.unread) || 0,
                    ticketReplied: Number(props.ticketReplied) || 0
                };
            },
            computed: {
                items: function () {
                    var self = this;
                    var base = [
                        { key: 'panel',         label: '用户首页',   icon: 'Grid' },
                        { key: 'announcements', label: '服务器公告', icon: 'Promotion' },
                        { key: 'tickets',       label: '工单反馈',   icon: 'ChatLineRound', badge: self.ticketReplied || undefined },
                        { key: 'shop',          label: '商城',       icon: 'ShoppingCart' },
                        { key: 'orders',        label: '我的订单',   icon: 'Tickets' },
                        { key: 'profile',       label: '个人资料',   icon: 'User' },
                        { key: 'application',   label: '入服申请',   icon: 'Document' },
                        { key: 'notifications', label: '站内通知',   icon: 'Bell', badge: self.unread || undefined },
                        { key: 'security',      label: '安全中心',   icon: 'Lock' },
                        { key: 'account_delete', label: '注销账号',  icon: 'Delete' }
                    ];
                    if (props.aiChatEnabled) {
                        base.splice(3, 0, { key: 'ai_assistant', label: '智能客服', icon: 'Service' });
                    }
                    return base;
                },
                staffItems: function () {
                    if (!this.isStaff) return [];
                    return [
                        { key: 'staff_panel',         label: '工作台概览', icon: 'DataBoard' },
                        { key: 'staff_messages',      label: '留言管理',   icon: 'ChatSquare' },
                        { key: 'staff_announcements', label: '公告管理',   icon: 'Promotion' },
                        { key: 'staff_tickets',       label: '工单管理',   icon: 'MessageBox' },
                        { key: 'staff_applications',  label: '入服审核',   icon: 'CircleCheck' }
                    ];
                }
            },
            methods: {
                nav: function (key) {
                    this.activeKey = key;
                    if (window.switchUserTab) {
                        window.switchUserTab(key);
                    } else {
                        window.location.href = 'panel.php?tab=' + key;
                    }
                    if (window.closeSidebar) window.closeSidebar();
                },
                home:   function () { window.location.href = '../index.html'; },
                logout: function () { window.location.href = 'login.php?action=logout'; }
            }
        };
    };
}());
