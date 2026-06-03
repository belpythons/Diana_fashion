<template>
    <div>
        <h2 class="text-lg font-bold text-gray-900 uppercase tracking-wider mb-6">Pengaturan Profil</h2>

        <form @submit.prevent="handleSubmit" class="space-y-4 max-w-lg">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input v-model="form.name" type="text" required class="w-full text-sm border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F]" />
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email</label>
                <input v-model="form.email" type="email" required class="w-full text-sm border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F]" />
            </div>

            <div class="pt-4 border-t border-gray-100">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-3">Ubah Kata Sandi (Opsional)</span>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Kata Sandi Baru</label>
                        <input v-model="form.password" type="password" class="w-full text-sm border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F]" placeholder="Kosongkan jika tidak diubah" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Konfirmasi Kata Sandi Baru</label>
                        <input v-model="form.password_confirmation" type="password" class="w-full text-sm border border-gray-300 rounded-sm px-3 py-2.5 focus:outline-none focus:border-[#FF1F8F]" placeholder="Kosongkan jika tidak diubah" />
                    </div>
                </div>
            </div>

            <button type="submit" :disabled="loading" class="bg-[#FF1F8F] hover:bg-[#D91678] disabled:opacity-50 text-white text-xs font-bold py-3 px-6 rounded-sm uppercase tracking-wider transition-colors cursor-pointer mt-6">
                <span v-if="loading">Menyimpan...</span>
                <span v-else>Simpan Perubahan</span>
            </button>
        </form>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    user: { type: Object, default: null }
});

const emit = defineEmits(['show-notification', 'profile-updated']);

const loading = ref(false);

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
});

const handleSubmit = async () => {
    if (loading.value) return;
    loading.value = true;

    try {
        const payload = {
            name: form.name,
            email: form.email
        };

        if (form.password) {
            payload.password = form.password;
            payload.password_confirmation = form.password_confirmation;
        }

        const response = await axios.post('/api/customer/profile/update', payload);
        
        emit('profile-updated', response.data.user);
        emit('show-notification', 'Profil sukses diperbarui!');

        // Reset password fields
        form.password = '';
        form.password_confirmation = '';

    } catch (error) {
        console.error('Gagal memperbarui profil:', error);
        const errMsg = error.response?.data?.message || 'Gagal memperbarui profil.';
        emit('show-notification', errMsg);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (props.user) {
        form.name = props.user.name;
        form.email = props.user.email;
    }
});
</script>
