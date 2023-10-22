// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Biblio
// https://dev.to/robertobutti/how-to-start-building-your-static-website-with-svelte-and-tailwindcss-hbk
// https://flaviocopes.com/fix-dirname-not-defined-es-module-scope/
// https://github.com/sveltejs/kit/issues/5107
// https://github.com/sveltejs/kit/tree/master/packages/adapter-static
// https://github.com/sveltejs/kit/tree/master/packages/adapter-static#spa-mode
// https://github.com/sveltejs/svelte-preprocess
// https://kit.svelte.dev/docs/adapter-static
// https://kit.svelte.dev/docs/configuration#csp
// https://kit.svelte.dev/docs/configuration#embedded
// https://kit.svelte.dev/docs/configuration#files
// https://kit.svelte.dev/docs/configuration#prerender
// https://stackoverflow.com/questions/69378392/sveltekit-base-url-for-subdirectory-throws-404
// https://stackoverflow.com/questions/72956324/how-can-i-use-vite-env-variables-in-svelte-config-js
// https://stackoverflow.com/questions/74325393/how-to-define-base-urls-in-sveltekit
// https://svelte.dev/docs#compile-time-svelte-compile
// https://vitejs.dev/guide/env-and-mode.html

import adapter from '@sveltejs/adapter-static';
import preprocess from 'svelte-preprocess';
import 'dotenv/config';

let baseHref = process.env.VITE_MWS_BASE_HREF || '';
console.warn("CONFIGURE svelte.config.js for : ", process.env.NODE_ENV);

if (process.env.NODE_ENV === 'development' && process.env.VITE_MWS_BASE_HREF) {
	console.warn("DISABELING VITE_MWS_BASE_HREF for developpement, to avoid buggy behavior");
	// Use scss preprocess:preprocess{scss:{includePaths:[...]}}
	// is not enough to avoid scss fails when using kit.path.base...
	// SO WE AVOID BASE_HREF for DEV ENV :
	baseHref = '';
}

/** @type {import('@sveltejs/kit').Config} */
const config = {
	preprocess: [
		preprocess({
			postcss: true,
		})
	],
	// accessors: true,
	prerender: {
		default: true,
	},
	kit: {
		adapter: adapter({
			pages: 'build',
			assets: 'build',
			// precompress: true,
			fallback: null
		}),
		paths: {
			base: baseHref,
		},
		alias: {
			'@app': 'src',
			'@app-wp-display-i18n-routes': 'src/routes/(wp-display)/[[i18n=i18n]]',
		},
		files: {
			serviceWorker: 'src/service-worker',
		},
		// If true, SvelteKit will add its event listeners related to navigation etc
		// on the parent of %sveltekit.body% instead of window, 
		// and will pass params from the server
		// rather than inferring them from location.pathname.
		embedded: false,
		prerender: {
			handleHttpError: (e) => {
				const {
					path,
					referrer,
					message
				} = e;
				// ignore deliberate link to shiny 404 page
				if (path === '/not-found' && referrer === '/blog/how-we-built-our-404-page') {
					return;
				}

				console.log("Fail at path : ", path, " referrer : ", referrer, "err", e);
				// otherwise fail the build
				throw new Error(message);
			},
			entries: [
				'/',
				'/mws/core/sitemap',
				'/mws/wp-display/sitemap',
			],
		}
	}
};

export default config;