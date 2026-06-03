<template>
    <div class="min-h-screen bg-[#F9FAFB] flex flex-col font-sans">
        <!-- Header / Navbar -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <router-link to="/" class="flex items-center space-x-2.5">
                            <img src="/logo.jpg" alt="Logo" class="h-9 w-9 rounded-full object-cover border border-gray-100 shadow-sm" />
                            <span class="text-lg font-extrabold text-[#FF1F8F] tracking-tight uppercase">Diana Fashion</span>
                        </router-link>
                    </div>

                    <!-- Navigation Links -->
                    <nav class="hidden md:flex space-x-8">
                        <router-link to="/" class="text-sm font-medium text-gray-700 hover:text-[#FF1F8F] transition-colors">
                            Semua Produk
                        </router-link>
                        <a href="#" @click.prevent="$emit('filter-category', 'Atasan')" class="text-sm font-medium text-gray-700 hover:text-[#FF1F8F] transition-colors">
                            Atasan
                        </a>
                        <a href="#" @click.prevent="$emit('filter-category', 'Bawahan')" class="text-sm font-medium text-gray-700 hover:text-[#FF1F8F] transition-colors">
                            Bawahan
                        </a>
                        <a href="#" @click.prevent="$emit('filter-category', 'Gamis')" class="text-sm font-medium text-gray-700 hover:text-[#FF1F8F] transition-colors">
                            Gamis
                        </a>
                    </nav>

                    <!-- Auth and Cart Utilities -->
                    <div class="flex items-center space-x-6">
                        <!-- Cart -->
                        <router-link to="/cart" id="cart-nav-target" class="relative text-gray-600 hover:text-[#FF1F8F] transition-colors flex items-center">
                            <!-- SVG Cart Icon (unfilled, 1.5px stroke - SKILL.md 6.4) -->
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                            <span v-if="cartCount > 0" class="absolute -top-1 -right-2 bg-[#FF1F8F] text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                                {{ cartCount }}
                            </span>
                        </router-link>

                        <!-- User Auth -->
                        <div class="flex items-center space-x-4">
                            <template v-if="user">
                                <router-link to="/customer" class="text-sm font-medium text-gray-700 hover:text-[#FF1F8F] transition-colors flex items-center space-x-1">
                                    <span>Hai, {{ user.name }}</span>
                                </router-link>
                                
                                <a v-if="user.role === 'admin'" href="/admin" class="bg-gray-100 hover:bg-[#FF1F8F] hover:text-white text-gray-700 text-xs font-semibold px-2.5 py-1 rounded-sm transition-all border border-gray-200">
                                    Admin Panel
                                </a>

                                <button @click="logout" class="text-xs font-semibold text-gray-500 hover:text-red-500 transition-colors">
                                    Keluar
                                </button>
                            </template>
                            <template v-else>
                                <router-link to="/login" class="text-sm font-semibold text-[#FF1F8F] hover:text-[#D91678] transition-colors">
                                    Masuk
                                </router-link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-grow">
            <slot></slot>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-8">
            <div class="max-w-7xl mx-auto px-4 text-center sm:px-6 lg:px-8">
                <p class="text-xs text-gray-400 font-mono">
                    &copy; 2026 Diana Fashion Omnichannel. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed } from 'vue';

defineProps({
    user: { type: Object, default: null },
    cartCount: { type: Number, default: 0 }
});

const emit = defineEmits(['logout', 'filter-category']);

const logout = () => {
    emit('logout');
};
</script>
