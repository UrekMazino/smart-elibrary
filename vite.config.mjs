import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Builds the guest pages' assets (Tailwind + Alpine) into public/build; layouts/guest.blade.php
// loads them with @vite. The admin and OPAC pages use the template assets under public/ as-is.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
