/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.{js,json,svelte,ts,scss,html}",

    "./templates/**/*.html.twig",
    "./src/**/*.php",

    // TODO : inject with bundle recipe ? or webpack bundle local build ?
    // Symfony Bundle
    // TODO : better to use link inside VENDOR instead of DEV structure for prod build and keeping stuff locally inside root folder
    // "../../../packages/mws-moon-manager/assets/**/*.scss", 
    // ../../../packages/mws-moon-manager to replace with ./vendor/monwoo/mws-moon-manager-bundle
    "./vendor/monwoo/mws-moon-manager-bundle/assets/**/*.scss",
    "./vendor/monwoo/mws-moon-manager-bundle/assets/**/*.js",
    "./vendor/monwoo/mws-moon-manager-bundle/assets/**/*.svelte",
    "./vendor/monwoo/mws-moon-manager-bundle/assets/**/*.ts",
    "./vendor/monwoo/mws-moon-manager-bundle/templates/**/*.html.twig",
    "./vendor/monwoo/mws-moon-manager-bundle/src/**/*.php",
    "./vendor/monwoo/mws-moon-manager-bundle/src/**/*.php",
    // TODO : integrate preline ? done svelte + jQuery way for now
    // './vendor/monwoo/mws-moon-manager-bundle/node_modules/@preline/dist/*.js',
    // './vendor/monwoo/mws-moon-manager-bundle/node_modules/@preline/scrollspy/*.js',
    // Svelte UX package
    "./vendor/monwoo/mws-moon-manager-ux-bundle/components/**/*.{js,svelte,ts}",
    "./vendor/monwoo/mws-demo-bundle/{assets,src,templates}/**/*.{js,svelte,ts,php,twig}",
    "./vendor/monwoo/mws-google-photo-reader-bundle/{assets,src,templates}/**/*.{js,svelte,ts,php,twig}",
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
      },
      screens: {
        // https://github.com/tailwindlabs/tailwindcss/discussions/2397
        'tall': {
          'raw': `only screen and (max-height: 960px) and (max-width: 480px)`
        },
        'wide': {
          'raw': `only screen and (max-height: 480px) and (max-width: 960px)`
        },
        'portrait': {
          'raw': '(orientation: portrait)'
        },
        'landscape': {
          'raw': '(orientation: landscape)'
        },
        // TIPS : rewrite md behavior instead of using code refactoring 
        // to have wide layout on landscape :
        // (if you prefer refactor), use regex : /md:([^ ]+)/
        // and replacement pattern : 'md:$1 landscape:$1'

        // TIPS : not so good, will have to resize small 
        //        for wide screens (+ break original md logic to big spaces...)
        // 'md': {
        //   // https://tailwindcss.com/docs/responsive-design
        //   // 'raw': '(min-width: 768px)',
        //   // 'raw': '(min-width: 768px) or (orientation: landscape)'
        //   'raw': '(min-width: 768px) or (max-height: 480px)'
        // }
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
    // require('./vendor/monwoo/mws-moon-manager-bundle/node_modules/@preline/plugin')
    // TODO : ReferenceError: self is not defined @preline/scrollspy/index.js:1:299 :
    //        Why post css going up to this file ? tailwind check ? but ok on svelte file ?
    // require('./vendor/monwoo/mws-moon-manager-bundle/node_modules/@preline/scrollspy')
  ],
}