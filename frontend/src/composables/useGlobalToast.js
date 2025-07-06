import { ref } from 'vue';

// Global toast instance
const globalToastInstance = ref(null);

export const useGlobalToast = () => {
    // Set toast instance (dipanggil dari App.vue atau layout utama)
    const setGlobalToast = (toastInstance) => {
        globalToastInstance.value = toastInstance;
    };

    // Toast methods yang bisa dipanggil dari mana saja
    const showSuccess = (summary, detail = '', life = 3000, closable = true) => {
        if (globalToastInstance.value) {
            globalToastInstance.value.add({
                severity: 'success',
                summary,
                detail,
                life,
                closable
            });
        } else {
            console.warn('Toast instance not initialized');
        }
    };

    const showError = (summary, detail , life = 5000, closable = true) => {
        if (globalToastInstance.value) {
            globalToastInstance.value.add({
                severity: 'error',
                summary,
                detail,
                life,
                closable
            });
        } else {
            console.warn('Toast instance not initialized');
        }
    };

    const showWarn = (summary, detail = '', life = 4000, closable = true, hideIcon = false) => {
        if (globalToastInstance.value) {
            globalToastInstance.value.add({
                severity: 'warn',
                summary,
                detail,
                life,
                closable,
                styleClass: hideIcon ? 'no-icon-toast' : ''
            });
        } else {
            console.warn('Toast instance not initialized');
        }
    };

    const showInfo = (summary, detail = '', life = 3000, closable = true) => {
        if (globalToastInstance.value) {
            globalToastInstance.value.add({
                severity: 'info',
                summary,
                detail,
                life,
                closable
            });
        } else {
            console.warn('Toast instance not initialized');
        }
    };

    // Custom toast dengan konfigurasi lengkap
    const showCustomToast = (options) => {
        if (globalToastInstance.value) {
            globalToastInstance.value.add({
                severity: 'info',
                life: 3000,
                ...options
            });
        } else {
            console.warn('Toast instance not initialized');
        }
    };

    // Hapus semua toast
    const clearAllToasts = () => {
        if (globalToastInstance.value) {
            globalToastInstance.value.removeAllGroups();
        }
    };

    return {
        setGlobalToast,
        showSuccess,
        showError,
        showWarn,
        showInfo,
        showCustomToast,
        clearAllToasts
    };
};