import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        axios: typeof axios;
        Pusher: typeof Pusher;
        Echo: Echo;
    }
}

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.baseURL =
    import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api';

/**
 * Creates a dedicated axios instance for fetching the CSRF cookie.
 * This is necessary because the cookie endpoint is at the root of the
 * backend domain, not under the /api prefix.
 */
export const csrfCookie = () => {
    const apiBaseUrl = window.axios.defaults.baseURL.replace('/api', '');
    return axios.get('/sanctum/csrf-cookie', {
        baseURL: apiBaseUrl,
    });
};

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authorizer: (channel: { name: string }) => {
        return {
            authorize: (
                socketId: string,
                callback: (error: boolean, authData: object) => void,
            ) => {
                // Use a custom axios config to hit the correct auth endpoint,
                // which is not under the '/api' prefix.
                const apiBaseUrl = window.axios.defaults.baseURL.replace(
                    '/api',
                    '',
                );
                axios
                    .post(
                        '/broadcasting/auth',
                        {
                            socket_id: socketId,
                            channel_name: channel.name,
                        },
                        { baseURL: apiBaseUrl },
                    )
                    .then((response) =>
                        callback(false, response.data as object),
                    )
                    .catch((error) => callback(true, error as object));
            },
        };
    },
});
