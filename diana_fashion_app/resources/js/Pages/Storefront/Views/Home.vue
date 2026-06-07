<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative">
        <!-- 1. Bento Grid Promo / AI Recommendation (SKILL.md 6.3 - Layout 8:4) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-12">
            <!-- Bento Promo: Hero Banner Premium (Col-8) -->
            <div class="lg:col-span-8 bg-gray-950 rounded-sm p-8 sm:p-10 text-white flex flex-col justify-between min-h-[320px] relative overflow-hidden">
                <!-- Backdrop Grid Geometric Magenta (Consistent with LoginRegister) -->
                <div class="absolute inset-0 opacity-10 bg-[linear-gradient(to_right,#FF1F8F_1px,transparent_1px),linear-gradient(to_bottom,#FF1F8F_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>
                <!-- Subtle Radial Light Glow -->
                <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-[#FF1F8F]/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10 max-w-lg">
                    <span class="text-[9px] font-mono font-bold uppercase tracking-widest text-[#FF1F8F] bg-[#FF1F8F]/10 border border-[#FF1F8F]/20 px-3 py-1 rounded-sm">
                        Koleksi Eksklusif 2026
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black mt-4 tracking-tight leading-none uppercase">
                        Gaya Premium &<br>Autentik Untuk Anda
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-400 mt-4 leading-relaxed">
                        Temukan mahakarya fashion pilihan berkualitas tinggi dengan bahan pilihan terbaik, dirancang eksklusif untuk melengkapi penampilan elegan Anda dalam setiap momen istimewa.
                    </p>
                </div>
                <div class="mt-8 relative z-10">
                    <button @click="scrollToProducts" class="bg-[#FF1F8F] hover:bg-[#D91678] text-white text-xs font-bold px-6 py-3 rounded-sm uppercase tracking-wider transition-colors cursor-pointer border-0">
                        Jelajahi Katalog
                    </button>
                </div>
            </div>

            <!-- ARIMA AI Recommendation Card (Side Panel, Col-4) -->
            <div class="lg:col-span-4 bg-[#F9FAFB] border border-gray-200 border-t-4 border-t-[#FF1F8F] rounded-sm p-6 flex flex-col justify-between min-h-[320px]">
                <div>
                    <!-- Header with design system unfilled trendline SVG -->
                    <div class="flex items-center space-x-2">
                        <svg class="h-4.5 w-4.5 text-[#FF1F8F]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>
                        <span class="text-xs font-bold text-gray-900 uppercase tracking-widest font-mono">Trending AI Rekomendasi</span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1.5 leading-normal">Prediksi produk terlaris minggu ini dihitung secara berkala berbasis algoritma autoregresif ARIMA.</p>
                    
                    <div class="mt-4 space-y-3">
                        <div v-for="rec in arimaRecs" :key="rec.id" class="flex items-center space-x-3 py-2 border-b border-gray-100 last:border-0">
                            <!-- Monospace SKU initial identifier -->
                            <div class="w-9 h-9 bg-white border border-gray-200 rounded-sm flex items-center justify-center text-[10px] font-bold text-gray-500 font-mono uppercase">
                                {{ (rec.sku || '').substring(0, 3) }}
                            </div>
                            <div class="flex-grow min-w-0">
                                <h4 class="text-xs font-bold text-gray-900 truncate" :title="rec.name">{{ rec.name }}</h4>
                                <span class="text-[10px] text-gray-400 font-mono">Stok: {{ rec.stock }} pcs</span>
                            </div>
                            <!-- Mini Sparkline AI Trend (SVG Sparkline) -->
                            <div class="w-14 h-8 flex items-center shrink-0">
                                <svg class="w-full h-full" viewBox="0 0 60 20">
                                    <path :d="getSparklinePath(rec)" fill="none" stroke="#FF1F8F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <circle :cx="58" :cy="getSparklineLastY(rec)" r="1.5" fill="#FF1F8F" class="animate-pulse" />
                                </svg>
                            </div>
                            <span class="text-[9px] bg-pink-50 text-[#FF1F8F] font-bold px-1.5 py-0.5 rounded-sm shrink-0 uppercase tracking-wider font-mono">
                                Hot
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200/60">
                    <span class="text-[9px] font-mono text-gray-400 uppercase tracking-wider">Terintegrasi otomatis dengan AI Forecasting</span>
                </div>
            </div>
        </div>

        <!-- 2. Katalog Konten Grid (Golden Ratio Layout) -->
        <div id="products-section" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Mobile Filter Toggle Button (tampil hanya di mobile) -->
            <div class="lg:hidden col-span-1">
                <button @click="showMobileFilter = !showMobileFilter" 
                        class="w-full bg-white border border-gray-200 hover:border-[#FF1F8F] text-gray-800 text-xs font-bold py-3 px-4 rounded-sm transition-all cursor-pointer flex justify-between items-center shadow-xs">
                    <span class="uppercase tracking-wider">Cari & Filter</span>
                    <span class="text-[10px] text-gray-400 font-mono">{{ showMobileFilter ? 'Tutup' : 'Buka' }}</span>
                </button>
            </div>

            <!-- Sidebar Filter Premium (Col-3) -->
            <div :class="{ 'hidden': !showMobileFilter, 'block': showMobileFilter }" class="lg:block lg:col-span-3 bg-white border border-gray-200 rounded-sm p-6 h-fit lg:sticky lg:top-20">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest border-b border-gray-100 pb-3 mb-5">Cari & Filter</h3>
                
                <!-- Pencarian Kata Kunci -->
                <div class="mb-6">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Pencarian Nama</label>
                    <input v-model="filters.keyword" @input="onKeywordInput" type="text" placeholder="Cari gaun, kemeja..." class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F] focus:ring-0 transition-colors" />
                </div>

                <!-- Kategori Button Selectors (Jauh Lebih Premium dari Radio) -->
                <div class="mb-6">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2.5">Kategori</label>
                    <div class="space-y-1.5">
                        <button v-for="cat in ['Atasan', 'Bawahan', 'Gamis']" :key="cat" @click="filters.category = cat" :class="filters.category === cat ? 'border-[#FF1F8F] bg-pink-50/15 text-[#FF1F8F] font-bold' : 'border-gray-200 hover:border-gray-300 text-gray-600'" class="w-full text-left text-xs border p-2.5 rounded-sm transition-all cursor-pointer bg-white">
                            {{ cat }}
                        </button>
                        <button @click="filters.category = ''" :class="filters.category === '' ? 'border-[#FF1F8F] bg-pink-50/15 text-[#FF1F8F] font-bold' : 'border-gray-200 hover:border-gray-300 text-gray-600'" class="w-full text-left text-xs border p-2.5 rounded-sm transition-all cursor-pointer bg-white">
                            Semua Kategori
                        </button>
                    </div>
                </div>

                <!-- Urutkan Berdasarkan -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Urutan Harga</label>
                    <select v-model="filters.sort" class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F] transition-colors bg-white">
                        <option value="newest">Terbaru</option>
                        <option value="price_asc">Harga Terendah</option>
                        <option value="price_desc">Harga Tertinggi</option>
                    </select>
                </div>
            </div>

            <!-- Grid Produk & Pagination (Col-9) -->
            <div class="lg:col-span-9">
                <div class="flex justify-between items-center mb-6">
                    <span class="text-xs text-gray-400 font-mono">Menampilkan {{ (products || []).length }} item terdaftar</span>
                </div>

                <div v-if="loading" class="flex justify-center items-center py-24">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#FF1F8F]"></div>
                </div>

                <div v-else-if="!products || products.length === 0" class="text-center py-24 bg-white border border-gray-200 rounded-sm">
                    <p class="text-sm text-gray-400">Tidak ada produk yang cocok dengan pencarian Anda.</p>
                </div>

                <!-- Product Grid -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <!-- Product Card -->
                    <div v-for="product in products" :key="product.id" class="bg-white border border-gray-200 rounded-sm overflow-hidden flex flex-col justify-between hover:border-gray-300 transition-colors group">
                        <!-- Click area for details modal -->
                        <div class="p-4 flex-grow cursor-pointer" @click="openProductDetail(product)">
                            <!-- Image Container with hover transition -->
                            <div class="w-full aspect-square bg-gray-50 flex items-center justify-center border border-gray-100 rounded-sm mb-4 relative overflow-hidden">
                                <svg class="w-12 h-12 stroke-gray-300 group-hover:scale-105 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                                
                                <!-- ARIMA Badge (Height 24px flat solid, uppercase tracked) -->
                                <div v-if="isArimaTrending(product)" class="absolute top-2.5 left-2.5 bg-[#FF1F8F] text-white text-[9px] font-bold px-2.5 py-1 rounded-sm uppercase tracking-widest select-none z-10 flex items-center h-[24px]">
                                    [ TRENDING ]
                                </div>

                                <!-- Sisa 1 Badge -->
                                <div v-if="product.stock === 1" class="absolute top-2.5 right-2.5 bg-red-600 text-white text-[9px] font-bold px-2 py-1 rounded-sm uppercase tracking-wider select-none z-10 flex items-center h-[24px]">
                                    Sisa 1
                                </div>
                            </div>
                            <span class="text-[10px] font-bold font-mono uppercase text-gray-400 tracking-wider">
                                {{ product.category?.name }}
                            </span>
                            <h3 class="text-xs sm:text-sm font-bold text-gray-900 mt-1 truncate group-hover:text-[#FF1F8F] transition-colors" :title="product.name">
                                {{ product.name }}
                            </h3>
                            <span class="text-[10px] font-mono text-gray-400 mt-1 block">SKU: {{ product.sku }}</span>
                        </div>
                        
                        <!-- Pricing & CTA Block (Selective color highlight) -->
                        <div class="px-4 py-3.5 border-t border-gray-100 bg-[#F9FAFB] flex items-center justify-between">
                            <div>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Harga</span>
                                <span class="text-xs sm:text-sm font-bold text-gray-900 font-mono">Rp {{ formatNumber(product.price) }}</span>
                            </div>
                            
                            <button 
                                @click.stop="handleAddToCart(product, $event)" 
                                :disabled="product.stock <= 0" 
                                class="bg-white border border-gray-200 hover:border-[#FF1F8F] hover:bg-[#FF1F8F] hover:text-white text-gray-700 text-[11px] font-bold px-3 py-2 rounded-sm transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-gray-700 disabled:hover:border-gray-200"
                            >
                                {{ product.stock > 0 ? '+ Keranjang' : 'Habis' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination Controller -->
                <div v-if="pagination.last_page > 1" class="mt-10 flex justify-center items-center space-x-4">
                    <button :disabled="filters.page === 1" @click="changePage(filters.page - 1)" class="px-4 py-2 border border-gray-300 rounded-sm text-xs font-bold text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 cursor-pointer">
                        Sebelumnya
                    </button>
                    <span class="text-xs font-mono text-gray-500 font-semibold">Halaman {{ filters.page }} dari {{ pagination.last_page }}</span>
                    <button :disabled="filters.page === pagination.last_page" @click="changePage(filters.page + 1)" class="px-4 py-2 border border-gray-300 rounded-sm text-xs font-bold text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 cursor-pointer">
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>

        <!-- Product Detail Modal (Bento & Golden Ratio Layout) -->
        <div v-if="showModal && selectedProduct" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop with Blur -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-xs transition-opacity" @click="closeModal"></div>
            
            <!-- Modal Content (Flat, rounded-sm, border border-gray-200) -->
            <div class="bg-white rounded-sm overflow-hidden border border-gray-200 w-full max-w-3xl relative z-10 grid grid-cols-1 md:grid-cols-12 animate-fadeIn">
                <!-- Close Button -->
                <button @click="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-900 z-20 cursor-pointer bg-transparent border-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Left Side: Product Image (Col-6) -->
                <div class="md:col-span-6 bg-gray-50 flex items-center justify-center p-8 border-b md:border-b-0 md:border-r border-gray-100 min-h-[300px] relative">
                    <svg class="w-24 h-24 stroke-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    
                    <!-- Trending Badge inside Modal -->
                    <div v-if="isArimaTrending(selectedProduct)" class="absolute top-4 left-4 bg-[#FF1F8F] text-white text-[9px] font-bold px-2.5 py-1 rounded-sm uppercase tracking-widest flex items-center h-[24px]">
                        [ TRENDING ]
                    </div>
                </div>

                <!-- Right Side: Details (Col-6) -->
                <div class="md:col-span-6 p-8 flex flex-col justify-between">
                    <div class="space-y-4">
                        <span class="text-[9px] font-mono font-bold uppercase tracking-widest text-[#FF1F8F] bg-[#FF1F8F]/10 px-2.5 py-1 rounded-sm inline-block">
                            {{ selectedProduct.category?.name }}
                        </span>
                        <h2 class="text-xl font-extrabold text-gray-900 tracking-tight leading-tight uppercase">
                            {{ selectedProduct.name }}
                        </h2>
                        <p class="text-[10px] text-gray-400 font-mono">SKU: {{ selectedProduct.sku }}</p>
                        
                        <div class="pt-2">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Harga</span>
                            <span class="text-base sm:text-lg font-bold text-[#FF1F8F] font-mono">Rp {{ formatNumber(selectedProduct.price) }}</span>
                        </div>

                        <div class="pt-2">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Ketersediaan Stok</span>
                            <span v-if="selectedProduct.stock === 1" class="text-xs font-bold text-red-600 font-mono bg-red-50 px-2 py-1 rounded-sm border border-red-200 inline-block">Sisa 1 Pcs Saja!</span>
                            <span v-else-if="selectedProduct.stock <= 0" class="text-xs font-bold text-gray-400 font-mono bg-gray-50 px-2 py-1 rounded-sm border border-gray-200 inline-block">Stok Habis</span>
                            <span v-else class="text-xs font-semibold text-gray-700 font-mono">{{ selectedProduct.stock }} pcs tersedia</span>
                        </div>
                    </div>

                    <div class="pt-6">
                        <button 
                            @click="handleAddToCart(selectedProduct, $event); closeModal()" 
                            :disabled="selectedProduct.stock <= 0" 
                            class="w-full bg-[#FF1F8F] hover:bg-[#D91678] text-white text-xs font-bold py-3.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer border-0 text-center disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-[#FF1F8F]"
                        >
                            {{ selectedProduct.stock > 0 ? '+ Tambah ke Keranjang' : 'Habis' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    categoryFilter: { type: String, default: '' }
});

const emit = defineEmits(['add-to-cart', 'show-notification']);

const loading = ref(false);
const products = ref([]);
const arimaRecs = ref([]);
const pagination = ref({});

const selectedProduct = ref(null);
const showModal = ref(false);
const showMobileFilter = ref(false);

const filters = reactive({
    category: '',
    keyword: '',
    sort: 'newest',
    page: 1
});

// Watch category filter dari navbar
watch(() => props.categoryFilter, (newVal) => {
    filters.category = newVal;
});

// Fetch Produk
const fetchProducts = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/storefront/products', { params: filters });
        products.value = response.data?.data ?? [];
        pagination.value = response.data ?? {};
    } catch (error) {
        console.error('Gagal mengambil produk:', error);
        products.value = [];
        pagination.value = {};
    } finally {
        loading.value = false;
    }
};

// Fetch ARIMA Recommendations
const fetchArimaRecs = async () => {
    try {
        const response = await axios.get('/api/storefront/recommendations');
        arimaRecs.value = response.data ?? [];
    } catch (error) {
        console.error('Gagal mengambil rekomendasi ARIMA:', error);
        arimaRecs.value = [];
    }
};

// Auto-fetch saat filter berubah (SKILL.md 5.3)
watch(
    () => [filters.category, filters.sort],
    () => {
        filters.page = 1;
        fetchProducts();
    }
);

// Debounce untuk search keyword
let debounceTimer = null;
const onKeywordInput = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filters.page = 1;
        fetchProducts();
    }, 300);
};

const changePage = (page) => {
    filters.page = page;
    fetchProducts();
    scrollToProducts();
};

const addToCart = (product) => {
    emit('add-to-cart', product);
};

const scrollToProducts = () => {
    document.getElementById('products-section')?.scrollIntoView({ behavior: 'smooth' });
};

const formatNumber = (num) => {
    return Number(num).toLocaleString('id-ID');
};

const isArimaTrending = (product) => {
    return (arimaRecs.value || []).some(r => r && r.id === product.id);
};

const openProductDetail = (product) => {
    selectedProduct.value = product;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedProduct.value = null;
};

const handleAddToCart = (product, event) => {
    addToCart(product);
    triggerSmoothCartAnimation(event);
};

// ──── Smooth Cart Animation (Web Animations API) ────
const triggerSmoothCartAnimation = (event) => {
    // Target the SVG icon inside #cart-nav-target precisely
    const cartLink = document.getElementById('cart-nav-target');
    if (!cartLink) return;
    const cartSvg = cartLink.querySelector('svg');
    if (!cartSvg) return;

    const svgRect = cartSvg.getBoundingClientRect();
    const startX = event.clientX;
    const startY = event.clientY;
    // Exact center of the SVG cart icon
    const endX = svgRect.left + svgRect.width / 2;
    const endY = svgRect.top + svgRect.height / 2;

    // ─ 1. Origin burst ring ─
    spawnBurstRing(startX, startY);

    // ─ 2. Flying particles (3 focused particles) ─
    const configs = [
        { size: 10, delay: 0,  dur: 650, arcBias: -0.35 },
        { size: 7,  delay: 50, dur: 700, arcBias: 0.25  },
        { size: 5,  delay: 100, dur: 750, arcBias: -0.15 },
    ];

    configs.forEach((cfg) => {
        setTimeout(() => {
            launchParticle(startX, startY, endX, endY, cfg);
        }, cfg.delay);
    });

    // ─ 3. Cart SVG bounce when first particle arrives ─
    setTimeout(() => {
        bounceCartSvg(cartSvg);
    }, configs[0].dur * 0.75);

    // ─ 4. Button checkmark flash ─
    spawnSuccessCheck(event);
};

// ── Launch single particle with CSS @keyframes via Web Animations API ──
const launchParticle = (sx, sy, ex, ey, cfg) => {
    const el = document.createElement('div');
    el.style.cssText = `
        position: fixed;
        z-index: 9999;
        pointer-events: none;
        width: ${cfg.size}px;
        height: ${cfg.size}px;
        border-radius: 50%;
        background: #FF1F8F;
        box-shadow: 0 0 ${cfg.size}px rgba(255, 31, 143, 0.5);
        will-change: transform, opacity;
    `;
    document.body.appendChild(el);

    // Calculate smooth parabolic arc control point
    const dx = ex - sx;
    const dy = ey - sy;
    const dist = Math.sqrt(dx * dx + dy * dy);
    // Arc height scales with distance, bias randomizes the horizontal curve
    const arcHeight = Math.min(dist * 0.4, 120);
    const midX = (sx + ex) / 2 + cfg.arcBias * dist;
    const midY = Math.min(sy, ey) - arcHeight;

    // Generate smooth bezier keyframes (12 steps for buttery motion)
    const steps = 12;
    const keyframes = [];
    for (let i = 0; i <= steps; i++) {
        const t = i / steps;
        // Quadratic bezier: B(t) = (1-t)²·P0 + 2(1-t)t·P1 + t²·P2
        const inv = 1 - t;
        const x = inv * inv * sx + 2 * inv * t * midX + t * t * ex;
        const y = inv * inv * sy + 2 * inv * t * midY + t * t * ey;
        // Scale: start at 1, peak at 1.4 at t=0.3, shrink to 0 at end
        const scale = t < 0.3
            ? 1 + t * (1.4 / 0.3 - 1 / 0.3)
            : 1.4 * (1 - ((t - 0.3) / 0.7)) ** 0.8;
        // Opacity: full until 70%, then fade
        const opacity = t < 0.7 ? 1 : 1 - ((t - 0.7) / 0.3);

        keyframes.push({
            left: `${x}px`,
            top: `${y}px`,
            transform: `translate(-50%, -50%) scale(${Math.max(0, scale).toFixed(3)})`,
            opacity: `${Math.max(0, opacity).toFixed(3)}`,
        });
    }

    const anim = el.animate(keyframes, {
        duration: cfg.dur,
        easing: 'cubic-bezier(0.25, 0.1, 0.25, 1)',
        fill: 'forwards',
    });

    anim.onfinish = () => {
        el.remove();
    };
};

// ── Burst Ring: Expanding ring from origin ──
const spawnBurstRing = (x, y) => {
    const el = document.createElement('div');
    el.style.cssText = `
        position: fixed;
        z-index: 9998;
        pointer-events: none;
        border-radius: 50%;
        border: 2px solid #FF1F8F;
        will-change: transform, opacity;
    `;
    document.body.appendChild(el);

    el.animate([
        {
            left: `${x}px`, top: `${y}px`,
            width: '0px', height: '0px',
            transform: 'translate(-50%, -50%)',
            opacity: '0.7',
        },
        {
            left: `${x}px`, top: `${y}px`,
            width: '50px', height: '50px',
            transform: 'translate(-50%, -50%)',
            opacity: '0',
        }
    ], {
        duration: 350,
        easing: 'cubic-bezier(0.2, 0.8, 0.2, 1)',
        fill: 'forwards',
    }).onfinish = () => el.remove();
};

// ── Cart SVG Bounce (targets the SVG icon directly) ──
const bounceCartSvg = (svgEl) => {
    svgEl.animate([
        { transform: 'scale(1)',    offset: 0 },
        { transform: 'scale(1.35)', offset: 0.25 },
        { transform: 'scale(0.85)', offset: 0.5 },
        { transform: 'scale(1.15)', offset: 0.75 },
        { transform: 'scale(1)',    offset: 1 },
    ], {
        duration: 450,
        easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
    });
};

// ── Success Checkmark on Button ──
const spawnSuccessCheck = (event) => {
    const btn = event.currentTarget || event.target;
    if (!btn) return;

    const rect = btn.getBoundingClientRect();
    const el = document.createElement('div');
    el.innerHTML = `<svg style="width:16px;height:16px;color:white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`;
    el.style.cssText = `
        position: fixed;
        z-index: 9999;
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
        left: ${rect.left}px;
        top: ${rect.top}px;
        width: ${rect.width}px;
        height: ${rect.height}px;
        background: #FF1F8F;
        border-radius: 2px;
        will-change: transform, opacity;
    `;
    document.body.appendChild(el);

    el.animate([
        { opacity: '0', transform: 'scale(0.8)' },
        { opacity: '1', transform: 'scale(1)',   offset: 0.15 },
        { opacity: '1', transform: 'scale(1)',   offset: 0.7 },
        { opacity: '0', transform: 'scale(1.05)' },
    ], {
        duration: 700,
        easing: 'ease-out',
        fill: 'forwards',
    }).onfinish = () => el.remove();
};

const getSparklinePath = (rec) => {
    const seed = (rec.sku || 'ATS').charCodeAt(4) || 65;
    const points = [];
    for (let i = 0; i < 6; i++) {
        const x = i * 11.6; // Scale x to 60px
        const wave = Math.sin((i + seed) * 1.5) * 3;
        const trend = i * 1.2; // Proyeksi ARIMA positif
        const y = 14 - (wave + trend);
        points.push(`${x},${Math.max(2, Math.min(18, y))}`);
    }
    return `M ${points.join(' L ')}`;
};

const getSparklineLastY = (rec) => {
    const seed = (rec.sku || 'ATS').charCodeAt(4) || 65;
    const wave = Math.sin((5 + seed) * 1.5) * 3;
    const trend = 5 * 1.2;
    return Math.max(2, Math.min(18, 14 - (wave + trend)));
};

onMounted(() => {
    fetchProducts();
    fetchArimaRecs();
});
</script>

<style scoped>
/* Modal fade-in animation */
.animate-fadeIn {
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.97) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
