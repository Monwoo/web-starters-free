/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// TODO : integrate with some 'app' encore existing builds and configs... best way to extend ?

// TIPS : should be init BEFORE registerSvelteControllerComponents that use it
import Routing from 'fos-router';
const baseUrlFull = process.env.BASE_HREF_FULL;
Routing.setBaseUrl(baseUrlFull);

// <script src="{{ asset('bundles/fosjsrouting/js/router.min.js') }}"></script>
// <script src="{{ path('fos_js_routing_js', { callback: 'fos.Router.setData' }) }}"></script>
// Above not working... trying the JSON way :
// https://github.com/FriendsOfSymfony/FOSJsRoutingBundle/blob/master/Resources/doc/usage.rst
const routes = require('../../../apps/mws-sf-pdf-billings/backend/assets/fos-routes.json');
console.log(routes);
Routing.setRoutingData(routes);

import { registerSvelteControllerComponents } from '@symfony/ux-svelte';
import './bootstrap.js';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

registerSvelteControllerComponents(require.context('./svelte/controllers', true, /\.svelte$/));