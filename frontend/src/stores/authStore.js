import { ref } from 'vue';
import apiClient from '@/api/axios';
import { defineStore } from 'pinia';
import { useRouter } from 'vue-router';
import { useGlobalToast } from '@/composables/useGlobalToast';

export const useAuthenticationStore = defineStore('auth', () => {

    // state
    const { showSuccess, showError } = useGlobalToast();
    const showDialog = ref(false);
    const isLoading = ref(false);
    const errors = ref({
        general: '',    // Error umum (misal: server error, koneksi gagal)
        username: '',   // Error khusus untuk field username
        email: '',      // Error khusus untuk field email
        password: ''    // Error khusus untuk field password
    });
    
    // Initialize currentUser dengan null, bukan dari localStorage
    const currentUser = ref(null);

    const router = useRouter();

    // Helper
    const clearErrors = () => {
        errors.value = {
            general: '',
            username: '',
            email: '',
            password: ''
        };
    };
    const setFieldError = (field, message) => {
        errors.value[field] = message;
    };

    // actions
    const loginStore = async (input) => {
        clearErrors();
        isLoading.value = true;

        try {
            let hasError = false;
            if (!input.email) {
                setFieldError('email', 'Email is required');
                hasError = true;
            }
            if (!input.password) {
                setFieldError('password', 'Password is required');
                hasError = true;
            }
            if (hasError) {
                isLoading.value = false;
                return;
            }

            const { data } = await apiClient.post('/auth/login', {
                email: input.email,
                password: input.password
            });

            // Set currentUser dengan data user yang benar
            currentUser.value = data.user;
            
            // Simpan user data yang benar ke localStorage
            localStorage.setItem('user', JSON.stringify(data.user));
            
            // Simpan token ke localStorage juga
            localStorage.setItem('token', data.token);

            showSuccess('Success', 'Login berhasil');
            showDialog.value = false;
            router.push({ name: 'Dashboard' });
        } catch (error) {
            // Cek jenis error berdasarkan status code HTTP
            if (error.response?.status === 422) {
                const backendErrors = error.response.data.errors;
                if (backendErrors) {
                    // Loop melalui setiap error dari backend
                    Object.keys(backendErrors).forEach(field => {
                        // Cek apakah field error ada di struktur error kita
                        if (errors.value.hasOwnProperty(field)) {
                            // Ambil pesan error pertama untuk field tersebut
                            setFieldError(field, backendErrors[field][0]);
                        }
                    });
                }

            } else if (error.response?.status === 401) {
                const message = error.response?.data?.message || 'Invalid credentials';
                setFieldError('general', message);
            } else {
                // Error lainnya: Server error, network error, dll
                const message = error.response?.data?.message || 'Internal server error';
                setFieldError('general', message);
            }
        } finally {
            isLoading.value = false;
        }
    };

    const registerStore = async (input) => {
        clearErrors();
        isLoading.value = true;

        try {
            let hasError = false;
            if (!input.username) {
                setFieldError('username', 'Username is required');
                hasError = true;
            }
            if (!input.email) {
                setFieldError('email', 'Email is required');
                hasError = true;
            }
            if (!input.password) {
                setFieldError('password', 'Password is required');
                hasError = true;
            }
            if (hasError) {
                isLoading.value = false;
                return;
            }

            const { data } = await apiClient.post('/auth/register', {
                username: input.username,
                email: input.email,
                password: input.password
            });

            // Set currentUser dengan data user yang benar
            currentUser.value = data.user;
            
            // Simpan user data yang benar ke localStorage
            localStorage.setItem('user', JSON.stringify(data.user));
            
            // Simpan token ke localStorage juga
            localStorage.setItem('token', data.token);

            showDialog.value = false;
            router.push({ name: 'Dashboard' });
        } catch (error) {
            // Cek jenis error berdasarkan status code HTTP
            if (error.response?.status === 422) {
                const backendErrors = error.response.data.errors;
                if (backendErrors) {
                    // Loop melalui setiap error dari backend
                    Object.keys(backendErrors).forEach(field => {
                        // Cek apakah field error ada di struktur error kita
                        if (errors.value.hasOwnProperty(field)) {
                            // Ambil pesan error pertama untuk field tersebut
                            setFieldError(field, backendErrors[field][0]);
                        }
                    });
                }

            } else if (error.response?.status === 401) {
                const message = error.response?.data?.message || 'Invalid credentials';
                setFieldError('general', message);
            } else {
                const message = error.response?.data?.message || 'Internal server error';
                setFieldError('general', message);
            }
        } finally {
            isLoading.value = false;
        }
    }

    const logoutStore = async () => {
        try {
            await apiClient.post('/auth/logout');
            
            // Bersihkan localStorage dan state
            localStorage.removeItem('user');
            localStorage.removeItem('token');
            currentUser.value = null;
            
            showSuccess('Success', 'Logout berhasil');
            router.push({ name: 'ambil-antrian' });
        } catch (error) {
            // console.log('Logout error:', error);
            localStorage.removeItem('user');
            localStorage.removeItem('token');
            currentUser.value = null;
            router.push({ name: 'ambil-antrian' });
        }
    };

    const loadUserFromLocalStorage = () => {
        try {
            const userString = localStorage.getItem('user');
            const token = localStorage.getItem('token');
            
            if (userString && userString !== 'undefined' && userString !== 'null') {
                const userData = JSON.parse(userString);
                currentUser.value = userData;
            } else {
                currentUser.value = null;
            }
        } catch (error) {
            currentUser.value = null;
            console.error("Gagal parse user dari localStorage:", error);
        }
    };

    // actions end

    return {
        // State variables
        showDialog,
        isLoading,
        errors,
        currentUser,

        // Functions
        loginStore,
        registerStore,
        clearErrors,
        setFieldError,
        logoutStore,
        loadUserFromLocalStorage 
    };
});