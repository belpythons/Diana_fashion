<template>
    <div class="min-h-screen bg-[#F9FAFB] flex flex-col font-sans">
        <!-- Header / Navbar -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <!-- Mobile Hamburger Button -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden mr-3 text-gray-600 hover:text-[#FF1F8F] focus:outline-none cursor-pointer border-0 bg-transparent">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

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
                                    <svg class="w-5 h-5 md:hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    <span class="hidden md:inline">Hai, {{ user.name }}</span>
                                </router-link>
                                
                                <a v-if="user.role === 'admin'" href="/admin" class="bg-gray-100 hover:bg-[#FF1F8F] hover:text-white text-gray-700 text-xs font-semibold px-2 py-0.5 md:px-2.5 md:py-1 rounded-sm transition-all border border-gray-200">
                                    <span class="md:hidden">Adm</span>
                                    <span class="hidden md:inline">Admin Panel</span>
                                </a>

                                <button @click="logout" class="text-xs font-semibold text-gray-500 hover:text-red-500 transition-colors border-0 bg-transparent cursor-pointer">
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

            <!-- Mobile Menu Dropdown (tampil hanya di mobile saat di-toggle) -->
            <transition name="slide-fade">
                <div v-if="mobileMenuOpen" class="md:hidden bg-white border-t border-gray-100 py-3 px-4 space-y-2 shadow-inner">
                    <router-link to="/" @click="mobileMenuOpen = false" class="block text-sm font-medium text-gray-700 hover:text-[#FF1F8F] py-2 transition-colors">
                        Semua Produk
                    </router-link>
                    <a href="#" @click.prevent="$emit('filter-category', 'Atasan'); mobileMenuOpen = false" class="block text-sm font-medium text-gray-700 hover:text-[#FF1F8F] py-2 transition-colors">
                        Atasan
                    </a>
                    <a href="#" @click.prevent="$emit('filter-category', 'Bawahan'); mobileMenuOpen = false" class="block text-sm font-medium text-gray-700 hover:text-[#FF1F8F] py-2 transition-colors">
                        Bawahan
                    </a>
                    <a href="#" @click.prevent="$emit('filter-category', 'Gamis'); mobileMenuOpen = false" class="block text-sm font-medium text-gray-700 hover:text-[#FF1F8F] py-2 transition-colors">
                        Gamis
                    </a>
                </div>
            </transition>
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
import { ref, computed } from 'vue';

defineProps({
    user: { type: Object, default: null },
    cartCount: { type: Number, default: 0 }
});

const emit = defineEmits(['logout', 'filter-category']);

const mobileMenuOpen = ref(false);

const logout = () => {
    emit('logout');
};
</script>

<style scoped>
.slide-fade-enter-active {
  transition: all 0.2s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.15s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}
</style>
