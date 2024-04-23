<svelte:options accessors />

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2022-2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Footer from "./Footer.svelte";
  // TIPS : <svelte:options accessors /> needed for external end-to-end test
  //        (and standalone component export....)
  import Header from "./Header.svelte";
  // import TemplateChoiceItem from '../message/TemplateChoiceItem.svelte';
  import debounce from 'lodash/debounce';
import { onMount } from "svelte";

  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let headerClass = ""; // "md:py-5";
  export let mainClass = "p-5";
  export let footerClass = "py-2 md:py-3 wide:py-0";
   
  export let locale;
  export let viewTemplate;

  export let userDelay = 300;
  const isMobileRule = "(max-width: 768px) and (min-height: 480px)";
  export let isMobile = window.matchMedia(isMobileRule)?.matches;
  const isWideRule = "only screen and (max-height: 480px) and (max-width: 960px)";
  export let isWide = window.matchMedia(isWideRule)?.matches;

  // const onResize = async (e) => {
  const onResize = (e) => {
    // isMobile = window.matchMedia("(max-width: 768px)")?.matches;
    // isMobile = window.matchMedia("(max-width: 768px) and (orientation: landscape)")?.matches;
    // isMobile = window.matchMedia("((max-width: 768px) and (min-height: 480px))"
    // + " and not (only screen and (max-height: 480px) and (max-width: 960px))")?.matches;
    try {
          isMobile = window.matchMedia(isMobileRule)?.matches;
        } catch (e) {
          console.warn(e);
        }
        try {
          // TODO : matchMedia throw like die without error
          isWide = window.matchMedia(isWideRule)?.matches;
        } catch (e) {
          console.warn(e);
        }
        console.log('[MoonManager UX] resize detected :', {
          isMobile, isWide
        });
    // TODO : Logger.debug => send log to remote logger to test on multi devices...
  }

  // TODO : scroll top on page load to avoid auto scroll
  // when loading letting scroll at wrong place
  // in non scrollable body... ?

  // TIPS : NEED to be REGISTRED BEFORE surveyJs lib load ... ?
  // // https://svelte.dev/docs/custom-elements-api (
  // // TODO : auto load instead of injected ?)
  // // customElements.define('mws-msg-template-choice-item', TemplateChoiceItem.element);
  // // customElements.define('mws-msg-template-choice-item', () => (new TemplateChoiceItem({})).element);
  // window.customElements.define('mws-msg-template-choice-item',  TemplateChoiceItem);

  onMount(() => {
    // https://stackoverflow.com/questions/5498934/detect-change-in-orientation-using-javascript
    // NOTE: orientationChange is deprecated
    // on:orientationchange={debounce(onResize, userDelay)}
    // Instead use screen.orientation using the screenOrientation interface
    // which is triggered by the screenorientation.onchange event

    const contentLoadListener = () => {
      console.log('Mobile resize');
      // debounce(() => console.log('Ok did resize'), userDelay)();
      debounce(async () => {
        console.log('Ok, debounce resize');
        try {
          isMobile = window.matchMedia(isMobileRule)?.matches;
        } catch (e) {
          console.warn(e);
        }
        try {
          // TODO : matchMedia throw like die without error
          isWide = window.matchMedia(isWideRule)?.matches;
        } catch (e) {
          console.warn(e);
        }
        console.log('[MoonManager UX] Hacky resize detected :', {
          isMobile, isWide
        });
      }, userDelay)();

      // debounce(onResize, userDelay)();
      // debounce(() => onResize(null), userDelay)();
    };
    // const contentLoadListener = () => console.log('Ok will resize');
    // window.addEventListener("DOMContentLoaded", contentLoadListener);
    // TIPS : Svelte 'on:orientationchange' not always triggering..., use :
    // (on mobile rotate, below OK) :

    // window.addEventListener("orientationchange", contentLoadListener);

    window.addEventListener("DOMContentLoaded", onResize);
    window.addEventListener("orientationchange", onResize);
    return () => {
      window.removeEventListener("DOMContentLoaded", onResize);
      window.removeEventListener("orientationchange", onResize);
    };
  });
</script>
<!-- 
  on:orientationchangeend={debounce(onResize, userDelay)}
  on:resize={debounce(onResize, userDelay)}

  // TODO : buggy svelte ?
    on:orientationchange={debounce(onResize, userDelay)} not working...

-->

<svelte:window
  on:resize={debounce(onResize, userDelay)}
  on:orientationchange={debounce(onResize, userDelay)}
/>

<slot name="mws-body">
  <!-- // TODO : on mobile 'rotation', sound like  "wide:h-[100dvw]" 
  will ajust the fixed bottom component, 
  but will messup if refresh as wide screen.... -->

  <!-- // TIPS : add big magin bottom in wide screen
  to allow scroll down to hide the url navigation bar ?
    => not enough, desactivate sticky bottom instead,
    scrolling MAIN BODY only sound ok 
    to remove mobile nav on scroll down...

    so even for mobile landscape, do not keep sticky bottom footer...

    add pb-21 for mobile screen forseen h100%, to get enough space to trigger scroll down...

  flex flex-col
    h-max md:h-[100dvh] print:h-auto wide:h-auto">
  -->

  <div class="
  flex flex-col justify-between items-stretch
  min-h-[100dvh] md:h-[100dvh] print:h-auto wide:h-auto">
    <slot name="mws-header-container">
      <header class="rounded-b-lg bg-gray-700 text-white text-center {headerClass}">
        <Header {locale} {viewTemplate}>
          <slot name="mws-header">
              <!-- Sticky Header and Footer with Tailwind 😎 -->
          </slot>
        </Header>
      </header>
    </slot>
    <main class="flex-1 md:overflow-y-auto header {mainClass}">
      <slot />
    </main>
    <slot name="mws-footer-container">
      <footer class="bg-gray-700 text-gray-300
        text-center text-white
        text-xs md:text-base
        {footerClass}">
        <Footer {copyright}>
          <slot name="mws-footer" />
        </Footer>
      </footer>
    </slot>
  </div>
</slot>
