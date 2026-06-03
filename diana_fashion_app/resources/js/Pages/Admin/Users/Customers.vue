<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Direktori Pelanggan</h3>
            <p class="text-xs text-gray-500 mt-1">Daftar pelanggan yang terdaftar secara mandiri melalui web E-Commerce Diana Fashion.</p>
        </div>

        <!-- Filter Area -->
        <div class="bg-white border border-gray-200 rounded-sm p-6">
            <div class="relative w-full md:w-80">
                <input v-model="filters.keyword" @input="onSearch" type="text" placeholder="Cari nama atau email..." class="w-full text-xs border border-gray-300 rounded-sm px-4 py-2 focus:outline-none focus:border-[#FF1F8F] transition-colors" />
            </div>
        </div>

        <!-- Table Grid -->
        <div class="bg-white border border-gray-200 rounded-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold">
                            <th class="py-3.5 px-6">ID</th>
                            <th class="py-3.5 px-6">Nama Lengkap</th>
                            <th class="py-3.5 px-6">Alamat Email</th>
                            <th class="py-3.5 px-6">Tanggal Bergabung</th>
                            <th class="py-3.5 px-6 text-center">Riwayat Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="customers.length === 0" class="border-b border-gray-100">
                            <td colspan="5" class="py-16 text-center text-gray-400">Tidak ada pelanggan terdaftar.</td>
                        </tr>
                        <tr v-else v-for="cust in customers" :key="cust.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6 font-mono text-gray-400">#{{ cust.id }}</td>
                            <td class="py-4 px-6 font-semibold text-gray-900">{{ cust.name }}</td>
                            <td class="py-4 px-6 text-gray-600 font-mono">{{ cust.email }}</td>
                            <td class="py-4 px-6 text-gray-400 font-mono">{{ formatDate(cust.created_at) }}</td>
                            <td class="py-4 px-6 text-center">
                                <button @click="viewHistory(cust)" class="text-blue-500 hover:text-blue-600 font-semibold cursor-pointer">Lihat Riwayat Belanja</button>
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

        <!-- History Modal Backdrop -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white border border-gray-200 rounded-sm w-full max-w-xl p-6 shadow-xl space-y-6">
                <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                        Riwayat Belanja: {{ activeCustomer.name }}
                    </h3>
                </div>

                <!-- Riwayat Transaksi Tabel -->
                <div class="overflow-y-auto max-h-80 border border-gray-100 rounded-sm">
                    <table class="w-full text-[11px] text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold">
                                <th class="py-2.5 px-4">No. Nota</th>
                                <th class="py-2.5 px-4 text-center">Status</th>
                                <th class="py-2.5 px-4 text-right">Total (Rp)</th>
                                <th class="py-2.5 px-4 text-center">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="ordersHistory.length === 0" class="border-b border-gray-50">
                                <td colspan="4" class="py-8 text-center text-gray-400">Belum ada riwayat transaksi.</td>
                            </tr>
                            <tr v-else v-for="order in ordersHistory" :key="order.id" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 px-4 font-mono font-bold">{{ order.increment_id }}</td>
                                <td class="py-2.5 px-4 text-center">
                                    <span :class="getStatusClass(order.status)" class="px-1.5 py-0.5 rounded-sm text-[8px] font-bold uppercase">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono font-semibold">Rp {{ formatNumber(order.total_price) }}</td>
                                <td class="py-2.5 px-4 text-center font-mono text-gray-400">{{ formatDate(order.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-between items-center text-xs font-bold text-gray-900">
                    <span>Total Belanja Berhasil: Rp {{ formatNumber(totalSpent) }}</span>
                    <button @click="showModal = false" class="bg-gray-900 hover:bg-gray-800 text-white text-[10px] font-bold px-4 py-2.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer">
                        Tutup Riwayat
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const customers = ref([]);
const showModal = ref(false);
const activeCustomer = ref({});
const ordersHistory = ref([]);

const filters = reactive({
    keyword: '',
    page: 1
});

const pagination = ref({
    current_page: 1,
    last_page: 1
});

const fetchCustomers = async () => {
    try {
        const response = await axios.get('/api/admin/customers', {
            params: filters
        });
        customers.value = response.data.data;
        pagination.value = response.data;
    } catch (e) {
        console.error('Gagal memuat direktori pelanggan:', e);
    }
};

let debounceTimer = null;
const onSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filters.page = 1;
        fetchCustomers();
    }, 300);
};

const changePage = (page) => {
    filters.page = page;
    fetchCustomers();
};

const viewHistory = async (cust) => {
    activeCustomer.value = cust;
    try {
        const response = await axios.get(`/api/admin/customers/${cust.id}`);
        ordersHistory.value = response.data.orders || [];
        showModal.value = true;
    } catch (e) {
        console.error(e);
    }
};

const totalSpent = computed(() => {
    return ordersHistory.value
        .filter(order => order.status === 'completed')
        .reduce((sum, order) => sum + Number(order.total_price), 0);
});

const getStatusClass = (status) => {
    if (status === 'completed') return 'bg-green-100 text-green-700';
    if (status === 'canceled') return 'bg-red-100 text-red-700';
    return 'bg-yellow-100 text-yellow-700';
};

const formatNumber = (num) => Number(num).toLocaleString('id-ID');
const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

onMounted(() => {
    fetchCustomers();
});
</script>
