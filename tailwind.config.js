import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import motion from 'tailwindcss-motion';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#823E87',
                    50: '#F5EBF6',
                    100: '#E8D3EA',
                    200: '#D1A7D5',
                    300: '#BA7BC0',
                    400: '#A34FAB',
                    500: '#823E87',
                    600: '#68326C',
                    700: '#4E2551',
                    800: '#341936',
                    900: '#1A0C1B',
                },
            },
        },
    },

    plugins: [
        forms, 
        motion,
        require('daisyui')
    ],

    daisyui: {
        themes: [
            {
                light: {
                    "primary": "#823E87",
                    "primary-content": "#ffffff",
                    "secondary": "#BA7BC0",
                    "accent": "#A34FAB",
                    "neutral": "#2b3440",
                    "base-100": "#ffffff",
                    "base-200": "#f8f9fa",
                    "base-300": "#f1f3f5",
                    "info": "#3abff8",
                    "success": "#36d399",
                    "warning": "#fbbd23",
                    "error": "#f87272",
                },
            },
        ],
    },
};
