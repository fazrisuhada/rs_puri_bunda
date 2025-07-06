import { createRouter, createWebHistory } from 'vue-router'
import AmbilAntrian from '../components/Antrian/AmbilAntrian.vue'
import ListAntrian from '../components/Antrian/ListAntrian.vue'
import DisplayAntrian from '@/components/Antrian/DisplayAntrian.vue'

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
    }
  ],
})

export default router
