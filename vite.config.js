import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/outfit-builder.js', 'resources/js/bravos-outfit.js', 'resources/js/checkout.js', 'resources/js/combo-builder.js', 'resources/js/nosotros.js', 'resources/css/explore.css', 'resources/js/explore.js', 'resources/css/girls.css', 'resources/js/girls.js', 'resources/js/admin-datetime.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
