<template>
    <div class="min-h-screen bg-[#F9FAFB] flex font-sans text-[#111827]">
        <!-- Sidebar Kiri -->
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between fixed h-screen z-30">
            <div>
                <!-- Brand Header -->
                <div class="h-16 flex items-center px-6 border-b border-gray-100 space-x-2.5">
                    <img src="/logo.jpg" alt="Logo" class="h-8 w-8 rounded-full object-cover border border-gray-200" />
                    <span class="text-xs font-black text-gray-900 tracking-wider uppercase">Diana Admin</span>
                </div>

                <!-- Navigation Links grouped by features -->
                <nav class="p-4 space-y-4">
                    <div v-for="group in menuGroups" :key="group.title" class="space-y-1.5">
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest px-4 block">{{ group.title }}</span>
                        <div class="space-y-0.5">
                            <router-link v-for="item in group.items" :key="item.path" :to="item.path" :class="$route.path === item.path ? 'bg-pink-50/70 text-[#FF1F8F] font-bold border-l-2 border-[#FF1F8F]' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'" class="flex items-center px-4 py-2 text-xs rounded-sm transition-all cursor-pointer">
                                <component :is="item.icon" class="h-4 w-4 mr-3 stroke-current" />
                                {{ item.name }}
                            </router-link>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Bottom User Account & Logout -->
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-900 truncate max-w-[140px]">{{ adminName }}</p>
                        <span class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider">Administrator</span>
                    </div>
                    <button @click="logout" class="text-gray-400 hover:text-red-500 transition-colors cursor-pointer" title="Keluar Sesi">
                        <svg class="h-4.5 w-4.5 stroke-2 stroke-current" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-grow pl-64 flex flex-col min-h-screen">
            <!-- Header Atas -->
            <header class="h-16 bg-white border-b border-gray-200 flex justify-between items-center px-8 sticky top-0 z-20">
                <h1 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ currentPageName }}</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-[10px] bg-[#FF1F8F] text-white font-mono px-2 py-0.5 rounded-sm uppercase font-bold">Online</span>
                </div>
            </header>

            <!-- Page Workspace -->
            <main class="p-8 flex-grow">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const adminName = ref('Admin Diana');

const menuGroups = [
    {
        title: 'Ikhtisar & Utama',
        items: [
            {
                name: 'Dashboard Metrik',
                path: '/admin',
                icon: {
                    render() {
                        return h('svg', { class: 'h-4 w-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z' })
                        ]);
                    }
                }
            }
        ]
    },
    {
        title: 'Operasional Toko',
        items: [
            {
                name: 'Kelola Produk',
                path: '/admin/products',
                icon: {
                    render() {
                        return h('svg', { class: 'h-4 w-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z' })
                        ]);
                    }
                }
            },
            {
                name: 'Riwayat Transaksi',
                path: '/admin/orders',
                icon: {
                    render() {
                        return h('svg', { class: 'h-4 w-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z' })
                        ]);
                    }
                }
            }
        ]
    },
    {
        title: 'Kecerdasan & Analisis',
        items: [
            {
                name: 'ARIMA AI Tuning',
                path: '/admin/arima',
                icon: {
                    render() {
                        return h('svg', { class: 'h-4 w-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
                            h('polyline', { points: '22 7 13.5 15.5 8.5 10.5 2 17' }),
                            h('polyline', { points: '16 7 22 7 22 13' })
                        ]);
                    }
                }
            },
            {
                name: 'Laporan Penjualan',
                path: '/admin/reports',
                icon: {
                    render() {
                        return h('svg', { class: 'h-4 w-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18V6A2.25 2.25 0 0 1 5.25 3.75h9.75m.75 0l5.25 5.25' })
                        ]);
                    }
                }
            }
        ]
    },
    {
        title: 'Manajemen Pengguna',
        items: [
            {
                name: 'Direktori Pelanggan',
                path: '/admin/customers',
                icon: {
                    render() {
                        return h('svg', { class: 'h-4 w-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z' })
                        ]);
                    }
                }
            },
            {
                name: 'Kelola Staf Toko',
                path: '/admin/staff',
                icon: {
                    render() {
                        return h('svg', { class: 'h-4 w-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
                            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.97 5.97 0 00-.75-2.985m-.001-3.978a3 3 0 110-6m0 6a3 3 0 01-1.072-2.3m-5.845 8.3c-.482-.12-.99-.18-1.513-.18-.523 0-1.031.06-1.513.18M6 18.72a9.094 9.094 0 01-3.741-.479 3 3 0 014.682-2.72m-.94 3.198l-.001.031c0 .225.012.447.037.666A11.944 11.944 0 0012 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0018 18.72m-12 0a5.97 5.97 0 01.75-2.985m.001-3.978a3 3 0 100-6m0 6a3 3 0 001.072-2.3' })
                        ]);
                    }
                }
            }
        ]
    }
];

const currentPageName = computed(() => {
    for (const group of menuGroups) {
        const item = group.items.find(m => m.path === route.path);
        if (item) return item.name;
    }
    return 'Admin Panel';
});

const checkUser = async () => {
    try {
        const response = await axios.get('/api/auth/user');
        adminName.value = response.data.user.name;
    } catch (e) {
        adminName.value = 'Admin Diana';
    }
};

const logout = async () => {
    try {
        await axios.post('/api/auth/logout');
        window.location.href = '/';
    } catch (e) {
        window.location.href = '/';
    }
};

onMounted(() => {
    checkUser();
});
</script>
