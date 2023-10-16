/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.{js,json,svelte,ts,scss}",

    "./templates/**/*.html.twig",
    "./src/**/*.php",

    // TODO : inject with bundle recipe ? or webpack bundle local build ?
    // Symfony Bundle
    "../../../packages/mws-moon-manager/assets/**/*.scss",
    "../../../packages/mws-moon-manager/assets/**/*.js",
    "../../../packages/mws-moon-manager/assets/**/*.svelte",
    "../../../packages/mws-moon-manager/assets/**/*.ts",
    "../../../packages/mws-moon-manager/templates/**/*.html.twig",
    "../../../packages/mws-moon-manager/src/**/*.php",
    // Svelte UX package
    "../../../packages/mws-moon-manager-ux/components/**/*.js",
    "../../../packages/mws-moon-manager-ux/components/**/*.svelte",
    "../../../packages/mws-moon-manager-ux/components/**/*.ts",
  ],
  theme: {
    extend: {},
  },
  plugins: [
    // https://github.com/tailwindlabs/tailwindcss-forms
    require('@tailwindcss/forms'),
  ],
}

