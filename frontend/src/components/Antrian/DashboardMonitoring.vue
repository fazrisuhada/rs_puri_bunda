<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6 rounded-2xl">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard Monitoring</h1>
        <p class="text-gray-600 dark:text-gray-300 mt-2">Sistem Antrian Real-time</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Dashboard Content -->
      <div v-else-if="dashboardData" class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Antrian Aktif -->
          <Card class="bg-white dark:bg-gray-800 shadow-sm border dark:border-gray-700">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Antrian Aktif</p>
                  <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ dashboardData.jumlah_antrian_aktif }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="pi pi-users text-blue-600 text-2xl"></i>
                </div>
              </div>
            </template>
          </Card>

          <!-- Staff Aktif -->
          <Card class="bg-white dark:bg-gray-800 shadow-sm border dark:border-gray-700">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Staff Aktif</p>
                  <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ dashboardData.jumlah_staff_aktif }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="pi pi-check text-green-600 text-2xl"></i>
                </div>
              </div>
            </template>
          </Card>

          <!-- Total Pelayanan Hari Ini -->
          <Card class="bg-white dark:bg-gray-800 shadow-sm border dark:border-gray-700">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Pelayanan</p>
                  <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ totalPelayananHariIni }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="pi pi-chart-line text-purple-600 text-2xl"></i>
                </div>
              </div>
            </template>
          </Card>

          <!-- Rata-rata Waktu -->
          <Card class="bg-white dark:bg-gray-800 shadow-sm border dark:border-gray-700">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Rata-rata Waktu</p>
                  <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ averageServiceTime }} min</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="pi pi-clock text-orange-600 text-2xl"></i>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Top Staff -->
          <Card class="bg-white dark:bg-gray-800 shadow-sm border dark:border-gray-700">
            <template #header>
              <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top 3 Staff Terbaik</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">Berdasarkan jumlah pelayanan</p>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <div 
                  v-for="(staff, index) in dashboardData.top_staff" 
                  :key="staff.staff_id"
                  class="flex items-center justify-between p-4 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                  <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                      <div 
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                        :class="getRankBadgeClass(index)"
                      >
                        {{ index + 1 }}
                      </div>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">{{ staff.nama_staff }}</p>
                      <p class="text-sm text-gray-600 dark:text-gray-300">Staff ID: {{ staff.staff_id }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ staff.total_pelayanan }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">pelayanan</p>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Statistik Waktu Pelayanan -->
          <Card class="bg-white dark:bg-gray-800 shadow-sm border dark:border-gray-700">
            <template #header>
              <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statistik Waktu Pelayanan</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">Rata-rata waktu per staff</p>
              </div>
            </template>
            <template #content>
              <div v-if="dashboardData.statistik_waktu_pelayanan.length > 0" class="space-y-4">
                <div 
                  v-for="stat in dashboardData.statistik_waktu_pelayanan" 
                  :key="stat.staff_id"
                  class="flex items-center justify-between p-4 rounded-lg border border-gray-100 dark:border-gray-700"
                >
                  <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                      <i class="pi pi-user text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">{{ stat.nama_staff }}</p>
                      <p class="text-sm text-gray-600 dark:text-gray-300">ID: {{ stat.staff_id }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stat.rata_rata_waktu_menit }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">menit</p>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <i class="pi pi-info-circle text-gray-400 dark:text-gray-500 text-4xl mb-2"></i>
                <p class="text-gray-600 dark:text-gray-300">Belum ada data statistik waktu pelayanan</p>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-red-500 dark:text-red-400 text-6xl mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Gagal Memuat Data</h3>
        <p class="text-gray-600 dark:text-gray-300 mb-4">{{ error }}</p>
        <Button 
          label="Coba Lagi" 
          icon="pi pi-refresh" 
          @click="fetchDashboardData"
          class="p-button-outlined"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import apiClient from '@/api/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'

// Reactive data
const dashboardData = ref(null)
const loading = ref(true)
const error = ref(null)

// Computed properties
const totalPelayananHariIni = computed(() => {
  if (!dashboardData.value?.top_staff) return 0
  return dashboardData.value.top_staff.reduce((total, staff) => total + staff.total_pelayanan, 0)
})

const averageServiceTime = computed(() => {
  if (!dashboardData.value?.statistik_waktu_pelayanan?.length) return 0
  const total = dashboardData.value.statistik_waktu_pelayanan.reduce(
    (sum, stat) => sum + stat.rata_rata_waktu_menit, 0
  )
  return (total / dashboardData.value.statistik_waktu_pelayanan.length).toFixed(1)
})

// Methods
const fetchDashboardData = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await apiClient.get('/dashboard-monitoring')
    const result = response.data

    if (result.success) {
      dashboardData.value = result.data
    } else {
      throw new Error(result.message || 'Failed to load dashboard data')
    }
    
    if (result.success) {
      dashboardData.value = result.data
    } else {
      throw new Error(result.message || 'Failed to load dashboard data')
    }
  } catch (err) {
    error.value = err.message
    console.error('Error fetching dashboard data:', err)
  } finally {
    loading.value = false
  }
}

const getRankBadgeClass = (index) => {
  const classes = [
    'bg-yellow-500', // 1st place - gold
    'bg-gray-400',   // 2nd place - silver
    'bg-orange-600'  // 3rd place - bronze
  ]
  return classes[index] || 'bg-blue-500'
}

// Lifecycle
onMounted(() => {
  fetchDashboardData()
  
  // Auto refresh every 30 seconds
  const interval = setInterval(fetchDashboardData, 30000)
  
  // Cleanup interval on unmount
  return () => clearInterval(interval)
})
</script>

<style scoped>
.p-card-content {
  padding: 1.5rem;
}
</style>