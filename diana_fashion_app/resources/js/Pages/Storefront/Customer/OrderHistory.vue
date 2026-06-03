<template>
    <div>
        <h2 class="text-lg font-bold text-gray-900 uppercase tracking-wider mb-6">Riwayat Belanja</h2>

        <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-[#FF1F8F]"></div>
        </div>

        <div v-else-if="orders.length === 0" class="text-center py-12">
            <p class="text-sm text-gray-500 font-sans">Anda belum pernah melakukan pemesanan.</p>
        </div>

        <div v-else class="space-y-4">
            <div v-for="order in orders" :key="order.id" class="border border-gray-200 rounded-sm p-6 hover:border-gray-300 transition-colors">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <span class="text-xs font-bold font-mono text-gray-900">{{ order.increment_id }}</span>
                        <span class="text-xs text-gray-400 block mt-0.5 font-mono">{{ formatDate(order.created_at) }}</span>
                    </div>

                    <!-- Status Badges -->
                    <span :class="getStatusClass(order.status)" class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-sm">
                        {{ order.status }}
                    </span>
                </div>

                <div class="flex justify-between items-center border-t border-gray-100 pt-4 bg-gray-50 -mx-6 -mb-6 p-6 rounded-b-sm">
                    <div>
                        <span class="text-xs text-gray-500 block leading-tight">Total Belanja</span>
                        <span class="text-sm font-bold text-gray-900 font-mono">Rp {{ formatNumber(order.total_price) }}</span>
                    </div>

                    <router-link :to="`/customer/orders/${order.id}`" class="text-xs font-semibold text-[#FF1F8F] hover:text-[#D91678] transition-colors">
                        Lihat Detail
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['show-notification']);

const loading = ref(false);
const orders = ref([]);

const fetchOrders = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/customer/orders');
        orders.value = response.data.data;
    } catch (error) {
        console.error('Gagal mengambil riwayat belanja:', error);
        emit('show-notification', 'Gagal memuat riwayat belanja.');
    } finally {
        loading.value = false;
    }
};

const getStatusClass = (status) => {
    if (status === 'completed') return 'bg-emerald-50 text-emerald-600';
    if (status === 'canceled') return 'bg-rose-50 text-rose-600';
    return 'bg-amber-50 text-amber-600'; // pending
};

const formatNumber = (num) => {
    return Number(num).toLocaleString('id-ID');
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    fetchOrders();
});
</script>
