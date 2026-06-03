import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';

// 1. Import Admin Components
import AdminLayout from './Pages/Admin/Layouts/AdminLayout.vue';
import DashboardIndex from './Pages/Admin/Dashboard/Index.vue';
import ProductsIndex from './Pages/Admin/Products/Index.vue';
import OrdersIndex from './Pages/Admin/Orders/Index.vue';
import CustomersIndex from './Pages/Admin/Users/Customers.vue';
import StaffIndex from './Pages/Admin/Users/Staff.vue';
import ArimaDashboard from './Pages/Admin/ArimaDashboard.vue';
import ReportsIndex from './Pages/Admin/Reports/Index.vue';

// 2. Setup Nested Routing untuk Admin SPA
const routes = [
    {
        path: '/admin',
        component: AdminLayout,
        children: [
            { path: '', component: DashboardIndex },
            { path: 'products', component: ProductsIndex },
            { path: 'orders', component: OrdersIndex },
            { path: 'customers', component: CustomersIndex },
            { path: 'staff', component: StaffIndex },
            { path: 'arima', component: ArimaDashboard },
            { path: 'reports', component: ReportsIndex }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// 3. Mount Vue 3 App
const app = createApp({});
app.use(createPinia());
app.use(router);
app.mount('#admin-app');
