<template>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 bg-white border border-gray-200 rounded-sm overflow-hidden min-h-[580px]">
            <!-- Panel Kiri: Presentasi Brand (Col-7) - Berdasarkan Golden Ratio 62% -->
            <div class="lg:col-span-7 bg-gray-950 p-12 text-white flex flex-col justify-between relative min-h-[400px] lg:min-h-[580px] hidden lg:flex border-r border-gray-800">
                <!-- Background Geometric Grid (Minimalist & Futuristic CSS) -->
                <div class="absolute inset-0 opacity-10 bg-[linear-gradient(to_right,#FF1F8F_1px,transparent_1px),linear-gradient(to_bottom,#FF1F8F_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center space-x-2">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF1F8F] opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-[#FF1F8F]"></span>
                        </span>
                        <span class="text-xs font-black tracking-widest text-[#FF1F8F] uppercase font-mono">DIANA OMNICHANNEL</span>
                    </div>
                </div>

                <div class="relative z-10 my-auto space-y-6 max-w-md">
                    <h1 class="text-3xl font-extrabold tracking-tight leading-tight">
                        Temukan Gaya Sejati & Keanggunan Anda.
                    </h1>
                    <p class="text-sm text-gray-400 leading-relaxed font-sans">
                        Bergabunglah dengan Diana Fashion untuk menikmati koleksi busana eksklusif, gaun elegan, dan pakaian premium yang dirancang khusus untuk kenyamanan dan momen berharga Anda.
                    </p>
                </div>

                <div class="relative z-10 flex flex-wrap gap-3">
                    <span class="text-[9px] font-mono font-bold tracking-widest border border-gray-800 px-3 py-1.5 rounded-sm bg-gray-900/60 uppercase text-gray-300">
                        [ KOLEKSI EKSKLUSIF ]
                    </span>
                    <span class="text-[9px] font-mono font-bold tracking-widest border border-gray-800 px-3 py-1.5 rounded-sm bg-gray-900/60 uppercase text-gray-300">
                        [ KUALITAS PREMIUM ]
                    </span>
                </div>
            </div>

            <!-- Panel Kanan: Form Aktif (Col-5) - Berdasarkan Golden Ratio 38% -->
            <div class="lg:col-span-5 p-8 sm:p-12 flex flex-col justify-between min-h-[500px]">
                <div>
                    <!-- Tab Switcher (Flat & Minimalist) -->
                    <div class="flex border-b border-gray-200 mb-8">
                        <button 
                            @click="activeTab = 'login'" 
                            :class="activeTab === 'login' ? 'border-[#FF1F8F] text-[#FF1F8F] font-bold' : 'border-transparent text-gray-400 hover:text-gray-900'" 
                            class="flex-1 text-center text-xs font-bold uppercase tracking-wider pb-3 border-b-2 transition-all cursor-pointer bg-transparent border-0"
                        >
                            Masuk
                        </button>
                        <button 
                            @click="activeTab = 'register'" 
                            :class="activeTab === 'register' ? 'border-[#FF1F8F] text-[#FF1F8F] font-bold' : 'border-transparent text-gray-400 hover:text-gray-900'" 
                            class="flex-1 text-center text-xs font-bold uppercase tracking-wider pb-3 border-b-2 transition-all cursor-pointer bg-transparent border-0"
                        >
                            Daftar Baru
                        </button>
                    </div>

                    <!-- Render Form Login -->
                    <div v-if="activeTab === 'login'" class="animate-fadeIn">
                        <div class="mb-6">
                            <h2 class="text-base font-bold text-gray-900 uppercase tracking-wider">Selamat Datang Kembali</h2>
                            <p class="text-xs text-gray-500 mt-1">Masukkan kredensial Anda untuk masuk ke sistem.</p>
                        </div>
                        
                        <form @submit.prevent="handleLogin" class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Alamat Email</label>
                                <input v-model="loginForm.email" type="email" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F] focus:ring-0 transition-colors" placeholder="nama@email.com" />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Kata Sandi</label>
                                <input v-model="loginForm.password" type="password" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F] focus:ring-0 transition-colors" placeholder="••••••••" />
                            </div>

                            <button type="submit" :disabled="loginLoading" class="w-full bg-[#FF1F8F] hover:bg-[#D91678] disabled:opacity-50 text-white text-xs font-bold py-3.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer mt-6 text-center">
                                <span v-if="loginLoading">Memverifikasi Sesi...</span>
                                <span v-else>Masuk ke Akun</span>
                            </button>
                        </form>
                    </div>

                    <!-- Render Form Register -->
                    <div v-else class="animate-fadeIn">
                        <div class="mb-6">
                            <h2 class="text-base font-bold text-gray-900 uppercase tracking-wider">Mulai Bersama Kami</h2>
                            <p class="text-xs text-gray-500 mt-1">Buat akun untuk melacak order dan kemudahan berbelanja.</p>
                        </div>

                        <form @submit.prevent="handleRegister" class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Lengkap</label>
                                <input v-model="registerForm.name" type="text" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F] focus:ring-0 transition-colors" placeholder="Nama lengkap Anda" />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Alamat Email</label>
                                <input v-model="registerForm.email" type="email" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F] focus:ring-0 transition-colors" placeholder="nama@email.com" />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Kata Sandi</label>
                                <input v-model="registerForm.password" type="password" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F] focus:ring-0 transition-colors" placeholder="Minimal 8 karakter" />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Konfirmasi Kata Sandi</label>
                                <input v-model="registerForm.password_confirmation" type="password" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F] focus:ring-0 transition-colors" placeholder="Ulangi kata sandi" />
                            </div>

                            <button type="submit" :disabled="registerLoading" class="w-full bg-[#FF1F8F] hover:bg-[#D91678] disabled:opacity-50 text-white text-xs font-bold py-3.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer mt-6 text-center">
                                <span v-if="registerLoading">Mendaftarkan Akun...</span>
                                <span v-else>Daftar & Belanja</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <span class="text-[9px] font-mono text-gray-400 uppercase tracking-wider block text-center">
                        &copy; 2026 Diana Omnichannel. Protected by CSRF Sanctum.
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const emit = defineEmits(['auth-success', 'show-notification']);
const router = useRouter();

const activeTab = ref('login');
const loginLoading = ref(false);
const registerLoading = ref(false);

const loginForm = reactive({
    email: '',
    password: ''
});

const registerForm = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
});

const handleLogin = async () => {
    if (loginLoading.value) return;
    loginLoading.value = true;

    try {
        // Ambil CSRF cookie terlebih dahulu untuk Sanctum SPA
        await axios.get('/sanctum/csrf-cookie');

        const response = await axios.post('/api/auth/login', loginForm);
        emit('auth-success', response.data.user);
        emit('show-notification', 'Login berhasil!');
        
        // Redirect berdasarkan role
        if (response.data.user.role === 'admin') {
            window.location.href = '/admin';
        } else if (response.data.user.role === 'kasir') {
            window.location.href = '/pos';
        } else {
            router.push('/');
        }

    } catch (error) {
        console.error('Gagal login:', error);
        const errMsg = error.response?.data?.message || 'Email atau sandi salah.';
        emit('show-notification', errMsg);
    } finally {
        loginLoading.value = false;
    }
};

const handleRegister = async () => {
    if (registerLoading.value) return;
    registerLoading.value = true;

    try {
        await axios.get('/sanctum/csrf-cookie');
        
        const response = await axios.post('/api/auth/register', registerForm);
        emit('auth-success', response.data.user);
        emit('show-notification', 'Pendaftaran berhasil!');
        router.push('/');

    } catch (error) {
        console.error('Gagal registrasi:', error);
        const errMsg = error.response?.data?.message || 'Pendaftaran gagal.';
        emit('show-notification', errMsg);
    } finally {
        registerLoading.value = false;
    }
};
</script>
