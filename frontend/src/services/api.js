import axios from 'axios';

const timeoutMs = Number(import.meta.env.VITE_API_TIMEOUT_MS || 10000);

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  timeout: Number.isFinite(timeoutMs) && timeoutMs > 0 ? timeoutMs : 10000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('ims_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      localStorage.removeItem('ims_token');
      localStorage.removeItem('ims_user');
      window.dispatchEvent(new Event('ims:session-expired'));

      if (window.location.pathname !== '/login') {
        window.location.href = '/login?reason=session-expired';
      }
    }

    return Promise.reject(error);
  }
);

export default api;
