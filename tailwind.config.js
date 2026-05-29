import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        screens: {
            'xs': '320px',    // Celulares pequenos e estreitos (Ponta do Motorista)
            'sm': '640px',    // Celulares grandes / Tablets na vertical
            'md': '768px',    // Tablets na horizontal
            'lg': '1024px',   // Notebooks menores
            'xl': '1280px',   // Notebooks padrão
            '2xl': '1536px',  // Monitores Desktop
            '3xl': '1920px',  // Monitores Full HD (Padrão corporativo moderno)
            '4k': '2560px',   // Monitores Quad HD / 4K Base
            'tv': '3840px',   // Smart TVs / Video Walls e NOC (Mesa de Operações)
        },
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // ==========================================
                // PALETA OFICIAL 123FRETEI (Sincronizado com index.html)
                // ==========================================
                brand: {
                    50: '#fff8f5',
                    100: '#ffefeb',
                    200: '#ffdad1',
                    300: '#ffbba8',
                    400: '#ff8e6e',
                    500: '#ff5500', // A COR BASE DA PLATAFORMA (Botões, Destaques)
                    600: '#e64d00', // Hover (--c-brand-hover do index.html)
                    700: '#c23c00',
                    800: '#993100',
                    900: '#7a2b04',
                    950: '#421300',
                },
                // Superfícies frias (Slate) para redução de carga cognitiva
                surface: {
                    50: '#f8fafc',  // --c-bg-alt do index.html
                    100: '#f1f5f9',
                    200: '#e2e8f0', // --c-border do index.html
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b', // --c-text-muted do index.html
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a', // --c-text-main do index.html
                    950: '#020617', 
                },
            },
            spacing: {
                'safe-top': 'env(safe-area-inset-top)',
                'safe-bottom': 'env(safe-area-inset-bottom)',
                'safe-left': 'env(safe-area-inset-left)',
                'safe-right': 'env(safe-area-inset-right)',
            },
            zIndex: {
                'dropdown': '1000',
                'sticky': '1020',
                'fixed': '1030',
                'modal-backdrop': '1040',
                'modal': '1050',
                'popover': '1060',
                'tooltip': '1070',
                'toast': '1080',
                'max': '9999',
            },
            boxShadow: {
                'clinical-sm': '0 1px 2px 0 rgba(15, 23, 42, 0.05)',
                'clinical-md': '0 4px 6px -1px rgba(15, 23, 42, 0.08), 0 2px 4px -1px rgba(15, 23, 42, 0.04)',
                'clinical-lg': '0 10px 15px -3px rgba(15, 23, 42, 0.1), 0 4px 6px -2px rgba(15, 23, 42, 0.05)',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(5px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                scrollVerticalUp: {
                    '0%': { transform: 'translateY(0)' },
                    '100%': { transform: 'translateY(-50%)' },
                }
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-out forwards',
                'scroll-vertical-up': 'scrollVerticalUp linear infinite',
            }
        },
    },

    plugins: [
        forms,
        plugin(function({ addUtilities }) {
            addUtilities({
                '.scrollbar-hide': {
                    '-ms-overflow-style': 'none',
                    'scrollbar-width': 'none',
                    '&::-webkit-scrollbar': {
                        display: 'none'
                    }
                },
                '.scrollbar-clinical': {
                    'scrollbar-width': 'thin',
                    'scrollbar-color': '#94a3b8 transparent',
                    '&::-webkit-scrollbar': {
                        width: '6px',
                        height: '6px',
                    },
                    '&::-webkit-scrollbar-track': {
                        background: 'transparent',
                    },
                    '&::-webkit-scrollbar-thumb': {
                        backgroundColor: '#64748b',
                        borderRadius: '3px',
                    },
                    '&::-webkit-scrollbar-thumb:hover': {
                        backgroundColor: '#475569',
                    },
                },
                '.tabular-nums': {
                    'font-variant-numeric': 'tabular-nums',
                }
            })
        })
    ],
};