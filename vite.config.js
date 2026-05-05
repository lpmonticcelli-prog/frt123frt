import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },
    // --- BLINDAGEM HÍBRIDA WSL2 ---
    server: {
        host: '0.0.0.0', // Mantém a porta aberta no Linux
        port: 5173,
        strictPort: true,
        cors: true, 
        hmr: {
            host: 'localhost' // Força o Laravel a buscar os assets no endereço que sabemos que funciona
        }
    }
});