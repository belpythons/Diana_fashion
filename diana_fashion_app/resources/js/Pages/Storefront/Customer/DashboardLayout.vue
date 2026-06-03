<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Sidebar Navigation (col-span-3) -->
            <aside class="lg:col-span-3 bg-white border border-gray-200 rounded-sm p-6 h-fit">
                <div class="mb-6">
                    <h2 class="text-sm font-bold text-gray-900 truncate">{{ user?.name }}</h2>
                    <span class="text-[10px] font-mono text-gray-400 block mt-0.5">{{ user?.email }}</span>
                </div>
                
                <nav class="space-y-2">
                    <router-link to="/customer" class="flex items-center space-x-2 text-sm font-semibold text-gray-700 hover:text-[#FF1F8F] px-3 py-2 rounded-sm transition-colors" active-class="bg-pink-50 text-[#FF1F8F]!">
                        <span>Dashboard</span>
                    </router-link>
                    <router-link to="/customer/orders" class="flex items-center space-x-2 text-sm font-semibold text-gray-700 hover:text-[#FF1F8F] px-3 py-2 rounded-sm transition-colors" active-class="bg-pink-50 text-[#FF1F8F]!">
                        <span>Riwayat Belanja</span>
                    </router-link>
                    <router-link to="/customer/profile" class="flex items-center space-x-2 text-sm font-semibold text-gray-700 hover:text-[#FF1F8F] px-3 py-2 rounded-sm transition-colors" active-class="bg-pink-50 text-[#FF1F8F]!">
                        <span>Pengaturan Akun</span>
                    </router-link>
                </nav>
            </aside>

            <!-- Main Content Area (col-span-9) -->
            <div class="lg:col-span-9 bg-white border border-gray-200 rounded-sm p-8 min-h-[400px]">
                <router-view :user="user" @show-notification="$emit('show-notification', $event)" @profile-updated="$emit('profile-updated', $event)"></router-view>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    user: { type: Object, default: null }
});

defineEmits(['show-notification', 'profile-updated']);
</script>
