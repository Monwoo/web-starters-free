<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";

  // export let users:any[] = []; // TODO : not Typescript ?
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let lookup;
  export let offers;
  export let offersPaginator;
  export let viewTemplate;
  export let lookupForm;

  console.debug(locale);
  console.debug(lookup);
  console.debug(offers);
  console.debug(offersPaginator);
  console.debug(viewTemplate);
  console.debug(lookupForm);
</script>

<Base {copyright} {locale} {viewTemplate}>
  <div class="flex flex-wrap">
    <div class="label">
      Recherche d'une offre via :
    </div>
    <div class="detail">
      {@html lookupForm}
    </div>
  </div>
  {@html offersPaginator}

  { JSON.stringify(offers) }
</Base>

<!-- <style lang="scss">
  // TODO : post CSS syntax allowed in svelte scss ?
  // Done in packages/mws-moon-manager/assets/styles/app.scss
  // .label {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
  // .detail {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
</style> -->