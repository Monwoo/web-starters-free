// TODO : MoonManagerBundle recipe + factorize integration... hard coded for now :
// TIPS : use encore or webpack path configs instead ? @moon-manger/...
// import '../../../../packages/mws-moon-manager/assets/app.js';

import { registerSvelteControllerComponents } from '@symfony/ux-svelte';
import './bootstrap.js';
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// TIPS : 
// https://stackoverflow.com/questions/68552282/give-different-color-to-top-and-bottom-of-the-webpage-body-overscroll
// TIPS : gradient do not sound to work with scroll bg color, need to be unique color ?
// document.documentElement.style.background = `linear-gradient(to right, rgb(var(--mws-background-rgb)), rgb(var(--mws-background-light-rgb)), rgb(var(--mws-background-rgb)))`;
// document.documentElement.style.background = `linear-gradient(
//   to right,
//   RGB(var(--mws-background-rgb)),
//   RGB(var(--mws-background-light-rgb)),
//   RGB(var(--mws-background-rgb))
// )`;
document.documentElement.style.background = `RGB(var(--mws-background-rgb))`;

// alert("OK from app");


registerSvelteControllerComponents(require.context('./svelte/controllers', true, /\.svelte$/));