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
window.axios.defaults.baseURL = 'http://localhost/api'; // Assumes backend is on /api

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authorizer: (channel: { name: string }) => {
        return {
            authorize: (socketId: string, callback: (error: boolean, authData: object) => void) => {
                axios
                    .post('/broadcasting/auth', {
                        socket_id: socketId,
                        channel_name: channel.name,
                    })
                    .then((response) => callback(false, response.data as object))
                    .catch((error) => callback(true, error as object));
            },
        };
    },
});
