/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './pages/**/*.php',
    './templates/**/*.php',
    './admin/**/*.php',
  ],
  theme: {
    extend: {
      animation: {
        'fade-in': 'fadeIn 0.45s ease-out both',
        'fade-in-slow': 'fadeIn 0.65s ease-out both',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(8px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
    },
  },
  plugins: [require('daisyui')],
  daisyui: {
    themes: [
      {
        infohub: {
          primary: '#4f7cff',
          'primary-content': '#f8fafc',
          secondary: '#1e293b',
          'secondary-content': '#e2e8f0',
          accent: '#38bdf8',
          'accent-content': '#0f172a',
          neutral: '#1e293b',
          'neutral-content': '#e2e8f0',
          'base-100': '#0b1220',
          'base-200': '#111b2e',
          'base-300': '#0f172a',
          'base-content': '#e6eefc',
          info: '#38bdf8',
          success: '#4ade80',
          warning: '#fbbf24',
          error: '#f87171',
        },
      },
    ],
    darkTheme: 'infohub',
  },
};
