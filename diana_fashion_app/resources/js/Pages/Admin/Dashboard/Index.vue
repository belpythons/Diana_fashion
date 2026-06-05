<template>
    <div class="space-y-8">
        <!-- Metrik Omnichannel Cards -->
        <div :class="showTrendChart ? 'lg:grid-cols-5' : 'lg:grid-cols-4'" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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

            <!-- Card 5: Proyeksi AI (7 Hari) -->
            <div v-if="showTrendChart" class="bg-white border border-[#FF1F8F] rounded-sm p-6 flex flex-col justify-between shadow-xs animate-fadeIn">
                <div>
                    <div class="flex justify-between items-start">
                        <span class="text-[10px] font-bold text-[#FF1F8F] uppercase tracking-wider block">Proyeksi AI (7 Hari)</span>
                        <span class="text-[8px] bg-pink-100 text-[#FF1F8F] font-bold px-1.5 py-0.5 rounded-xs font-mono uppercase">ARIMA</span>
                    </div>
                    <h3 class="text-xl font-extrabold text-gray-900 mt-2 font-mono">Rp {{ formatNumber(Math.round(totalForecastRevenue)) }}</h3>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-50 flex flex-col space-y-1 text-[9px] text-gray-400 font-medium">
                    <div class="flex justify-between">
                        <span>Akurasi Model:</span>
                        <span class="font-bold text-green-600 font-mono">{{ 100 - trendMape }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Order Model:</span>
                        <span class="font-bold text-gray-700 font-mono uppercase">{{ trendArimaOrder }}</span>
                    </div>
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
                            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Proyeksi Tren Pendapatan Toko Global (ARIMA AI)</h4>
                            <p class="text-[10px] text-gray-400 font-semibold mt-1">Status: <span class="text-[#FF1F8F] font-bold">Tren Ter-tune</span> | Terakhir Tuning: <span class="text-gray-900 font-bold font-mono">{{ lastTuningTime }}</span></p>
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
                            <!-- Dots (Interaktif dengan Hover) -->
                            <circle 
                                v-for="(point, idx) in chartPoints" 
                                :key="point.id" 
                                :cx="point.x" 
                                :cy="point.y" 
                                :fill="point.isPredict ? '#FF1F8F' : '#9CA3AF'" 
                                :r="activeIndex === idx ? 5.5 : 3" 
                                @mouseenter="activeIndex = idx"
                                @mouseleave="activeIndex = -1"
                                class="cursor-pointer transition-all duration-100"
                            />
                        </svg>

                        <!-- Tooltip HTML Absolut Reaktif Premium -->
                        <div 
                            v-if="activeIndex !== -1 && chartPoints[activeIndex]" 
                            :style="{ 
                                left: `${(chartPoints[activeIndex].x / 600) * 100}%`, 
                                top: `${(chartPoints[activeIndex].y / 150) * 100 - 18}%` 
                            }" 
                            class="absolute -translate-x-1/2 -translate-y-full bg-gray-900/95 backdrop-blur-xs text-white text-[9px] px-2.5 py-1.5 rounded-sm shadow-md font-mono z-10 pointer-events-none whitespace-nowrap border border-gray-800 transition-all duration-75"
                        >
                            <div class="flex items-center space-x-1">
                                <span :class="chartPoints[activeIndex].isPredict ? 'bg-[#FF1F8F]' : 'bg-gray-400'" class="h-1.5 w-1.5 rounded-full inline-block"></span>
                                <span class="font-bold text-gray-300">{{ formatDateLabel(chartPoints[activeIndex].date) }}</span>
                            </div>
                            <p class="text-[#FF1F8F] font-extrabold mt-1 text-[10px]">Rp {{ formatNumber(Math.round(chartPoints[activeIndex].sales)) }}</p>
                        </div>
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

                    <!-- Detail Estimasi Pendapatan Harian (7 Hari Ke Depan) -->
                    <div class="border-t border-gray-100 pt-5">
                        <span class="text-[10px] font-bold text-gray-900 uppercase tracking-wider block mb-3 flex items-center">
                            <span class="h-1.5 w-1.5 bg-[#FF1F8F] rounded-full mr-2 animate-pulse"></span>
                            Rincian Estimasi Pendapatan Harian (7 Hari Ke Depan)
                        </span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3">
                            <div v-for="(p, idx) in trendChartData.prediksi" :key="idx" 
                                 class="bg-white border border-gray-100 rounded-xs p-3 text-center transition-all duration-300 hover:border-[#FF1F8F] hover:shadow-xs group">
                                <span class="text-[8px] font-bold text-gray-400 uppercase tracking-wider block group-hover:text-gray-500 transition-colors">{{ formatDateLabelDay(p.date) }}</span>
                                <span class="text-[9px] font-medium text-gray-500 block mt-0.5">{{ formatDateLabelDate(p.date) }}</span>
                                <span class="text-xs font-extrabold text-[#FF1F8F] font-mono block mt-2 group-hover:scale-105 transition-transform duration-200">Rp {{ formatNumber(Math.round(p.sales)) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Reality Check — Aktual vs Prediksi ARIMA -->
                <div class="bg-white border border-[#E5E7EB] rounded-sm p-6 space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-[#E5E7EB] pb-4">
                        <div class="flex items-start">
                            <!-- ARIMA Icon SVG -->
                            <div class="p-2 bg-pink-50 rounded-sm mr-3">
                                <svg class="w-5 h-5 text-[#FF1F8F]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">  
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>  
                                    <polyline points="16 7 22 7 22 13"></polyline>  
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-[#111827] uppercase tracking-wider">Reality Check — Aktual vs Prediksi ARIMA (Pendapatan Toko Global)</h4>
                                <p class="text-[10px] text-[#6B7280] font-semibold mt-1">
                                    Perbandingan performa pendapatan harian toko terhadap model proyeksi global ARIMA 7 hari terakhir.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Loader & Status Messages -->
                    <div v-if="rcLoading" class="py-12 flex flex-col items-center justify-center space-y-3">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-[#FF1F8F]"></div>
                        <span class="text-[10px] font-bold text-[#6B7280] uppercase tracking-wider font-mono">Memuat Data Reality Check...</span>
                    </div>

                    <div v-else-if="rcMessage" class="py-12 text-center bg-red-50/20 border border-[#F87171]/20 rounded-sm p-6">
                        <p class="text-xs text-[#F87171] font-mono">{{ rcMessage }}</p>
                    </div>

                    <!-- Reality Check Data Cards Grid -->
                    <div v-else class="space-y-6 animate-fadeIn">
                        <!-- Accuracy Alert / Summary Header -->
                        <div class="flex justify-between items-center bg-[#F9FAFB] border border-[#E5E7EB] rounded-sm p-4 text-xs font-mono">
                            <span class="font-bold text-[#6B7280]">Akurasi Target Pendapatan:</span>
                            <div class="flex items-center space-x-2">
                                <span class="font-extrabold text-[#111827]">{{ rcAccuracySummary.good }} dari {{ rcAccuracySummary.total }} hari</span>
                                <span class="bg-[#34D399]/10 text-[#34D399] font-mono text-[10px] font-bold px-2 py-0.5 rounded-sm flex items-center">
                                    <svg class="w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    SESUAI
                                </span>
                            </div>
                        </div>

                        <!-- 7 Days Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3">
                            <div 
                                v-for="(day, idx) in rcData" 
                                :key="idx" 
                                :class="[
                                    'bg-white border rounded-xs p-3 text-center transition-all duration-300 flex flex-col justify-between h-48',
                                    day.status === 'green' ? 'border-t-4 border-t-[#34D399] border-[#E5E7EB] hover:border-[#FF1F8F]' : '',
                                    day.status === 'yellow' ? 'border-t-4 border-t-[#FBBF24] border-[#E5E7EB] hover:border-[#FF1F8F]' : '',
                                    day.status === 'red' ? 'border-t-4 border-t-[#F87171] border-[#E5E7EB] hover:border-[#FF1F8F]' : '',
                                    day.status === 'upcoming' ? 'border-t-4 border-t-[#E5E7EB] border-[#E5E7EB] bg-[#F9FAFB] opacity-60' : ''
                                ]"
                            >
                                <!-- Date Label -->
                                <div>
                                    <span class="text-[8px] font-bold text-[#6B7280] uppercase tracking-wider block">{{ formatDateLabelDay(day.date) }}</span>
                                    <span class="text-[9px] font-medium text-[#111827] block mt-0.5">{{ formatDateLabelDate(day.date) }}</span>
                                </div>

                                <!-- Center Status & Progress Bar -->
                                <div class="my-2 flex flex-col items-center">
                                    <template v-if="day.status === 'upcoming'">
                                        <!-- Clock outline SVG -->
                                        <svg class="w-4 h-4 text-gray-400 mb-1 animate-pulse" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span class="text-[8px] font-mono font-bold text-gray-400 uppercase tracking-wider">Menunggu</span>
                                    </template>
                                    <template v-else>
                                        <!-- Mini Progress Bar / Visual Ratio -->
                                        <div class="w-full bg-[#E5E7EB] h-1.5 rounded-full overflow-hidden mt-1 max-w-[50px] mx-auto">
                                            <div 
                                                :class="[
                                                    'h-full transition-all duration-500',
                                                    day.status === 'green' ? 'bg-[#34D399]' : '',
                                                    day.status === 'yellow' ? 'bg-[#FBBF24]' : '',
                                                    day.status === 'red' ? 'bg-[#F87171]' : ''
                                                ]"
                                                :style="{ width: Math.min((day.actual / (day.predicted || 1)) * 100, 100) + '%' }"
                                            ></div>
                                        </div>
                                        
                                        <!-- Dot status -->
                                        <div class="mt-1.5 flex items-center justify-center space-x-1">
                                            <span 
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full inline-block',
                                                    day.status === 'green' ? 'bg-[#34D399]' : '',
                                                    day.status === 'yellow' ? 'bg-[#FBBF24]' : '',
                                                    day.status === 'red' ? 'bg-[#F87171]' : ''
                                                ]"
                                            ></span>
                                            <span 
                                                :class="[
                                                    'text-[8px] font-mono font-bold uppercase tracking-wider',
                                                    day.status === 'green' ? 'text-[#34D399]' : '',
                                                    day.status === 'yellow' ? 'text-[#FBBF24]' : '',
                                                    day.status === 'red' ? 'text-[#F87171]' : ''
                                                ]"
                                            >
                                                {{ day.status === 'green' ? 'Akurat' : day.status === 'yellow' ? 'Rendah' : 'Kritis' }}
                                            </span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Prediction vs Actual Details -->
                                <div class="text-[8px] font-mono pt-2 border-t border-[#E5E7EB] text-left space-y-0.5 mt-auto">
                                    <div class="flex justify-between">
                                        <span class="text-[#6B7280]">PRD:</span>
                                        <span class="font-bold text-[#111827]">Rp{{ formatNumber(day.predicted) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-[#6B7280]">AKT:</span>
                                        <span class="font-bold text-[#111827]">{{ day.status === 'upcoming' ? '—' : 'Rp' + formatNumber(day.actual) }}</span>
                                    </div>

                                    <!-- Deviation Badge -->
                                    <div v-if="day.status !== 'upcoming'" class="flex justify-between items-center pt-1 mt-0.5 border-t border-dashed border-gray-100">
                                        <span class="text-[#6B7280]">DEV:</span>
                                        <span 
                                            :class="[
                                                'font-bold px-1 py-0.5 rounded-xs text-[8px]',
                                                day.status === 'green' ? 'bg-[#34D399]/10 text-[#34D399]' : '',
                                                day.status === 'yellow' ? 'bg-[#FBBF24]/10 text-[#FBBF24]' : '',
                                                day.status === 'red' ? 'bg-[#F87171]/10 text-[#F87171]' : ''
                                            ]"
                                        >
                                            {{ day.deviation_percent <= 0 ? '+' + Math.abs(day.deviation_percent) : '-' + day.deviation_percent }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legend Details -->
                        <div class="flex flex-wrap gap-6 text-[9px] font-mono text-[#6B7280] bg-[#F9FAFB] p-4 rounded-sm border border-[#E5E7EB]">
                            <span class="font-bold text-[#111827] uppercase tracking-wider mr-2">Indikator Target:</span>
                            <span class="flex items-center"><span class="h-2 w-2 rounded-full mr-2" style="background-color: #34D399;"></span> Hijau: Aktual ≥ 90% Prediksi (Deviasi ≤ 10%)</span>
                            <span class="flex items-center"><span class="h-2 w-2 rounded-full mr-2" style="background-color: #FBBF24;"></span> Kuning: Aktual 70% - 90% Prediksi (Deviasi 10% - 30%)</span>
                            <span class="flex items-center"><span class="h-2 w-2 rounded-full mr-2" style="background-color: #F87171;"></span> Merah: Aktual &lt; 70% Prediksi (Deviasi &gt; 30%)</span>
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
import { ref, computed, onMounted, watch } from 'vue';
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
const trendProductName = ref('Penjualan Toko Global');
const trendArimaOrder = ref('N/A');
const trendMape = ref(0);
const lastTuningTime = ref('Belum pernah');
const activeIndex = ref(-1);
const trendChartData = ref({
    historis: [],
    prediksi: []
});

// Reality Check States
const rcData = ref([]);              // Array hasil reality check
const rcLoading = ref(false);        // Loading state
const rcMessage = ref('');           // Pesan jika belum ada prediksi

// Computed: Summary akurasi
const rcAccuracySummary = computed(() => {
    const past = rcData.value.filter(d => d.status !== 'upcoming');
    const good = past.filter(d => d.status === 'green');
    return { good: good.length, total: past.length };
});

// Fetch Reality Check
const fetchRealityCheck = async () => {
    rcLoading.value = true;
    rcMessage.value = '';
    try {
        const res = await axios.get('/api/admin/dashboard/reality-check');
        rcData.value = res.data.data;
        rcMessage.value = res.data.data.length === 0 ? res.data.message : '';
    } catch (e) {
        console.error('Reality check gagal:', e);
        rcMessage.value = 'Gagal memuat data reality check.';
    } finally {
        rcLoading.value = false;
    }
};

const totalForecastRevenue = computed(() => {
    return trendChartData.value.prediksi.reduce((sum, p) => sum + Number(p.sales), 0);
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

const fetchDashboardData = async () => {
    rcLoading.value = true;
    rcMessage.value = '';
    try {
        const response = await axios.get('/api/admin/dashboard/init');
        const data = response.data;

        // 1. Map Metrics
        if (data.metrics_data) {
            metrics.value = data.metrics_data.metrics;
            comparison.value = data.metrics_data.comparison;
        }

        // 2. Map Reality Check
        if (data.reality_check_data) {
            rcData.value = data.reality_check_data;
            rcMessage.value = data.reality_check_data.length === 0 ? 'Belum ada data reality check.' : '';
        }

        // 3. Map ARIMA Logs & Status
        if (data.arima_logs) {
            latestLogs.value = data.arima_logs;
            arimaLogsCount.value = data.arima_logs.length;
            arimaStatus.value = 'ok';
        }

        // 4. Map ARIMA Trend Chart
        if (data.arima_trend) {
            const trend = data.arima_trend;
            trendArimaOrder.value = trend.arima_order;
            trendMape.value = trend.mape_score;
            lastTuningTime.value = trend.last_tuning_time ? formatDate(trend.last_tuning_time) : 'Belum pernah';
            
            trendChartData.value.historis = trend.historis;
            trendChartData.value.prediksi = trend.prediksi;
            showTrendChart.value = trend.historis.length > 0;
        }

    } catch (error) {
        arimaStatus.value = 'offline';
        console.error('Gagal mengambil inisialisasi data dashboard:', error);
        rcMessage.value = 'Gagal memuat data dashboard.';
    } finally {
        rcLoading.value = false;
    }
};

const checkArimaService = async () => {
    // Tetap sediakan sebagai check-up independen jika dibutuhkan
    try {
        const logsResponse = await axios.get('/api/admin/arima-logs');
        latestLogs.value = logsResponse.data;
        arimaLogsCount.value = logsResponse.data.length;
        arimaStatus.value = 'ok';
    } catch (error) {
        arimaStatus.value = 'offline';
    }
};

// Manual trigger untuk melatih ulang / refresh model ARIMA (Butuh kalkulasi berat)
const fetchArimaTrend = async () => {
    rcLoading.value = true;
    try {
        const arimaRes = await axios.post('/api/admin/predict-arima-global', {
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

        if (arimaRes.data.log) {
            lastTuningTime.value = formatDate(arimaRes.data.log.created_at);
        }

        // Muat ulang data reality check setelah forecast diperbarui
        fetchRealityCheck();
    } catch (e) {
        console.error('Gagal melatih ulang model ARIMA:', e);
    } finally {
        rcLoading.value = false;
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
        points.push({ 
            id: `h-${index}`, 
            x, 
            y, 
            isPredict: false, 
            sales: Number(p.sales), 
            date: p.date 
        });
    });

    // Hitung Prediksi
    const histLen = hist.length;
    pred.forEach((p, index) => {
        const x = (histLen + index) * stepWidth;
        const y = 130 - ((p.sales - minVal) / range) * 110;
        points.push({ 
            id: `p-${index}`, 
            x, 
            y, 
            isPredict: true, 
            sales: Number(p.sales), 
            date: p.date 
        });
    });

    return points;
});

const formatDateLabel = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });
};

const formatDateLabelDay = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { weekday: 'long' }).toUpperCase();
};

const formatDateLabelDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short'
    });
};

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
    fetchDashboardData();
});</script>
