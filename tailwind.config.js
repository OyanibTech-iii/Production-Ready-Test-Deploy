/** @type {import('tailwindcss').Config} */
import plugin from 'tailwindcss/plugin';
export default {
  darkMode: 'class',
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
    "./templates/**/*.twig",
    "./assets/**/*.{js,ts,vue}",
    "./public/**/*.html",
  ],
  theme: {
    extend: {

      colors: {
        primary: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#03A64A',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
          950: '#052e16',
        },
        forest: {
          50: '#f0f9f4',
          100: '#dcf2e3',
          200: '#bce5cc',
          300: '#8dd1a8',
          400: '#5bb57e',
          500: '#094021',
          600: '#083a1e',
          700: '#07301a',
          800: '#062616',
          900: '#051f12',
          950: '#03120a',
        },
        gray: {
          50: '#f8fafc',
          100: '#f1f5f9',
          200: '#e2e8f0',
          300: '#cbd5e1',
          400: '#94a3b8',
          500: '#6A7F74',
          600: '#475569',
          700: '#334155',
          800: '#1e293b',
          900: '#0f172a',
          950: '#020617',
        },
        background: '#F7FAFC',
        'bright-green': '#03A64A',
        'dark-forest-green': '#094021',
        'light-gray': '#6A7F74',
      },
      textShadow: {
        sm: '0 1px 2px var(--gray-950)',
        DEFAULT: '0 2px 4px var(--gray-950)',
        lg: '0 8px 16px var(--gray-950)',
      },
      fontFamily: {
        sans: ['Poppins', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'Noto Sans', 'sans-serif'],
        heading: ['Poppins', 'ui-sans-serif', 'system-ui'],
        'tagalog': ['"Noto Sans Tagalog"', 'sans-serif'],

      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'slide-in': 'slideIn 0.3s ease-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideIn: {
          '0%': { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(0)' },
        },
      },
      safelist: [
        'font-tagalog',
      ],
    },
  },
  plugins: [
    plugin(function ({ matchUtilities, theme }) {
      matchUtilities(
        {
          'text-shadow': (value) => ({
            textShadow: value,
          }),
        },
        { values: theme('textShadow') }
      )
    }),],
};