<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Manajemen Staf Internal</h3>
                <p class="text-xs text-gray-500 mt-1">Mengelola hak akses karyawan toko (Administrator & Staf Kasir POS).</p>
            </div>
            
            <button @click="openCreateModal" class="bg-[#FF1F8F] hover:bg-[#D91678] text-white text-xs font-bold px-4 py-2.5 rounded-sm uppercase tracking-wider transition-colors cursor-pointer text-center">
                + Tambah Karyawan Baru
            </button>
        </div>

        <!-- Filter Area -->
        <div class="bg-white border border-gray-200 rounded-sm p-6">
            <div class="relative w-full md:w-80">
                <input v-model="filters.keyword" @input="onSearch" type="text" placeholder="Cari nama atau email karyawan..." class="w-full text-xs border border-gray-300 rounded-sm px-4 py-2 focus:outline-none focus:border-[#FF1F8F] transition-colors" />
            </div>
        </div>

        <!-- Table Grid -->
        <div class="bg-white border border-gray-200 rounded-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold">
                            <th class="py-3.5 px-6">ID</th>
                            <th class="py-3.5 px-6">Nama Karyawan</th>
                            <th class="py-3.5 px-6">Alamat Email</th>
                            <th class="py-3.5 px-6 text-center">Hak Akses (Role)</th>
                            <th class="py-3.5 px-6 text-center">Operasional</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="staffList.length === 0" class="border-b border-gray-100">
                            <td colspan="5" class="py-16 text-center text-gray-400">Tidak ada karyawan internal terdaftar.</td>
                        </tr>
                        <tr v-else v-for="staff in staffList" :key="staff.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6 font-mono text-gray-400">#{{ staff.id }}</td>
                            <td class="py-4 px-6 font-semibold text-gray-900">{{ staff.name }}</td>
                            <td class="py-4 px-6 text-gray-600 font-mono">{{ staff.email }}</td>
                            <td class="py-4 px-6 text-center">
                                <span :class="staff.role === 'admin' ? 'bg-pink-100 text-[#FF1F8F] font-bold' : 'bg-gray-100 text-gray-600'" class="px-2.5 py-0.5 rounded-sm text-[9px] uppercase font-bold tracking-wider">
                                    {{ staff.role }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex justify-center space-x-3">
                                    <button @click="openEditModal(staff)" class="text-blue-500 hover:text-blue-600 font-semibold cursor-pointer">Ubah</button>
                                    <button @click="deleteStaff(staff)" class="text-red-500 hover:text-red-600 font-semibold cursor-pointer">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="pagination.last_page > 1" class="p-6 border-t border-gray-100 flex justify-between items-center bg-gray-50">
                <span class="text-[10px] text-gray-400">Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
                <div class="flex space-x-2">
                    <button :disabled="pagination.current_page === 1" @click="changePage(pagination.current_page - 1)" class="border border-gray-300 hover:border-gray-400 bg-white text-gray-700 text-[10px] font-bold px-3 py-1.5 rounded-sm transition-colors cursor-pointer disabled:opacity-40">
                        Sebelumnya
                    </button>
                    <button :disabled="pagination.current_page === pagination.last_page" @click="changePage(pagination.current_page + 1)" class="border border-gray-300 hover:border-gray-400 bg-white text-gray-700 text-[10px] font-bold px-3 py-1.5 rounded-sm transition-colors cursor-pointer disabled:opacity-40">
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>

        <!-- Tambah / Edit Modal Backdrop -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white border border-gray-200 rounded-sm w-full max-w-sm p-6 shadow-xl space-y-6">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-3">
                    {{ isEditMode ? 'Edit Data Karyawan' : 'Registrasi Karyawan Baru' }}
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Hak Akses (Role)</label>
                        <select v-model="form.role" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]">
                            <option value="">Pilih Hak Akses...</option>
                            <option value="admin">Administrator</option>
                            <option value="kasir">Kasir POS Toko Fisik</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                        <input v-model="form.name" type="text" placeholder="Nama karyawan..." required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Alamat Email</label>
                        <input v-model="form.email" type="email" placeholder="email@diana.com" required class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Kata Sandi {{ isEditMode ? '(Biarkan kosong jika tidak diubah)' : '' }}
                        </label>
                        <input v-model="form.password" type="password" placeholder="Minimal 8 karakter..." :required="!isEditMode" class="w-full text-xs border border-gray-300 rounded-sm px-3 py-2 focus:outline-none focus:border-[#FF1F8F]" />
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
                        <button type="button" @click="closeModal" class="border border-gray-300 hover:bg-gray-50 text-gray-700 text-[10px] font-bold px-4 py-2 rounded-sm transition-colors cursor-pointer">
                            Batalkan
                        </button>
                        <button type="submit" class="bg-[#FF1F8F] hover:bg-[#D91678] text-white text-[10px] font-bold px-4 py-2 rounded-sm uppercase tracking-wider transition-colors cursor-pointer">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Toast Notification -->
        <div v-if="toast" class="fixed bottom-4 right-4 z-[999] bg-gray-900 text-white text-xs font-semibold px-4 py-3 rounded-sm shadow-lg flex items-center space-x-2">
            <span>{{ toast }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios_real from 'axios';

const staffList = ref([]);
const toast = ref('');
let toastTimer = null;

const filters = reactive({
    keyword: '',
    page: 1
});

const pagination = ref({
    current_page: 1,
    last_page: 1
});

// Modal State
const showModal = ref(false);
const isEditMode = ref(false);

const form = reactive({
    id: null,
    name: '',
    email: '',
    password: '',
    role: ''
});

const fetchStaff = async () => {
    try {
        const response = await axios_real.get('/api/admin/staff', {
            params: filters
        });
        staffList.value = response.data.data;
        pagination.value = response.data;
    } catch (e) {
        showToast('Gagal memuat manajemen staf.');
    }
};

let debounceTimer = null;
const onSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filters.page = 1;
        fetchStaff();
    }, 300);
};

const changePage = (page) => {
    filters.page = page;
    fetchStaff();
};

const openCreateModal = () => {
    isEditMode.value = false;
    form.id = null;
    form.name = '';
    form.email = '';
    form.password = '';
    form.role = '';
    showModal.value = true;
};

const openEditModal = (staff) => {
    isEditMode.value = true;
    form.id = staff.id;
    form.name = staff.name;
    form.email = staff.email;
    form.password = '';
    form.role = staff.role;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = async () => {
    try {
        if (isEditMode.value) {
            await axios_real.put(`/api/admin/staff/${form.id}`, form);
            showToast('Karyawan berhasil diperbarui.');
        } else {
            await axios_real.post('/api/admin/staff', form);
            showToast('Karyawan baru berhasil didaftarkan.');
        }
        showModal.value = false;
        fetchStaff();
    } catch (error) {
        const msg = error.response?.data?.message || 'Gagal menyimpan data karyawan.';
        showToast(msg);
    }
};

const deleteStaff = async (staff) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus staf "${staff.name}"?`)) return;

    try {
        await axios_real.delete(`/api/admin/staff/${staff.id}`);
        showToast('Karyawan berhasil dinonaktifkan.');
        fetchStaff();
    } catch (error) {
        const msg = error.response?.data?.message || 'Gagal menghapus karyawan.';
        showToast(msg);
    }
};

const showToast = (msg) => {
    toast.value = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.value = '';
    }, 3000);
};

onMounted(() => {
    fetchStaff();
});
</script>
