import './bootstrap';
import { createApp, ref, computed, onMounted } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';

// Import Views & Layouts
import MainLayout from './Pages/Storefront/Layouts/MainLayout.vue';
import Home from './Pages/Storefront/Views/Home.vue';
import CartCheckout from './Pages/Storefront/Views/CartCheckout.vue';
import LoginRegister from './Pages/Storefront/Views/LoginRegister.vue';
import DashboardLayout from './Pages/Storefront/Customer/DashboardLayout.vue';
import OrderHistory from './Pages/Storefront/Customer/OrderHistory.vue';
import ProfileSetting from './Pages/Storefront/Customer/ProfileSetting.vue';

// Child Dashboard Component
const CustomerDashboard = {
    props: ['user'],
    template: `
        <div>
            <h2 class="text-lg font-bold text-gray-900 uppercase tracking-wider mb-6">Dashboard Pelanggan</h2>
            <p class="text-sm text-gray-600">Selamat datang kembali, <strong>{{ user?.name }}</strong>. Di sini Anda dapat memantau riwayat pembelian Anda dan memperbarui info profil diri.</p>
        </div>
    `
};

const routes = [
    { path: '/', component: Home, name: 'home' },
    { path: '/cart', component: CartCheckout, name: 'cart' },
    { path: '/login', component: LoginRegister, name: 'login' },
    { 
        path: '/customer', 
        component: DashboardLayout,
        beforeEnter: (to, from, next) => {
            const loggedIn = localStorage.getItem('diana_logged_in') === 'true';
            if (!loggedIn) {
                next('/login');
            } else {
                next();
            }
        },
        children: [
            { path: '', component: CustomerDashboard, name: 'customer.dashboard' },
            { path: 'orders', component: OrderHistory, name: 'customer.orders' },
            { path: 'profile', component: ProfileSetting, name: 'customer.profile' }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

const app = createApp({
    components: { MainLayout },
    setup() {
        const user = ref(null);
        const cart = ref([]);
        const notification = ref('');
        const notificationTimer = ref(null);
        const categoryFilter = ref('');

        const cartCount = computed(() => cart.value.reduce((sum, item) => sum + item.qty, 0));

        // Load Cart dari LocalStorage
        const loadCart = () => {
            const stored = localStorage.getItem('diana_fashion_cart');
            if (stored) {
                try {
                    cart.value = JSON.parse(stored);
                } catch (e) {
                    localStorage.removeItem('diana_fashion_cart');
                }
            }
        };

        // Simpan Cart ke LocalStorage
        const saveCart = () => {
            localStorage.setItem('diana_fashion_cart', JSON.stringify(cart.value));
        };

        const addToCart = (product) => {
            if (product.stock <= 0) {
                showNotification(`Gagal: Stok "${product.name}" habis.`);
                return;
            }
            const existing = cart.value.find(item => item.id === product.id);
            if (existing) {
                if (existing.qty >= product.stock) {
                    showNotification(`Stok terbatas: Hanya tersedia ${product.stock} pcs.`);
                    return;
                }
                existing.qty += 1;
            } else {
                cart.value.push({ ...product, qty: 1 });
            }
            saveCart();
            showNotification(`Berhasil menambahkan "${product.name}" ke keranjang.`);
        };

        const updateQty = (id, qty) => {
            const item = cart.value.find(i => i.id === id);
            if (item) {
                if (qty > item.stock) {
                    showNotification(`Stok terbatas: Hanya tersedia ${item.stock} pcs.`);
                    return;
                }
                item.qty = qty;
                saveCart();
            }
        };

        const removeItem = (id) => {
            cart.value = cart.value.filter(i => i.id !== id);
            saveCart();
        };

        const clearCart = () => {
            cart.value = [];
            localStorage.removeItem('diana_fashion_cart');
        };

        // Autentikasi Check
        const checkUser = async () => {
            try {
                const response = await axios.get('/api/auth/user');
                user.value = response.data.user;
                localStorage.setItem('diana_logged_in', 'true');
            } catch (error) {
                user.value = null;
                localStorage.removeItem('diana_logged_in');
            }
        };

        const handleLogout = async () => {
            try {
                await axios.post('/api/auth/logout');
                user.value = null;
                localStorage.removeItem('diana_logged_in');
                showNotification('Anda telah keluar dari akun.');
                window.location.href = '/';
            } catch (error) {
                console.error('Gagal logout:', error);
            }
        };

        const handleAuthSuccess = (userData) => {
            user.value = userData;
            localStorage.setItem('diana_logged_in', 'true');
        };

        const handleProfileUpdated = (userData) => {
            user.value = userData;
        };

        const showNotification = (msg) => {
            notification.value = msg;
            clearTimeout(notificationTimer.value);
            notificationTimer.value = setTimeout(() => {
                notification.value = '';
            }, 3000);
        };

        const setCategoryFilter = (cat) => {
            categoryFilter.value = cat;
            router.push('/');
        };

        onMounted(() => {
            loadCart();
            checkUser();
        });

        return {
            user,
            cart,
            cartCount,
            notification,
            categoryFilter,
            addToCart,
            updateQty,
            removeItem,
            clearCart,
            handleLogout,
            handleAuthSuccess,
            handleProfileUpdated,
            showNotification,
            setCategoryFilter
        };
    }
});

app.use(createPinia());
app.use(router);
app.mount('#app');
