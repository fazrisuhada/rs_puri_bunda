<template>
    <div class="card p-5">
        <Menubar :model="items" class="!text-xs !font-medium !bg-white-500 !shadow-md !shadow-white-500/50">
            <template #end>
                <Button v-if="!currentUser" label="Login" icon="pi pi-user" @click="showDialog = true" />
                <SplitButton v-else :model="itemsSplitBtn" severity="secondary" size="small" outlined>
                    <span class="flex items-center cursor-pointer">
                        <Avatar image="https://primefaces.org/cdn/primevue/images/avatar/amyelsner.png" class="mr-2"
                            shape="circle" style="width: 22px; height: 22px;" />
                        <span class="!text-xs">{{ currentUser.name }}</span>
                    </span>
                </SplitButton>
            </template>
        </Menubar>
    </div>

    <!-- dialog login start-->
    <AuthFrm v-model:visible="showDialog" />
    <!-- dialog login end -->

    <!-- dialog ambil antrian start-->
    <AmbilAntrian />
    <!-- dialog ambil antrian end -->
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import Menubar from 'primevue/menubar';
import AuthFrm from "@/components/Auth/AuthFrm.vue";
import AmbilAntrian from "@/components/Antrian/AmbilAntrian.vue";

// pinia
import { storeToRefs } from "pinia";
import { useAuthenticationStore } from "@/stores/authStore.js";
import { useAmbilAntrianStore } from "@/stores/ambilAntrianStore.js";
import router from "@/router";

const authStore = useAuthenticationStore();
const ambilAntrianStore = useAmbilAntrianStore();

// state pinia
const { currentUser, showDialog } = storeToRefs(authStore);
// action pinia
const { logoutStore } = authStore;

const items = computed(() => {
    const baseItems = [
        {
            label: 'Ambil Antrian',
            icon: 'pi pi-user-plus',
            command: () => {
                ambilAntrianStore.openDialog();
            }
        },
        {
            label: 'Display Antrian',
            icon: 'pi pi-desktop',
            command: () => {
                window.open('/display-antrian', '_blank');
            }
        },
        {
            label: 'Dashboard Monitoring',
            icon: 'pi pi-objects-column',
            command: () => {
                router.push({ name: 'dashboard-monitoring' });
            }
        }
    ];

    // Jika user sudah login, sisipkan List Antrian di urutan ke-2
    if (currentUser.value) {
        baseItems.splice(1, 0, {
            label: 'List Antrian',
            icon: 'pi pi-address-book',
            command: () => {
                router.push({ name: 'list-antrian' });
            }
        });
    }

    return baseItems;
});

const itemsSplitBtn = [
    {
        label: 'Dashboard',
        class: '!text-xs',
        command: () => {
            console.log('Dashboard clicked');
        }
    },
    {
        separator: true
    },
    {
        label: 'Sign Out',
        class: '!text-xs',
        command: () => {
            logoutStore();
        }
    }
];

onMounted(() => {
    // Pastikan user dimuat dari localStorage saat component dimount
    authStore.loadUserFromLocalStorage();
});

</script>
