<svelte:options accessors />

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2022-2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Footer from "./Footer.svelte";
  // TIPS : <svelte:options accessors /> needed for external end-to-end test
  //        (and standalone component export....)
  import Header from "./Header.svelte";
  // import TemplateChoiceItem from '../message/TemplateChoiceItem.svelte';

  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let viewTemplate;

  // TODO : scroll top on page load to avoid auto scroll
  // when loading letting scroll at wrong place
  // in non scrollable body... ?

  // TIPS : NEED to be REGISTRED BEFORE surveyJs lib load ... ?
  // // https://svelte.dev/docs/custom-elements-api (
  // // TODO : auto load instead of injected ?)
  // // customElements.define('mws-msg-template-choice-item', TemplateChoiceItem.element);
  // // customElements.define('mws-msg-template-choice-item', () => (new TemplateChoiceItem({})).element);
  // window.customElements.define('mws-msg-template-choice-item',  TemplateChoiceItem);
</script>

<slot name="mws-body">
  <div class="flex flex-col h-screen">
    <header class="md:py-5 bg-gray-700 text-white text-center">
      <Header {locale} {viewTemplate}>
        <slot name="mws-header">
            Sticky Header and Footer with Tailwind 😎
        </slot>
      </Header>
    </header>
    <main class="flex-1 overflow-y-auto p-5">
      <slot />
    </main>
    <footer class="py-5 bg-gray-700 text-gray-300 text-center text-white">
      <Footer {copyright}>
        <slot name="mws-footer" />
      </Footer>
    </footer>
  </div>
</slot>
