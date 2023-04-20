// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Biblio :
// https://github.com/microsoft/playwright/issues/7035
// https://github.com/iamyoki/playwright-watch#readme
// https://github.com/davipon/svelte-add-vitest
// https://stackoverflow.com/questions/71182930/how-can-i-set-the-baseurl-for-expect-in-playwright
// node_modules/@playwright/test/types/test.d.ts
// https://blog.logrocket.com/using-console-colors-node-js/
// https://playwright.dev/docs/test-configuration
// https://stackoverflow.com/questions/70532792/playwright-url-config-environment-variables-for-multiple-apps-and-different-ba
// https://playwright.dev/docs/test-timeouts
// https://github.com/microsoft/playwright/issues/15757
// https://stackoverflow.com/questions/71183912/how-do-i-ignore-https-errors-for-devices-in-playwright

import { type PlaywrightTestConfig, devices } from '@playwright/test';
import chalk from 'chalk';
import 'dotenv/config';

chalk.level = 1; // Use colours in the VS Code Debug Window

if (process.env.VITE_MWS_BASE_HREF) {
	console.info(chalk.green(
		"WILL use BASE_HREF",
		process.env.VITE_MWS_BASE_HREF
	));
}
// // For MAMP server : (need .htaccess updates if no https)
// const baseURL = "http://localhost:8888";
// For HTTPS MAMP server (self signed certificates)
const baseURL = "https://localhost";

process.env.PLAYWRIGHT_TEST_BASE_URL = baseURL; // To avoid wrong test url on 443 port instead of https

const config: PlaywrightTestConfig = {
	// TIPS if you use MAMP :
	// Symlink your build folder inside your MAMP hosts, then use :
	webServer: {
		command: 'echo "Server should be configured and running under MAMP"',
		port: 443, // Will change PLAYWRIGHT_TEST_BASE_URL to http://localhost:443
		reuseExistingServer: true, // Most closest to prod env
	},
	timeout: 60000 * 5, // 5 minutes timeout for test launch
	globalTimeout: 60000 * 5, // 5 minutes timeout for server builds (this one is for all)
	expect: {
		timeout: 7000 // 7sec, Maximum waitting time per test assertions (4sec for dev loads, 3sec SEO Limit)
	},
	use: {
		...devices['chromium'],
		baseURL,
		actionTimeout: 3000, // 3sec, SEO Limit before bad user interaction...
		navigationTimeout: 7000, // 7sec, Maximum waitting time per test assertions (4sec for dev loads, 3sec SEO Limit)
		// TIPS : remove SSL security for localhost self signed certificates :
        contextOptions: {
			ignoreHTTPSErrors: true,
			
		},
        launchOptions: {
			args: [
				// '--disable-web-security',
				'--unsafely-treat-insecure-origin-as-secure=https://localhost',
				'--ignore-certificate-errors',
			],
		},  
	},
	testMatch: 'tests/functional/**/*.test.ts',
};

export default config;
