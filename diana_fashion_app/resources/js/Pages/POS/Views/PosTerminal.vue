<template>
    <div class="min-h-screen bg-[#F9FAFB] flex flex-col font-sans text-[#111827]">
        <!-- Navbar Kasir -->
        <header class="bg-white border-b border-gray-200 h-16 flex justify-between items-center px-6 sticky top-0 z-50">
            <div class="flex items-center space-x-3">
                <img src="/logo.jpg" alt="Logo" class="h-9 w-9 rounded-full object-cover border border-gray-100" />
                <span class="text-base font-extrabold text-[#FF1F8F] tracking-tight uppercase">Diana POS</span>
                <span class="text-[10px] bg-gray-100 text-gray-500 font-mono px-2 py-0.5 rounded-sm">Kasir: {{ kasirName }}</span>
            </div>

            <!-- Tab Menu -->
            <div class="flex space-x-1 bg-gray-100 p-1 rounded-sm">
                <button @click="activeTab = 'terminal'" :class="activeTab === 'terminal' ? 'bg-white text-gray-900' : 'text-gray-500 hover:text-gray-900'" class="text-xs font-semibold px-4 py-1.5 rounded-sm transition-colors cursor-pointer">
                    Terminal Kasir
                </button>
                <button @click="activeTab = 'queue'" :class="activeTab === 'queue' ? 'bg-white text-gray-900' : 'text-gray-500 hover:text-gray-900'" class="text-xs font-semibold px-4 py-1.5 rounded-sm transition-colors cursor-pointer relative">
                    Antrean Online
                    <span v-if="queueCount > 0" class="absolute -top-1 -right-1 bg-[#FF1F8F] text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full">
                        {{ queueCount }}
                    </span>
                </button>
            </div>

            <div>
                <button @click="logout" class="text-xs font-semibold text-gray-500 hover:text-red-500 transition-colors cursor-pointer">
                    Keluar Sesi
                </button>
            </div>
        </header>

        <!-- Main Workspace (Tab: Terminal) -->
        <div v-if="activeTab === 'terminal'" class="flex-grow grid grid-cols-1 lg:grid-cols-12 gap-6 p-6 overflow-hidden">
            
            <!-- Area Kiri: Pencarian & Hasil Cari Produk (col-span-8 - Golden Ratio Area 62%) -->
            <div class="lg:col-span-8 flex flex-col justify-between space-y-6">
                <div class="bg-white border border-gray-200 rounded-sm p-6 flex flex-col flex-grow">
                    <!-- Search Input -->
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Cari Produk (SKU / Nama)</label>
                        <div class="relative">
                            <input v-model="searchKeyword" @input="onSearchInput" type="text" placeholder="Masukkan SKU atau Nama barang..." class="w-full text-sm border border-gray-300 rounded-sm px-4 py-2.5 focus:outline-none focus:border-[#FF1F8F] transition-colors" />
                            <span v-if="searching" class="absolute right-3 top-3 animate-spin rounded-full h-4 w-4 border-b-2 border-[#FF1F8F]"></span>
                        </div>
                        <span class="text-[10px] text-gray-400 mt-1 block">SKU menggunakan prefix matching (indeks B-Tree), Nama menggunakan FULLTEXT search.</span>
                    </div>

                    <!-- Kategori Tab Selector -->
                    <div class="mb-6 flex flex-wrap gap-2 items-center">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider mr-2">Kategori:</span>
                        <button v-for="cat in ['', 'Atasan', 'Bawahan', 'Gamis']" :key="cat" @click="setCategory(cat)" :class="selectedCategory === cat ? 'bg-[#FF1F8F] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'" class="text-[10px] font-bold px-3 py-1.5 rounded-sm transition-all cursor-pointer">
                            {{ cat === '' ? 'Semua' : cat }}
                        </button>
                    </div>

                    <!-- Search Results Grid -->
                    <div class="flex-grow overflow-y-auto max-h-[480px]">
                        <div v-if="searchResults.length === 0" class="h-full flex items-center justify-center border-2 border-dashed border-gray-100 rounded-sm py-16">
                            <p class="text-sm text-gray-400">Tidak ada produk yang cocok dengan pencarian atau kategori ini.</p>
                        </div>

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            <div v-for="product in searchResults" :key="product.id" class="bg-white border border-gray-200 rounded-sm p-4 flex flex-col justify-between hover:border-gray-300 transition-colors">
                                <div>
                                    <span class="text-[9px] font-bold font-mono text-gray-400 uppercase">{{ product.category?.name }}</span>
                                    <h4 class="text-xs font-semibold text-gray-900 truncate mt-0.5">{{ product.name }}</h4>
                                    <span class="text-[10px] text-gray-400 font-mono mt-0.5 block">SKU: {{ product.sku }}</span>
                                    <span class="text-[10px] text-gray-500 font-mono mt-1 block">Stok Tersisa: {{ product.stock }} pcs</span>
                                </div>
                                <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between items-center">
                                    <span class="text-xs font-bold text-gray-900">Rp {{ formatNumber(product.price) }}</span>
                                    <button @click="addToCart(product)" :disabled="product.stock <= 0" class="bg-white border border-[#FF1F8F] text-[#FF1F8F] hover:bg-[#FF1F8F] hover:text-white disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-[#FF1F8F] text-[10px] font-bold px-2.5 py-1.5 rounded-sm transition-colors cursor-pointer">
                                        {{ product.stock > 0 ? '+ Tambah' : 'Habis' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Area Kanan: Keranjang Transaksi & Pembayaran (col-span-4 - Golden Ratio Area 38%) -->
            <div class="lg:col-span-4 bg-white border border-gray-200 rounded-sm p-6 flex flex-col justify-between h-fit sticky top-24">
                <div>
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Keranjang Transaksi</h3>
                        
                        <!-- Utility Buttons (Hold/Recall) -->
                        <div class="flex space-x-2">
                            <button @click="holdCart" :disabled="cart.length === 0" class="text-[10px] font-bold border border-gray-300 hover:border-[#FF1F8F] hover:text-[#FF1F8F] text-gray-600 px-2 py-1 rounded-sm transition-colors cursor-pointer disabled:opacity-40">
                                Parkir (Hold)
                            </button>
                            <button @click="recallCart" class="text-[10px] font-bold bg-[#FF1F8F] hover:bg-[#D91678] text-white px-2 py-1 rounded-sm transition-colors cursor-pointer">
                                Panggil (Recall)
                            </button>
                        </div>
                    </div>

                    <!-- Cart Item List -->
                    <div class="space-y-3 max-h-[260px] overflow-y-auto pr-1 mb-6 border-b border-gray-100 pb-4">
                        <div v-if="cart.length === 0" class="text-center py-8 text-xs text-gray-400">
                            Keranjang kosong.
                        </div>
                        
                        <div v-for="item in cart" :key="item.id" class="flex justify-between items-center text-xs border-b border-gray-50 pb-2 last:border-0">
                            <div class="flex-grow pr-3">
                                <h4 class="font-semibold text-gray-900 truncate max-w-[140px]">{{ item.name }}</h4>
                                <span class="text-[10px] text-gray-400 block font-mono">Rp {{ formatNumber(item.price) }}</span>
                            </div>
                            
                            <!-- Qty Control -->
                            <div class="flex items-center border border-gray-200 rounded-sm mr-3">
                                <button @click="updateQty(item.id, item.qty - 1)" class="px-1.5 py-0.5 bg-gray-50 hover:bg-gray-100 font-bold">-</button>
                                <span class="px-2 py-0.5 font-mono text-[10px]">{{ item.qty }}</span>
                                <button @click="updateQty(item.id, item.qty + 1)" class="px-1.5 py-0.5 bg-gray-50 hover:bg-gray-100 font-bold">+</button>
                            </div>

                            <button @click="removeItem(item.id)" class="text-red-500 hover:text-red-600 font-semibold text-[10px]">Hapus</button>
                        </div>
                    </div>

                    <!-- Ringkasan Nilai -->
                    <div class="space-y-2 border-b border-gray-100 pb-4 mb-4">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Total Item</span>
                            <span class="font-mono">{{ totalItems }} pcs</span>
                        </div>
                        <div class="flex justify-between text-xs font-bold text-gray-900">
                            <span>Grand Total</span>
                            <span class="font-mono text-base text-[#FF1F8F]">Rp {{ formatNumber(totalPrice) }}</span>
                        </div>
                    </div>

                    <!-- Identitas Pembeli (Autocomplete) -->
                    <div class="space-y-3 border-b border-gray-100 pb-4 mb-4 relative">
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider">Identitas Pembeli</label>
                        <div class="relative">
                            <input v-model="customerQuery" @input="onCustomerSearchInput" type="text" placeholder="Ketik nama pembeli..." class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F] transition-colors" />
                            <span v-if="searchingCustomers" class="absolute right-3 top-3.5 animate-spin rounded-full h-3 w-3 border-b-2 border-[#FF1F8F]"></span>
                        </div>

                        <!-- Autocomplete Floating Dropdown -->
                        <div v-if="showCustomerSuggestions && customerSuggestions.length > 0" class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-sm shadow-lg max-h-40 overflow-y-auto z-50">
                            <div v-for="cust in customerSuggestions" :key="cust.id" @click="selectCustomer(cust)" class="px-3 py-2 text-xs hover:bg-gray-50 transition-colors cursor-pointer flex justify-between items-center border-b border-gray-50 last:border-b-0">
                                <div>
                                    <span class="font-semibold text-gray-900 block">{{ cust.name }}</span>
                                    <span class="text-[9px] text-gray-400 block">{{ cust.email }}</span>
                                </div>
                                <span class="text-[8px] bg-pink-50 text-[#FF1F8F] font-bold px-1.5 py-0.5 rounded-sm uppercase tracking-wider">Hubungkan</span>
                            </div>
                        </div>

                        <!-- Member Connected Alert -->
                        <div v-if="selectedCustomer" class="mt-2 text-[10px] text-green-700 font-semibold flex items-center justify-between bg-green-50 px-3 py-2 rounded-sm border-l-2 border-green-500">
                            <span class="flex items-center">
                                <span class="mr-1.5">✓</span> Member Terhubung: {{ selectedCustomer.name }}
                            </span>
                            <button type="button" @click="clearSelectedCustomer" class="text-red-500 hover:text-red-600 font-bold underline transition-colors cursor-pointer">Batal</button>
                        </div>
                    </div>

                    <!-- Pilihan Pembayaran & Checkout -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-2">Metode Pembayaran</label>
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <button v-for="method in ['Cash', 'QRIS', 'Transfer']" :key="method" @click="paymentMethod = method" :class="paymentMethod === method ? 'border-[#FF1F8F] bg-pink-50 text-[#FF1F8F]' : 'border-gray-200 hover:border-gray-300 text-gray-700 bg-white'" class="text-[10px] font-bold border py-2 rounded-sm transition-all cursor-pointer text-center">
                                {{ method }}
                            </button>
                        </div>

                        <button :disabled="cart.length === 0 || checkoutLoading" @click="processCheckout" class="w-full bg-[#FF1F8F] hover:bg-[#D91678] disabled:opacity-40 text-white text-xs font-bold py-3 rounded-sm uppercase tracking-wider transition-colors cursor-pointer text-center">
                            <span v-if="checkoutLoading">Memproses...</span>
                            <span v-else>Bayar Sekarang ({{ paymentMethod }})</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Workspace (Tab: Queue Antrean Online) -->
        <div v-if="activeTab === 'queue'" class="flex-grow p-6">
            <div class="bg-white border border-gray-200 rounded-sm p-6">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-6">Antrean Pesanan E-Commerce (Web Pending)</h3>

                <div v-if="queueLoading" class="flex justify-center items-center py-16">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#FF1F8F]"></div>
                </div>

                <div v-else-if="queue.length === 0" class="text-center py-16 text-sm text-gray-400">
                    Tidak ada antrean pesanan online yang tertunda.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold">
                                <th class="py-3 px-4">No. Nota</th>
                                <th class="py-3 px-4">Pelanggan</th>
                                <th class="py-3 px-4">Tanggal Pesan</th>
                                <th class="py-3 px-4">Rincian Belanja</th>
                                <th class="py-3 px-4 text-right">Total Tagihan</th>
                                <th class="py-3 px-4 text-center">Aksi Operasional</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in queue" :key="order.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-4 font-mono font-bold">{{ order.increment_id }}</td>
                                <td class="py-4 px-4">{{ order.user?.name }}</td>
                                <td class="py-4 px-4 font-mono text-gray-400">{{ formatDate(order.created_at) }}</td>
                                <td class="py-4 px-4">
                                    <div class="space-y-1">
                                        <div v-for="item in order.items" :key="item.id" class="text-[10px] text-gray-600">
                                            - {{ item.product?.name }} (x{{ item.qty }})
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-right font-mono font-semibold">Rp {{ formatNumber(order.total_price) }}</td>
                                <td class="py-4 px-4 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <button @click="validateQueue(order.id, 'acc')" class="bg-[#FF1F8F] hover:bg-[#D91678] text-white text-[10px] font-bold px-3 py-1.5 rounded-sm transition-colors cursor-pointer">
                                            Setujui (ACC)
                                        </button>
                                        <button @click="validateQueue(order.id, 'reject')" class="bg-white border border-gray-300 hover:border-red-500 hover:text-red-500 text-gray-700 text-[10px] font-bold px-3 py-1.5 rounded-sm transition-colors cursor-pointer">
                                            Tolak (Reject)
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div v-if="notification" class="fixed bottom-4 right-4 z-[999] bg-gray-900 text-white text-xs font-semibold px-4 py-3 rounded-sm border-l-4 border-[#FF1F8F] flex items-center space-x-2">
            <span>{{ notification }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const activeTab = ref('terminal');
const kasirName = ref('Staf Toko');

// State Terminal
const searchKeyword = ref('');
const searching = ref(false);
const searchResults = ref([]);
const cart = ref([]);
const paymentMethod = ref('Cash');
const checkoutLoading = ref(false);

// State Identitas Pembeli (Autocomplete)
const customerQuery = ref('');
const selectedCustomer = ref(null); // Objek member yang dipilih
const customerSuggestions = ref([]);
const showCustomerSuggestions = ref(false);
const searchingCustomers = ref(false);

// State Queue
const queue = ref([]);
const queueLoading = ref(false);

// State Toast
const notification = ref('');
let notificationTimer = null;

const totalItems = computed(() => cart.value.reduce((sum, item) => sum + item.qty, 0));
const totalPrice = computed(() => cart.value.reduce((sum, item) => sum + (item.price * item.qty), 0));
const queueCount = computed(() => queue.value.length);

// Debounce untuk search di POS (Resolusi #5.2)
const selectedCategory = ref('');

const fetchPosProducts = async () => {
    searching.value = true;
    try {
        const response = await axios.get('/api/pos/products/search', {
            params: {
                keyword: searchKeyword.value,
                category: selectedCategory.value
            }
        });
        searchResults.value = response.data;
    } catch (error) {
        console.error('Gagal memuat produk POS:', error);
    } finally {
        searching.value = false;
    }
};

let debounceTimer = null;
const onSearchInput = () => {
    clearTimeout(debounceTimer);
    searching.value = true;
    
    debounceTimer = setTimeout(() => {
        fetchPosProducts();
    }, 300);
};

const setCategory = (cat) => {
    selectedCategory.value = cat;
    fetchPosProducts();
};

const addToCart = (product) => {
    const existing = cart.value.find(item => item.id === product.id);
    if (existing) {
        if (existing.qty >= product.stock) {
            showNotification(`Stok untuk "${product.name}" terbatas.`);
            return;
        }
        existing.qty += 1;
    } else {
        cart.value.push({
            id: product.id,
            sku: product.sku,
            name: product.name,
            price: product.price,
            stock: product.stock,
            qty: 1 // Resolusi #3
        });
    }
    showNotification(`"${product.name}" ditambahkan.`);
};

const updateQty = (id, qty) => {
    const item = cart.value.find(i => i.id === id);
    if (item) {
        if (qty > item.stock) {
            showNotification(`Stok "${item.name}" terbatas.`);
            return;
        }
        if (qty < 1) return;
        item.qty = qty;
    }
};

const removeItem = (id) => {
    cart.value = cart.value.filter(i => i.id !== id);
};

const holdCart = () => {
    localStorage.setItem('diana_pos_held_cart', JSON.stringify({
        items: cart.value,
        timestamp: Date.now()
    }));
    cart.value = [];
    showNotification('Transaksi diparkir (Hold).');
};

const recallCart = async () => {
    const stored = localStorage.getItem('diana_pos_held_cart');
    if (!stored) {
        showNotification('Tidak ada transaksi diparkir.');
        return;
    }

    try {
        const parsed = JSON.parse(stored);
        
        // Panggil API validasi harga & stok terbaru saat dipanggil ulang (Recall Held Cart)
        const response = await axios.post('/api/pos/cart/validate-prices', {
            items: parsed.items.map(item => ({ id: item.id, qty: item.qty }))
        });

        cart.value = response.data.items;
        
        if (response.data.has_changes) {
            showNotification('Perhatian: Harga produk telah berubah sejak disimpan.');
        } else if (response.data.insufficient_stock) {
            showNotification('Perhatian: Beberapa stok produk tidak mencukupi.');
        } else {
            showNotification('Transaksi dipulihkan (Recall).');
        }

        // Hapus dari parkir
        localStorage.removeItem('diana_pos_held_cart');

    } catch (error) {
        console.error('Gagal memulihkan held cart:', error);
        showNotification('Gagal memulihkan held cart.');
    }
};

const processCheckout = async () => {
    if (checkoutLoading.value) return;
    checkoutLoading.value = true;

    try {
        const payload = {
            items: cart.value.map(i => ({ id: i.id, qty: i.qty })),
            payment_method: paymentMethod.value,
            customer_id: selectedCustomer.value ? selectedCustomer.value.id : null,
            customer_name: !selectedCustomer.value && customerQuery.value.trim() !== '' ? customerQuery.value.trim() : null
        };

        await axios.post('/api/pos/checkout', payload);
        
        showNotification('Pembayaran berhasil diselesaikan!');

        // Fire & Forget: resetPosState secara instan tanpa reload halaman
        resetPosState();

    } catch (error) {
        console.error('Gagal memproses POS checkout:', error);
        const errMsg = error.response?.data?.message || 'Transaksi gagal diproses.';
        showNotification(errMsg);
    } finally {
        checkoutLoading.value = false;
    }
};

// Reset State POS (Fire & Forget)
const resetPosState = () => {
    cart.value = [];
    searchKeyword.value = '';
    searchResults.value = [];
    paymentMethod.value = 'Cash';
    customerQuery.value = '';
    selectedCustomer.value = null;
    customerSuggestions.value = [];
    showCustomerSuggestions.value = false;
};

let customerDebounceTimer = null;
const onCustomerSearchInput = () => {
    if (selectedCustomer.value) {
        selectedCustomer.value = null;
    }

    clearTimeout(customerDebounceTimer);
    
    if (customerQuery.value.trim() === '') {
        customerSuggestions.value = [];
        showCustomerSuggestions.value = false;
        return;
    }

    searchingCustomers.value = true;
    customerDebounceTimer = setTimeout(async () => {
        try {
            const response = await axios.get('/api/pos/customers', {
                params: { search: customerQuery.value }
            });
            customerSuggestions.value = response.data;
            showCustomerSuggestions.value = true;
        } catch (error) {
            console.error('Gagal memuat pelanggan terdaftar:', error);
        } finally {
            searchingCustomers.value = false;
        }
    }, 300);
};

const selectCustomer = (cust) => {
    selectedCustomer.value = cust;
    customerQuery.value = cust.name;
    showCustomerSuggestions.value = false;
    customerSuggestions.value = [];
};

const clearSelectedCustomer = () => {
    selectedCustomer.value = null;
    customerQuery.value = '';
    showCustomerSuggestions.value = false;
    customerSuggestions.value = [];
};

// Queue Operasional
const fetchQueue = async () => {
    queueLoading.value = true;
    try {
        const response = await axios.get('/api/pos/queue');
        queue.value = response.data;
    } catch (error) {
        console.error('Gagal mengambil antrean online:', error);
    } finally {
        queueLoading.value = false;
    }
};

const validateQueue = async (orderId, action) => {
    try {
        const response = await axios.post(`/api/pos/queue/${orderId}/validate`, { action });
        showNotification(response.data.message);
        
        // Refresh antrean
        fetchQueue();
    } catch (error) {
        console.error('Gagal memproses antrean:', error);
    }
};

// User Profile Retrieval
const checkUser = async () => {
    try {
        const response = await axios.get('/api/auth/user');
        kasirName.value = response.data.user.name;
    } catch (e) {
        kasirName.value = 'Kasir Toko';
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

const showNotification = (msg) => {
    notification.value = msg;
    clearTimeout(notificationTimer);
    notificationTimer = setTimeout(() => {
        notification.value = '';
    }, 3000);
};

const formatNumber = (num) => Number(num).toLocaleString('id-ID');

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    checkUser();
    fetchQueue();
    fetchPosProducts();
});
</script>
