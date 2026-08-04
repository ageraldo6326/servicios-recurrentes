import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                app: '#f7f8fc',
                surface: '#ffffff',
                'surface-soft': '#f0f4ff',
                ink: '#0e1b31',
                muted: '#687386',
                line: '#d9deea',
                brand: '#087d74',
            },
        },
    },

    plugins: [forms],
};
