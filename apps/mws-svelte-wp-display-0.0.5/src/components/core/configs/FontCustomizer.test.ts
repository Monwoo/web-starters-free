// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Biblio
// https://vitest.dev/guide/features.html (When running concurrent tests, Snapshots and Assertions must use expect from the local Test Context)
// https://vitest.dev/guide/test-context.html#test-context
// https://vitest.dev/guide/snapshot.html (When using Snapshots with async concurrent tests, expect from the local Test Context must be used to ensure the right test is detected)
// https://svelte.dev/tutorial/onmount
// node_modules/@testing-library/svelte/dist/pure.js
// node_modules/svelte/internal/index.mjs:1947
import { render, type RenderResult } from "@testing-library/svelte";
import { tick } from 'svelte';
import logger from '@app/services/logger';
import { prettyDOM } from '@testing-library/dom';
import FontCustomizer from "@app/components/core/configs/FontCustomizer.svelte";
import { it } from 'vitest'
import { locale, t } from "svelte-i18n";
import { get } from "svelte/store";
import * as svelteModule from 'svelte';
import * as appConfigWpDisplayModule from "@app/stores/app-config.wp-display";
import { mwsCssVars } from "@app/stores/app-config.wp-display";
import { theme } from "@app/stores/app-config";

const testedCssVars = {
    light: {
        '--mws-font-family': 'Test-font-for-light',
    },
    dark: {
        '--mws-font-family': 'Test-font-for-dark',
    },
};

describe(`[${FontCustomizer.name}]`, () => {
    it("Do renderings", async ({ expect }) => {
        logger.logLevel = 2;

        const htmlContainer = document.createElement("div");

        let resp: RenderResult<FontCustomizer> = render(FontCustomizer, {
            props: {
                class: "my-custom-test-css-classe",
            }, 
        },{
            container: htmlContainer,
        });
        logger.log("#A-01 Component :", resp.component);
        await tick(); // Wait for UI refresh

        logger.log("#A-01", prettyDOM(resp.container));
        expect(resp.container.outerHTML).toContain("my-custom-test-css-classe");

        // TIPS : studdy internal to have some best fist mounting system :
        // const stubComponent = { $$: { on_mount: [] }};
        // set_current_component(stubComponent);
        // resp.rerender({
        //     class: "my-other-custom-test-css-classe",
        // });
        // logger.log("#A-02 Component :", resp.component);
        // logger.log("#A-02 Stub Component :", stubComponent);
        // mount_component(resp.component, htmlContainer, null, null);
        // await tick(); // Wait for UI refresh

        // Simply Mock onMount for now :
        let onMounts: any[] = [];
        const onMountSpy = vi.spyOn(svelteModule, 'onMount').mockImplementation((h:any) => {
            onMounts.push(h);
        });
        onMountSpy.mockClear();
        resp.rerender({
            class: "my-other-custom-test-css-classe",
        });
        expect(onMountSpy).toBeCalled();
        // Setup our css vars and theme for onMount test
        mwsCssVars.set(testedCssVars);
        theme.set('light');
        
        logger.log("#A-02 onMounts", onMounts);
        // wait for our component onMount callback (the first one, next are childs onMounts)
        // Warning : onMounts may return a function to be called at onDestory step
        //          => we do not call it here since we KNOW our function returns void..
        const logSpy = vi.spyOn(console, 'log');
        await onMounts[0]();
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy.mock.calls[2][0]).toContain('[FontCustomizer] Will mount');
        expect(logSpy.mock.calls[5][0]).toBe(
            '%c[FontCustomizer] Did select font from theme light: Test-font-for-light '
        );
        logger.log("#A-02", prettyDOM(resp.container));
        expect(resp.container.outerHTML).not.toContain("my-custom-test-css-classe");
        expect(resp.container.outerHTML).toContain("my-other-custom-test-css-classe");
        expect(resp.container.outerHTML).toContain("Test-font-for-light");

        onMountSpy.mockClear();
        theme.set('unknow-theme');
        mwsCssVars.set({}); // Subscribed from previous onMount call

        onMounts = [];
        resp.rerender({
            class: "my-3rd-custom-test-css-classe",
        });
        expect(onMountSpy).toBeCalled();

        logSpy.mockClear();
        await onMounts[0]();
        // Wait for stores propagations :
        await new Promise(r => setTimeout(r, 500));

        expect(logSpy).toHaveBeenCalled();
        expect(logSpy.mock.calls[2][0]).toContain('[FontCustomizer] Will mount');
        expect(logSpy.mock.calls.slice(-1)[0][0]).toBe(
            '%c[FontCustomizer] Did select font from theme unknow-theme: Shantell Sans '
        );

        logger.log("#A-03", prettyDOM(resp.container));
        expect(resp.container.outerHTML).not.toContain("my-custom-test-css-classe");
        expect(resp.container.outerHTML).not.toContain("my-other-custom-test-css-classe");
        expect(resp.container.outerHTML).toContain("my-3rd-custom-test-css-classe");
        expect(resp.container.outerHTML).not.toContain("Test-font-for-light");
        expect(resp.container.outerHTML).toContain("Shantell Sans");

        const getCssSpy = vi.spyOn(appConfigWpDisplayModule, 'getCssProperty')
        .mockReturnValue(Promise.resolve(undefined));
        theme.set('unknow-theme-2');
        mwsCssVars.set({ 'light' : {}}); // Subscribed from previous onMount call
        // Wait for stores propagations :
        await new Promise(r => setTimeout(r, 500));

        expect(logSpy.mock.calls.slice(-1)[0][0]).toBe(
            '%c[FontCustomizer] Did select font from theme unknow-theme-2: Shantell Sans '
        );
        expect(resp.container.outerHTML).toContain("Shantell Sans");
    });

    it('Do I18n switchs', async () => {
        let resp: RenderResult<FontCustomizer> = render(FontCustomizer);
        await locale.set('en');
        let trans = get(t)('mws.core.configs.fontCustomizer.label', { locale: 'en' });
        expect(resp.container.textContent).toContain(trans);

        await locale.set('fr');
        trans = get(t)('mws.core.configs.fontCustomizer.label', { locale: 'fr' });
        expect(resp.container.textContent).toContain(trans);

        // Safe to use $set here, sound messed up if used resp.rerender before usage of $set...
        resp.component.$set({
            class: "my-4th-custom-test-css-classe",
        });
        await tick(); // Wait for UI refresh
        await new Promise(r => setTimeout(r, 500));
        expect(resp.container.outerHTML).toContain("my-4th-custom-test-css-classe");
    });
});
