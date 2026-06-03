<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Diana Fashion — E-Commerce Storefront</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [v-cloak] { display: none; }
    </style>
</head>
<body class="bg-[#F9FAFB] text-[#111827] font-sans antialiased">
    <div id="app" v-cloak>
        <!-- Toast Notification (Premium micro-animation style) -->
        <div v-if="notification" class="fixed bottom-4 right-4 z-50 bg-gray-900 text-white text-xs font-semibold px-4 py-3 rounded-sm border border-gray-800 shadow-lg flex items-center space-x-2 transition-all duration-300">
            <span>@{{ notification }}</span>
        </div>

        <main-layout :user="user" :cart-count="cartCount" v-on:logout="handleLogout" v-on:filter-category="setCategoryFilter">
            <router-view 
                :cart="cart" 
                :user="user" 
                :category-filter="categoryFilter"
                v-on:add-to-cart="addToCart" 
                v-on:update-qty="updateQty" 
                v-on:remove-item="removeItem" 
                v-on:clear-cart="clearCart"
                v-on:auth-success="handleAuthSuccess"
                v-on:profile-updated="handleProfileUpdated"
                v-on:show-notification="showNotification">
            </router-view>
        </main-layout>
    </div>
</body>
</html>
