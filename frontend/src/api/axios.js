import axios from 'axios';

// Buat instance axios
const apiClient = axios.create({
    baseURL: 'http://localhost:8000/api', // Sesuaikan dengan URL backend Anda
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Request interceptor - menambahkan token ke setiap request
apiClient.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor - handle token expired
apiClient.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        // Jika token expired atau unauthorized
        if (error.response?.status === 401) {
            // Bersihkan localStorage jika token tidak valid
            localStorage.removeItem('user');
            localStorage.removeItem('token');
            
            // Redirect ke halaman login jika bukan request login
            if (!error.config.url.includes('/auth/login')) {
                window.location.href = '/'; // atau route login Anda
            }
        }
        
        return Promise.reject(error);
    }
);

export default apiClient;