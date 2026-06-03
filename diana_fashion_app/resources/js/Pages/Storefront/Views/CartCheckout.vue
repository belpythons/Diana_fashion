<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-8 font-sans">Keranjang Belanja</h1>

        <div v-if="cart.length === 0" class="text-center py-24 bg-white border border-gray-200 rounded-sm">
            <p class="text-sm text-gray-500 font-sans">Keranjang belanja Anda masih kosong.</p>
            <router-link to="/" class="mt-4 inline-block bg-[#FF1F8F] hover:bg-[#D91678] text-white text-xs font-semibold px-6 py-2.5 rounded-sm transition-colors cursor-pointer">
                Mulai Belanja
            </router-link>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Rincian Item (col-span-8) -->
            <div class="lg:col-span-8 space-y-4">
                <div v-for="item in cart" :key="item.id" class="bg-white border border-gray-200 rounded-sm p-6 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gray-50 flex items-center justify-center border border-gray-100 rounded-sm text-xs text-gray-400 font-mono">
                            {{ item.sku.substring(0, 3) }}
                        </div>
                        <div>
                            <span class="text-[10px] font-bold font-mono text-gray-400 uppercase">{{ item.category?.name }}</span>
                            <h3 class="text-sm font-semibold text-gray-900 truncate max-w-sm">{{ item.name }}</h3>
                            <span class="text-xs text-gray-500 block">Rp {{ formatNumber(item.price) }}</span>
                        </div>
                    </div>

                    <!-- Kuantitas Control -->
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center border border-gray-200 rounded-sm">
                            <button @click="updateQty(item.id, item.qty - 1)" class="px-2 py-1 hover:bg-gray-100 text-xs font-bold">-</button>
                            <span class="px-4 py-1 text-xs font-semibold font-mono">{{ item.qty }}</span>
                            <button @click="updateQty(item.id, item.qty + 1)" class="px-2 py-1 hover:bg-gray-100 text-xs font-bold">+</button>
                        </div>
                        <button @click="removeItem(item.id)" class="text-xs font-semibold text-red-500 hover:text-red-600 transition-colors">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Total & Checkout (col-span-4) -->
            <div class="lg:col-span-4 bg-white border border-gray-200 rounded-sm p-6 h-fit">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-6">Ringkasan Pesanan</h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Total Barang</span>
                        <span class="font-mono">{{ totalItems }} pcs</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-900 font-bold border-t border-gray-100 pt-4">
                        <span>Total Pembayaran</span>
                        <span class="font-mono text-lg text-[#FF1F8F]">Rp {{ formatNumber(totalPrice) }}</span>
                    </div>
                </div>

                <!-- Checkout Button -->
                <div class="mt-8">
                    <!-- Kasus 1: User Belum Login (Resolusi #1 - WAJIB LOGIN UNTUK CHECKOUT) -->
                    <div v-if="!user" class="p-4 bg-pink-50 border border-pink-100 rounded-sm mb-4">
                        <p class="text-xs text-[#FF1F8F] leading-relaxed">Anda harus masuk (login) ke dalam akun Anda terlebih dahulu untuk menyelesaikan pesanan.</p>
                        <router-link to="/login" class="mt-3 block text-center bg-[#FF1F8F] hover:bg-[#D91678] text-white text-xs font-bold py-2 rounded-sm transition-colors">
                            Masuk / Login Akun
                        </router-link>
                    </div>

                    <!-- Kasus 2: User Sudah Login (Bisa Checkout) -->
                    <button v-else :disabled="submitting" @click="handleCheckout" class="w-full bg-[#FF1F8F] hover:bg-[#D91678] disabled:opacity-50 text-white text-sm font-bold py-3 rounded-sm tracking-wide transition-colors cursor-pointer text-center">
                        <span v-if="submitting">Memproses...</span>
                        <span v-else>Checkout via WhatsApp</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    cart: { type: Array, default: () => [] },
    user: { type: Object, default: null }
});

const emit = defineEmits(['update-qty', 'remove-item', 'clear-cart', 'show-notification']);

const submitting = ref(false);

const totalItems = computed(() => {
    return props.cart.reduce((sum, item) => sum + item.qty, 0);
});

const totalPrice = computed(() => {
    return props.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
});

const updateQty = (id, qty) => {
    if (qty < 1) return;
    emit('update-qty', id, qty);
};

const removeItem = (id) => {
    emit('remove-item', id);
};

const handleCheckout = async () => {
    if (submitting.value) return;
    submitting.value = true;

    try {
        const payload = {
            items: props.cart.map(item => ({
                id: item.id,
                qty: item.qty // Resolusi #3
            }))
        };

        const response = await axios.post('/api/storefront/checkout', payload);
        
        // 1. Berikan notifikasi sukses
        emit('show-notification', 'Pesanan sukses dibuat! Mengalihkan ke WhatsApp...');

        // 2. Buka tab baru ke WhatsApp Redirect URL
        if (response.data.whatsapp_url) {
            window.open(response.data.whatsapp_url, '_blank');
        }

        // 3. Bersihkan keranjang belanja di frontend (Fire & Forget / refresh state)
        emit('clear-cart');

    } catch (error) {
        console.error('Gagal melakukan checkout:', error);
        const errMsg = error.response?.data?.message || 'Terjadi kesalahan saat memproses checkout.';
        emit('show-notification', errMsg);
    } finally {
        submitting.value = false;
    }
};

const formatNumber = (num) => {
    return Number(num).toLocaleString('id-ID');
};
</script>
