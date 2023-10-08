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

alert("OK from app");

registerSvelteControllerComponents(require.context('./svelte/controllers', true, /\.svelte$/));