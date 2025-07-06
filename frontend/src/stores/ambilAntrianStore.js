import { ref } from 'vue';
import { defineStore } from 'pinia';
import apiClient from '@/api/axios'
import { useGlobalToast } from '@/composables/useGlobalToast';

export const useAmbilAntrianStore = defineStore('ambil-antrian', () => {
    const { showSuccess, showError } = useGlobalToast();
    const showDialogAntrian = ref(false);
    const isLoading = ref(false);
    const antrianResponse = ref(null);
    const errorMessage = ref('');

    function openDialog() {
        showDialogAntrian.value = true;
    }

    function closeDialog() {
        showDialogAntrian.value = false;
    }

    async function simpanAntrian(jenis) {
        isLoading.value = true;
        errorMessage.value = '';
        antrianResponse.value = null;

        try {
            const response = await apiClient.post('/antrian', {
                jenis_antrian: jenis
            });
            antrianResponse.value = response.data.data;

            showSuccess('Sukses', 'Antrian berhasil diambil');

            closeDialog();
        } catch (error) {
            if (error.response) {
                errorMessage.value = error.response.data.message || 'Gagal ambil antrian';
            } else {
                errorMessage.value = 'Terjadi kesalahan jaringan';
            }

            showError('Gagal', errorMessage.value);
        } finally {
            isLoading.value = false;
        }
    }

    return {
        showDialogAntrian,
        isLoading,
        antrianResponse,
        errorMessage,
        openDialog,
        closeDialog,
        simpanAntrian, // ✅ HARUS ADA DI SINI!
    };
});
