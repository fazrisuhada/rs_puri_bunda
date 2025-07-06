<template>
  <Toast position="bottom-left"/>
  <div>
    <Header />
    <div class="p-5 ml-1">
      <RouterView />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Header from "./layouts/navbar/Header.vue";

// prime vue 
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { useGlobalToast } from '@/composables/useGlobalToast.js';

const toast = useToast();
const { setGlobalToast } = useGlobalToast();

// const getResponseBE = async () => {
//   const data = await useAPI.get("/test");
//   console.log(data);
// }

onMounted(() => {
  // getResponseBE();
  setGlobalToast(toast);
})
</script>

<style scoped>
.p-toast-message-error .p-toast-icon {
  display: none !important;
}

:deep(.p-toast-message-error .p-toast-icon) {
  display: none !important;
}
</style>