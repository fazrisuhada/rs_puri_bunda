<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-6 rounded-xl">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">
                            <i class="pi pi-megaphone text-blue-600 mr-3"></i>
                            Panggil Antrian
                        </h1>
                        <p class="text-gray-600">Kelola urutan pemanggilan antrian pasien</p>
                    </div>
                    <div class="flex gap-3">
                        <Button label="Refresh Data" icon="pi pi-refresh" @click="fetchData" :loading="isLoading"
                            severity="secondary" class="!px-6 !py-3" />
                        <Button label="Antrian Selanjutnya" icon="pi pi-volume-up" @click="panggilSelanjutnya"
                            :disabled="!nextPatient || isPlaying" :loading="isPlaying" class="!px-6 !py-3" />
                    </div>
                </div>

                <!-- Setting Suara -->
                <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Pengaturan Suara</h3>
                    <div class="flex gap-4 items-center">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Volume:</label>
                            <input type="range" min="0.1" max="1" step="0.1" v-model="voiceSettings.volume"
                                class="w-20" />
                            <span class="text-sm text-gray-600">{{ Math.round(voiceSettings.volume * 100) }}%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Kecepatan:</label>
                            <input type="range" min="0.5" max="2" step="0.1" v-model="voiceSettings.rate"
                                class="w-20" />
                            <span class="text-sm text-gray-600">{{ voiceSettings.rate }}x</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Pitch:</label>
                            <input type="range" min="0.5" max="2" step="0.1" v-model="voiceSettings.pitch"
                                class="w-20" />
                            <span class="text-sm text-gray-600">{{ voiceSettings.pitch }}</span>
                        </div>
                        <Button label="Test Suara" icon="pi pi-play" @click="testVoice" severity="info" size="small"
                            :disabled="isPlaying" />
                    </div>
                </div>
            </div>

            <div class="flex gap-6 mb-8">
                <!-- Sedang Dipanggil -->
                <div v-if="currentPatient"
                    class="w-1/2 bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">
                                <i class="pi pi-user text-white mr-3"></i>
                                Sedang Dipanggil
                            </h2>
                            <div class="flex items-center gap-6">
                                <div class="bg-white/20 rounded-lg p-4">
                                    <div class="text-sm opacity-90">Nomor Antrian</div>
                                    <div class="text-3xl font-bold">{{ currentPatient.nomor_antrian }}</div>
                                </div>
                                <div class="bg-white/20 rounded-lg p-4">
                                    <div class="text-sm opacity-90">Jenis Antrian</div>
                                    <div class="text-lg font-semibold capitalize">{{ currentPatient.jenis_antrian }}</div>
                                </div>
                                <div class="bg-white/20 rounded-lg p-4">
                                    <div class="text-sm opacity-90">Waktu Kedatangan</div>
                                    <div class="text-lg font-semibold">{{ formatTime(currentPatient.waktu_kedatangan) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="animate-pulse">
                            <i class="pi pi-volume-up text-6xl" :class="{ 'animate-bounce': isPlaying }"></i>
                        </div>
                    </div>
                </div>

                <!-- Pasien Selanjutnya -->
                <div v-if="nextPatient"
                    class="w-1/2 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl shadow-xl p-8 mb-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">
                                <i class="pi pi-clock text-white mr-3"></i>
                                Selanjutnya
                            </h2>
                            <div class="flex items-center gap-6">
                                <div class="bg-white/20 rounded-lg p-4">
                                    <div class="text-sm opacity-90">Nomor Antrian</div>
                                    <div class="text-3xl font-bold">{{ nextPatient.nomor_antrian }}</div>
                                </div>
                                <div class="bg-white/20 rounded-lg p-4">
                                    <div class="text-sm opacity-90">Jenis Antrian</div>
                                    <div class="text-lg font-semibold capitalize">{{ nextPatient.jenis_antrian }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List Antrian -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="pi pi-list text-blue-600 mr-3"></i>
                        Urutan Pemanggilan
                    </h2>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="pi pi-info-circle"></i>
                        <span>Total: {{ urutanPemanggilan.length }} antrian</span>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex justify-center items-center py-12">
                    <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
                </div>

                <!-- Empty State -->
                <div v-else-if="!urutanPemanggilan.length" class="text-center py-12">
                    <i class="pi pi-inbox text-6xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Tidak Ada Antrian</h3>
                    <p class="text-gray-500">Belum ada antrian yang perlu dipanggil</p>
                </div>

                <!-- Tabel Antrian -->
                <div v-else class="overflow-hidden">
                    <DataTable :value="urutanPemanggilan" :rows="10" :paginator="true" class="!border-0">
                        <Column field="urutan" header="Urutan" class="!w-20">
                            <template #body="{ data }">
                                <div class="flex items-center justify-center">
                                    <Badge :value="data.urutan"
                                        :severity="data.jenis_antrian === 'reservasi' ? 'success' : 'info'"
                                        class="text-lg font-bold" />
                                </div>
                            </template>
                        </Column>

                        <Column field="nomor_antrian" header="Nomor Antrian" class="!w-32">
                            <template #body="{ data }">
                                <div class="flex items-center">
                                    <i class="pi pi-ticket text-blue-600 mr-2"></i>
                                    <span class="font-semibold text-lg text-slate-900">{{ data.nomor_antrian }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="jenis_antrian" header="Jenis Antrian" class="!w-32">
                            <template #body="{ data }">
                                <Tag :value="data.jenis_antrian"
                                    :severity="data.jenis_antrian === 'reservasi' ? 'success' : 'info'"
                                    class="!text-sm !font-semibold !capitalize" />
                            </template>
                        </Column>

                        <Column field="waktu_kedatangan" header="Waktu Kedatangan" class="!w-48">
                            <template #body="{ data }">
                                <div class="flex items-center">
                                    <i class="pi pi-clock text-slate-900 mr-2"></i>
                                    <span class="text-sm font-medium text-slate-900">{{
                                        formatDateTime(data.waktu_kedatangan) }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Status" class="!w-32">
                            <template #body="{ data }">
                                <div class="flex items-center">
                                    <div v-if="data.antrian_status_id === 2"
                                        class="flex items-center text-green-600 font-semibold">
                                        <i class="pi pi-volume-up mr-2"></i>
                                        Dipanggil
                                    </div>
                                    <div v-else-if="data.antrian_status_id === 1" class="flex items-center text-orange-600">
                                        <i class="pi pi-hourglass mr-2"></i>
                                        Menunggu
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Aksi" class="!w-24">
                            <template #body="{ data }">
                               <Button 
                                    icon="pi pi-volume-up" 
                                    @click="panggilUlang(data)"
                                    severity="success" 
                                    size="small" 
                                    outlined 
                                    class="!p-2 !mr-2" 
                                    v-tooltip="'Panggil Ulang'"
                                    :disabled="isPlaying" 
                                />

                                <Button 
                                    icon="pi pi-check" 
                                    @click="selesaiDilayani(data)"
                                    severity="success" 
                                    size="small" 
                                    outlined 
                                    class="!p-2" 
                                    v-tooltip="'Selesai Dilayani'"
                                    :disabled="data.antrian_status_id === 1"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import apiClient from '@/api/axios';
import { useGlobalToast } from '@/composables/useGlobalToast';

// Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';

// Composables
const { showSuccess, showError } = useGlobalToast();

// State
const isLoading = ref(false);
const isPlaying = ref(false);
const urutanPemanggilan = ref([]);
const currentPatient = ref(null);

// Voice Settings
const voiceSettings = ref({
    volume: 1.0,
    rate: 1.0,
    pitch: 1.0,
    lang: 'id-ID'
});

// Computed
const nextPatient = computed(() => {
    // Ambil pasien selanjutnya yang belum dipanggil (antrian_status_id === 1)
    const waitingPatients = urutanPemanggilan.value.filter(item => item.antrian_status_id === 1);
    return waitingPatients.length > 0 ? waitingPatients[0] : null;
});

// Helper
const updateCurrentPatient = () => {
    // Cari pasien yang sedang dipanggil (antrian_status_id === 2)
    const calledPatients = urutanPemanggilan.value.filter(item => item.antrian_status_id === 2);
    
    if (calledPatients.length > 0) {
        // Jika ada beberapa pasien yang dipanggil, ambil yang terakhir (berdasarkan waktu atau urutan)
        currentPatient.value = calledPatients[calledPatients.length - 1];
    } else {
        currentPatient.value = null;
    }
};

// Voice Methods
const speak = (text) => {
    return new Promise((resolve, reject) => {
        // cek apakah browser mendukung text-to-speech
        if (!window.speechSynthesis) {
            showError('Error', 'Browser tidak mendukung text-to-speech');
            reject(new Error('Speech synthesis not supported'));
            return;
        }

        // batalkan pemutaran suara sebelumnya
        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);

        // Set voice settings
        utterance.volume = voiceSettings.value.volume;
        utterance.rate = voiceSettings.value.rate;
        utterance.pitch = voiceSettings.value.pitch;
        utterance.lang = voiceSettings.value.lang;

        // Event listeners
        utterance.onstart = () => {
            isPlaying.value = true;
        };

        utterance.onend = () => {
            isPlaying.value = false;
            resolve();
        };

        utterance.onerror = (event) => {
            isPlaying.value = false;
            showError('Error', 'Gagal memutar suara');
            reject(event);
        };

        // Mulai pemutaran
        window.speechSynthesis.speak(utterance);
    });
};

const generateCallMessage = (patient) => {
    let message = `Nomor antrian ${patient.nomor_antrian}`;

    if (patient.jenis_antrian === 'reservasi') {
        message += ` reservasi`;
    } else {
        message += ` walk in`;
    }

    message += ` silahkan menuju ke loket pendaftaran`;

    return message;
};

const testVoice = async () => {
    try {
        await speak('Tes suara sistem antrian. Nomor antrian A 1 silahkan menuju ke loket pendaftaran.');
        showSuccess('Success', 'Test suara berhasil');
    } catch (error) {
        console.error('Error testing voice:', error);
    }
};

// Methods
const fetchData = async (showMessage = true) => {
    isLoading.value = true;
    try {
        const { data } = await apiClient.get('/antrian');

        if (data.success) {
            urutanPemanggilan.value = data.data.urutan_pemanggilan;
            
            // Update currentPatient setelah data dimuat
            updateCurrentPatient();
            
            if (showMessage) {
                showSuccess('Success', 'Data berhasil dimuat');
            }
        } else {
            showError('Error', 'Gagal memuat data antrian');
        }
    } catch (error) {
        console.error('Error fetching data:', error);
        showError('Error', 'Terjadi kesalahan saat memuat data');
    } finally {
        isLoading.value = false;
    }
};

const panggilSelanjutnya = async () => {
    try {
        const { data } = await apiClient.post('/antrian/panggil-selanjutnya');

        if (!data.success) {
            showError('Gagal', data.message);
            return;
        }

        const patient = data.data;

        await fetchData(false); // false = tidak tampilkan success message

        // Suara panggilan
        const message = generateCallMessage(patient);
        await speak(message);

        showSuccess('Success', `Memanggil ${patient.nomor_antrian}`);
    } catch (error) {
        console.error('Error panggil selanjutnya:', error);
        showError('Error', 'Gagal memanggil antrian selanjutnya');
    }
};

const panggilUlang = async (patient) => {
    try {
        const { data } = await apiClient.post('/antrian/panggil', {
            nomor_antrian: patient.nomor_antrian
        });

        if (data.success) {
            await fetchData(false); // false = tidak tampilkan success message

            // Tampilkan suara
            const message = generateCallMessage(patient);
            await speak(message);

            showSuccess('Success', data.message);
        } else {
            showError('Gagal', data.message);
        }
    } catch (error) {
        console.error('Error panggil antrian:', error);
        showError('Error', 'Gagal memanggil antrian');
    }
};

const selesaiDilayani = async (patient) => {
    try {
        const { data } = await apiClient.put(`/antrian/${patient.antrian_id}/selesai-dipanggil`);

        if (data.success) {
            showSuccess('Success', data.message);

            // Hapus dari daftar urutan
            const index = urutanPemanggilan.value.findIndex(item => item.antrian_id === patient.antrian_id);
            if (index !== -1) {
                urutanPemanggilan.value.splice(index, 1);
            }

            // Update currentPatient
            updateCurrentPatient();
        } else {
            showError('Error', data.message);
        }
    } catch (error) {
        console.error('Error selesai dilayani:', error);
        showError('Error', 'Gagal menyelesaikan antrian');
    }
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Lifecycle
onMounted(() => {
    fetchData();
    // Cek apakah browser mendukung text-to-speech
    if (!window.speechSynthesis) {
        showError('Warning', 'Browser tidak mendukung fitur text-to-speech');
    }
});

// Cleanup on unmount
onUnmounted(() => {
    if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }
});
</script>

<style scoped>
:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
    background: rgba(59, 246, 159, 0.105) !important;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
    background-color: #ffffff !important;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #c8ffcf !important;
}

:deep(.p-badge) {
    min-width: 2rem;
    height: 2rem;
    line-height: 2rem;
}

/* Animation for voice playing */
@keyframes bounce {
    0%, 20%, 53%, 80%, 100% {
        transform: translate3d(0, 0, 0);
    }
    40%, 43% {
        transform: translate3d(0, -10px, 0);
    }
    70% {
        transform: translate3d(0, -5px, 0);
    }
    90% {
        transform: translate3d(0, -2px, 0);
    }
}

.animate-bounce {
    animation: bounce 1s ease-in-out infinite;
}
</style>