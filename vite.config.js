import {defineConfig, splitVendorChunkPlugin} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { visualizer } from "rollup-plugin-visualizer";

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
        splitVendorChunkPlugin(),
        visualizer()
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Supposedly going to be removed in Vite7, but can't find stylesheets from components without it
                api: 'legacy',
                silenceDeprecations: [
                    'legacy-js-api'
                ]
            }
        }
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js'
        }
    },
    server: {
        watch: {
            usePolling: true
        },
        hmr: {
            host: 'localhost'
        }
    }
});
