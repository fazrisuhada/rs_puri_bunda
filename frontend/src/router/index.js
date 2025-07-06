import { createRouter, createWebHistory } from 'vue-router'
import AmbilAntrian from '../components/Antrian/AmbilAntrian.vue'
import ListAntrian from '../components/Antrian/ListAntrian.vue'
import DisplayAntrian from '@/components/Antrian/DisplayAntrian.vue'
import DashboardMonitoring from '@/components/Antrian/DashboardMonitoring.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'ambil-antrian',
      component: AmbilAntrian,
    },
    {
      path: '/list-antrian',
      name: 'list-antrian',
      component: ListAntrian,
    },
    {
      path: '/display-antrian',
      name: 'display-antrian',
      component: DisplayAntrian,
    },
    {
      path: '/dashboard-monitoring',
      name: 'dashboard-monitoring',
      component: DashboardMonitoring
    }
  ],
})

export default router
