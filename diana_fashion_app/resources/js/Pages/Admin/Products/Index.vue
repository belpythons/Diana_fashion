<template>
    <div class="space-y-6">
        <!-- Header & Action Tombol -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Inventaris Barang</h3>
                <p class="text-xs text-gray-500 mt-1">Mengelola persediaan stok dan harga retail produk Diana Fashion.</p>
            </div>
            
            <button @click="openCreateModal" class="bg-[#FF1F8F] hover:bg-[#D91678] text-white text-xs font-bold px-4 py-2.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer text-center">
                + Tambah Produk Baru
            </button>
        </div>

        <!-- Filter Area -->
        <div class="bg-white border border-gray-200 rounded-sm p-6 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="relative w-full md:w-80">
                <input v-model="filters.keyword" @input="onSearch" type="text" placeholder="Cari SKU atau Nama barang..." class="w-full text-xs border border-gray-300 rounded-sm px-4 py-2 focus:outline-none focus:border-[#FF1F8F] transition-colors" />
                <span class="text-[9px] text-gray-400 mt-1 block">SKU: prefix match, Nama: FULLTEXT search</span>
            </div>

            <div class="flex items-center space-x-4 w-full md:w-auto justify-end">
                <label class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer">
                    <input type="checkbox" v-model="filters.low_stock" @change="fetchProducts" class="mr-2 accent-[#FF1F8F]" />
                    Stok Menipis (< 5 pcs)
                </label>
            </div>
        </div>

        <!-- Low Stock Banner Alert -->
        <div v-if="lowStockCount > 0" class="bg-red-50 border-l-4 border-red-500 p-4 text-red-700 text-xs font-semibold rounded-r-sm flex justify-between items-center">
            <span>Perhatian: Terdapat {{ lowStockCount }} produk dengan persediaan stok kritis di bawah 5 pcs!</span>
            <button @click="filterLowStock" class="underline text-red-800 hover:text-red-900 cursor-pointer">Lihat Semua →</button>
        </div>

        <!-- Product Table Grid -->
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
                            <td colspan="6" class="py-16 text-center text-gray-400">Tidak ada produk ditemukan.</td>
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
                                <span :class="product.stock < 5 ? 'bg-red-100 text-red-700 font-bold' : 'bg-gray-100 text-gray-600'" class="px-2 py-0.5 rounded-sm font-mono text-[10px]">
                                    {{ product.stock }} pcs
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex justify-center space-x-3">
                                    <button @click="openEditModal(product)" class="text-blue-500 hover:text-blue-600 font-semibold cursor-pointer">Ubah</button>
                                    <button @click="deleteProduct(product)" class="text-red-500 hover:text-red-600 font-semibold cursor-pointer">Hapus</button>
                                </div>
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

        <!-- Tambah / Edit Modal Backdrop -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white border border-gray-200 rounded-sm w-full max-w-md p-6 shadow-xl space-y-6">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-3">
                    {{ isEditMode ? 'Perbarui Data Produk' : 'Registrasi Produk Baru' }}
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Kategori Barang</label>
                        <select v-model="form.category_id" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]">
                            <option value="">Pilih Kategori...</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nomor SKU</label>
                            <input v-model="form.sku" type="text" placeholder="Contoh: ATS-001" required :disabled="isEditMode" class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F] disabled:bg-gray-50 disabled:text-gray-400" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Persediaan Stok</label>
                            <input v-model="form.stock" type="number" min="0" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nama Barang</label>
                        <input v-model="form.name" type="text" placeholder="Masukkan nama komplit produk..." required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Harga Eceran (Rupiah)</label>
                        <input v-model="form.price" type="number" min="0" placeholder="100000" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Unggah Gambar (Rekomendasi Supabase)</label>
                            <input type="file" @change="onFileChange" accept="image/*" class="w-full text-xs border border-gray-300 rounded-sm px-3 py-1.5 focus:outline-none focus:border-[#FF1F8F] file:mr-3 file:py-1 file:px-2 file:rounded-sm file:border-0 file:text-[9px] file:font-semibold file:bg-pink-50 file:text-[#FF1F8F] hover:file:bg-pink-100 cursor-pointer" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Preview Gambar</label>
                            <div class="flex items-center space-x-3">
                                <img :src="imagePreview || form.image_url || '/logo.jpg'" class="w-10 h-10 rounded-sm object-cover border border-gray-200" />
                                <span class="text-[9px] text-gray-400 font-mono leading-tight">Mendukung file JPG, PNG, atau WEBP.</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Tautan Gambar Manual (Opsional)</label>
                        <input v-model="form.image_url" type="url" placeholder="https://..." class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
                        <button type="button" @click="closeModal" class="border border-gray-300 hover:bg-gray-50 text-gray-700 text-[10px] font-bold px-4 py-2 rounded-sm transition-colors cursor-pointer">
                            Batalkan
                        </button>
                        <button type="submit" :disabled="submitLoading" class="bg-[#FF1F8F] hover:bg-[#D91678] text-white text-[10px] font-bold px-4 py-2 rounded-sm uppercase tracking-wider transition-colors cursor-pointer disabled:opacity-40">
                            <span v-if="submitLoading">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
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
const categories = ref([]);
const lowStockCount = ref(0);

const filters = reactive({
    keyword: '',
    low_stock: false,
    page: 1
});

const pagination = ref({
    current_page: 1,
    last_page: 1
});

// Modal State
const showModal = ref(false);
const isEditMode = ref(false);
const toast = ref('');
let toastTimer = null;

const submitLoading = ref(false);
const selectedFile = ref(null);
const imagePreview = ref('');

const form = reactive({
    id: null,
    category_id: '',
    sku: '',
    name: '',
    stock: 0,
    price: 0,
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
        showToast('Gagal memuat katalog produk.');
    }
};

const checkLowStock = async () => {
    try {
        const response = await axios.get('/api/admin/products', {
            params: { low_stock: 'true' }
        });
        lowStockCount.value = response.data.total;
    } catch (e) {
        console.error(e);
    }
};

const fetchCategories = async () => {
    try {
        categories.value = [
            { id: 1, name: 'Atasan' },
            { id: 2, name: 'Bawahan' },
            { id: 3, name: 'Gamis' }
        ];
    } catch (e) {
        console.error(e);
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

const filterLowStock = () => {
    filters.low_stock = true;
    filters.page = 1;
    fetchProducts();
};

const changePage = (page) => {
    filters.page = page;
    fetchProducts();
};

// Handle File Change & Preview
const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedFile.value = file;
        imagePreview.value = URL.createObjectURL(file);
    } else {
        selectedFile.value = null;
        imagePreview.value = '';
    }
};

// Modal Operations
const openCreateModal = () => {
    isEditMode.value = false;
    form.id = null;
    form.category_id = '';
    form.sku = '';
    form.name = '';
    form.stock = 0;
    form.price = 0;
    form.image_url = '';
    selectedFile.value = null;
    imagePreview.value = '';
    showModal.value = true;
};

const openEditModal = (product) => {
    isEditMode.value = true;
    form.id = product.id;
    form.category_id = product.category_id;
    form.sku = product.sku;
    form.name = product.name;
    form.stock = product.stock;
    form.price = Math.round(product.price);
    form.image_url = product.image_url || '';
    selectedFile.value = null;
    imagePreview.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = async () => {
    if (submitLoading.value) return;
    submitLoading.value = true;

    try {
        const formData = new FormData();
        formData.append('category_id', form.category_id);
        formData.append('sku', form.sku);
        formData.append('name', form.name);
        formData.append('stock', form.stock);
        formData.append('price', form.price);
        
        if (form.image_url) {
            formData.append('image_url', form.image_url);
        }
        if (selectedFile.value) {
            formData.append('image', selectedFile.value);
        }

        if (isEditMode.value) {
            formData.append('_method', 'PUT'); // Metode override Laravel untuk upload file di update PUT
            await axios.post(`/api/admin/products/${form.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            showToast('Produk berhasil diperbarui.');
        } else {
            await axios.post('/api/admin/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            showToast('Produk baru berhasil ditambahkan.');
        }
        showModal.value = false;
        fetchProducts();
        checkLowStock();
    } catch (error) {
        const msg = error.response?.data?.message || 'Gagal menyimpan perubahan.';
        showToast(msg);
    } finally {
        submitLoading.value = false;
    }
};

const deleteProduct = async (product) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus produk "${product.name}"?`)) return;

    try {
        await axios.delete(`/api/admin/products/${product.id}`);
        showToast('Produk berhasil dihapus.');
        fetchProducts();
        checkLowStock();
    } catch (error) {
        const msg = error.response?.data?.message || 'Gagal menghapus produk.';
        showToast(msg);
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
    checkLowStock();
    fetchCategories();
});
</script>
