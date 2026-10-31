/**
 * 十字架生存服 前台主脚本
 * ============================================================
 * 模块结构（按初始化顺序）：
 *   1. State          —— 跨模块共享状态
 *   2. DOM            —— 安全 DOM / URL 辅助（XSS 防护）
 *   3. UserSession    —— 顶部导航登录态
 *   4. Announce       —— 首页公告弹窗
 *   5. LazyReveal     —— 图片懒加载 + 滚动渐显
 *   6. Gallery        —— 相册轮播
 *   7. Nav            —— 移动端导航汉堡菜单
 *   8. CMS            —— 拉取并应用后台 CMS 内容
 *   9. ServerStatus   —— 在线人数
 *  10. TeamCarousel   —— 团队卡片无缝滚动
 *  11. ContactForm    —— 联系表单 + 图片上传
 *
 * 入口：DOMContentLoaded → 各模块 init()
 * ============================================================
 */
(function () {
    'use strict';

    // ============================================================
    // 1. State —— 跨模块共享状态
    // ============================================================
    const state = {
        serverIP: 'mc.cross17.icu',
        siteMode: 'international',
        neteaseTierCap: 4,
        // 主 IntersectionObserver，懒加载与团队克隆共用
        io: null,
    };

    // ============================================================
    // 2. DOM —— 安全 DOM / URL 辅助
    // ============================================================
    const $ = (sel) => document.querySelector(sel);

    function escapeHtml(text) {
        // 注意：用 \x26 代替字面量 '&'，避免被某些主机的输出过滤器把
        // '&#39;' 等 HTML 实体反向解码导致脚本语法错误。
        return String(text == null ? '' : text)
            .replace(/&/g,  '\x26amp;')
            .replace(/</g,  '\x26lt;')
            .replace(/>/g,  '\x26gt;')
            .replace(/"/g,  '\x26quot;')
            .replace(/'/g,  '\x26#39;');
    }

    function normalizeMediaUrl(url) {
        if (typeof url !== 'string') return '';
        let value = url.trim().replace(/\\/g, '/');
        const cssUrlMatch = value.match(/^url\((['"]?)(.*?)\1\)$/i);
        if (cssUrlMatch) value = cssUrlMatch[2].trim();
        if (!value || /^(javascript|data):/i.test(value)) return '';
        value = value.replace(/^\.\.\/\.?\//, './');
        if (/^\.\.\/(uploads|png|egg|assets|user\/uploads)\//i.test(value)) value = './' + value.replace(/^\.\.\//, '');
        if (/^admin\/(uploads|assets)\//i.test(value)) value = './' + value;
        if (/^https?:\/\//i.test(value) || value.startsWith('#')) return value;
        if (value.startsWith('./') || value.startsWith('/') || value.startsWith('../')) return value.replace(/ /g, '%20');
        if (/^[A-Za-z0-9_\-./% ]+$/.test(value) && (value.indexOf('/') !== -1 || /\.(png|jpe?g|gif|webp|svg|bmp|ico)$/i.test(value))) return './' + value.replace(/^\/+/, '').replace(/ /g, '%20');
        return '';
    }

    function safeText(el, text) {
        if (el && text != null) el.textContent = text;
    }

    function safeImgSrc(el, url) {
        if (!el || !url) return;
        const safeUrl = normalizeMediaUrl(url);
        if (!safeUrl) return;

        if (!el.dataset.defaultSrc) {
            const originalSrc = el.getAttribute('data-src') || el.getAttribute('src') || '';
            if (originalSrc && !originalSrc.startsWith('data:')) el.dataset.defaultSrc = originalSrc;
        }
        if (!el.dataset.fallbackBound) {
            el.dataset.fallbackBound = '1';
            el.addEventListener('error', function () {
                const fallback = normalizeMediaUrl(el.dataset.defaultSrc || '');
                if (fallback && el.getAttribute('src') !== fallback) {
                    el.setAttribute('data-src', fallback);
                    el.setAttribute('src', fallback);
                }
            });
        }
        el.setAttribute('data-src', safeUrl);
        el.setAttribute('src', safeUrl);
    }

    function createSafeImg(url, alt, className) {
        const img = document.createElement('img');
        if (className) img.className = className;
        img.alt = alt || '';
        safeImgSrc(img, url);
        return img;
    }

    function safeBg(el, url) {
        if (!el || !url) return;
        const safeUrl = normalizeMediaUrl(url);
        if (!safeUrl) return;
        el.style.backgroundImage = "url('" + safeUrl.replace(/'/g, "\\'") + "')";
        if (el.hasAttribute('data-bg')) el.removeAttribute('data-bg');
    }

    function safeLink(el, url) {
        if (!el || !url) return;
        if (typeof url === 'string') {
            if (url.startsWith('https://') || url.startsWith('http://') || url.startsWith('#') || url.startsWith('/')) {
                el.href = url;
            } else if (/^[a-zA-Z0-9]/.test(url) && url.includes('.')) {
                el.href = 'https://' + url;
            }
        }
    }

    // 复制服务器 IP（被 toggle 触发，也供 CMS 重新生成的复制按钮使用）
    function copyServerIP() {
        navigator.clipboard.writeText(state.serverIP).then(() => {
            setTimeout(() => {
                const toggle = document.getElementById('toggle');
                if (toggle) toggle.checked = false;
            }, 2000);
        }).catch(() => {});
    }

    // ============================================================
    // 3. UserSession —— 顶部导航登录态
    // ============================================================
    const userSession = {
        init() {
            fetch('user/api/index.php?action=session')
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    const entry = document.getElementById('navUserEntry');
                    if (!entry || !data) return;
                    if (data.installed === false) {
                        entry.hidden = true;
                        return;
                    }
                    entry.hidden = false;
                    if (data.logged_in) {
                        let html = '<a href="user/panel.php" class="nav-register-btn">' + (data.username || '用户中心') + '</a>';
                        if (data.unread_notifications > 0) {
                            html += ' <a href="user/panel.php?tab=notifications" class="nav-notif-badge" title="未读通知">' + data.unread_notifications + '</a>';
                        }
                        html += ' <a href="user/login.php?action=logout" class="nav-logout-link">退出</a>';
                        entry.innerHTML = html;
                    }
                })
                .catch(() => {});
        }
    };

    // ============================================================
    // 4. Announce —— 首页公告弹窗
    // ============================================================
    const announce = {
        STORAGE_KEY: 'cross17_announcement_popup_hidden',
        LEVEL_LABELS: { info: '服务器公告', success: '活动通知', warning: '维护通知', danger: '紧急通知' },
        els: null,
        activeId: '',

        init() {
            const els = {
                wrap:    document.getElementById('homeAnnouncements'),
                list:    document.getElementById('homeAnnouncementsList'),
                popup:   document.getElementById('announcementPopup'),
                close:   document.getElementById('announcementPopupClose'),
                badge:   document.getElementById('announcementPopupBadge'),
                title:   document.getElementById('announcementPopupTitle'),
                time:    document.getElementById('announcementPopupTime'),
                content: document.getElementById('announcementPopupContent'),
            };
            for (const k in els) if (!els[k]) return;
            this.els = els;

            els.close.addEventListener('click', () => this._close());
            els.popup.addEventListener('click', (e) => {
                if (e.target && e.target.getAttribute('data-close-popup') === '1') this._close();
            });

            this._fetch();
        },

        _fetch() {
            fetch('admin/public_api.php?act=announcements')
                .then(r => r.ok ? r.json() : null)
                .then(res => {
                    if (!res || !res.success || !res.data) return;
                    const homeList  = Array.isArray(res.data.home)  ? res.data.home  : [];
                    const popupList = Array.isArray(res.data.popup) ? res.data.popup : [];
                    this.els.wrap.hidden = true;
                    this.els.list.innerHTML = '';
                    const popupItem = popupList.length ? popupList[0] : (homeList.length ? homeList[0] : null);
                    if (popupItem) {
                        const popupId = String(popupItem.id || '');
                        if (popupId && localStorage.getItem(this.STORAGE_KEY) !== popupId) {
                            this._open(popupItem);
                        }
                    }
                })
                .catch(() => {});
        },

        _open(item) {
            if (!item) return;
            const els = this.els;
            this.activeId = String(item.id || '');
            els.badge.textContent   = this.LEVEL_LABELS[item.level] || '服务器公告';
            els.title.textContent   = item.title || '服务器公告';
            els.time.textContent    = item.start_at || item.publish_at || item.created_at || '';
            els.content.innerHTML   = escapeHtml(item.content || '').replace(/\n/g, '<br>');
            els.popup.hidden = false;
            document.body.classList.add('has-announcement-popup');
        },

        _close() {
            this.els.popup.hidden = true;
            document.body.classList.remove('has-announcement-popup');
            if (this.activeId) localStorage.setItem(this.STORAGE_KEY, this.activeId);
        }
    };

    // ============================================================
    // 5. LazyReveal —— 图片懒加载 + 滚动渐显
    // ============================================================
    const lazyReveal = {
        SELECTOR: '[data-src], [data-bg], .scroll-fade-up, .section-header, .spec-card',
        REVEAL_CLASSES: ['scroll-fade-up', 'section-header', 'spec-card'],

        init() {
            if (!('IntersectionObserver' in window)) {
                this._fallback();
                return;
            }
            state.io = new IntersectionObserver((entries, obs) => this._onIntersect(entries, obs), {
                rootMargin: '200px 0px',
                threshold: 0.01,
            });
            document.querySelectorAll(this.SELECTOR).forEach(el => state.io.observe(el));

            // 兜底：hero 区如果未触发观察则强制渐显
            setTimeout(() => {
                document.querySelectorAll('.hero .scroll-fade-up:not(.revealed)').forEach(el => {
                    el.classList.add('revealed');
                });
            }, 300);
        },

        _onIntersect(entries, obs) {
            for (let i = 0; i < entries.length; i++) {
                const entry = entries[i];
                if (!entry.isIntersecting) continue;
                const el = entry.target;

                if (el.tagName === 'IMG' && el.dataset.src) {
                    el.src = el.dataset.src;
                    el.removeAttribute('data-src');
                }
                if (el.dataset.bg) {
                    safeBg(el, el.dataset.bg);
                }
                if (this.REVEAL_CLASSES.some(c => el.classList.contains(c))) {
                    el.classList.add('revealed');
                }
                obs.unobserve(el);
            }
        },

        _fallback() {
            document.querySelectorAll('.scroll-fade-up, .section-header, .spec-card').forEach(el => {
                el.classList.add('revealed');
            });
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
            document.querySelectorAll('[data-bg]').forEach(el => {
                safeBg(el, el.dataset.bg);
            });
        }
    };

    // ============================================================
    // 6. Gallery —— 相册轮播
    // ============================================================
    const gallery = {
        AUTO_INTERVAL: 5000,
        FADE_MS: 300,
        images: [
            { src: './png/f5ea0ca06bf5ac36704b7277536ab53d.jpg', desc: '宏伟的主城大厅' },
            { src: './png/5e1e1be033cbd911e62327519886379f.jpg', desc: '精美的玩家建筑' },
            { src: './png/9cca3afcca8c0a79eac6a39aad5d65ec.jpg', desc: '广阔的生存世界' },
            { src: './egg/img1_bcd004c0.jpg',                    desc: '热闹的活动现场' },
            { src: './egg/img2_ab032cdc.jpg',                    desc: '激情的PVP对战' },
        ],
        currentIndex: 0,
        isTransitioning: false,
        autoPlayTimer: null,
        preloadEnabled: false,
        preloadedSources: new Set(),
        els: {},

        init() {
            this.els.image = document.getElementById('galleryImage');
            this.els.desc  = document.getElementById('galleryDescription');
            this.els.prev  = document.getElementById('prevBtn');
            this.els.next  = document.getElementById('nextBtn');

            this._lazyPreload();

            if (!this._ready()) return;
            this.els.next.addEventListener('click', () => this.next());
            this.els.prev.addEventListener('click', () => this.prev());

            this._startAutoPlay();

            const container = document.querySelector('.gallery-carousel-container');
            if (container) {
                container.addEventListener('mouseenter', () => this._stopAutoPlay(), { passive: true });
                container.addEventListener('mouseleave', () => this._startAutoPlay(), { passive: true });
            }

            // 隐藏标签页时暂停自动播放，节省 CPU
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this._stopAutoPlay();
                else this._startAutoPlay();
            });
        },

        // 由 CMS 模块调用：用后台数据替换轮播图
        replaceFromCms(items) {
            const next = [];
            (items || []).forEach(g => {
                const src = normalizeMediaUrl(g.src);
                if (src) next.push({ src: src, desc: g.caption });
            });
            if (next.length) {
                this.images = next;
                this.currentIndex = 0;
            }
            if (this.images.length && this.els.image && this.els.desc) {
                safeImgSrc(this.els.image, this.images[0].src);
                this.els.desc.textContent = this.images[0].desc || '';
            }
        },

        next() {
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
            this._update(this.currentIndex);
        },

        prev() {
            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
            this._update(this.currentIndex);
        },

        _ready() {
            return this.els.image && this.els.desc && this.els.prev && this.els.next;
        },

        _update(index) {
            if (this.isTransitioning) return;
            this.isTransitioning = true;
            this.els.image.classList.add('fade-out');
            setTimeout(() => {
                this.els.image.src = this.images[index].src;
                this.els.desc.textContent = this.images[index].desc;
                this.els.image.classList.remove('fade-out');
                this.isTransitioning = false;
                this._preloadNext();
            }, this.FADE_MS);
        },

        _startAutoPlay() {
            this._stopAutoPlay();
            this.autoPlayTimer = setInterval(() => this.next(), this.AUTO_INTERVAL);
        },

        _stopAutoPlay() {
            if (this.autoPlayTimer) {
                clearInterval(this.autoPlayTimer);
                this.autoPlayTimer = null;
            }
        },

        _lazyPreload() {
            const sec = document.getElementById('gallery');
            const doPreload = () => {
                if (this.preloadEnabled) return;
                this.preloadEnabled = true;
                this._preloadNext();
            };
            if (sec && 'IntersectionObserver' in window) {
                const io = new IntersectionObserver((entries, obs) => {
                    if (entries[0].isIntersecting) { doPreload(); obs.unobserve(sec); }
                }, { rootMargin: '400px 0px' });
                io.observe(sec);
            } else if (sec) {
                doPreload();
            }
        },

        _preloadNext() {
            if (!this.preloadEnabled || this.images.length < 2) return;
            const next = this.images[(this.currentIndex + 1) % this.images.length];
            if (!next || this.preloadedSources.has(next.src)) return;
            this.preloadedSources.add(next.src);
            const img = new Image();
            img.src = next.src;
        }
    };

    // ============================================================
    // 7. Nav —— 移动端导航汉堡菜单
    // ============================================================
    const nav = {
        hamburger: null,
        links: null,
        backdrop: null,

        init() {
            this.hamburger = document.querySelector('.hamburger');
            this.links     = document.querySelector('.nav-links');
            if (!this.hamburger || !this.links) return;

            // 注入背景蒙版（仅移动端 CSS 可见），点击即关闭
            this.backdrop = document.createElement('div');
            this.backdrop.className = 'nav-backdrop';
            this.backdrop.setAttribute('aria-hidden', 'true');
            document.body.appendChild(this.backdrop);

            this.hamburger.addEventListener('click', () => this._toggle());
            this.backdrop.addEventListener('click', () => this._setOpen(false));

            // 点击菜单链接后自动关闭
            this.links.addEventListener('click', (e) => {
                if (e.target.tagName === 'A') this._setOpen(false);
            });

            // ESC 关闭 + 视口放大到桌面尺寸时强制恢复
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this._setOpen(false);
            });
            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) this._setOpen(false);
            });
        },

        _toggle() {
            this._setOpen(!this.links.classList.contains('active'));
        },

        _setOpen(open) {
            this.hamburger.classList.toggle('active', open);
            this.links.classList.toggle('active', open);
            this.backdrop.classList.toggle('active', open);
            document.body.classList.toggle('nav-open', open);
        }
    };

    // ============================================================
    // 8. CMS —— 拉取并应用后台内容
    // ============================================================
    const cms = {
        init() {
            fetch('admin/public_api.php?act=content')
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) return;
                    if (data.site)      this.applySite(data.site);
                    if (data.hero)      this.applyHero(data.hero);
                    if (data.specs)     this.applySpecs(data.specs);
                    if (data.help)      this.applyHelp(data.help);
                    if (data.features)  this.applyFeatures(data.features);
                    if (data.gallery)   this.applyGallery(data.gallery);
                    if (data.team)      this.applyTeam(data.team);
                    if (data.contact)   this.applyContact(data.contact);
                    if (data.community) this.applyCommunity(data.community);
                    if (data.footer)    this.applyFooter(data.footer);
                    serverStatus.fetch();
                })
                .catch(() => {});
        },

        applySite(data) {
            const siteLogo   = document.getElementById('siteLogo');
            const footerLogo = document.getElementById('footerLogo');

            // 自定义 ICO 图标：替换浏览器标签页 / 收藏夹中的站点图标（与 LOGO 独立）
            if (data.favicon_ico) {
                let iconLink = document.querySelector('link[rel="icon"]');
                if (!iconLink) {
                    iconLink = document.createElement('link');
                    iconLink.rel = 'icon';
                    document.head.appendChild(iconLink);
                }
                iconLink.type = 'image/x-icon';
                iconLink.removeAttribute('sizes');
                iconLink.href = data.favicon_ico;
            }

            if (data.logo_image) {
                if (siteLogo) {
                    siteLogo.textContent = '';
                    siteLogo.appendChild(createSafeImg(data.logo_image, 'Logo', 'logo-img'));
                }
                if (footerLogo) {
                    footerLogo.textContent = '';
                    footerLogo.appendChild(createSafeImg(data.logo_image, 'Logo', 'footer-logo-img'));
                }
            } else if (data.logo_text) {
                const logoText = siteLogo && siteLogo.querySelector('.logo-text');
                if (logoText) logoText.textContent = data.logo_text;
                const footerText = footerLogo && footerLogo.querySelector('.footer-logo-text');
                if (footerText) footerText.textContent = data.logo_text;
            }

            // 服务器地址：仅在后台【没填】时才用默认值，且一旦页面写好就不再被覆盖。
            // （后台若填了旧地址，请到 后台 → 站点设置 → 服务器地址 改成 mc.cross17.icu）
            (function lockServerIP() {
                var locked = 'mc.cross17.icu';
                var ipOnly = function (v) {
                    if (typeof v !== 'string') return '';
                    var t = v.trim().toLowerCase();
                    if (!t) return '';
                    // 只认合法的域名/IP，挡掉 "加载中..." 之类占位文案
                    if (!/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?(:\d+)?$/i.test(t.replace(/^https?:\/\//, ''))) return '';
                    return v.trim();
                };
                var apply = function (forced) {
                    var v = ipOnly(forced) || ipOnly(data.server_ip) || locked;
                    state.serverIP = v;
                    safeText(document.getElementById('server-ip'), v);
                    safeText(document.getElementById('help-ip'),   v);
                    return v;
                };
                var v = apply();
                // 后台延迟回传或重新渲染时，锁定为已确认的值，不再覆盖
                setTimeout(function () { apply(v); }, 0);
                window.addEventListener('load', function () { apply(v); });
            })();

            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.onclick = function () {
                    navigator.clipboard.writeText(state.serverIP).then(() => {
                        const orig = this.innerHTML;
                        this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                        setTimeout(() => { this.innerHTML = orig; }, 2000);
                    });
                };
            });

            if (data.server_mode === 'netease') {
                state.siteMode = 'netease';
                const tierCaps = { shangyao: 4, shanfeng: 12, yunding: 40 };
                state.neteaseTierCap = tierCaps[data.netease_tier] || 4;
                const copyLabel = document.querySelector('.boton-minecraft .texto-boton span:first-child');
                if (copyLabel) copyLabel.textContent = '复制山头链接';
            }
        },

        applyHero(data) {
            safeBg($('#home'), data.bg_image);
            const badge = $('.hero-badge');
            if (badge && data.badge) badge.lastChild.textContent = ' ' + data.badge;

            const h1 = $('.hero h1');
            if (h1 && data.title_line1 && data.title_highlight) {
                h1.textContent = '';
                h1.appendChild(document.createTextNode(data.title_line1));
                h1.appendChild(document.createElement('br'));
                const span = document.createElement('span');
                span.className = 'highlight';
                span.textContent = data.title_highlight;
                h1.appendChild(span);
            }
            safeText($('.hero-subtitle'), data.subtitle);

            // player_count 由服务器状态 API 提供，跳过 CMS 覆盖

            if (data.features && data.features.length) {
                const container = $('.hero-features');
                if (container) {
                    const frag = document.createDocumentFragment();
                    data.features.forEach(f => {
                        const div = document.createElement('div');
                        div.className = 'h-feature';
                        div.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                        div.appendChild(document.createTextNode(f));
                        frag.appendChild(div);
                    });
                    container.textContent = '';
                    container.appendChild(frag);
                }
            }
        },

        applySpecs(data) {
            safeBg($('#specs'), data.bg_image);
            safeText($('#specs .section-title'),    data.title);
            safeText($('#specs .section-subtitle'), data.subtitle);
            const cards = document.querySelectorAll('.spec-card');
            (data.items || []).forEach((item, i) => {
                const c = cards[i];
                if (!c) return;
                safeText(c.querySelector('.spec-title'), item.title);
                safeText(c.querySelector('.spec-desc'),  item.desc);
                safeText(c.querySelector('.spec-value'), item.value);
                safeImgSrc(c.querySelector('.spec-icon'), item.icon);
            });
        },

        applyHelp(data) {
            safeBg($('#help-docs'), data.bg_image);
            safeText($('#help-docs .section-title'),    data.title);
            safeText($('#help-docs .section-subtitle'), data.subtitle);
            const cards = document.querySelectorAll('.step-card');
            (data.steps || []).forEach((step, i) => {
                if (!cards[i]) return;
                safeText(cards[i].querySelector('.step-title'), step.title);
                safeText(cards[i].querySelector('.step-desc'),  step.desc);
            });
        },

        applyFeatures(data) {
            safeBg($('#features'), data.bg_image);
            safeText($('#features .section-title'),    data.title);
            safeText($('#features .section-subtitle'), data.subtitle);
            const cards = document.querySelectorAll('.feature-card');
            (data.items || []).forEach((item, i) => {
                if (!cards[i]) return;
                safeText(cards[i].querySelector('h3'), item.title);
                safeText(cards[i].querySelector('p'),  item.desc);
                safeImgSrc(cards[i].querySelector('.feature-icon'), item.icon);
            });
        },

        applyGallery(data) {
            safeBg($('#gallery'), data.bg_image);
            safeText($('#gallery .section-title'),    data.title);
            safeText($('#gallery .section-subtitle'), data.subtitle);
            if (data.items && data.items.length) {
                gallery.replaceFromCms(data.items);
            }
        },

        applyTeam(data) {
            safeBg($('#team'), data.bg_image);
            safeText($('#team .section-title'),    data.title);
            safeText($('#team .section-subtitle'), data.subtitle);
            const members = data.members || [];
            if (!members.length) return;

            const wrapper = document.getElementById('teamWrapper');
            if (!wrapper) return;

            // 清除所有旧卡片（原始 + 克隆）
            wrapper.querySelectorAll('.team-card, .team-card-clone').forEach(c => c.remove());

            // 根据 CMS 数据动态重建卡片
            const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            members.forEach(function(m) {
                const card = document.createElement('div');
                card.className = 'team-card';

                const avatarDiv = document.createElement('div');
                avatarDiv.className = 'team-avatar';
                const img = document.createElement('img');
                img.src = PLACEHOLDER;
                img.alt = escapeHtml(m.name || '') + '头像';
                img.loading = 'lazy';
                if (m.avatar) {
                    img.setAttribute('data-src', m.avatar);
                    // 注册到懒加载 observer
                    if (state.io) state.io.observe(img);
                }
                avatarDiv.appendChild(img);
                card.appendChild(avatarDiv);

                const nameEl = document.createElement('h3');
                nameEl.className = 'team-name';
                nameEl.textContent = m.name || '';
                card.appendChild(nameEl);

                const roleEl = document.createElement('p');
                roleEl.className = 'team-role';
                roleEl.textContent = m.role || '';
                card.appendChild(roleEl);

                const descEl = document.createElement('p');
                descEl.className = 'team-desc';
                descEl.textContent = m.desc || '';
                card.appendChild(descEl);

                const btn = document.createElement('a');
                btn.className = 'team-contact-btn';
                btn.textContent = '联系我';
                if (m.contact_link) safeLink(btn, m.contact_link);
                else btn.setAttribute('href', '#');
                card.appendChild(btn);

                wrapper.appendChild(card);
            });

            // 重建克隆，保持无缝滚动
            wrapper.querySelectorAll('.team-card').forEach(function(card) {
                const clone = card.cloneNode(true);
                clone.classList.add('team-card-clone');
                wrapper.appendChild(clone);
            });

            // 克隆中的懒加载图片也注册到 observer
            if (state.io) {
                wrapper.querySelectorAll('.team-card-clone img[data-src]').forEach(function(img) {
                    state.io.observe(img);
                });
            }
        },

        applyContact(data) {
            safeBg($('#contact'), data.bg_image);
        },

        applyCommunity(data) {
            const community = $('#community');
            safeBg(community, data.bg_image);
            if (community && !data.bg_image && !community.style.backgroundImage) {
                safeBg(community, community.getAttribute('data-bg') || 'png/wj_Narcissa_3.png');
            }
            safeText($('#community .section-title'),    data.title);
            safeText($('#community .section-subtitle'), data.subtitle);
            const cards = document.querySelectorAll('.community-card');
            [0, 1].forEach(i => {
                if (!cards[i]) return;
                const prefix = i === 0 ? 'qq' : 'wechat';
                safeText(cards[i].querySelector('h3'), data[prefix + '_text'] || '');
                safeText(cards[i].querySelector('p'),  data[prefix + '_desc'] || '');
                const qr = cards[i].querySelector('.qr-code');
                if (qr && data[prefix + '_qr']) {
                    qr.textContent = '';
                    const img = createSafeImg(data[prefix + '_qr'], '二维码');
                    img.style.cssText = 'width:100%;height:100%;object-fit:contain;';
                    qr.appendChild(img);
                    qr.style.opacity = '1';
                    qr.style.background = 'none';
                }
                safeLink(cards[i].querySelector('a'), data[prefix + '_link']);
            });
        },

        applyFooter(data) {
            safeText($('.footer-desc'), data.desc);
            const copy = document.querySelector('.footer-bottom .container p:first-child');
            if (copy && data.copyright) copy.textContent = data.copyright;

            if (data.friend_links && data.friend_links.length) {
                const list = document.getElementById('footerFriendLinks');
                if (list) {
                    list.textContent = '';
                    data.friend_links.forEach(link => {
                        const li = document.createElement('li');
                        const a  = document.createElement('a');
                        a.textContent = link.name;
                        safeLink(a, link.url);
                        if (!a.href) a.href = '#';
                        li.appendChild(a);
                        list.appendChild(li);
                    });
                }
            }
        }
    };

    // ============================================================
    // 9. ServerStatus —— 在线人数
    // ============================================================
    const serverStatus = {
        fetch() {
            const dot       = $('.status-dot');
            const container = $('.status-text');
            const text      = $('.highlight-green');

            // 网易版：固定显示当前山头容量
            if (state.siteMode === 'netease') {
                if (container) {
                    container.textContent = '';
                    container.append('最多可支持 ');
                    const span = document.createElement('span');
                    span.className = 'highlight-green';
                    span.textContent = state.neteaseTierCap;
                    container.appendChild(span);
                    container.append(' 名玩家');
                }
                return;
            }

            if (text) text.textContent = '加载中...';
            fetch('admin/public_api.php?act=server_status')
                .then(r => r.ok ? r.json() : null)
                .then(res => {
                    if (res && res.success && res.data) {
                        if (text) text.textContent = res.data.p;
                    } else {
                        if (text) text.textContent = '离线';
                        if (dot)  dot.style.backgroundColor = '#ef4444';
                    }
                })
                .catch(() => {
                    if (text) text.textContent = '离线';
                    if (dot) {
                        dot.style.backgroundColor = '#ef4444';
                        dot.style.boxShadow = '0 0 10px #ef4444';
                    }
                });
        }
    };

    // ============================================================
    // 10. TeamCarousel —— 团队卡片无缝滚动
    // ============================================================
    const teamCarousel = {
        init() {
            const wrapper = document.getElementById('teamWrapper');
            if (!wrapper) return;

            // 复制一份卡片用于无缝循环
            const originals = wrapper.querySelectorAll('.team-card');
            for (let i = 0; i < originals.length; i++) {
                const clone = originals[i].cloneNode(true);
                clone.classList.add('team-card-clone');
                wrapper.appendChild(clone);
            }

            // 让懒加载也覆盖到克隆出来的图片
            if (state.io) {
                wrapper.querySelectorAll('img[data-src]').forEach(img => state.io.observe(img));
            }

            // 离开视口时暂停 CSS 动画，节省 CPU
            const section = document.getElementById('team');
            if (section && 'IntersectionObserver' in window) {
                const io = new IntersectionObserver((entries) => {
                    wrapper.style.animationPlayState = entries[0].isIntersecting ? 'running' : 'paused';
                }, { rootMargin: '100px 0px' });
                io.observe(section);
            }
        }
    };

    // ============================================================
    // 11. ContactForm —— 联系表单 + 图片上传
    // ============================================================
    const contactForm = {
        MAX_FILES: 3,
        MAX_SIZE: 5 * 1024 * 1024,
        selectedFiles: [],
        els: {},

        init() {
            const form = document.getElementById('contactForm');
            if (!form) return;
            this.els = {
                form,
                area:    document.getElementById('uploadArea'),
                input:   document.getElementById('attachment'),
                preview: document.getElementById('uploadPreview'),
                editor:  document.getElementById('msgEditor'),
                hint:    document.getElementById('attachHint'),
            };

            this._bindUpload();
            this._bindDragDrop();
            form.addEventListener('submit', (e) => this._submit(e));
        },

        _bindUpload() {
            const { area, input } = this.els;
            if (!area || !input) return;
            area.addEventListener('click', () => input.click());
            input.addEventListener('change', () => {
                this._addFiles(input.files);
                input.value = '';
            });
        },

        _bindDragDrop() {
            const { editor } = this.els;
            if (!editor) return;
            editor.addEventListener('dragover', (e) => {
                e.preventDefault();
                editor.style.borderColor = '#10b981';
            });
            editor.addEventListener('dragleave', (e) => {
                if (!editor.contains(e.relatedTarget)) editor.style.borderColor = '';
            });
            editor.addEventListener('drop', (e) => {
                e.preventDefault();
                editor.style.borderColor = '';
                this._addFiles(e.dataTransfer.files);
            });
        },

        _addFiles(files) {
            for (const file of files) {
                if (this.selectedFiles.length >= this.MAX_FILES) break;
                if (!file.type.startsWith('image/')) continue;
                if (file.size > this.MAX_SIZE) {
                    alert('图片 "' + file.name + '" 超过5MB限制');
                    continue;
                }
                this.selectedFiles.push(file);
            }
            this._renderPreview();
        },

        _renderPreview() {
            const { preview, hint } = this.els;
            preview.innerHTML = '';
            this.selectedFiles.forEach((file, i) => {
                const item = document.createElement('div');
                item.className = 'upload-preview-item';
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'remove-btn';
                btn.textContent = '×';
                btn.setAttribute('aria-label', '移除图片');
                btn.addEventListener('click', () => {
                    this.selectedFiles.splice(i, 1);
                    this._renderPreview();
                });
                item.appendChild(img);
                item.appendChild(btn);
                preview.appendChild(item);
            });
            if (hint) hint.textContent = this.selectedFiles.length > 0
                ? this.selectedFiles.length + '/3 张'
                : '最多3张，每张≤5MB';
        },

        _submit(e) {
            e.preventDefault();
            const { form } = this.els;
            const submitBtn = form.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span>发送中...</span>';
            submitBtn.style.opacity = '0.8';
            submitBtn.disabled = true;

            const formData = new FormData(form);
            formData.delete('attachments');
            this.selectedFiles.forEach((file, i) => formData.append('image_' + i, file));

            fetch('admin/public_api.php?act=submit_message', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        submitBtn.innerHTML = '<span>发送成功！</span>';
                        submitBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                        submitBtn.style.opacity = '1';
                        form.reset();
                        this.selectedFiles = [];
                        this._renderPreview();
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.style.background = '';
                            submitBtn.disabled = false;
                        }, 3000);
                    } else {
                        alert('发送失败: ' + result.message);
                        submitBtn.innerHTML = originalText;
                        submitBtn.style.opacity = '';
                        submitBtn.disabled = false;
                    }
                })
                .catch(() => {
                    alert('发送出错，请稍后重试');
                    submitBtn.innerHTML = originalText;
                    submitBtn.style.opacity = '';
                    submitBtn.disabled = false;
                });
        }
    };

    // ============================================================
    // Entry —— 启动入口
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        userSession.init();
        announce.init();

        // 复制 IP 切换器（与 toggle 联动）
        const toggle = document.getElementById('toggle');
        if (toggle) toggle.addEventListener('change', () => { if (toggle.checked) copyServerIP(); });

        lazyReveal.init();   // 必须先于 teamCarousel，使共享 IO 已就绪
        gallery.init();
        nav.init();
        cms.init();          // 异步，会回调 serverStatus.fetch()
        teamCarousel.init();
        contactForm.init();
    });
})();
