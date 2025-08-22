/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./api/*.php",
    "./*.html",
    "./*.js",
    // Adicionar mais especificidade
    "./home.php",
    "./index.php",
    "./login.php"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
