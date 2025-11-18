import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { visualizer } from "rollup-plugin-visualizer";

function manualChunks(id) {
    if (id.includes('node_modules')) {
        return 'vendor';
    }

    return null;
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/ts/app.ts',
            ],
            refresh: true
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false
                }
            }
        }),
        visualizer()
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js'
        }
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: manualChunks
            }
        }
    },
    server: {
        watch: {
            usePolling: false
        },
        hmr: {
            host: 'localhost'
        }
    }
});
