// 🌖🌖 Copyright Monwoo 2023 🌖🌖, improved by Miguel Monwoo, service@monwoo.com

const path = require('path');
const Encore = require('@symfony/webpack-encore');
// const FosRouting = require('fos-router/webpack/FosRouting');
// const svelteConfig = require('./svelte.config.mjs');
// import svelteConfig from './svelte.config.mjs';
const svelteConfig = import('./svelte.config.mjs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

var dotenv = require('dotenv');
const env = dotenv.config();

// TODO : doc : Not starting with : "/"
const baseHref = env.parsed?.BASE_HREF ?? "";
// starting with : "/"
const baseHrefFull = env.parsed?.BASE_HREF_FULL ?? ""; // TODO : duplication ? remove ? easy hack for now...
const baseHrefPort = env.parsed?.BASE_HREF_PORT ?? null;
Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    // https://github.com/FriendsOfSymfony/FOSJsRoutingBundle/blob/master/Resources/doc/usage.rst
    // .addPlugin(new FosRouting()) // SOUND messed up with bundle load...
    
    // .addPlugin(new MiniCssExtractPlugin({filename:'styles.css'}))
    // Additionally, if you're using multiple entrypoints, 
    // you may wish to change new MiniCssExtractPlugin('styles.css') for 
    // new MiniCssExtractPlugin('[name].css') to generate one CSS file per entrypoint.
    // .addPlugin(new MiniCssExtractPlugin({filename:'[mwsMoonManager].css'}))
    // .addPlugin(new MiniCssExtractPlugin({filename:'[app].css'}))
    // TIPS : Error: Conflict: Multiple chunks emit assets to the same file, use [name]
    // https://stackoverflow.com/questions/42148632/conflict-multiple-assets-emit-to-the-same-filename
    .addPlugin(new MiniCssExtractPlugin({filename:'[name].css'}))

    .enablePostCssLoader()

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    // TODO : add from mwsMoonManager recipe ?
    .addEntry('mwsMoonManager', '../../../packages/mws-moon-manager/assets/app.js')
    // .addEntry('mwsMoonManager', '../../../packages/mws-moon-manager/public/build/app.js')
    .addEntry('app', './assets/app.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .configureDefinePlugin(options => {
        options['process.env'] = options['process.env'] ?? {};
        options['process.env'].BASE_HREF = JSON.stringify(baseHref);
        options['process.env'].BASE_HREF_FULL = JSON.stringify(baseHrefFull);
        options['process.env'].BASE_HREF_PORT = JSON.stringify(baseHrefPort);
        // TODO : strange : why below is having trouble to work ? :
        // options['process.env'].BASE_HREF = baseHref;
        // options['process.env'].BASE_HREF_FULL = baseHrefFull;
        // options['process.env'].BASE_HREF_PORT = baseHrefPort;
    })

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    // enables Sass/SCSS support
    //.enableSassLoader()
    .enableSassLoader((options) => {
        options.additionalData = `
            $baseHref: "${baseHref}";
        `;
        // don't forget the ;
        // now, the var 'faa' can be used in scss files
    })

    // optionally enable forked type script for faster builds
    // https://www.npmjs.com/package/fork-ts-checker-webpack-plugin
    // requires that you have a tsconfig.json file that is setup correctly.
    // .enableForkedTypeScriptTypesChecking()

    .enableSvelte() // Classic svelte loader

    // CUSTOM svelte loader :
    // https://www.reddit.com/r/symfony/comments/mqu2o0/help_svelte_typescript_integration_with_symfony/
    // https://symfony.com/doc/current/frontend/encore/custom-loaders-plugins.html
    // https://www.npmjs.com/package/svelte-loader
    // .addLoader({ // TODO : Error: ParseError: The keyword 'let' is reserved (17:1)
    //     // test: /assets\/.+\.svelte$/,
    //     test: /\.(html|svelte)$/,
    //     exclude: /node_modules/,
    //     loader: 'svelte-loader',
    //     resolve: {
    //         mainFields: ['svelte', 'browser', 'module', 'main'],
    //         extensions: ['.cjs', '.mjs', '.js', '.ts', '.d.ts', '.svelte'],
    //     },
    //     // options: {
    //     //     emitCss: true,
    //     //     preprocess: sveltePreprocess({})
    //     // }
    //     options: svelteConfig,
    // })
    .addLoader({ // TODO : Error: ParseError: The keyword 'let' is reserved (17:1)
        // test: /assets\/.+\.svelte$/,
        test: /\.(ts|d.ts)$/,
        loader: 'ts-loader',
        resolve: {
            extensions: ['.ts', '.d.ts'],
        },
        // options: {
        //     emitCss: true,
        //     preprocess: sveltePreprocess({})
        // }
        // options: svelteConfig,
    })
    // .addLoader({
    //     // required to prevent errors from Svelte on Webpack 5+, omit on Webpack 4
    //     test: /node_modules\/svelte\/.*\.mjs$/,
    //     resolve: {
    //         fullySpecified: false
    //     }
    // })
    .addLoader({
        test: /\.css$/,
        use: [
            MiniCssExtractPlugin.loader,
            {
                loader: 'css-loader',
                options: {
                    url: false, // necessary if you use url('/path/to/some/asset.png|jpg|gif')
                    sourceMap: !Encore.isProduction()
                }
            }
        ]
    })
    // https://symfony.com/doc/current/frontend/encore/typescript.html
    // uncomment if you use TypeScript
    .enableTypeScriptLoader()


    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()
    ;

let config = Encore.getWebpackConfig();
// config.resolve.alias = {
//     'local': path.resolve(__dirname, './resources/src')
// };

// https://github.com/sveltejs/svelte-loader#resolveconditionnames
// https://github.com/sveltejs/svelte-loader#usage
config.resolve.conditionNames = ['svelte', 'browser', 'import'];

// https://www.reddit.com/r/symfony/comments/mqu2o0/help_svelte_typescript_integration_with_symfony/
// config.resolve.extensions = ['.mjs', '.js', '.svelte'];
// let svelte = config.module.rules.pop();
// config.module.rules.unshift(svelte);

// https://www.npmjs.com/package/svelte-loader
config.resolve.extensions = ['.mjs', '.cjs', '.js', '.ts', '.svelte'];
config.resolve.mainFields = ['svelte', 'browser', 'module', 'main'];

config.resolve.alias.svelte = path
    .resolve('node_modules', 'svelte/src/runtime');
// Svelte 3: path.resolve('node_modules', 'svelte')

module.exports = config;
