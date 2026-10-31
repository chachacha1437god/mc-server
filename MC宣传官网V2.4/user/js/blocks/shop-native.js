(function () {
    'use strict';

    window.EpBlocks = window.EpBlocks || {};

    var API = 'api/index.php';
    var payment = Vue.reactive({ loaded: false, enabled: false, type: '', types: [], selected: '' });
    var dialogs = null;

    function get(action, params) {
        var query = new URLSearchParams(Object.assign({ action: action }, params || {}));
        return fetch(API + '?' + query.toString(), { credentials: 'same-origin' }).then(function (response) {
            return response.json();
        });
    }

    function post(action, data) {
        var form = new FormData();
        Object.keys(data || {}).forEach(function (key) { form.append(key, data[key]); });
        form.set('action', action);
        form.set('csrf', window.userCsrf || '');
        return fetch(API, { method: 'POST', body: form, credentials: 'same-origin' }).then(function (response) {
            return response.json();
        });
    }

    function message(text, type) {
        ElementPlus.ElMessage({ message: text, type: type || 'info' });
    }

    function loadPayment() {
        return get('shop_pay_config').then(function (result) {
            if (result.status !== 'success') throw new Error(result.message || '支付配置加载失败');
            var data = result.data || {};
            payment.loaded = true;
            payment.enabled = !!data.enabled;
            payment.type = data.type || '';
            payment.types = data.types || [];
            payment.selected = payment.selected || payment.type || (payment.types[0] && payment.types[0].value) || '';
        }).catch(function () {
            payment.loaded = true;
            payment.enabled = false;
            payment.types = [];
            payment.selected = '';
        });
    }

    function imageUrl(path) {
        if (!path) return '';
        return /^(https?:)?\/\//.test(path) || path.charAt(0) === '/' ? path : '../' + path.replace(/^\/+/, '');
    }

    function formatTime(value) {
        return value ? String(value).replace('T', ' ').slice(0, 19) : '-';
    }

    var CATALOG_TEMPLATE = ''
        + '<el-card class="ep-shop-card ep-native-catalog" shadow="never">'
        + '  <template #header><div class="ep-card-head"><div><h2>商城</h2><p>浏览服务器商品与服务</p></div><el-button :icon="Refresh" circle title="刷新" :loading="loading" @click="load"></el-button></div></template>'
        + '  <div class="ep-shop-filters"><div class="ep-shop-search-row"><el-input v-model.trim="keyword" placeholder="搜索商品" clearable :prefix-icon="Search" @keyup.enter="applyFilters" @clear="applyFilters"></el-input><el-button type="primary" @click="applyFilters">搜索</el-button></div>'
        + '  <div v-if="categories.length" class="ep-shop-cats"><el-check-tag :checked="categoryId === 0" @change="pickCategory(0)">全部</el-check-tag><el-check-tag v-for="category in categories" :key="category.id" :checked="categoryId === Number(category.id)" @change="pickCategory(Number(category.id))">{{ category.name }}</el-check-tag></div></div>'
        + '  <div v-if="loading && !items.length" class="ep-native-product-grid"><el-card v-for="index in 8" :key="index" shadow="never" class="ep-native-product-card"><el-skeleton animated><template #template><el-skeleton-item variant="image" class="ep-native-product-skeleton-image"></el-skeleton-item><div class="ep-native-product-body"><el-skeleton-item variant="h3" style="width:72%"></el-skeleton-item><el-skeleton-item variant="text" style="width:92%"></el-skeleton-item><el-skeleton-item variant="button" style="width:100%"></el-skeleton-item></div></template></el-skeleton></el-card></div>'
        + '  <el-result v-else-if="error" icon="error" title="商品加载失败" :sub-title="error"><template #extra><el-button type="primary" plain @click="load">重新加载</el-button></template></el-result>'
        + '  <el-empty v-else-if="!items.length" :description="keyword || categoryId ? \'没有符合条件的商品\' : \'商城还没有商品\'"></el-empty>'
        + '  <div v-else class="ep-native-product-grid"><el-card v-for="product in items" :key="product.id" shadow="hover" class="ep-native-product-card" :class="{ \'is-sold-out\': stock(product) <= 0 }" @click="open(product)">'
        + '    <div class="ep-native-product-cover"><el-image v-if="product.cover_image" :src="image(product.cover_image)" fit="cover" lazy><template #error><div class="ep-native-product-placeholder"><el-icon :size="42"><Box></Box></el-icon></div></template></el-image><div v-else class="ep-native-product-placeholder"><el-icon :size="42"><Box></Box></el-icon></div><el-tag v-if="discounted(product)" type="danger" effect="dark" size="small" class="ep-native-product-discount">优惠</el-tag><div v-if="stock(product) <= 0" class="ep-native-product-soldout"><el-tag type="info" effect="dark">已售罄</el-tag></div></div>'
        + '    <div class="ep-native-product-body"><div class="ep-native-product-title"><h3>{{ product.name }}</h3><el-tag :type="stockType(product)" size="small" effect="plain">{{ stockText(product) }}</el-tag></div><p v-if="product.subtitle">{{ product.subtitle }}</p><div class="ep-native-product-price"><span>¥</span><strong>{{ product.price }}</strong><del v-if="discounted(product)">¥{{ product.original_price }}</del></div><el-button type="primary" :disabled="stock(product) <= 0" @click.stop="quick(product)">{{ stock(product) <= 0 ? \'已售罄\' : \'立即购买\' }}</el-button></div>'
        + '  </el-card></div>'
        + '  <el-pagination v-if="total > perPage" class="ep-shop-pager" background layout="total, prev, pager, next" :total="total" :page-size="perPage" v-model:current-page="page" @current-change="load"></el-pagination>'
        + '</el-card>';

    window.EpBlocks.shopCatalog = function () {
        return {
            template: CATALOG_TEMPLATE,
            data: function () {
                return {
                    Refresh: ElementPlusIconsVue.Refresh,
                    Search: ElementPlusIconsVue.Search,
                    items: [],
                    categories: [],
                    keyword: '',
                    categoryId: 0,
                    page: 1,
                    perPage: 24,
                    total: 0,
                    loading: false,
                    error: ''
                };
            },
            mounted: function () {
                this.loadCategories();
                this.load();
                window.shopRefreshProducts = this.load.bind(this);
            },
            beforeUnmount: function () {
                delete window.shopRefreshProducts;
            },
            methods: {
                image: imageUrl,
                stock: function (product) { return Number(product.stock) || 0; },
                discounted: function (product) { return Number(product.original_price) > Number(product.price); },
                stockType: function (product) { var count = this.stock(product); return count <= 0 ? 'info' : (count <= 5 ? 'warning' : 'success'); },
                stockText: function (product) { var count = this.stock(product); return count <= 0 ? '已售罄' : (count <= 5 ? '仅剩 ' + count : '库存 ' + count); },
                loadCategories: function () { var self = this; get('shop_categories').then(function (result) { if (result.status === 'success') self.categories = result.data || []; }); },
                applyFilters: function () { this.page = 1; this.load(); },
                pickCategory: function (id) { this.categoryId = id; this.page = 1; this.load(); },
                load: function () { var self = this; self.loading = true; self.error = ''; return get('shop_products', { page: self.page, category_id: self.categoryId, keyword: self.keyword }).then(function (result) { if (result.status !== 'success') throw new Error(result.message || '加载失败'); var data = result.data || {}; self.items = data.items || []; self.total = Number(data.total) || 0; self.perPage = Number(data.per_page) || 24; self.page = Number(data.page) || self.page; }).catch(function (error) { self.items = []; self.total = 0; self.error = error.message || '请检查网络连接后重试'; }).finally(function () { self.loading = false; }); },
                open: function (product) { if (dialogs) dialogs.openDetail(product.id); },
                quick: function (product) { if (dialogs) dialogs.quickBuy(product.id, product.name, product.price, this.stock(product), product.min_qty, product.max_qty); }
            }
        };
    };

    var DIALOGS_TEMPLATE = ''
        + '<div>'
        + '  <el-dialog v-model="detailVisible" title="商品详情" width="min(760px, 95vw)" destroy-on-close class="ep-native-shop-dialog">'
        + '    <div v-loading="detailLoading" class="ep-native-detail">'
        + '      <template v-if="product">'
        + '        <el-image v-if="product.cover_image" class="ep-native-detail-cover" :src="cover" :preview-src-list="[cover]" fit="contain"></el-image>'
        + '        <div v-else class="ep-native-detail-placeholder"><el-icon :size="48"><Box></Box></el-icon></div>'
        + '        <div class="ep-native-detail-heading"><div><h2>{{ product.name }}</h2><p v-if="product.subtitle">{{ product.subtitle }}</p></div><el-tag :type="stockType">{{ stockLabel }}</el-tag></div>'
        + '        <div class="ep-native-price"><span>¥</span><strong>{{ product.price }}</strong><del v-if="hasDiscount">¥{{ product.original_price }}</del><el-tag v-if="hasDiscount" type="danger" size="small">优惠</el-tag></div>'
        + '        <el-descriptions :column="3" border class="ep-native-product-meta"><el-descriptions-item label="分类">{{ product.category_name || \'未分类\' }}</el-descriptions-item><el-descriptions-item label="库存">{{ Number(product.stock) || 0 }} 件</el-descriptions-item><el-descriptions-item label="已售">{{ Number(product.sales_count) || 0 }} 件</el-descriptions-item></el-descriptions>'
        + '        <section v-if="product.description" class="ep-native-product-section"><h3>商品介绍</h3><p>{{ product.description }}</p></section>'
        + '        <el-alert v-if="product.delivery_note" type="warning" :closable="false" show-icon><template #title>发货说明</template>{{ product.delivery_note }}</el-alert>'
        + '      </template>'
        + '    </div>'
        + '    <template #footer><div class="ep-native-dialog-footer"><div class="ep-native-quantity"><span>购买数量</span><el-input-number v-model="quantity" :min="quantityMin" :max="quantityMax" :disabled="soldOut"></el-input-number><small v-if="quantityHint">{{ quantityHint }}</small></div><div><el-button @click="detailVisible=false">关闭</el-button><el-button type="primary" :disabled="soldOut || !product" @click="checkoutFromDetail">{{ soldOut ? \'已售罄\' : \'立即购买\' }}</el-button></div></div></template>'
        + '  </el-dialog>'
        + '  <el-dialog v-model="checkoutVisible" title="确认订单" width="min(560px, 95vw)" :close-on-click-modal="false" destroy-on-close class="ep-native-shop-dialog">'
        + '    <template v-if="checkoutItem && !orderCreated">'
        + '      <div class="ep-native-checkout-summary"><div class="ep-native-checkout-icon"><el-icon><Box></Box></el-icon></div><div><strong>{{ checkoutItem.name }}</strong><small>单价 ¥{{ money(checkoutItem.price) }}</small></div><el-input-number v-model="checkoutItem.qty" :min="checkoutItem.minQty" :max="checkoutItem.maxQty"></el-input-number></div>'
        + '      <div class="ep-native-checkout-total"><span>合计</span><strong>¥{{ checkoutTotal }}</strong></div>'
        + '      <el-form label-position="top" class="ep-native-checkout-form" @submit.prevent="submitOrder"><el-form-item label="Minecraft ID" required><el-input v-model.trim="form.mc" maxlength="32" placeholder="用于发货"></el-input></el-form-item><el-form-item label="留言备注"><el-input v-model="form.note" type="textarea" :rows="3" maxlength="500" show-word-limit></el-input></el-form-item><el-alert :type="payment.enabled ? \'success\' : \'info\'" :closable="false" show-icon :title="payHint"></el-alert><el-form-item v-if="payment.enabled && payment.types.length > 1" label="支付方式" class="ep-native-pay-types"><el-radio-group v-model="payment.selected"><el-radio-button v-for="item in payment.types" :key="item.value" :value="item.value">{{ item.label }}</el-radio-button></el-radio-group></el-form-item></el-form>'
        + '    </template>'
        + '    <el-result v-else-if="orderCreated" icon="success" title="订单已创建" sub-title="可在我的订单中查询处理状态"><template #extra><div class="ep-native-order-no">{{ orderNumber }}</div></template></el-result>'
        + '    <template #footer><template v-if="!orderCreated"><el-button @click="checkoutVisible=false">取消</el-button><el-button type="primary" :loading="submitting" @click="submitOrder">{{ payment.enabled ? \'提交并支付\' : \'提交订单\' }}</el-button></template><template v-else><el-button @click="continueShopping">继续逛</el-button><el-button type="primary" @click="viewOrders">查看订单</el-button></template></template>'
        + '  </el-dialog>'
        + '</div>';

    window.EpBlocks.shopDialogs = function (props) {
        return {
            template: DIALOGS_TEMPLATE,
            data: function () {
                var savedMc = '';
                try { savedMc = localStorage.getItem('shop_last_mc_id') || ''; } catch (ignore) {}
                return {
                    payment: payment,
                    detailVisible: false,
                    detailLoading: false,
                    product: null,
                    quantity: 1,
                    checkoutVisible: false,
                    checkoutItem: null,
                    form: { mc: props.mcName || savedMc, note: '' },
                    submitting: false,
                    orderCreated: false,
                    orderNumber: ''
                };
            },
            computed: {
                cover: function () { return imageUrl(this.product && this.product.cover_image); },
                soldOut: function () { return !this.product || Number(this.product.stock) <= 0; },
                hasDiscount: function () { return this.product && Number(this.product.original_price) > Number(this.product.price); },
                stockType: function () { var stock = Number(this.product && this.product.stock) || 0; return stock <= 0 ? 'danger' : (stock <= 5 ? 'warning' : 'success'); },
                stockLabel: function () { var stock = Number(this.product && this.product.stock) || 0; return stock <= 0 ? '已售罄' : (stock <= 5 ? '仅剩 ' + stock + ' 件' : '现货充足'); },
                quantityMin: function () { return Math.max(1, Number(this.product && this.product.min_qty) || 1); },
                quantityMax: function () { var stock = Math.max(1, Number(this.product && this.product.stock) || 1); var limit = Number(this.product && this.product.max_qty) || 0; return limit > 0 ? Math.min(stock, limit) : stock; },
                quantityHint: function () { if (!this.product) return ''; var parts = []; if (this.quantityMin > 1) parts.push('最少 ' + this.quantityMin); if (Number(this.product.max_qty) > 0) parts.push('最多 ' + this.product.max_qty); return parts.join('，'); },
                checkoutTotal: function () { return this.checkoutItem ? (Number(this.checkoutItem.price) * Number(this.checkoutItem.qty)).toFixed(2) : '0.00'; },
                payHint: function () { return payment.enabled ? '提交后将跳转在线支付，支付成功后系统自动确认到账。' : '提交后请联系群内管理员完成支付，到账后管理员会处理订单。'; }
            },
            mounted: function () { dialogs = this; loadPayment(); },
            beforeUnmount: function () { if (dialogs === this) dialogs = null; },
            methods: {
                money: function (value) { return Number(value).toFixed(2); },
                openDetail: function (id) { var self = this; self.detailVisible = true; self.detailLoading = true; self.product = null; get('shop_product', { id: id }).then(function (result) { if (result.status !== 'success') throw new Error(result.message || '商品加载失败'); self.product = result.data; self.quantity = Math.min(self.quantityMax, self.quantityMin); }).catch(function (error) { message(error.message || '商品加载失败', 'error'); self.detailVisible = false; }).finally(function () { self.detailLoading = false; }); },
                quickBuy: function (id, name, price, stock, minQty, maxQty) { var min = Math.max(1, Number(minQty) || 1); var max = Math.max(1, Number(stock) || 1); if (Number(maxQty) > 0) max = Math.min(max, Number(maxQty)); this.openCheckout({ id: id, name: name, price: price, qty: Math.min(max, min), minQty: min, maxQty: max }); },
                checkoutFromDetail: function () { if (!this.product || this.soldOut) return; this.detailVisible = false; this.openCheckout({ id: this.product.id, name: this.product.name, price: this.product.price, qty: this.quantity, minQty: this.quantityMin, maxQty: this.quantityMax }); },
                openCheckout: function (item) { this.checkoutItem = Object.assign({}, item); this.orderCreated = false; this.orderNumber = ''; this.checkoutVisible = true; loadPayment(); },
                submitOrder: function () { var self = this; if (self.submitting || !self.checkoutItem) return; if (!self.form.mc) return message('请填写 Minecraft ID', 'warning'); self.submitting = true; var data = { items: JSON.stringify([{ product_id: self.checkoutItem.id, quantity: self.checkoutItem.qty }]), contact_mc: self.form.mc, contact_note: self.form.note }; if (payment.enabled) { data.pay_now = '1'; data.pay_type = payment.selected || payment.type; } post('shop_create_order', data).then(function (result) { if (result.status !== 'success') throw new Error(result.message || '提交失败'); try { localStorage.setItem('shop_last_mc_id', self.form.mc); } catch (ignore) {} self.orderCreated = true; self.orderNumber = result.order_no || '-'; self.checkoutItem = null; if (window.shopRefreshProducts) window.shopRefreshProducts(); if (result.payment && result.payment.url) window.location.href = result.payment.url; else message(result.message || '订单已创建', 'success'); }).catch(function (error) { message(error.message || '提交失败', 'error'); }).finally(function () { self.submitting = false; }); },
                continueShopping: function () { this.checkoutVisible = false; },
                viewOrders: function () { this.checkoutVisible = false; if (window.switchUserTab) window.switchUserTab('orders'); }
            }
        };
    };

    var ORDERS_TEMPLATE = ''
        + '<el-card class="ep-native-orders" shadow="never">'
        + '  <template #header><div class="ep-card-head"><div><h2>我的订单</h2><p>查询付款、发货和完成状态</p></div><div><el-button circle title="刷新" :loading="loading" @click="load"><el-icon><Refresh></Refresh></el-icon></el-button><el-button type="primary" plain @click="shop"><el-icon><ShoppingCart></ShoppingCart></el-icon>去逛商城</el-button></div></div></template>'
        + '  <el-tabs v-model="status" @tab-change="filter"><el-tab-pane v-for="item in statuses" :key="item.value" :label="item.label" :name="item.value"></el-tab-pane></el-tabs>'
        + '  <el-alert :type="payment.enabled ? \'success\' : \'info\'" :closable="false" show-icon :title="payHint" class="ep-native-orders-hint"></el-alert>'
        + '  <el-radio-group v-if="payment.enabled && payment.types.length > 1" v-model="payment.selected" class="ep-native-orders-pay"><el-radio-button v-for="item in payment.types" :key="item.value" :value="item.value">{{ item.label }}</el-radio-button></el-radio-group>'
        + '  <el-table :data="rows" v-loading="loading" stripe empty-text="还没有订单" @row-click="openDetail"><el-table-column prop="order_no" label="订单号" min-width="180"><template #default="scope"><span class="ep-native-order-code">{{ scope.row.order_no }}</span></template></el-table-column><el-table-column label="商品" min-width="220" show-overflow-tooltip><template #default="scope">{{ itemSummary(scope.row) }}</template></el-table-column><el-table-column label="金额" width="110"><template #default="scope"><strong class="ep-native-order-price">¥{{ scope.row.total }}</strong></template></el-table-column><el-table-column label="状态" width="100"><template #default="scope"><el-tag :type="statusType(scope.row.status)">{{ scope.row.status_label }}</el-tag></template></el-table-column><el-table-column label="下单时间" width="170"><template #default="scope">{{ fmt(scope.row.created_at) }}</template></el-table-column><el-table-column label="操作" width="190" fixed="right"><template #default="scope"><el-button v-if="scope.row.status === \'pending_payment\' && payment.enabled" link type="primary" :loading="payingId === scope.row.id" @click.stop="pay(scope.row)">去支付</el-button><el-button v-if="scope.row.status === \'pending_payment\'" link type="danger" @click.stop="cancel(scope.row)">取消</el-button><el-button v-if="canBuyAgain(scope.row)" link type="success" @click.stop="buyAgain(scope.row)">再来一单</el-button><el-button link @click.stop="openDetail(scope.row)">详情</el-button></template></el-table-column></el-table>'
        + '  <el-pagination v-if="total" class="ep-native-orders-pager" background layout="total, prev, pager, next" :total="total" :page-size="10" v-model:current-page="page" @current-change="load"></el-pagination>'
        + '</el-card>'
        + '<el-drawer v-model="detailVisible" title="订单详情" size="min(520px, 94vw)" destroy-on-close><div v-loading="detailLoading"><template v-if="detail"><el-descriptions :column="1" border><el-descriptions-item label="订单号">{{ detail.order_no }}</el-descriptions-item><el-descriptions-item label="状态"><el-tag :type="statusType(detail.status)">{{ detail.status_label }}</el-tag></el-descriptions-item><el-descriptions-item label="Minecraft ID">{{ detail.contact_mc || \'-\' }}</el-descriptions-item><el-descriptions-item label="下单时间">{{ fmt(detail.created_at) }}</el-descriptions-item><el-descriptions-item v-if="detail.paid_at" label="支付时间">{{ fmt(detail.paid_at) }}</el-descriptions-item><el-descriptions-item v-if="detail.shipped_at" label="发货时间">{{ fmt(detail.shipped_at) }}</el-descriptions-item></el-descriptions><el-table :data="detail.items || []" class="ep-native-order-items"><el-table-column prop="product_name" label="商品" min-width="150"></el-table-column><el-table-column prop="unit_price" label="单价" width="90"></el-table-column><el-table-column prop="quantity" label="数量" width="70"></el-table-column><el-table-column prop="subtotal" label="小计" width="90"></el-table-column></el-table><div class="ep-native-detail-total">订单合计 <strong>¥{{ detail.total }}</strong></div><el-alert v-if="detail.contact_note" :title="detail.contact_note" type="info" :closable="false" show-icon></el-alert></template></div></el-drawer>';

    window.EpBlocks.shopOrders = function () {
        return {
            template: ORDERS_TEMPLATE,
            data: function () { return { payment: payment, Refresh: ElementPlusIconsVue.Refresh, rows: [], total: 0, page: 1, status: '', loading: false, payingId: 0, detailVisible: false, detailLoading: false, detail: null, statuses: [{ value: '', label: '全部' }, { value: 'pending_payment', label: '待支付' }, { value: 'paid', label: '已支付' }, { value: 'shipped', label: '已发货' }, { value: 'completed', label: '已完成' }, { value: 'cancelled', label: '已取消' }] }; },
            computed: { payHint: function () { return payment.enabled ? '待支付订单可在线付款，成功后系统会自动确认到账并触发发货。' : '请联系群内管理员完成支付，到账后管理员会标记订单并发货。'; } },
            mounted: function () { loadPayment(); this.load(); window.shopOrdersReload = this.load.bind(this); },
            beforeUnmount: function () { delete window.shopOrdersReload; },
            methods: {
                fmt: formatTime,
                shop: function () { if (window.switchUserTab) window.switchUserTab('shop'); },
                filter: function () { this.page = 1; this.load(); },
                itemSummary: function (order) { return (order.items || []).map(function (item) { return item.product_name + ' ×' + item.quantity; }).join('、'); },
                statusType: function (status) { return { pending_payment: 'warning', paid: 'primary', shipped: 'primary', completed: 'success', cancelled: 'info', refunded: 'danger' }[status] || 'info'; },
                canBuyAgain: function (order) { return ['completed', 'cancelled', 'refunded'].indexOf(order.status) >= 0 && order.items && order.items.length === 1; },
                load: function () { var self = this; self.loading = true; get('shop_my_orders', { page: self.page, status: self.status }).then(function (result) { if (result.status !== 'success') throw new Error(result.message || '订单加载失败'); var data = result.data || {}; self.rows = data.items || []; self.total = Number(data.total) || 0; self.page = Number(data.page) || self.page; }).catch(function (error) { message(error.message || '订单加载失败', 'error'); }).finally(function () { self.loading = false; }); },
                pay: function (order) { var self = this; if (self.payingId) return; self.payingId = order.id; post('shop_pay_order', { id: order.id, pay_type: payment.selected || payment.type }).then(function (result) { if (result.status !== 'success' || !result.payment || !result.payment.url) throw new Error(result.message || '创建支付失败'); window.location.href = result.payment.url; }).catch(function (error) { message(error.message || '创建支付失败', 'error'); }).finally(function () { self.payingId = 0; }); },
                cancel: function (order) { var self = this; ElementPlus.ElMessageBox.confirm('确认取消订单“' + order.order_no + '”？商品库存将自动退回。', '取消订单', { confirmButtonText: '确认取消', cancelButtonText: '保留订单', type: 'warning' }).then(function () { return post('shop_cancel_order', { id: order.id }); }).then(function (result) { if (result.status !== 'success') throw new Error(result.message || '取消失败'); message(result.message || '订单已取消', 'success'); self.load(); }).catch(function (error) { if (error !== 'cancel' && error !== 'close') message(error.message || '取消失败', 'error'); }); },
                buyAgain: function (order) { this.shop(); setTimeout(function () { window.shopOpenDetail(order.items[0].product_id); }, 150); },
                openDetail: function (order) { var self = this; self.detailVisible = true; self.detailLoading = true; self.detail = null; get('shop_order_detail', { id: order.id }).then(function (result) { if (result.status !== 'success') throw new Error(result.message || '详情加载失败'); self.detail = result.data; }).catch(function (error) { message(error.message || '详情加载失败', 'error'); self.detailVisible = false; }).finally(function () { self.detailLoading = false; }); }
            }
        };
    };

    window.shopOpenDetail = function (id) { if (dialogs) dialogs.openDetail(id); };
    window.shopQuickBuy = function (id, name, price, stock, minQty, maxQty) { if (dialogs) dialogs.quickBuy(id, name, price, stock, minQty, maxQty); };
    window.shopCloseDetail = function () { if (dialogs) dialogs.detailVisible = false; };
    window.shopCloseCheckout = function () { if (dialogs) dialogs.checkoutVisible = false; };
}());