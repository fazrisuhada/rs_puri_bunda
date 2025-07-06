<template>
  <Dialog
    v-model:visible="showDialogAntrian"
    header="Ambil Antrian"
    :style="{ width: '25rem' }"
    modal
  >
    <span class="text-surface-500 dark:text-surface-400 block mb-4">Pilih jenis antrian</span>

    <div class="flex items-center gap-4 mb-4">
      <AutoComplete
        v-model="jenisAntrian"
        dropdown
        :suggestions="filteredAntrian"
        @complete="search"
        placeholder="pilih satu..."
        class="w-full"
      />
    </div>

    <div v-if="errorMessage" class="text-red-500 text-sm mb-2">{{ errorMessage }}</div>

    <div class="flex justify-end gap-2">
      <Button
        type="button"
        label="Batal"
        severity="secondary"
        @click="closeDialog"
      />
      <Button
        type="button"
        label="Ambil Antrian"
        :loading="isLoading"
        :disabled="!jenisAntrian"
        @click="simpanAntrian(jenisAntrian)"
      />
    </div>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue'

// Pinia store
import { storeToRefs } from 'pinia'
import { useAmbilAntrianStore } from '@/stores/ambilAntrianStore'

const ambilAntrianStore = useAmbilAntrianStore()

// Bind state dari store
const {
  showDialogAntrian,
  isLoading,
  errorMessage
} = storeToRefs(ambilAntrianStore)

// Aksi dari store
const {
  simpanAntrian,
  closeDialog
} = ambilAntrianStore

// Local state
const jenisAntrian = ref('')
const filteredAntrian = ref([])

const antrianOptions = ['reservasi', 'walk-in']

function search(event) {
  let query = event.query.toLowerCase()
  filteredAntrian.value = antrianOptions.filter(item =>
    item.toLowerCase().includes(query)
  )
}
</script>
