import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import tokens from './resources/js/tokens.json';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
        './resources/js/**/*.ts',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: tokens.color.ink,
                muted: tokens.color.muted,
                bg: tokens.color.bg,
                card: tokens.color.card,
                accent: tokens.color.accent,
                success: tokens.color.success,
                warning: tokens.color.warning,
                error: tokens.color.error,
                border: tokens.color.border,
            },
            spacing: {
                xs: `${tokens.space.xs}px`,
                sm: `${tokens.space.sm}px`,
                md: `${tokens.space.md}px`,
                lg: `${tokens.space.lg}px`,
                xl: `${tokens.space.xl}px`,
                '2xl': `${tokens.space['2xl']}px`,
                '3xl': `${tokens.space['3xl']}px`,
            },
            fontSize: {
                sm: [`${tokens.font.sm}px`, '1.25'],
                base: [`${tokens.font.base}px`, '1.5'],
                lg: [`${tokens.font.lg}px`, '1.5'],
                xl: [`${tokens.font.xl}px`, '1.4'],
                '2xl': [`${tokens.font['2xl']}px`, '1.3'],
                '3xl': [`${tokens.font['3xl']}px`, '1.2'],
            },
            borderRadius: {
                sm: `${tokens.radius.sm}px`,
                md: `${tokens.radius.md}px`,
                lg: `${tokens.radius.lg}px`,
                xl: `${tokens.radius.xl}px`,
            },
            boxShadow: {
                sm: tokens.shadow.sm,
                md: tokens.shadow.md,
                lg: tokens.shadow.lg,
            },
        },
    },

    plugins: [forms],
};
