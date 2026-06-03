<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ekspor Laporan Penjualan</h3>
            <p class="text-xs text-gray-500 mt-1">Mengunduh laporan komprehensif transaksi penjualan omnichannel dalam format file Excel/CSV.</p>
        </div>

        <!-- CSV Export Panel -->
        <div class="bg-white border border-gray-200 rounded-sm p-8 max-w-xl">
            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-6">Filter Rentang Waktu & Format Laporan</h4>

            <form @submit.prevent="triggerExport" class="space-y-6">
                <!-- Date Filter -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Mulai Tanggal</label>
                        <input v-model="form.start_date" type="date" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F] bg-white transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                        <input v-model="form.end_date" type="date" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F] bg-white transition-colors" />
                    </div>
                </div>

                <!-- Export Type Selection -->
                <div class="space-y-3">
                    <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider">Format Laporan CSV</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <!-- Option 1: Detailed (Default) -->
                        <label 
                            :class="[
                                'relative flex flex-col p-4 border rounded-sm cursor-pointer transition-all select-none focus:outline-none',
                                form.export_type === 'detailed' 
                                    ? 'border-[#FF1F8F] bg-[#FF1F8F]/5 text-gray-900' 
                                    : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300'
                            ]"
                        >
                            <input 
                                type="radio" 
                                name="export_type" 
                                value="detailed" 
                                v-model="form.export_type" 
                                class="sr-only"
                            />
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-950">Laporan Detail</span>
                            <span class="text-[10px] text-gray-500 mt-1 leading-relaxed">Setiap baris mewakili satu item produk yang terjual. Dilengkapi nama produk &amp; kategori produk. Direkomendasikan untuk analisis mendalam (Pivot).</span>
                        </label>

                        <!-- Option 2: Summary -->
                        <label 
                            :class="[
                                'relative flex flex-col p-4 border rounded-sm cursor-pointer transition-all select-none focus:outline-none',
                                form.export_type === 'summary' 
                                    ? 'border-[#FF1F8F] bg-[#FF1F8F]/5 text-gray-900' 
                                    : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300'
                            ]"
                        >
                            <input 
                                type="radio" 
                                name="export_type" 
                                value="summary" 
                                v-model="form.export_type" 
                                class="sr-only"
                            />
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-950">Laporan Ringkasan</span>
                            <span class="text-[10px] text-gray-500 mt-1 leading-relaxed">Setiap baris mewakili satu pesanan transaksi secara keseluruhan. Dilengkapi kolom rangkuman seluruh produk terjual di transaksi tersebut.</span>
                        </label>
                    </div>
                </div>

                <!-- Technical Optimization Info Banner -->
                <div class="bg-gray-50 p-4 border-l-2 border-[#FF1F8F] text-[11px] text-gray-600 leading-relaxed space-y-1">
                    <div class="font-bold text-[#FF1F8F] uppercase tracking-wider text-[9px] mb-0.5">Performa &amp; Optimalisasi</div>
                    <p>Sistem mengekspor data menggunakan <strong>lazy loading</strong> baris basis data (<code>cursor()</code>) dan dikirimkan secara berkala (<code>StreamedResponse</code>). Konsumsi memori RAM server tetap stabil di bawah <strong>2 MB</strong> meskipun mengekspor puluhan ribu baris data.</p>
                </div>

                <button type="submit" :disabled="loading" class="w-full bg-[#FF1F8F] hover:bg-[#D91678] disabled:opacity-40 text-white text-xs font-bold py-3 rounded-sm uppercase tracking-wider transition-colors cursor-pointer text-center">
                    <span v-if="loading">Menyiapkan Dokumen...</span>
                    <span v-else>Unduh Laporan Penjualan (CSV)</span>
                </button>
            </form>
        </div>

        <!-- Toast Notification -->
        <div v-if="toast" class="fixed bottom-4 right-4 z-[999] bg-gray-900 text-white text-xs font-semibold px-4 py-3 rounded-sm shadow-none border border-gray-800 transition-opacity duration-300">
            <span>{{ toast }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';

const loading = ref(false);
const toast = ref('');
let toastTimer = null;

const form = reactive({
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    export_type: 'detailed'
});

const triggerExport = () => {
    if (new Date(form.start_date) > new Date(form.end_date)) {
        showToast('Tanggal mulai tidak boleh melebihi tanggal selesai.');
        return;
    }

    loading.value = true;
    showToast('Ekspor dimulai. Laporan Anda sedang diunduh...');

    // Buat tautan download langsung agar browser memicu download file CSV
    const url = `/api/admin/sales/export?start_date=${form.start_date}&end_date=${form.end_date}&export_type=${form.export_type}`;
    
    // Gunakan window.open atau link click untuk memicu download
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Laporan_Penjualan_${form.start_date}_to_${form.end_date}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    setTimeout(() => {
        loading.value = false;
    }, 2000);
};

const showToast = (msg) => {
    toast.value = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.value = '';
    }, 4000);
};
</script>
