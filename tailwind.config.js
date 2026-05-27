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
                // 'Inter' é mandatória no B2B. Garante formatação tabular em dados financeiros e alta legibilidade.
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                // Mono estrita para Hashes de Carga, IDs de Transação e Logs.
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Paleta corporativa blindada (Cor Central de Ação)
                brand: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9', // Core Action Color (Azul 123fretei)
                    600: '#0284c7', // Hover
                    700: '#0369a1', // Active
                    800: '#075985',
                    900: '#0c4a6e',
                    950: '#082f49',
                },
                // Superfícies frias (Slate) para redução de carga cognitiva e fadiga visual
                surface: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0', // Bordas sutis e Divisórias
                    300: '#cbd5e1',
                    400: '#94a3b8', // Ícones desabilitados
                    500: '#64748b', // Textos secundários
                    600: '#475569', // Labels e Metadados
                    700: '#334155', // Texto principal
                    800: '#1e293b', // Sidebars
                    900: '#0f172a',
                    950: '#020617', // Headers profundos
                },
            },
            spacing: {
                // Controle milimétrico para Viewports Mobile hostis (Notches, Barras do iOS/Android)
                'safe-top': 'env(safe-area-inset-top)',
                'safe-bottom': 'env(safe-area-inset-bottom)',
                'safe-left': 'env(safe-area-inset-left)',
                'safe-right': 'env(safe-area-inset-right)',
            },
            zIndex: {
                // Grade restrita de camadas para evitar conflitos de empilhamento de Modais e Dropdowns
                'dropdown': '1000',
                'sticky': '1020',
                'fixed': '1030',
                'modal-backdrop': '1040',
                'modal': '1050',
                'popover': '1060',
                'tooltip': '1070',
                'toast': '1080',
                'max': '9999', // Global Loaders / Notificações Push Críticas
            },
            boxShadow: {
                'clinical-sm': '0 1px 2px 0 rgba(15, 23, 42, 0.05)',
                'clinical-md': '0 4px 6px -1px rgba(15, 23, 42, 0.08), 0 2px 4px -1px rgba(15, 23, 42, 0.04)',
                'clinical-lg': '0 10px 15px -3px rgba(15, 23, 42, 0.1), 0 4px 6px -2px rgba(15, 23, 42, 0.05)',
            }
        },
    },

    plugins: [
        forms,
        // Injeção de Utilitários Cross-Browser para manter arquivos .vue limpos
        plugin(function({ addUtilities }) {
            addUtilities({
                '.scrollbar-hide': {
                    '-ms-overflow-style': 'none', /* IE e Edge */
                    'scrollbar-width': 'none', /* Firefox */
                    '&::-webkit-scrollbar': {
                        display: 'none' /* Chrome e Safari */
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