<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Diana Fashion — POS Terminal</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/pos.js'])
    <style>
        [v-cloak] { display: none; }
    </style>
</head>
<body class="bg-[#F9FAFB] text-[#111827] font-sans antialiased overflow-hidden">
    <div id="pos-app" v-cloak>
        <router-view></router-view>
    </div>
</body>
</html>
