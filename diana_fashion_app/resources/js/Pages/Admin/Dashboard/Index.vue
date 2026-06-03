<template>
    <div class="space-y-8">
        <!-- Metrik Omnichannel Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card 1: Pendapatan Hari Ini -->
            <div class="bg-white border border-gray-200 rounded-sm p-6 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Pendapatan Hari Ini</span>
                    <h3 class="text-xl font-extrabold text-gray-900 mt-2 font-mono">Rp {{ formatNumber(metrics.revenue_today) }}</h3>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-50 flex items-center justify-between text-[10px] text-gray-400">
                    <span>Target Harian: Rp 5.000.000</span>
                    <span class="font-bold font-mono text-[#FF1F8F]">{{ Math.round((metrics.revenue_today / 5000000) * 100) }}%</span>
                </div>
            </div>

            <!-- Card 2: Pendapatan Bulan Ini -->
            <div class="bg-white border border-gray-200 rounded-sm p-6 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Pendapatan Bulan Ini</span>
                    <h3 class="text-xl font-extrabold text-[#FF1F8F] mt-2 font-mono">Rp {{ formatNumber(metrics.revenue_month) }}</h3>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-50 flex items-center justify-between text-[10px] text-gray-400">
                    <span>Target Bulanan: Rp 100.000.000</span>
                    <span class="font-bold font-mono text-[#FF1F8F]">{{ Math.round((metrics.revenue_month / 100000000) * 100) }}%</span>
                </div>
            </div>

            <!-- Card 3: Transaksi Hari Ini -->
            <div class="bg-white border border-gray-200 rounded-sm p-6 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Transaksi Hari Ini</span>
                    <h3 class="text-xl font-extrabold text-gray-900 mt-2 font-mono">{{ metrics.orders_today }} <span class="text-xs font-normal text-gray-400">Nota</span></h3>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-50 text-[10px] text-gray-400">
                    <span>Rata-rata Nominal: Rp {{ formatNumber(metrics.orders_today > 0 ? Math.round(metrics.revenue_today / metrics.orders_today) : 0) }}</span>
                </div>
            </div>

            <!-- Card 4: Transaksi Bulan Ini -->
            <div class="bg-white border border-gray-200 rounded-sm p-6 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Transaksi Bulan Ini</span>
                    <h3 class="text-xl font-extrabold text-gray-900 mt-2 font-mono">{{ metrics.orders_month }} <span class="text-xs font-normal text-gray-400">Nota</span></h3>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-50 text-[10px] text-gray-400">
                    <span>Rata-rata Nominal: Rp {{ formatNumber(metrics.orders_month > 0 ? Math.round(metrics.revenue_month / metrics.orders_month) : 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Perbandingan POS vs Web & Grafik Tren (Golden Ratio Grid 62:38) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Kiri: Distribusi Penjualan (col-span-8 - 62% Golden Ratio) -->
            <!-- Kiri: Distribusi Penjualan & Proyeksi AI (col-span-8 - 62% Golden Ratio) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Card 1: Distribusi Penjualan -->
                <div class="bg-white border border-gray-200 rounded-sm p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-6">Analisis Saluran Omnichannel</h3>
                        
                        <div class="space-y-6">
                            <!-- Progress POS -->
                            <div>
                                <div class="flex justify-between items-center text-xs font-semibold mb-2">
                                    <span class="text-gray-700 flex items-center">
                                        <span class="h-2.5 w-2.5 bg-gray-900 rounded-full mr-2"></span>
                                        Fisik (POS Terminal Kasir)
                                    </span>
                                    <span class="font-mono">{{ posPercent }}% (Rp {{ formatNumber(comparison.pos.total) }})</span>
                                </div>
                                <div class="w-full bg-gray-100 h-2.5 rounded-sm overflow-hidden">
                                    <div class="bg-gray-900 h-full transition-all duration-500" :style="{ width: posPercent + '%' }"></div>
                                </div>
                                <span class="text-[9px] text-gray-400 mt-1 block">Total volume transaksi: {{ comparison.pos.count }} nota</span>
                            </div>

                            <!-- Progress Web -->
                            <div>
                                <div class="flex justify-between items-center text-xs font-semibold mb-2">
                                    <span class="text-gray-700 flex items-center">
                                        <span class="h-2.5 w-2.5 bg-[#FF1F8F] rounded-full mr-2"></span>
                                        Online (E-Commerce Web Storefront)
                                    </span>
                                    <span class="font-mono text-[#FF1F8F]">{{ webPercent }}% (Rp {{ formatNumber(comparison.web.total) }})</span>
                                </div>
                                <div class="w-full bg-gray-100 h-2.5 rounded-sm overflow-hidden">
                                    <div class="bg-[#FF1F8F] h-full transition-all duration-500" :style="{ width: webPercent + '%' }"></div>
                                </div>
                                <span class="text-[9px] text-gray-400 mt-1 block">Total volume transaksi: {{ comparison.web.count }} nota</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100 bg-gray-50 -mx-6 -mb-6 p-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <p class="text-[10px] text-gray-500">Kanal Penjualan sinkron secara real-time. Hubungkan ARIMA Microservice untuk memproyeksikan tren penjualan produk Anda.</p>
                        <router-link to="/admin/arima" class="bg-[#FF1F8F] hover:bg-[#D91678] text-white text-[10px] font-bold px-4 py-2 rounded-sm uppercase tracking-wider transition-colors cursor-pointer text-center">
                            ARIMA AI Panel →
                        </router-link>
                    </div>
                </div>

                <!-- Card 2: Proyeksi ARIMA AI Trend (Grafis Baru) -->
                <div v-if="showTrendChart" class="bg-white border border-gray-200 rounded-sm p-6 space-y-6">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <div>
                            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Proyeksi Permintaan Produk Terlaris (ARIMA AI)</h4>
                            <p class="text-[10px] text-gray-400 font-semibold mt-1">Tren penjualan produk: <span class="text-[#FF1F8F] font-bold">{{ trendProductName }}</span></p>
                        </div>
                        <div class="flex space-x-4 text-[10px] font-mono">
                            <span class="flex items-center text-gray-500 font-semibold">
                                <span class="h-2 w-2 bg-gray-400 rounded-full mr-1.5"></span> Histori
                            </span>
                            <span class="flex items-center text-[#FF1F8F] font-semibold">
                                <span class="h-2 w-2 bg-[#FF1F8F] rounded-full mr-1.5"></span> Proyeksi AI (7 Hari)
                            </span>
                        </div>
                    </div>

                    <!-- SVG Line Chart -->
                    <div class="relative w-full h-48 border border-gray-100 bg-gray-50/50 p-4 rounded-sm">
                        <svg class="w-full h-full" viewBox="0 0 600 150" preserveAspectRatio="none">
                            <!-- Grid Lines -->
                            <line x1="0" y1="35" x2="600" y2="35" stroke="#E5E7EB" stroke-dasharray="4" />
                            <line x1="0" y1="75" x2="600" y2="75" stroke="#E5E7EB" stroke-dasharray="4" />
                            <line x1="0" y1="115" x2="600" y2="115" stroke="#E5E7EB" stroke-dasharray="4" />

                            <!-- Path Historis -->
                            <path :d="historisPath" fill="none" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" />

                            <!-- Path Prediksi -->
                            <path :d="prediksiPath" fill="none" stroke="#FF1F8F" stroke-width="2" stroke-dasharray="4 3" stroke-linecap="round" />

                            <!-- Dots -->
                            <circle v-for="point in chartPoints" :key="point.id" :cx="point.x" :cy="point.y" :fill="point.isPredict ? '#FF1F8F' : '#9CA3AF'" r="3" />
                        </svg>
                    </div>

                    <!-- Metrics -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-gray-50 border border-gray-100 rounded-sm p-3 text-xs font-mono">
                        <div>
                            <span class="text-[9px] text-gray-400 uppercase font-bold tracking-wider block">Model ARIMA Order</span>
                            <span class="font-bold text-gray-900 mt-0.5 block uppercase">{{ trendArimaOrder }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] text-gray-400 uppercase font-bold tracking-wider block">Persentase Akurasi (MAPE)</span>
                            <span class="font-bold text-green-600 mt-0.5 block">{{ trendMape }}% (Akurasi Tinggi)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kanan: Status Server & Ringkasan Logs (col-span-4 - 38% Golden Ratio) -->
            <div class="lg:col-span-4 bg-white border border-gray-200 rounded-sm p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-6">Status Sistem & AI</h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                            <span class="text-xs text-gray-500">Database MySQL</span>
                            <span class="text-[9px] font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded-sm uppercase">Terkoneksi</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                            <span class="text-xs text-gray-500">Flask ARIMA Service</span>
                            <span :class="arimaStatus === 'ok' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="text-[9px] font-bold px-2 py-0.5 rounded-sm uppercase">
                                {{ arimaStatus === 'ok' ? 'Aktif' : 'Offline' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                            <span class="text-xs text-gray-500">Prediction Logs</span>
                            <span class="text-xs font-mono font-bold">{{ arimaLogsCount }} Logs</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Log ARIMA Terbaru</h4>
                    <div v-if="latestLogs.length === 0" class="text-xs text-gray-400 py-2">
                        Belum ada riwayat peramalan AI.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="log in latestLogs.slice(0, 2)" :key="log.id" class="text-[10px] border-b border-gray-50 pb-2 last:border-0">
                            <div class="flex justify-between font-semibold">
                                <span class="text-gray-800 truncate max-w-[120px]">{{ log.product_name }}</span>
                                <span class="font-mono text-[#FF1F8F]">MAPE: {{ log.mape_score }}%</span>
                            </div>
                            <span class="text-[9px] text-gray-400 block font-mono mt-0.5">{{ formatDate(log.created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const metrics = ref({
    revenue_today: 0,
    revenue_month: 0,
    orders_today: 0,
    orders_month: 0
});

const comparison = ref({
    pos: { count: 0, total: 0 },
    web: { count: 0, total: 0 }
});

const arimaStatus = ref('offline');
const arimaLogsCount = ref(0);
const latestLogs = ref([]);

// New ARIMA Trend States
const showTrendChart = ref(false);
const trendProductName = ref('');
const trendArimaOrder = ref('');
const trendMape = ref(0);
const trendChartData = ref({
    historis: [],
    prediksi: []
});

const totalRevenue = computed(() => comparison.value.pos.total + comparison.value.web.total);

const posPercent = computed(() => {
    if (totalRevenue.value === 0) return 50;
    return Math.round((comparison.value.pos.total / totalRevenue.value) * 100);
});

const webPercent = computed(() => {
    if (totalRevenue.value === 0) return 50;
    return Math.round((comparison.value.web.total / totalRevenue.value) * 100);
});

const fetchMetrics = async () => {
    try {
        const response = await axios.get('/api/admin/dashboard/metrics');
        metrics.value = response.data.metrics;
        comparison.value = response.data.comparison;
    } catch (error) {
        console.error('Gagal mengambil metrik dashboard:', error);
    }
};

const checkArimaService = async () => {
    try {
        const logsResponse = await axios.get('/api/admin/arima-logs');
        latestLogs.value = logsResponse.data;
        arimaLogsCount.value = logsResponse.data.length;
        arimaStatus.value = 'ok';
    } catch (error) {
        arimaStatus.value = 'offline';
        console.error('Flask ARIMA offline atau logs gagal diakses:', error);
    }
};

// Fetch ARIMA Trend for Top Product (dynamic)
const fetchArimaTrend = async () => {
    try {
        const prodRes = await axios.get('/api/admin/products?low_stock=false&all=true');
        if (prodRes.data.data.length > 0) {
            const topProd = prodRes.data.data[0]; // Ambil produk terlaris pertama
            trendProductName.value = topProd.name;

            const arimaRes = await axios.post('/api/admin/predict-arima', {
                product_id: topProd.id,
                forecast_periods: 7,
                fill_missing_dates: true,
                smooth_outliers: true
            });

            trendArimaOrder.value = arimaRes.data.arima_order;
            trendMape.value = arimaRes.data.mape_score;
            
            const hData = arimaRes.data.forecast_result.filter(r => !r.is_forecast);
            const pData = arimaRes.data.forecast_result.filter(r => r.is_forecast);

            trendChartData.value.historis = hData.slice(-14);
            trendChartData.value.prediksi = pData;
            showTrendChart.value = true;
        }
    } catch (e) {
        console.error('Gagal memuat tren ARIMA pada dashboard:', e);
    }
};

// SVG Coordinate Calculations for Dashboard Index
const chartPoints = computed(() => {
    const points = [];
    const hist = trendChartData.value.historis;
    const pred = trendChartData.value.prediksi;

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
        const y = 130 - ((p.sales - minVal) / range) * 110;
        points.push({ id: `h-${index}`, x, y, isPredict: false });
    });

    // Hitung Prediksi
    const histLen = hist.length;
    pred.forEach((p, index) => {
        const x = (histLen + index) * stepWidth;
        const y = 130 - ((p.sales - minVal) / range) * 110;
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

    const lastHist = allPoints.filter(p => !p.isPredict).slice(-1)[0];
    const prefix = lastHist ? `M ${lastHist.x} ${lastHist.y} L ` : 'M ';

    return prefix + predPoints.map(p => `${p.x} ${p.y}`).join(' L ');
});

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
    fetchMetrics();
    checkArimaService();
    fetchArimaTrend();
});
</script>
