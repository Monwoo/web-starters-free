// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// import adapter from '@sveltejs/adapter-static';
import preprocess from 'svelte-preprocess';
import 'dotenv/config';

// let baseHref = process.env.VITE_MWS_BASE_HREF || '';
// console.warn("CONFIGURE svelte.config.js for : ", process.env.NODE_ENV);

// if (process.env.NODE_ENV === 'development' && process.env.VITE_MWS_BASE_HREF) {
// 	console.warn("DISABELING VITE_MWS_BASE_HREF for developpement, to avoid buggy behavior");
// 	// Use scss preprocess:preprocess{scss:{includePaths:[...]}}
// 	// is not enough to avoid scss fails when using kit.path.base...
// 	// SO WE AVOID BASE_HREF for DEV ENV :
// 	baseHref = '';
// }

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
	// kit: {
	// 	adapter: adapter({
	// 		pages: 'build',
	// 		assets: 'build',
	// 		// precompress: true,
	// 		fallback: null
	// 	}),
	// 	paths: {
	// 		base: baseHref,
	// 	},
	// 	alias: {
	// 		'@app': 'src',
	// 		'@app-wp-display-i18n-routes': 'src/routes/(wp-display)/[[i18n=i18n]]',
	// 	},
	// 	files: {
	// 		serviceWorker: 'src/service-worker',
	// 	},
	// 	// If true, SvelteKit will add its event listeners related to navigation etc
	// 	// on the parent of %sveltekit.body% instead of window, 
	// 	// and will pass params from the server
	// 	// rather than inferring them from location.pathname.
	// 	embedded: false,
	// 	prerender: {
	// 		handleHttpError: (e) => {
	// 			const {
	// 				path,
	// 				referrer,
	// 				message
	// 			} = e;
	// 			// ignore deliberate link to shiny 404 page
	// 			if (path === '/not-found' && referrer === '/blog/how-we-built-our-404-page') {
	// 				return;
	// 			}

	// 			console.log("Fail at path : ", path, " referrer : ", referrer, "err", e);
	// 			// otherwise fail the build
	// 			throw new Error(message);
	// 		},
	// 		entries: [
	// 			'/',
	// 			'/mws/core/sitemap',
	// 			'/mws/wp-display/sitemap',
	// 		],
	// 	}
	// }
};

export default config;