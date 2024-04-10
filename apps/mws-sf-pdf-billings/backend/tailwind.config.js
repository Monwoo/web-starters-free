/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.{js,json,svelte,ts,scss,html}",

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
    "../../../packages/mws-moon-manager/src/**/*.php",
    // TODO : integrate preline ? done svelte + jQuery way for now
    // '../../../packages/mws-moon-manager/node_modules/@preline/dist/*.js',
    // '../../../packages/mws-moon-manager/node_modules/@preline/scrollspy/*.js',
    // Svelte UX package
    "../../../packages/mws-moon-manager-ux/components/**/*.{js,svelte,ts}",
    "../../../../mws-demo/{assets,src,templates}/**/*.{js,svelte,ts,php,twig}",
    // https://flowbite.com/docs/getting-started/quickstart/
    "./node_modules/flowbite/**/*.js",
    // https://flowbite-svelte.com/docs/pages/introduction
    './node_modules/flowbite-svelte/**/*.{html,js,svelte,ts}',
  ],
  theme: {
    extend: {
      colors: {
        // flowbite-svelte
        primary: {
          50: '#FFF5F2',
          100: '#FFF1EE',
          200: '#FFE4DE',
          300: '#FFD5CC',
          400: '#FFBCAD',
          500: '#FE795D',
          600: '#EF562F',
          700: '#EB4F27',
          800: '#CC4522',
          900: '#A5371B'
        }
      }
    }
  },
  darkMode: 'class',
  plugins: [
    // https://github.com/tailwindlabs/tailwindcss-forms
    require('@tailwindcss/forms'),
    require('flowbite/plugin')({
      charts: true,
  }),
    // require('../../../packages/mws-moon-manager/node_modules/@preline/plugin')
    // TODO : ReferenceError: self is not defined @preline/scrollspy/index.js:1:299 :
    //        Why post css going up to this file ? tailwind check ? but ok on svelte file ?
    // require('../../../packages/mws-moon-manager/node_modules/@preline/scrollspy')
  ],
}