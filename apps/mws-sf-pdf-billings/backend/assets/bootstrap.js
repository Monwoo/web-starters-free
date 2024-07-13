import { startStimulusApp } from '@symfony/stimulus-bridge';
// // Registers Stimulus controllers from controllers.json and in the controllers/ directory
// export const app = window.app ?? startStimulusApp(require.context(
export const app = startStimulusApp(require.context(
        '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
// // register any custom, 3rd party controllers here
// // app.register('some_controller_name', SomeImportedController);
window.app = app;
// TODO : config vite to use typescript and scss
// import { startStimulusApp, registerControllers } from "vite-plugin-symfony/stimulus/helpers" 
// const app = startStimulusApp();
// registerControllers(app, import.meta.glob('./controllers/*_(lazy)\?controller.[jt]s(x)\?'))