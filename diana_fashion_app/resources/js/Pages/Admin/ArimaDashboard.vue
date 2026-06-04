<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">ARIMA AI Forecasting Engine</h3>
                <p class="text-xs text-gray-500 mt-1">Mengonfigurasi parameter tuning model run-time dan meramalkan permintaan stok produk secara real-time.</p>
                <p class="text-[10px] text-gray-400 mt-1">Terakhir Tuning Global: <span class="text-[#FF1F8F] font-bold font-mono">{{ lastGlobalTuning }}</span></p>
            </div>
            <div>
                <button type="button" @click="tuneAllProducts" :disabled="predictLoading || batchLoading" class="bg-gray-900 hover:bg-black text-white text-xs font-bold px-4 py-2.5 rounded-sm uppercase tracking-wider transition-colors disabled:opacity-40 flex items-center space-x-2 cursor-pointer">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M19 12H5M12 19l7-7-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Tuning Seluruh Data Order</span>
                </button>
            </div>
        </div>

        <!-- Golden Ratio Grid (62:38) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Area Kiri: Form Tuning & Hasil Prediksi (col-span-8 - 62% Golden Ratio) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Tuning Configuration Card -->
                <div class="bg-white border border-gray-200 rounded-sm p-6 space-y-6">
                    <div class="flex border-b border-gray-100 pb-3 mb-4">
                        <button type="button" @click="selectionMode = 'single'" :class="selectionMode === 'single' ? 'border-[#FF1F8F] text-[#FF1F8F]' : 'border-transparent text-gray-400'" class="text-xs font-bold uppercase tracking-wider pb-2 border-b-2 mr-6 transition-all cursor-pointer">Tunggal (Satu Produk)</button>
                        <button type="button" @click="selectionMode = 'batch'" :class="selectionMode === 'batch' ? 'border-[#FF1F8F] text-[#FF1F8F]' : 'border-transparent text-gray-400'" class="text-xs font-bold uppercase tracking-wider pb-2 border-b-2 mr-6 transition-all cursor-pointer">Beberapa Produk (Batch)</button>
                        <button type="button" @click="selectionMode = 'global'" :class="selectionMode === 'global' ? 'border-[#FF1F8F] text-[#FF1F8F]' : 'border-transparent text-gray-400'" class="text-xs font-bold uppercase tracking-wider pb-2 border-b-2 mr-6 transition-all cursor-pointer">Penjualan Toko (Global)</button>
                        <button type="button" @click="selectionMode = 'config'" :class="selectionMode === 'config' ? 'border-[#FF1F8F] text-[#FF1F8F]' : 'border-transparent text-gray-400'" class="text-xs font-bold uppercase tracking-wider pb-2 border-b-2 transition-all cursor-pointer">Konfigurasi & Pipeline</button>
                    </div>

                    <form v-if="selectionMode !== 'config' && selectionMode !== 'global'" @submit.prevent="handleSubmit" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Produk (Tunggal) -->
                            <div v-if="selectionMode === 'single'">
                                <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Pilih Produk</label>
                                <select v-model="form.product_id" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F] bg-white">
                                    <option value="">Pilih barang untuk diuji...</option>
                                    <option v-for="prod in products" :key="prod.id" :value="prod.id">
                                        {{ prod.name }} (Stok: {{ prod.stock }} pcs)
                                    </option>
                                </select>
                            </div>

                            <!-- Periode Peramalan -->
                            <div :class="selectionMode === 'batch' ? 'col-span-1' : ''">
                                <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Periode Peramalan (Hari)</label>
                                <input v-model="form.forecast_periods" type="number" min="1" max="30" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                            </div>

                            <!-- Produk (Batch) -->
                            <div v-if="selectionMode === 'batch'" class="col-span-1 sm:col-span-2 space-y-3">
                                <div class="flex justify-between items-center">
                                    <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider">Pilih Beberapa Produk</label>
                                    <div class="flex space-x-3 text-[10px]">
                                        <button type="button" @click="selectAllProducts" class="text-[#FF1F8F] font-bold hover:underline cursor-pointer">Pilih Semua</button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button" @click="clearAllProducts" class="text-gray-500 font-bold hover:underline cursor-pointer">Hapus Semua</button>
                                    </div>
                                </div>
                                <input v-model="searchQuery" type="text" placeholder="Cari nama atau SKU produk..." class="w-full text-xs border border-gray-300 rounded-sm px-3 py-1.5 focus:outline-none focus:border-[#FF1F8F] mb-1" />
                                <div class="border border-gray-200 rounded-sm p-3 max-h-48 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-2 bg-gray-50/50">
                                    <label v-for="prod in filteredProducts" :key="prod.id" class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer hover:bg-gray-100/50 p-1.5 rounded-sm">
                                        <input type="checkbox" :value="prod.id" v-model="selectedProductIds" class="mr-2.5 accent-[#FF1F8F]" />
                                        <span class="truncate">{{ prod.name }} <span class="text-[9px] font-mono text-gray-400">({{ prod.sku }})</span></span>
                                    </label>
                                </div>
                                <p class="text-[10px] text-gray-400 font-semibold mt-1">Terpilih: {{ selectedProductIds.length }} dari {{ products.length }} produk.</p>
                            </div>
                        </div>

                        <!-- Parameter Opsional Tuning -->
                        <div class="flex flex-wrap gap-6 pt-2">
                            <label class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer">
                                <input type="checkbox" v-model="form.fill_missing_dates" class="mr-2 accent-[#FF1F8F]" />
                                Isi Tanggal Kosong dengan 0 (Zero-Fill)
                            </label>
                            <label class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer">
                                <input type="checkbox" v-model="form.smooth_outliers" class="mr-2 accent-[#FF1F8F]" />
                                Haluskan Outliers Ekstrim (IQR Method)
                            </label>
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                            <div class="text-[10px] text-gray-400 font-semibold">
                                <span v-if="selectionMode === 'single' && currentConfigTuningTime">
                                    Tuning Terakhir Produk: <span class="text-gray-900 font-bold font-mono">{{ currentConfigTuningTime }}</span>
                                </span>
                            </div>
                            <div class="flex space-x-3">
                                <button v-if="selectionMode === 'single'" type="button" @click="saveProductTuningConfig" :disabled="predictLoading || !form.product_id" class="border border-gray-300 hover:border-gray-400 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-sm uppercase tracking-wider transition-colors disabled:opacity-40 cursor-pointer">
                                    Simpan Setelan
                                </button>
                                <button type="submit" :disabled="predictLoading || batchLoading" class="bg-[#FF1F8F] hover:bg-[#D91678] disabled:opacity-40 text-white text-xs font-bold px-6 py-2.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer text-center">
                                    <span v-if="predictLoading">Mengalkulasi ARIMA di Flask...</span>
                                    <span v-else-if="batchLoading">Batch Tuning Berjalan...</span>
                                    <span v-else>Jalankan Prediksi AI</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Form Tren Penjualan Global -->
                    <form v-if="selectionMode === 'global'" @submit.prevent="runGlobalPrediction" class="space-y-4 animate-fadeIn">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Periode Peramalan -->
                            <div>
                                <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Periode Peramalan (Hari)</label>
                                <input v-model="globalForm.forecast_periods" type="number" min="1" max="30" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                            </div>
                            
                            <!-- Info Saja -->
                            <div class="flex items-center text-xs font-semibold text-gray-500 bg-gray-50 p-3 rounded-sm border border-gray-200">
                                <div>
                                    <p class="font-bold text-gray-700 uppercase text-[9px] tracking-wider">Sumber Data</p>
                                    <p class="mt-0.5 text-gray-600">Total pendapatan harian dari seluruh pesanan selesai (`completed orders`).</p>
                                </div>
                            </div>
                        </div>

                        <!-- Parameter Opsional Tuning -->
                        <div class="flex flex-wrap gap-6 pt-2">
                            <label class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer">
                                <input type="checkbox" v-model="globalForm.fill_missing_dates" class="mr-2 accent-[#FF1F8F]" />
                                Isi Tanggal Kosong dengan 0 (Zero-Fill)
                            </label>
                            <label class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer">
                                <input type="checkbox" v-model="globalForm.smooth_outliers" class="mr-2 accent-[#FF1F8F]" />
                                Haluskan Outliers Ekstrim (IQR Method)
                            </label>
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                            <div class="text-[10px] text-gray-400 font-semibold">
                                <span v-if="globalConfigTuningTime">
                                    Tuning Terakhir Global: <span class="text-gray-900 font-bold font-mono">{{ globalConfigTuningTime }}</span>
                                </span>
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" @click="saveGlobalTuningConfig" :disabled="predictLoading" class="border border-gray-300 hover:border-gray-400 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-sm uppercase tracking-wider transition-colors disabled:opacity-40 cursor-pointer">
                                    Simpan Setelan
                                </button>
                                <button type="submit" :disabled="predictLoading" class="bg-[#FF1F8F] hover:bg-[#D91678] disabled:opacity-40 text-white text-xs font-bold px-6 py-2.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer text-center">
                                    <span v-if="predictLoading">Mengalkulasi ARIMA di Flask...</span>
                                    <span v-else>Jalankan Prediksi AI Toko</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- ARIMA Config Panel & Pipeline (Opsi A) -->
                    <div v-else class="space-y-6 animate-fadeIn">
                        <div class="border-b border-gray-100 pb-4">
                            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Konfigurasi Hyperparameter ARIMA Global</h4>
                            <p class="text-[10px] text-gray-500 mt-1">Mengonfigurasi default search bounds dan preprocessing deret waktu untuk seluruh pipeline peramalan.</p>
                        </div>

                        <form @submit.prevent="saveArimaConfig" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Preprocessing & Forecast Settings -->
                                <div class="space-y-4">
                                    <h5 class="text-[10px] font-bold text-[#FF1F8F] uppercase tracking-widest border-b border-pink-50 pb-1">Setelan Default & Pra-pemrosesan</h5>
                                    
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Default Periode Peramalan (Hari)</label>
                                        <input v-model="configForm.arima_forecast_periods" type="number" min="1" max="30" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                                    </div>

                                    <div class="space-y-3 pt-2">
                                        <label class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer">
                                            <input type="checkbox" v-model="configForm.arima_fill_missing_dates" class="mr-2.5 accent-[#FF1F8F]" />
                                            Isi Tanggal Kosong dengan 0 (Zero-Fill)
                                        </label>
                                        <p class="text-[9px] text-gray-400 pl-6 -mt-1 leading-normal">Membangun rentang tanggal kontinu. Mengisi kekosongan data akibat hari libur / toko tutup dengan angka 0.</p>
                                        
                                        <label class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer">
                                            <input type="checkbox" v-model="configForm.arima_smooth_outliers" class="mr-2.5 accent-[#FF1F8F]" />
                                            Haluskan Outliers Ekstrim (IQR Method)
                                        </label>
                                        <p class="text-[9px] text-gray-400 pl-6 -mt-1 leading-normal">Membatasi penjualan harian yang melonjak ekstrim menggunakan batas atas 1.5x IQR Tukey.</p>
                                    </div>
                                </div>

                                <!-- Model Bounds Settings -->
                                <div class="space-y-4">
                                    <h5 class="text-[10px] font-bold text-[#FF1F8F] uppercase tracking-widest border-b border-pink-50 pb-1">Batasan Pencarian ARIMA Order</h5>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-600 uppercase tracking-wider mb-1">Start p (AR)</label>
                                            <input v-model="configForm.arima_start_p" type="number" min="0" max="5" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-1.5 focus:outline-none focus:border-[#FF1F8F]" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-600 uppercase tracking-wider mb-1">Start q (MA)</label>
                                            <input v-model="configForm.arima_start_q" type="number" min="0" max="5" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-1.5 focus:outline-none focus:border-[#FF1F8F]" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-600 uppercase tracking-wider mb-1">Max p (AR)</label>
                                            <input v-model="configForm.arima_max_p" type="number" min="1" max="10" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-1.5 focus:outline-none focus:border-[#FF1F8F]" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-600 uppercase tracking-wider mb-1">Max q (MA)</label>
                                            <input v-model="configForm.arima_max_q" type="number" min="1" max="10" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-1.5 focus:outline-none focus:border-[#FF1F8F]" />
                                        </div>
                                    </div>

                                    <div class="space-y-3 pt-2">
                                        <label class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer">
                                            <input type="checkbox" v-model="configForm.arima_stepwise" class="mr-2.5 accent-[#FF1F8F]" />
                                            Algoritma Stepwise (Lebih Cepat)
                                        </label>
                                        <p class="text-[9px] text-gray-400 pl-6 -mt-1 leading-normal">Menggunakan pendekatan stepwise pmdarima untuk pencarian koefisien model super cepat.</p>

                                        <label class="flex items-center text-xs font-semibold text-gray-600 cursor-pointer">
                                            <input type="checkbox" v-model="configForm.arima_seasonal" class="mr-2.5 accent-[#FF1F8F]" />
                                            Analisis Musiman (Seasonal Search)
                                        </label>
                                        <p class="text-[9px] text-gray-400 pl-6 -mt-1 leading-normal">Memperhitungkan efek tren musiman berkala (SARIMA) saat pencarian model.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-100">
                                <button type="submit" class="bg-[#FF1F8F] hover:bg-[#D91678] text-white text-xs font-bold px-6 py-2.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer">
                                    Simpan Konfigurasi
                                </button>
                            </div>
                        </form>

                        <!-- Pipeline Visualizer Section -->
                        <div class="border-t border-gray-100 pt-6 space-y-6">
                            <div>
                                <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Visualisasi Pipeline Peramalan ARIMA AI</h4>
                                <p class="text-[10px] text-gray-500 mt-1">Klik pada setiap langkah pipeline di bawah ini untuk melihat alur detail pra-pemrosesan, rumus statistik, dan visual snippet logikanya.</p>
                            </div>

                            <!-- Interactive Flow Chart Steps -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                                <button v-for="(step, idx) in pipelineSteps" :key="idx" type="button" @click="activePipelineStep = idx" :class="activePipelineStep === idx ? 'border-[#FF1F8F] bg-pink-50/20 text-[#FF1F8F]' : 'border-gray-200 hover:border-gray-300 text-gray-500 bg-gray-50/50'" class="border p-3 rounded-sm text-left transition-all cursor-pointer flex flex-col justify-between h-24 space-y-2">
                                    <div class="flex justify-between items-start w-full">
                                        <span class="text-[9px] font-bold font-mono px-1.5 py-0.5 rounded-full" :class="activePipelineStep === idx ? 'bg-pink-100 text-[#FF1F8F]' : 'bg-gray-100 text-gray-400'">0{{ idx + 1 }}</span>
                                        <span v-if="activePipelineStep === idx" class="flex h-2 w-2 relative">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF1F8F] opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-[#FF1F8F]"></span>
                                        </span>
                                    </div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider leading-tight text-[#FF1F8F] font-mono">{{ step.title }}</span>
                                </button>
                            </div>

                            <!-- Interactive Step Details Display Card -->
                            <div class="bg-gray-900 text-gray-100 rounded-sm p-5 space-y-4 border border-gray-800 border-l-4 border-[#FF1F8F]">
                                <div class="flex justify-between items-center border-b border-gray-800 pb-3">
                                    <div class="flex items-center space-x-2.5">
                                        <span class="text-xs font-bold text-[#FF1F8F] font-mono">Fase 0{{ activePipelineStep + 1 }}</span>
                                        <span class="h-1.5 w-1.5 bg-gray-700 rounded-full"></span>
                                        <span class="text-xs font-bold uppercase tracking-wider text-white">{{ pipelineSteps[activePipelineStep].title }}</span>
                                    </div>
                                    <span class="text-[9px] bg-gray-800 text-gray-400 font-mono px-2 py-0.5 rounded-xs uppercase">Interactive Detail</span>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                                    <!-- Explanation and Formulas -->
                                    <div class="lg:col-span-6 space-y-4 text-xs">
                                        <p class="text-gray-300 leading-relaxed font-medium">{{ pipelineSteps[activePipelineStep].description }}</p>
                                        
                                        <!-- Sleek Glassmorphic Formula Box -->
                                        <div class="bg-black/40 border border-gray-800 p-3.5 rounded-xs space-y-1.5 font-sans">
                                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest block">Pendekatan Matematis / Logika</span>
                                            <div class="text-sm font-semibold text-[#FF1F8F] font-mono" v-html="pipelineSteps[activePipelineStep].formula"></div>
                                        </div>
                                    </div>

                                    <!-- Code Snippet Mockup -->
                                    <div class="lg:col-span-6 space-y-2">
                                        <div class="flex justify-between text-[9px] text-gray-500 font-mono px-1">
                                            <span>diana_arima_pipeline.py</span>
                                            <span>Python 3.10</span>
                                        </div>
                                        <pre class="bg-black/60 border border-gray-800 p-4 rounded-xs text-[10px] font-mono text-pink-300 overflow-x-auto max-h-48 leading-relaxed whitespace-pre-wrap select-all"><code>{{ pipelineSteps[activePipelineStep].code }}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Batch Progress & Results Card -->
                <div v-if="batchLoading || batchResults.length > 0" class="bg-white border border-gray-200 rounded-sm p-6 space-y-4">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Hasil Batch Tuning</h4>
                        <span v-if="batchLoading" class="text-[10px] bg-pink-50 text-[#FF1F8F] font-bold px-2 py-0.5 rounded-xs uppercase animate-pulse">Sedang Tuning...</span>
                    </div>

                    <!-- Progress Bar -->
                    <div v-if="batchLoading" class="space-y-2">
                        <div class="flex justify-between text-[10px] font-bold text-gray-600">
                            <span>{{ batchProgress.currentName }}</span>
                            <span>{{ batchProgress.current }} / {{ batchProgress.total }} ({{ Math.round((batchProgress.current / batchProgress.total) * 100) }}%)</span>
                        </div>
                        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-[#FF1F8F] transition-all duration-300" :style="{ width: `${(batchProgress.current / batchProgress.total) * 100}%` }"></div>
                        </div>
                        <div class="flex justify-between text-[9px] text-gray-400 font-semibold font-mono">
                            <span>Sukses: {{ batchProgress.successCount }}</span>
                            <span>Rata-rata MAPE: {{ batchProgress.averageMape }}%</span>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div class="max-h-60 overflow-y-auto border border-gray-100 rounded-sm">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 text-[9px] text-gray-400 uppercase font-bold tracking-wider border-b border-gray-100">
                                <tr>
                                    <th class="px-4 py-2.5">Produk</th>
                                    <th class="px-4 py-2.5">SKU</th>
                                    <th class="px-4 py-2.5">ARIMA Order</th>
                                    <th class="px-4 py-2.5">MAPE</th>
                                    <th class="px-4 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="res in batchResults" :key="res.id" @click="selectBatchResult(res)" class="border-b border-gray-50 last:border-0 hover:bg-gray-50 cursor-pointer transition-colors" :class="{ 'bg-pink-50/30 font-bold': selectedBatchProductId === res.id }">
                                    <td class="px-4 py-3 text-gray-900">{{ res.name }}</td>
                                    <td class="px-4 py-3 font-mono text-gray-500">{{ res.sku }}</td>
                                    <td class="px-4 py-3 font-mono uppercase">{{ res.arimaOrder }}</td>
                                    <td class="px-4 py-3 font-mono">
                                        <span v-if="res.success" :class="res.mapeScore > 20 ? 'text-yellow-600' : 'text-green-600'">{{ res.mapeScore }}%</span>
                                        <span v-else class="text-red-500">-</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="res.success" class="text-[9px] bg-green-50 text-green-600 font-bold px-1.5 py-0.5 rounded-xs uppercase">Sukses</span>
                                        <span v-else class="text-[9px] bg-red-50 text-red-600 font-bold px-1.5 py-0.5 rounded-xs uppercase" :title="res.errorMessage">Gagal</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-[10px] text-gray-400 italic font-semibold">* Klik baris produk di atas untuk melihat detail grafik ramalan ARIMA di bawah.</p>
                </div>

                <!-- Grafik Peramalan SVG Reaktif Premium -->
                <div v-if="showChart" class="bg-white border border-gray-200 rounded-sm p-6 space-y-6">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Grafik Proyeksi Permintaan</h4>
                        <div class="flex space-x-4 text-[10px]">
                            <span class="flex items-center text-gray-500 font-semibold">
                                <span class="h-2 w-2 bg-gray-400 rounded-full mr-1.5"></span> Data Historis
                            </span>
                            <span class="flex items-center text-[#FF1F8F] font-semibold">
                                <span class="h-2 w-2 bg-[#FF1F8F] rounded-full mr-1.5"></span> Proyeksi ARIMA
                            </span>
                        </div>
                    </div>

                    <!-- SVG Line Chart Reaktif -->
                    <div class="relative w-full h-64 border border-gray-100 bg-gray-50/50 p-4 rounded-sm">
                        <svg class="w-full h-full" viewBox="0 0 600 220" preserveAspectRatio="none">
                            <!-- Grid Lines -->
                            <line x1="0" y1="50" x2="600" y2="50" stroke="#E5E7EB" stroke-dasharray="4" />
                            <line x1="0" y1="110" x2="600" y2="110" stroke="#E5E7EB" stroke-dasharray="4" />
                            <line x1="0" y1="170" x2="600" y2="170" stroke="#E5E7EB" stroke-dasharray="4" />

                            <!-- Path Historis -->
                            <path :d="historisPath" fill="none" stroke="#9CA3AF" stroke-width="2.5" stroke-linecap="round" />

                            <!-- Path Prediksi -->
                            <path :d="prediksiPath" fill="none" stroke="#FF1F8F" stroke-width="2.5" stroke-dasharray="5 3" stroke-linecap="round" />

                            <!-- Bulatan Hubungan -->
                            <circle v-for="point in chartPoints" :key="point.id" :cx="point.x" :cy="point.y" :fill="point.isPredict ? '#FF1F8F' : '#9CA3AF'" r="3.5" />
                        </svg>

                        <!-- Label Sumbu-X / Tanggal -->
                        <div class="flex justify-between text-[9px] text-gray-400 mt-2 font-mono px-2">
                            <span>Awal Penjualan</span>
                            <span>Hari Ini</span>
                            <span>Akhir Proyeksi ({{ form.forecast_periods }} hari)</span>
                        </div>
                    </div>

                    <!-- Informasi Model Metrics -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 bg-gray-50 border border-gray-100 rounded-sm p-4 text-xs">
                        <div>
                            <span class="text-[9px] text-gray-400 uppercase font-bold tracking-wider block">Koefisien ARIMA Order</span>
                            <p class="font-mono font-bold text-gray-900 mt-1 uppercase">{{ arimaOrder }}</p>
                        </div>
                        <div>
                            <span class="text-[9px] text-gray-400 uppercase font-bold tracking-wider block">Evaluasi MAPE Score</span>
                            <p :class="mapeScore > 20 ? 'text-yellow-600' : 'text-green-600'" class="font-mono font-bold mt-1">
                                {{ mapeScore }}% 
                                <span class="text-[9px] font-semibold font-sans ml-1 text-gray-400">({{ mapeMethod }})</span>
                            </p>
                        </div>
                        <div>
                            <span class="text-[9px] text-gray-400 uppercase font-bold tracking-wider block">Waktu Komputasi</span>
                            <p class="font-mono font-bold text-gray-900 mt-1">{{ execTimeMs }} ms</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Area Kanan: Log Pemantauan ARIMA Terbaru (col-span-4 - 38% Golden Ratio) -->
            <div class="lg:col-span-4 bg-white border border-gray-200 rounded-sm p-6 flex flex-col justify-between h-fit">
                <div>
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-6">Log Pemantauan Algoritma</h3>

                    <div v-if="logs.length === 0" class="text-center py-16 text-xs text-gray-400">
                        Tidak ada log prediksi terekam.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="log in logs" :key="log.id" class="text-xs border-b border-gray-100 pb-3.5 last:border-0 last:pb-0">
                            <div class="flex justify-between font-bold">
                                <span class="text-gray-900 truncate max-w-[130px]" :title="log.product_name">{{ log.product_name }}</span>
                                <span class="font-mono text-[#FF1F8F]">MAPE: {{ log.mape_score }}%</span>
                            </div>
                            <div class="flex justify-between text-[9px] text-gray-400 mt-1.5 font-mono">
                                <span>Pemicu: {{ log.user?.name || 'Sistem' }}</span>
                                <span>{{ formatDate(log.created_at) }}</span>
                            </div>
                            <div class="mt-1 flex items-center space-x-1.5">
                                <span class="text-[8px] bg-gray-100 text-gray-500 font-mono px-1 py-0.25 rounded-xs uppercase">Order: {{ log.arima_order }}</span>
                                <span class="text-[8px] bg-gray-100 text-gray-500 font-mono px-1 py-0.25 rounded-xs uppercase">Period: {{ log.forecast_periods }}d</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div v-if="toast" class="fixed bottom-4 right-4 z-[999] bg-gray-900 text-white text-xs font-semibold px-4 py-3 rounded-sm border-l-4 border-[#FF1F8F] flex items-center space-x-2">
            <span>{{ toast }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import axios from 'axios';

const products = ref([]);
const logs = ref([]);
const lastGlobalTuning = computed(() => {
    const globalLog = logs.value.find(l => l.product_name === 'GLOBAL_SALES');
    return globalLog ? formatDate(globalLog.created_at) : 'Belum pernah';
});
const toast = ref('');
let toastTimer = null;

const selectionMode = ref('single');
const selectedProductIds = ref([]);
const searchQuery = ref('');
const batchLoading = ref(false);
const selectedBatchProductId = ref(null);
const batchResults = ref([]);
const batchProgress = ref({
    current: 0,
    total: 0,
    currentName: '',
    averageMape: 0,
    successCount: 0
});

const form = reactive({
    product_id: '',
    forecast_periods: 7,
    fill_missing_dates: true,
    smooth_outliers: true
});

const globalForm = reactive({
    forecast_periods: 7,
    fill_missing_dates: true,
    smooth_outliers: true
});

const currentConfigTuningTime = ref(null);
const globalConfigTuningTime = ref(null);

// Real-time chart states
const showChart = ref(false);
const predictLoading = ref(false);
const arimaOrder = ref('N/A');
const mapeScore = ref(0.0);
const mapeMethod = ref('unknown');
const execTimeMs = ref(0);
const chartData = ref({
    historis: [],
    prediksi: []
});

const activePipelineStep = ref(0);
const pipelineSteps = [
    {
        title: 'Ingesti Data',
        description: 'Menarik riwayat penjualan harian dari database MySQL/Supabase menggunakan filter completed orders. Data ditarik atomic per produk ter-seeding.',
        formula: 'Sales_t = ∑ Qty(t) for completed orders',
        code: `sales_data = OrderItem.select(
    DB.raw('DATE(orders.created_at) as date'),
    DB.raw('SUM(order_items.qty) as total_sales')
)
.join('orders', 'order_items.order_id', '=', 'orders.id')
.where('order_items.product_id', product_id)
.where('orders.status', 'completed')
.groupBy('date').orderBy('date', 'asc').get()`
    },
    {
        title: 'Zero-Fill Gaps',
        description: 'Membangun time series kontinu berfrekuensi harian hulu-ke-hilir. Celah kekosongan tanggal akibat libur/tutup diisi dengan 0 secara presisi.',
        formula: 'reindex(pd.date_range(min, max), fill_value=0)',
        code: `# Pandas Daily Reindexing Gaps Fill
full_range = pd.date_range(
    start=df.index.min(), 
    end=df.index.max(), 
    freq='D'
)
df = df.reindex(full_range, fill_value=0)
df.index.name = 'date'`
    },
    {
        title: 'Outlier Smooth',
        description: 'Membatasi nilai penjualan ekstrim di luar kewajaran (anomali) menggunakan Tukey IQR (Interquartile Range) Method agar model ramalan tidak bias.',
        formula: 'Limit = Q3 + 1.5 * (Q3 - Q1) <br> Sales_t = clip(Sales_t, 0, Limit)',
        code: `# Tukey IQR Outlier Capping
q1 = df['total_sales'].quantile(0.25)
q3 = df['total_sales'].quantile(0.75)
iqr = q3 - q1
upper_limit = q3 + 1.5 * iqr

# Capping outliers dynamically
df['total_sales'] = np.clip(df['total_sales'], 0, upper_limit)`
    },
    {
        title: 'MAPE Adaptif',
        description: 'Menyeimbangkan kalkulasi akurasi model. Split 80/20 train-test jika data ≥ 15 hari untuk evaluasi out-of-sample MAPE. Sisa data minim dievaluasi in-sample.',
        formula: 'MAPE = (100% / N) * ∑ |(Y_t - Ŷ_t) / Y_t|',
        code: `# Out-of-sample Split and Evaluation
split_idx = int(len(ts_series) * 0.8)
train = ts_series.iloc[:split_idx]
test = ts_series.iloc[split_idx:]

# Out-of-sample MAPE calculation
test_preds = model.predict(n_periods=len(test))
mape = np.mean(np.abs((test - test_preds) / np.clip(test, 1, None))) * 100`
    },
    {
        title: 'Auto-ARIMA',
        description: 'Memilih secara dinamis model parameter (p, d, q) terbaik berdasarkan pencarian stepwise pmdarima untuk optimasi AIC/BIC terendah.',
        formula: 'ARIMA(p, d, q) x AIC = 2k - 2ln(L̂)',
        code: `# Auto-ARIMA stepwise coefficients bounds
model = auto_arima(
    ts_series, start_p=start_p, start_q=start_q, 
    max_p=max_p, max_q=max_q,
    d=None, seasonal=seasonal, stepwise=stepwise,
    error_action='ignore', suppress_warnings=True
)`
    },
    {
        title: 'Cache & Badge',
        description: 'Meng-capping ramalan ≥ 0 (no negative), mencatat riwayat pemantauan di prediction_logs, dan menyimpan 12 jam cache rekomendasi storefront.',
        formula: 'Forecast_result = max(Predicted_Sales, 0)',
        code: `# Capping negative predictions and caching
forecast_values = np.clip(model.predict(n_periods=periods), 0, None)

# Laravel cache update for storefront recommendations
Cache::put('arima_storefront_recommendations', $updatedRecs, now()->addHours(12))`
    }
];

const configForm = reactive({
    arima_forecast_periods: 7,
    arima_fill_missing_dates: true,
    arima_smooth_outliers: true,
    arima_start_p: 0,
    arima_start_q: 0,
    arima_max_p: 5,
    arima_max_q: 5,
    arima_seasonal: false,
    arima_stepwise: true
});

const fetchArimaConfig = async () => {
    try {
        const response = await axios.get('/api/admin/arima/config');
        Object.assign(configForm, response.data);
    } catch (e) {
        console.error('Gagal mengambil konfigurasi ARIMA:', e);
    }
};

const saveArimaConfig = async () => {
    try {
        showToast('Menyimpan konfigurasi parameter ARIMA...');
        const response = await axios.post('/api/admin/arima/config', configForm);
        showToast(response.data.message || 'Konfigurasi berhasil disimpan!');
    } catch (error) {
        const msg = error.response?.data?.message || 'Gagal menyimpan konfigurasi.';
        showToast(msg);
    }
};

const fetchProducts = async () => {
    try {
        const response = await axios.get('/api/admin/products?low_stock=false&all=true');
        products.value = response.data.data;
    } catch (e) {
        showToast('Gagal memuat katalog produk.');
    }
};

const fetchLogs = async () => {
    try {
        const response = await axios.get('/api/admin/arima-logs');
        logs.value = response.data;
    } catch (e) {
        console.error('Gagal mengambil logs ARIMA:', e);
    }
};

const selectAllProducts = () => {
    selectedProductIds.value = products.value.map(p => p.id);
};

const clearAllProducts = () => {
    selectedProductIds.value = [];
};

const filteredProducts = computed(() => {
    if (!searchQuery.value) return products.value;
    const q = searchQuery.value.toLowerCase();
    return products.value.filter(p => 
        p.name.toLowerCase().includes(q) || 
        p.sku.toLowerCase().includes(q)
    );
});

const selectBatchResult = (res) => {
    if (!res.success) return;
    selectedBatchProductId.value = res.id;
    arimaOrder.value = res.arimaOrder;
    mapeScore.value = res.mapeScore;
    mapeMethod.value = res.mapeMethod;
    execTimeMs.value = res.execTime;
    chartData.value = res.chartData;
    showChart.value = true;
};

const tuneAllProducts = () => {
    selectionMode.value = 'batch';
    selectedProductIds.value = products.value.map(p => p.id);
    runBatchPrediction();
};

const handleSubmit = () => {
    if (selectionMode.value === 'single') {
        runPrediction();
    } else {
        runBatchPrediction();
    }
};

const runPrediction = async () => {
    if (predictLoading.value) return;
    predictLoading.value = true;
    showToast('Menghubungi Flask ARIMA Microservice...');

    // Reset batch state
    selectedBatchProductId.value = null;
    batchResults.value = [];

    try {
        const response = await axios.post('/api/admin/predict-arima', form);
        
        arimaOrder.value = response.data.arima_order;
        mapeScore.value = response.data.mape_score;
        mapeMethod.value = response.data.mape_method;
        execTimeMs.value = response.data.log.execution_time_ms;

        // Populate Chart Data
        const hData = response.data.forecast_result.filter(r => !r.is_forecast);
        const pData = response.data.forecast_result.filter(r => r.is_forecast);

        // Jika data historis berlimpah, batasi 14 hari terakhir untuk kemudahan visualisasi
        chartData.value.historis = hData.slice(-14);
        chartData.value.prediksi = pData;

        showChart.value = true;
        showToast('Analisis peramalan ARIMA sukses dirender!');
        fetchLogs();

    } catch (error) {
        const msg = error.response?.data?.message || 'Proses kalkulasi model ARIMA gagal.';
        showToast(msg);
    } finally {
        predictLoading.value = false;
    }
};

const runGlobalPrediction = async () => {
    if (predictLoading.value) return;
    predictLoading.value = true;
    showToast('Menghubungi Flask ARIMA Microservice...');

    // Reset batch state
    selectedBatchProductId.value = null;
    batchResults.value = [];

    try {
        const response = await axios.post('/api/admin/predict-arima-global', globalForm);
        
        arimaOrder.value = response.data.arima_order;
        mapeScore.value = response.data.mape_score;
        mapeMethod.value = response.data.mape_method;
        execTimeMs.value = response.data.log.execution_time_ms;

        // Populate Chart Data
        const hData = response.data.forecast_result.filter(r => !r.is_forecast);
        const pData = response.data.forecast_result.filter(r => r.is_forecast);

        // Batasi 14 hari terakhir untuk kemudahan visualisasi
        chartData.value.historis = hData.slice(-14);
        chartData.value.prediksi = pData;

        showChart.value = true;
        showToast('Analisis peramalan ARIMA sukses dirender!');
        fetchLogs();

    } catch (error) {
        const msg = error.response?.data?.message || 'Proses kalkulasi model ARIMA gagal.';
        showToast(msg);
    } finally {
        predictLoading.value = false;
    }
};

const runBatchPrediction = async () => {
    if (batchLoading.value) return;
    const idsToTune = selectedProductIds.value;

    if (idsToTune.length === 0) {
        showToast('Silakan pilih minimal satu produk untuk di-tune.');
        return;
    }

    batchLoading.value = true;
    batchResults.value = [];
    batchProgress.value = {
        current: 0,
        total: idsToTune.length,
        currentName: '',
        averageMape: 0,
        successCount: 0
    };

    let totalMape = 0;
    selectedBatchProductId.value = null;
    showChart.value = false;

    for (const id of idsToTune) {
        const product = products.value.find(p => p.id === id);
        if (!product) continue;

        batchProgress.value.current++;
        batchProgress.value.currentName = `${product.name} (${product.sku})`;

        try {
            const response = await axios.post('/api/admin/predict-arima', {
                product_id: product.id,
                forecast_periods: form.forecast_periods,
                fill_missing_dates: form.fill_missing_dates,
                smooth_outliers: form.smooth_outliers
            });

            const mape = response.data.mape_score;
            totalMape += mape;
            batchProgress.value.successCount++;
            batchProgress.value.averageMape = Number((totalMape / batchProgress.value.successCount).toFixed(2));

            const hData = response.data.forecast_result.filter(r => !r.is_forecast);
            const pData = response.data.forecast_result.filter(r => r.is_forecast);

            const resultObj = {
                id: product.id,
                name: product.name,
                sku: product.sku,
                arimaOrder: response.data.arima_order,
                mapeScore: mape,
                mapeMethod: response.data.mape_method,
                execTime: response.data.log.execution_time_ms,
                chartData: {
                    historis: hData.slice(-14),
                    prediksi: pData
                },
                success: true
            };

            batchResults.value.unshift(resultObj);

            if (batchProgress.value.successCount === 1) {
                selectBatchResult(resultObj);
            }

        } catch (error) {
            batchResults.value.unshift({
                id: product.id,
                name: product.name,
                sku: product.sku,
                arimaOrder: 'Error',
                mapeScore: 0,
                mapeMethod: 'N/A',
                execTime: 0,
                chartData: null,
                success: false,
                errorMessage: error.response?.data?.message || 'Gagal kalkulasi.'
            });
        }
    }

    batchLoading.value = false;
    showToast(`Batch tuning selesai! Berhasil: ${batchProgress.value.successCount}/${batchProgress.value.total}`);
    fetchLogs();
};

// SVG Coordinate Calculations
const chartPoints = computed(() => {
    const points = [];
    const hist = chartData.value.historis;
    const pred = chartData.value.prediksi;

    if (hist.length === 0 && pred.length === 0) return [];

    const allValues = hist.concat(pred).map(p => Number(p.sales));
    const maxVal = Math.max(...allValues, 10);
    const minVal = Math.min(...allValues, 0);
    const range = maxVal - minVal;

    const totalSteps = hist.length + pred.length;
    const stepWidth = 600 / (totalSteps - 1 || 1);

    // Hitung Historis
    hist.forEach((p, index) => {
        const x = index * stepWidth;
        // Skala Y: 0 di bawah (180), max di atas (20)
        const y = 180 - ((p.sales - minVal) / range) * 160;
        points.push({ id: `h-${index}`, x, y, isPredict: false });
    });

    // Hitung Prediksi
    const histLen = hist.length;
    pred.forEach((p, index) => {
        const x = (histLen + index) * stepWidth;
        const y = 180 - ((p.sales - minVal) / range) * 160;
        points.push({ id: `p-${index}`, x, y, isPredict: true });
    });

    return points;
});

const historisPath = computed(() => {
    const histPoints = chartPoints.value.filter(p => !p.isPredict);
    if (histPoints.length === 0) return '';
    return histPoints.map((p, index) => `${index === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');
});

const prediksiPath = computed(() => {
    const allPoints = chartPoints.value;
    const predPoints = allPoints.filter(p => p.isPredict);
    if (predPoints.length === 0) return '';

    // Gabungkan titik historis terakhir dengan titik prediksi pertama agar path tersambung
    const lastHist = allPoints.filter(p => !p.isPredict).slice(-1)[0];
    const prefix = lastHist ? `M ${lastHist.x} ${lastHist.y} L ` : 'M ';

    return prefix + predPoints.map(p => `${p.x} ${p.y}`).join(' L ');
});

const showToast = (msg) => {
    toast.value = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.value = '';
    }, 4000);
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Watch product_id to load custom tuning config
watch(() => form.product_id, async (newVal) => {
    if (!newVal) {
        currentConfigTuningTime.value = null;
        return;
    }
    try {
        const response = await axios.get(`/api/admin/arima/tuning-configs/${newVal}`);
        if (response.data) {
            form.forecast_periods = response.data.forecast_periods;
            form.fill_missing_dates = response.data.fill_missing_dates;
            form.smooth_outliers = response.data.smooth_outliers;
            currentConfigTuningTime.value = response.data.last_tuned_at ? formatDate(response.data.last_tuned_at) : 'Belum pernah';
        }
    } catch (e) {
        console.error('Gagal mengambil setelan produk ARIMA:', e);
    }
});

// Watch selectionMode to load global custom tuning config
watch(selectionMode, async (newMode) => {
    if (newMode === 'global') {
        try {
            const response = await axios.get('/api/admin/arima/tuning-configs/global');
            if (response.data) {
                globalForm.forecast_periods = response.data.forecast_periods;
                globalForm.fill_missing_dates = response.data.fill_missing_dates;
                globalForm.smooth_outliers = response.data.smooth_outliers;
                globalConfigTuningTime.value = response.data.last_tuned_at ? formatDate(response.data.last_tuned_at) : 'Belum pernah';
            }
        } catch (e) {
            console.error('Gagal mengambil setelan global ARIMA:', e);
        }
    }
});

// Save Product Tuning Config
const saveProductTuningConfig = async () => {
    if (!form.product_id) return;
    try {
        showToast('Menyimpan setelan tuning produk...');
        const response = await axios.post('/api/admin/arima/tuning-configs', {
            product_id: String(form.product_id),
            forecast_periods: form.forecast_periods,
            fill_missing_dates: form.fill_missing_dates,
            smooth_outliers: form.smooth_outliers,
            start_p: configForm.arima_start_p,
            start_q: configForm.arima_start_q,
            max_p: configForm.arima_max_p,
            max_q: configForm.arima_max_q,
            seasonal: configForm.arima_seasonal,
            stepwise: configForm.arima_stepwise
        });
        showToast(response.data.message || 'Setelan produk berhasil disimpan!');
        
        // Refresh tuning timestamp info
        const response2 = await axios.get(`/api/admin/arima/tuning-configs/${form.product_id}`);
        currentConfigTuningTime.value = response2.data.last_tuned_at ? formatDate(response2.data.last_tuned_at) : 'Belum pernah';
    } catch (error) {
        showToast(error.response?.data?.message || 'Gagal menyimpan setelan.');
    }
};

// Save Global Tuning Config
const saveGlobalTuningConfig = async () => {
    try {
        showToast('Menyimpan setelan tuning global...');
        const response = await axios.post('/api/admin/arima/tuning-configs', {
            product_id: 'global',
            forecast_periods: globalForm.forecast_periods,
            fill_missing_dates: globalForm.fill_missing_dates,
            smooth_outliers: globalForm.smooth_outliers,
            start_p: configForm.arima_start_p,
            start_q: configForm.arima_start_q,
            max_p: configForm.arima_max_p,
            max_q: configForm.arima_max_q,
            seasonal: configForm.arima_seasonal,
            stepwise: configForm.arima_stepwise
        });
        showToast(response.data.message || 'Setelan global berhasil disimpan!');
        
        // Refresh tuning timestamp info
        const response2 = await axios.get('/api/admin/arima/tuning-configs/global');
        globalConfigTuningTime.value = response2.data.last_tuned_at ? formatDate(response2.data.last_tuned_at) : 'Belum pernah';
    } catch (error) {
        showToast(error.response?.data?.message || 'Gagal menyimpan setelan.');
    }
};

const fetchInitialGlobalConfig = async () => {
    try {
        const response = await axios.get('/api/admin/arima/tuning-configs/global');
        if (response.data) {
            globalForm.forecast_periods = response.data.forecast_periods;
            globalForm.fill_missing_dates = response.data.fill_missing_dates;
            globalForm.smooth_outliers = response.data.smooth_outliers;
            globalConfigTuningTime.value = response.data.last_tuned_at ? formatDate(response.data.last_tuned_at) : 'Belum pernah';
        }
    } catch (e) {
        console.error(e);
    }
};

onMounted(() => {
    fetchProducts();
    fetchLogs();
    fetchArimaConfig();
    fetchInitialGlobalConfig();
});

</script>

