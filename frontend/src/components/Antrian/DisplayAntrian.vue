<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-900 to-indigo-900 flex items-center justify-center p-4">
    <div class="max-w-4xl w-full">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Display Antrian</h1>
        <p class="text-blue-200">Informasi Antrian yang Sedang Dipanggil</p>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-4 border-white border-t-transparent mx-auto mb-4"></div>
        <p class="text-white text-lg">Memuat data...</p>
      </div>

      <!-- Display Antrian yang Sedang Dipanggil -->
      <div v-else-if="antrianDipanggil.length > 0" class="space-y-6">
        <div 
          v-for="antrian in antrianDipanggil" 
          :key="antrian.antrian_id"
          class="bg-white rounded-3xl shadow-2xl p-8 transform hover:scale-105 transition-all duration-300"
        >
          <div class="flex items-center justify-between">
            <!-- Nomor Antrian -->
            <div class="text-center">
              <p class="text-gray-600 text-lg mb-2">Nomor Antrian</p>
              <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-2xl px-8 py-4">
                <span class="text-5xl font-bold">{{ antrian.nomor_antrian }}</span>
              </div>
            </div>

            <!-- Jenis Antrian -->
            <div class="text-center">
              <p class="text-gray-600 text-lg mb-2">Jenis Antrian</p>
              <div class="flex flex-col items-center">
                <div 
                  class="w-20 h-20 rounded-full flex items-center justify-center text-white text-2xl font-bold mb-2"
                  :class="getJenisAntrianClass(antrian.jenis_antrian)"
                >
                  {{ getJenisAntrianLabel(antrian.jenis_antrian) }}
                </div>
                <span class="text-gray-700 font-semibold capitalize">{{ antrian.jenis_antrian }}</span>
              </div>
            </div>

            <!-- Staff/Loket -->
            <div class="text-center">
              <p class="text-gray-600 text-lg mb-2">Loket</p>
              <div class="bg-gray-100 rounded-2xl px-6 py-4">
                <div class="flex items-center justify-center mb-2">
                  <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="pi pi-user text-white text-xl"></i>
                  </div>
                </div>
                <p class="text-gray-800 font-semibold">{{ antrian.staff_name || 'Loket 1' }}</p>
                <p class="text-gray-600 text-sm">{{ antrian.loket_name || 'Pendaftaran' }}</p>
              </div>
            </div>
          </div>

          <!-- Waktu Dipanggil -->
          <div class="mt-6 text-center">
            <p class="text-gray-500 text-sm">Dipanggil pada: {{ formatDateTime(antrian.waktu_dipanggil) }}</p>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <div class="w-32 h-32 mx-auto mb-6 bg-white/20 rounded-full flex items-center justify-center">
          <i class="pi pi-clock text-white text-6xl"></i>
        </div>
        <h3 class="text-2xl font-bold text-white mb-2">Tidak Ada Antrian yang Dipanggil</h3>
        <p class="text-blue-200">Menunggu antrian selanjutnya...</p>
      </div>

      <!-- Auto Refresh Info -->
      <div class="mt-8 text-center">
        <p class="text-blue-200 text-sm">
          <i class="pi pi-refresh mr-2"></i>
          Pembaruan otomatis setiap {{ refreshInterval / 1000 }} detik
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import apiClient from '@/api/axios'

// State
const isLoading = ref(false)
const antrianDipanggil = ref([])
const refreshInterval = ref(100000) // 5 detik
let intervalId = null

// Methods
const fetchAntrianDipanggil = async () => {
  try {
    isLoading.value = true
    
    const { data } = await apiClient.get('/antrian')
    
    if (data.success) {
      // Filter antrian yang sedang dipanggil (status_id = 2)
      antrianDipanggil.value = data.data.urutan_pemanggilan.filter(
        antrian => antrian.antrian_status_id === 2
      ).map(antrian => ({
        ...antrian,
        waktu_dipanggil: antrian.waktu_dipanggil || new Date().toISOString(),
        staff_name: antrian.staff_name || 'Petugas',
        loket_name: 'Loket Pendaftaran'
      }))
    }
  } catch (error) {
    console.error('Error fetching antrian:', error)
  } finally {
    isLoading.value = false
  }
}

const getJenisAntrianLabel = (jenis) => {
  return jenis === 'reservasi' ? 'R' : 'W'
}

const getJenisAntrianClass = (jenis) => {
  return jenis === 'reservasi' 
    ? 'bg-gradient-to-r from-green-500 to-emerald-600' 
    : 'bg-gradient-to-r from-orange-500 to-red-500'
}

const formatDateTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const startAutoRefresh = () => {
  intervalId = setInterval(() => {
    fetchAntrianDipanggil()
  }, refreshInterval.value)
}

const stopAutoRefresh = () => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
}

// Lifecycle
onMounted(() => {
  fetchAntrianDipanggil()
  startAutoRefresh()
})

onUnmounted(() => {
  stopAutoRefresh()
})
</script>

<style scoped>
/* Custom animations */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Loading spinner */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Hover effects */
.hover\:scale-105:hover {
  transform: scale(1.05);
}

.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.duration-300 {
  transition-duration: 300ms;
}
</style>