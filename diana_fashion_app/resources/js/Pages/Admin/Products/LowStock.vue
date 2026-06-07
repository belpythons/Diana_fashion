<template>
    <div class="space-y-6">
        <!-- Header & Back Button -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <router-link to="/admin/products" class="text-xs font-bold text-[#FF1F8F] hover:underline flex items-center">
                        ← Kembali ke Inventaris
                    </router-link>
                </div>
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mt-2">Panel Stok Rendah (< 2 pcs)</h3>
                <p class="text-xs text-gray-500 mt-1">Daftar produk dengan persediaan kritis (0 atau 1 pcs) yang perlu segera ditambahkan stoknya agar dapat tampil di e-commerce.</p>
            </div>
        </div>

        <!-- Filter Area -->
        <div class="bg-white border border-gray-200 rounded-sm p-6 flex flex-col md:flex-row gap-6 items-center justify-between">
            <!-- Search SKU/Name -->
            <div class="relative w-full md:w-80">
                <input 
                    v-model="filters.keyword" 
                    @input="onSearch" 
                    type="text" 
                    placeholder="Cari SKU atau Nama barang..." 
                    class="w-full text-xs border border-gray-300 rounded-sm px-4 py-2.5 focus:outline-none focus:border-[#FF1F8F] transition-colors bg-white" 
                />
            </div>

            <!-- Category Selector Buttons -->
            <div class="flex flex-wrap gap-2 w-full md:w-auto justify-start md:justify-end">
                <button 
                    v-for="cat in ['Atasan', 'Bawahan', 'Gamis']" 
                    :key="cat" 
                    @click="setCategory(cat)" 
                    :class="filters.category === cat ? 'border-[#FF1F8F] bg-pink-50/15 text-[#FF1F8F] font-bold' : 'border-gray-200 hover:border-gray-300 text-gray-600'" 
                    class="text-xs border px-3 py-2 rounded-sm transition-all cursor-pointer bg-white"
                >
                    {{ cat }}
                </button>
                <button 
                    @click="setCategory('')" 
                    :class="filters.category === '' ? 'border-[#FF1F8F] bg-pink-50/15 text-[#FF1F8F] font-bold' : 'border-gray-200 hover:border-gray-300 text-gray-600'" 
                    class="text-xs border px-3 py-2 rounded-sm transition-all cursor-pointer bg-white"
                >
                    Semua
                </button>
            </div>
        </div>

        <!-- Low Stock Table -->
        <div class="bg-white border border-gray-200 rounded-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold">
                            <th class="py-3.5 px-6">SKU</th>
                            <th class="py-3.5 px-6">Nama Produk</th>
                            <th class="py-3.5 px-6">Kategori</th>
                            <th class="py-3.5 px-6 text-right">Harga Satuan</th>
                            <th class="py-3.5 px-6 text-center">Stok Fisik</th>
                            <th class="py-3.5 px-6 text-center">Operasional</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="products.length === 0" class="border-b border-gray-100">
                            <td colspan="6" class="py-16 text-center text-gray-400">Tidak ada produk dengan stok rendah ditemukan.</td>
                        </tr>
                        <tr v-else v-for="product in products" :key="product.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <img :src="product.image_url || '/logo.jpg'" class="w-8 h-8 rounded-sm object-cover border border-gray-200" />
                                    <span class="font-mono font-bold">{{ product.sku }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-semibold text-gray-900">{{ product.name }}</td>
                            <td class="py-4 px-6 text-gray-500">{{ product.category?.name }}</td>
                            <td class="py-4 px-6 text-right font-mono font-semibold">Rp {{ formatNumber(product.price) }}</td>
                            <td class="py-4 px-6 text-center">
                                <span 
                                    :class="product.stock === 0 ? 'bg-red-100 text-red-700 font-bold border border-red-200' : 'bg-amber-100 text-amber-700 font-bold border border-amber-200'" 
                                    class="px-2.5 py-1 rounded-sm font-mono text-[10px]"
                                >
                                    {{ product.stock === 0 ? 'HABIS (0 pcs)' : 'KRITIS (1 pcs)' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <button 
                                    @click="openEditStockModal(product)" 
                                    class="bg-gray-900 hover:bg-black text-white text-[10px] font-bold px-3 py-1.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer"
                                >
                                    Edit Stok
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="pagination.last_page > 1" class="p-6 border-t border-gray-100 flex justify-between items-center bg-gray-50">
                <span class="text-[10px] text-gray-400">Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
                <div class="flex space-x-2">
                    <button 
                        :disabled="pagination.current_page === 1" 
                        @click="changePage(pagination.current_page - 1)" 
                        class="border border-gray-300 hover:border-gray-400 bg-white text-gray-700 text-[10px] font-bold px-3 py-1.5 rounded-sm transition-colors cursor-pointer disabled:opacity-40"
                    >
                        Sebelumnya
                    </button>
                    <button 
                        :disabled="pagination.current_page === pagination.last_page" 
                        @click="changePage(pagination.current_page + 1)" 
                        class="border border-gray-300 hover:border-gray-400 bg-white text-gray-700 text-[10px] font-bold px-3 py-1.5 rounded-sm transition-colors cursor-pointer disabled:opacity-40"
                    >
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Stok Modal Backdrop -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white border border-gray-200 rounded-sm w-full max-w-sm p-6 shadow-xl space-y-6 animate-fadeIn">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-3">
                    Tambah / Edit Stok Cepat
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider">SKU / Nama Produk</span>
                        <span class="block font-mono font-bold text-gray-900 text-xs mt-1">{{ form.sku }}</span>
                        <span class="block text-xs text-gray-600 mt-0.5">{{ form.name }}</span>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Jumlah Persediaan Stok Baru</label>
                        <input 
                            v-model="form.stock" 
                            type="number" 
                            min="0" 
                            required 
                            placeholder="Contoh: 10" 
                            class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F] bg-white font-mono" 
                        />
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="border border-gray-300 hover:bg-gray-50 text-gray-700 text-[10px] font-bold px-4 py-2 rounded-sm transition-colors cursor-pointer"
                        >
                            Batalkan
                        </button>
                        <button 
                            type="submit" 
                            :disabled="submitLoading" 
                            class="bg-[#FF1F8F] hover:bg-[#D91678] text-white text-[10px] font-bold px-4 py-2 rounded-sm uppercase tracking-wider transition-colors cursor-pointer disabled:opacity-40"
                        >
                            <span v-if="submitLoading">Menyimpan...</span>
                            <span v-else>Perbarui Stok</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Toast Notification -->
        <div v-if="toast" class="fixed bottom-4 right-4 z-[999] bg-gray-900 text-white text-xs font-semibold px-4 py-3 rounded-sm shadow-lg flex items-center space-x-2">
            <span>{{ toast }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const products = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 1
});

const filters = reactive({
    keyword: '',
    category: '',
    low_stock_threshold: 2, // Hanya mengambil stok < 2
    page: 1
});

// Modal & Form States
const showModal = ref(false);
const submitLoading = ref(false);
const toast = ref('');
let toastTimer = null;

const form = reactive({
    id: null,
    category_id: '',
    sku: '',
    name: '',
    price: 0,
    stock: 0,
    image_url: ''
});

const fetchProducts = async () => {
    try {
        const response = await axios.get('/api/admin/products', {
            params: filters
        });
        products.value = response.data.data;
        pagination.value = response.data;
    } catch (e) {
        showToast('Gagal memuat daftar produk.');
    }
};

let debounceTimer = null;
const onSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filters.page = 1;
        fetchProducts();
    }, 300);
};

const setCategory = (catName) => {
    filters.category = catName;
    filters.page = 1;
    fetchProducts();
};

const changePage = (page) => {
    filters.page = page;
    fetchProducts();
};

// Modal Operations
const openEditStockModal = (product) => {
    form.id = product.id;
    form.category_id = product.category_id;
    form.sku = product.sku;
    form.name = product.name;
    form.price = Math.round(product.price);
    form.stock = product.stock;
    form.image_url = product.image_url || '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = async () => {
    if (submitLoading.value) return;
    submitLoading.value = true;

    try {
        await axios.put(`/api/admin/products/${form.id}`, {
            category_id: form.category_id,
            sku: form.sku,
            name: form.name,
            price: form.price,
            stock: form.stock,
            image_url: form.image_url
        });
        
        showToast('Stok produk berhasil diperbarui.');
        showModal.value = false;
        fetchProducts();
    } catch (error) {
        const msg = error.response?.data?.message || 'Gagal menyimpan persediaan stok.';
        showToast(msg);
    } finally {
        submitLoading.value = false;
    }
};

const showToast = (msg) => {
    toast.value = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.value = '';
    }, 3000);
};

const formatNumber = (num) => Number(num).toLocaleString('id-ID');

onMounted(() => {
    fetchProducts();
});
</script>

<style scoped>
.animate-fadeIn {
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.97) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
