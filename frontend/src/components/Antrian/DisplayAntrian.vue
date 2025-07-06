<template>
  <div
    class="min-h-screen bg-gradient-to-br bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4 rounded-2xl">
    <div class="max-w-6xl w-full">
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
      <div v-else-if="antrianDipanggil.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="antrian in antrianDipanggil" :key="antrian.antrian_id"
          class="bg-white rounded-3xl shadow-2xl p-8 transform hover:scale-105 transition-all duration-300">
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
                <div class="w-20 h-20 rounded-full flex items-center justify-center text-white text-2xl font-bold mb-2"
                  :class="getJenisAntrianClass(antrian.jenis_antrian)">
                  {{ getJenisAntrianLabel(antrian.jenis_antrian) }}
                </div>
                <span class="text-gray-700 font-semibold capitalize">{{ antrian.jenis_antrian }}</span>
              </div>
            </div>

            <!-- Staff/Loket -->
            <div class="text-center">
              <p class="text-gray-600 text-lg mb-2">Petugas</p>
              <div class="bg-gray-100 rounded-2xl px-6 py-4">
                <div class="flex items-center justify-center mb-2">
                  <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="pi pi-user text-white text-xl"></i>
                  </div>
                </div>
                <p class="text-gray-800 font-semibold">{{ getStaffName(antrian) }}</p>
                <p class="text-gray-600 text-sm">{{ antrian.loket_name || 'Loket Pendaftaran' }}</p>
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
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import apiClient from '@/api/axios'

// State
const isLoading = ref(false)
const antrianDipanggil = ref([])
let intervalId = null

// Methods
const fetchAntrianDipanggil = async () => {
  try {
    isLoading.value = true

    const { data } = await apiClient.get('/antrian')

    if (data.success) {
      // Filter antrian yang sedang dipanggil (status_id = 2)
      const filteredAntrian = data.data.urutan_pemanggilan.filter(
        antrian => antrian.antrian_status_id === 2
      )
      
      antrianDipanggil.value = filteredAntrian.map(antrian => {        
        return {
          ...antrian,
          waktu_dipanggil: antrian.waktu_dipanggil || new Date().toISOString(),
          loket_name: 'Loket Pendaftaran'
        }
      })
    }
  } catch (error) {
    console.error('Error fetching antrian:', error)
  } finally {
    isLoading.value = false
  }
}

// Helper function untuk mendapatkan nama staff
const getStaffName = (antrian) => {
  return antrian.staff_name || 'Petugas';
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

const stopAutoRefresh = () => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
}

// Setup WebSocket Listener
const setupWebSocketListener = () => {
  if (window.Echo) {
    window.Echo.channel('antrian-channel')
      .listen('.antrian.updated', (data) => {
        console.log('Antrian updated via WebSocket:', data)

        const updatedAntrian = data.antrian

        // Jika status = 2 (dipanggil), tambahkan atau update ke display
        if (updatedAntrian.antrian_status_id === 2) {
          // tampilkan di layar
          const existingIndex = antrianDipanggil.value.findIndex(
            a => a.id === updatedAntrian.id
          )

          const formattedAntrian = {
            ...updatedAntrian,
            waktu_dipanggil: updatedAntrian.waktu_dipanggil || new Date().toISOString(),
            loket_name: updatedAntrian.loket_name || 'Loket Pendaftaran',
            staff_name: updatedAntrian.staff_name || 'Petugas'
          }

          if (existingIndex !== -1) {
            antrianDipanggil.value[existingIndex] = formattedAntrian
          } else {
            antrianDipanggil.value.push(formattedAntrian)
          }

        } else {
          // selain status 2 (misalnya status 3 = selesai), hapus dari tampilan
          antrianDipanggil.value = antrianDipanggil.value.filter(
            a => a.id !== updatedAntrian.id
          )
        }
      })
  }
}

const cleanup = () => {
  stopAutoRefresh()
  if (window.Echo) {
    window.Echo.leaveChannel('antrian-channel')
  }
}

// Lifecycle
onMounted(() => {
  fetchAntrianDipanggil()
  setupWebSocketListener()
})

onUnmounted(() => {
  cleanup()
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