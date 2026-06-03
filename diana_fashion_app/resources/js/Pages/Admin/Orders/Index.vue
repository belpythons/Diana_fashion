<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Riwayat Penjualan</h3>
            <p class="text-xs text-gray-500 mt-1">Memantau transaksi omnichannel yang terjadi pada kanal toko fisik (POS) maupun online (E-Commerce Web).</p>
        </div>

        <!-- Filter Area -->
        <div class="bg-white border border-gray-200 rounded-sm p-6 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <!-- Search -->
            <div>
                <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">No. Nota Belanja</label>
                <input v-model="filters.keyword" @input="onSearch" type="text" placeholder="Masukkan nomor nota..." class="w-full text-xs border border-gray-300 rounded-sm px-4 py-2 focus:outline-none focus:border-[#FF1F8F] transition-colors" />
            </div>

            <!-- Kanal -->
            <div>
                <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Kanal Penjualan</label>
                <select v-model="filters.channel" @change="fetchOrders" class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]">
                    <option value="">Semua Kanal</option>
                    <option value="web">Online (E-Commerce)</option>
                    <option value="pos">Fisik (POS Terminal)</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Status Pesanan</label>
                <select v-model="filters.status" @change="fetchOrders" class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]">
                    <option value="">Semua Status</option>
                    <option value="pending">Tertunda (Pending)</option>
                    <option value="completed">Selesai (Completed)</option>
                    <option value="canceled">Dibatalkan (Canceled)</option>
                </select>
            </div>

            <!-- Date Range -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Mulai Tanggal</label>
                    <input v-model="filters.start_date" @change="fetchOrders" type="date" class="w-full text-xs border border-gray-300 rounded-sm px-2 py-1.5 focus:outline-none focus:border-[#FF1F8F]" />
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                    <input v-model="filters.end_date" @change="fetchOrders" type="date" class="w-full text-xs border border-gray-300 rounded-sm px-2 py-1.5 focus:outline-none focus:border-[#FF1F8F]" />
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white border border-gray-200 rounded-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold">
                            <th class="py-3.5 px-6">No. Nota</th>
                            <th class="py-3.5 px-6">Pelanggan / Guest</th>
                            <th class="py-3.5 px-6">Tanggal Transaksi</th>
                            <th class="py-3.5 px-6 text-center">Kanal</th>
                            <th class="py-3.5 px-6 text-center">Status</th>
                            <th class="py-3.5 px-6 text-right">Total Transaksi</th>
                            <th class="py-3.5 px-6 text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="orders.length === 0" class="border-b border-gray-100">
                            <td colspan="7" class="py-16 text-center text-gray-400">Tidak ada data transaksi ditemukan.</td>
                        </tr>
                        <tr v-else v-for="order in orders" :key="order.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6 font-mono font-bold">{{ order.increment_id }}</td>
                            <td class="py-4 px-6 font-semibold text-gray-900">{{ order.user?.name || 'Guest (Toko Fisik)' }}</td>
                            <td class="py-4 px-6 font-mono text-gray-400">{{ formatDate(order.created_at) }}</td>
                            <td class="py-4 px-6 text-center">
                                <span :class="order.channel === 'pos' ? 'bg-gray-100 text-gray-600' : 'bg-pink-100 text-[#FF1F8F] font-bold'" class="px-2 py-0.5 rounded-sm font-mono text-[9px] uppercase">
                                    {{ order.channel }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span :class="getStatusClass(order.status)" class="px-2 py-0.5 rounded-sm text-[9px] font-bold uppercase">
                                    {{ order.status }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right font-mono font-semibold text-gray-900">Rp {{ formatNumber(order.total_price) }}</td>
                            <td class="py-4 px-6 text-center">
                                <button @click="viewDetail(order)" class="text-blue-500 hover:text-blue-600 font-semibold cursor-pointer">Lihat Nota</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="pagination.last_page > 1" class="p-6 border-t border-gray-100 flex justify-between items-center bg-gray-50">
                <span class="text-[10px] text-gray-400">Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
                <div class="flex space-x-2">
                    <button :disabled="pagination.current_page === 1" @click="changePage(pagination.current_page - 1)" class="border border-gray-300 hover:border-gray-400 bg-white text-gray-700 text-[10px] font-bold px-3 py-1.5 rounded-sm transition-colors cursor-pointer disabled:opacity-40">
                        Sebelumnya
                    </button>
                    <button :disabled="pagination.current_page === pagination.last_page" @click="changePage(pagination.current_page + 1)" class="border border-gray-300 hover:border-gray-400 bg-white text-gray-700 text-[10px] font-bold px-3 py-1.5 rounded-sm transition-colors cursor-pointer disabled:opacity-40">
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>

        <!-- Detail Modal Backdrop -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white border border-gray-200 rounded-sm w-full max-w-lg p-6 shadow-xl space-y-6">
                <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                        Nota Belanja: {{ activeOrder.increment_id }}
                    </h3>
                    <span :class="getStatusClass(activeOrder.status)" class="px-2 py-0.5 rounded-sm text-[9px] font-bold uppercase">
                        {{ activeOrder.status }}
                    </span>
                </div>

                <!-- Info Header Nota -->
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-[9px] text-gray-400 uppercase font-bold tracking-wider block">Pelanggan</span>
                        <p class="font-semibold mt-0.5 text-gray-800">{{ activeOrder.user?.name || 'Guest (Toko Fisik)' }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ activeOrder.user?.email || 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-[9px] text-gray-400 uppercase font-bold tracking-wider block">Kanal & Metode</span>
                        <p class="font-semibold mt-0.5 text-gray-800 uppercase font-mono">{{ activeOrder.channel }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ activeOrder.payment_method || 'WhatsApp Checkout' }}</p>
                    </div>
                </div>

                <!-- Rincian Item Grid -->
                <div class="space-y-3 border-y border-gray-100 py-4 max-h-60 overflow-y-auto">
                    <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider">Daftar Barang Belanja</span>
                    
                    <div v-for="item in activeOrder.items" :key="item.id" class="flex justify-between items-center text-xs">
                        <div>
                            <p class="font-semibold text-gray-800">{{ item.product?.name || 'Produk Terhapus' }}</p>
                            <span class="text-[10px] text-gray-400 block font-mono">Rp {{ formatNumber(item.price_at_purchase) }} x {{ item.qty }} pcs</span>
                        </div>
                        <span class="font-mono font-bold text-gray-900">Rp {{ formatNumber(item.price_at_purchase * item.qty) }}</span>
                    </div>
                </div>

                <!-- Grand Total -->
                <div class="flex justify-between items-center text-sm font-bold text-gray-900 pt-2">
                    <span>Grand Total Belanja</span>
                    <span class="font-mono text-base text-[#FF1F8F]">Rp {{ formatNumber(activeOrder.total_price) }}</span>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button @click="showModal = false" class="bg-gray-900 hover:bg-gray-800 text-white text-[10px] font-bold px-5 py-2.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer">
                        Tutup Nota
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const orders = ref([]);
const showModal = ref(false);
const activeOrder = ref({});

const filters = reactive({
    keyword: '',
    channel: '',
    status: '',
    start_date: '',
    end_date: '',
    page: 1
});

const pagination = ref({
    current_page: 1,
    last_page: 1
});

const fetchOrders = async () => {
    try {
        const response = await axios.get('/api/admin/orders/history', {
            params: filters
        });
        orders.value = response.data.data;
        pagination.value = response.data;
    } catch (e) {
        console.error('Gagal mengambil riwayat transaksi:', e);
    }
};

let debounceTimer = null;
const onSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filters.page = 1;
        fetchOrders();
    }, 300);
};

const changePage = (page) => {
    filters.page = page;
    fetchOrders();
};

const viewDetail = (order) => {
    activeOrder.value = order;
    showModal.value = true;
};

const getStatusClass = (status) => {
    if (status === 'completed') return 'bg-green-100 text-green-700';
    if (status === 'canceled') return 'bg-red-100 text-red-700';
    return 'bg-yellow-100 text-yellow-700';
};

const formatNumber = (num) => Number(num).toLocaleString('id-ID');
const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    fetchOrders();
});
</script>
