import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import babel from 'vite-plugin-babel';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
        babel({
            babelConfig: {
                babelrc: false,
                configFile: './babel.config.json',
            },
            filter: /\.(jsx?|vue)$/,
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        target: ['es2015', 'safari12'],
        cssTarget: ['safari12'],
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
    optimizeDeps: {
        esbuildOptions: {
            target: 'es2015',
        },
    },
});
