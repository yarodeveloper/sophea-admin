/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./*.php",
        "./includes/**/*.php",
        "./sections/**/*.php",
        "./components/**/*.php",
        "./blog_content/**/*.php",
        "./admin/**/*.php",
        "./assets/**/*.js",
    ],
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                'gradient-primary': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                "primary": "#13a4ec",
                "background-light": "#f6f7f8",
                "background-dark": "#101c22",
                "card-light": "#ffffff",
                "card-dark": "#1a262d",
                "surface-dark": "#283339",
            },
            fontFamily: {
                "display": ["Manrope", "sans-serif"],
                "body": ["Inter", "sans-serif"]
            },
            boxShadow: {
                'glow': '0 0 20px rgba(102, 126, 234, 0.4)',
            },
            borderRadius: {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "2xl": "1rem",
                "full": "9999px"
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/container-queries'),
    ],
}
