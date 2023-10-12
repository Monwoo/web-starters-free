/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
import { registerSvelteControllerComponents } from '@symfony/ux-svelte';
import './bootstrap.js';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// TODO : integrate with some 'app' encore existing builds and configs... best way to extend ?

import Routing from 'fos-router';
const baseUrlFull = process.env.BASE_HREF_FULL;
Routing.setBaseUrl(baseUrlFull);

registerSvelteControllerComponents(require.context('./svelte/controllers', true, /\.svelte$/));