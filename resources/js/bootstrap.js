import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    window.axios.defaults.withCredentials = true;
    window.axios.interceptors.request.use(function (config) {
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [key, value] = cookie.trim().split('=');
            if (key === 'XSRF-TOKEN') {
                config.headers['X-XSRF-TOKEN'] = decodeURIComponent(value);
                break;
            }
        }
        return config;
    });
}
