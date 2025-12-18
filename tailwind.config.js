/** @type {import('tailwindcss').Config} */
import colors from "tailwindcss/colors";

export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.ts",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                /* =========================
                   Couleurs Tailwind par défaut
                ========================== */
                slate: colors.slate,
                gray: colors.gray,
                zinc: colors.zinc,
                neutral: colors.neutral,
                stone: colors.stone,

                red: colors.red,
                orange: colors.orange,
                amber: colors.amber,
                yellow: colors.yellow,
                lime: colors.lime,
                green: colors.green,
                emerald: colors.emerald,
                teal: colors.teal,
                cyan: colors.cyan,
                sky: colors.sky,
                blue: colors.blue,
                indigo: colors.indigo,
                violet: colors.violet,
                purple: colors.purple,
                fuchsia: colors.fuchsia,
                pink: colors.pink,
                rose: colors.rose,

                /* =========================
                   Tes couleurs personnalisées
                ========================== */
                primary: {
                    DEFAULT: "#ab2283",
                    dark: "#8a1c6a",
                    light: "#d536a8",
                },
                secondary: {
                    DEFAULT: "#31b6b8",
                    dark: "#289396",
                    light: "#5cd4d6",
                },
                dark: {
                    DEFAULT: "#474665",
                    light: "#5c5a7a",
                    lighter: "#767494",
                },

                /* Exemple nuance custom ajoutée */
                slateCustom: {
                    50: "#f8fafc",
                },
            },

            fontSize: {
                xs: "0.75rem",
                sm: "0.875rem",
                base: "1rem",
                lg: "1.125rem",
                xl: "1.25rem",
                "2xl": "1.5rem",
                "3xl": "1.875rem",
                "4xl": "2.25rem",
            },

            borderRadius: {
                xl: "12px",
                "2xl": "16px",
            },

            boxShadow: {
                soft: "0 4px 20px rgba(0, 0, 0, 0.08)",
                medium: "0 8px 30px rgba(0, 0, 0, 0.12)",
                hard: "0 12px 40px rgba(0, 0, 0, 0.15)",
            },

            animation: {
                "fade-in": "fadeIn 0.5s ease-out",
                "slide-up": "slideUp 0.3s ease-out",
                "pulse-soft":
                    "pulseSoft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite",
            },

            keyframes: {
                fadeIn: {
                    "0%": { opacity: "0" },
                    "100%": { opacity: "1" },
                },
                slideUp: {
                    "0%": { transform: "translateY(10px)", opacity: "0" },
                    "100%": { transform: "translateY(0)", opacity: "1" },
                },
                pulseSoft: {
                    "0%, 100%": { opacity: "1" },
                    "50%": { opacity: "0.7" },
                },
            },

            backdropBlur: {
                sm: "50px",
            },
        },
    },
    plugins: [],
};
