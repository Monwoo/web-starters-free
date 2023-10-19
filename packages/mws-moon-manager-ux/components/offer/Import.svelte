<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";

  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let viewTemplate;
  export let reportSummary = '';
  export let format;
  export let uploadForm;

  console.debug(locale);
</script>

<Base {copyright} {locale} {viewTemplate}>
  <div>
    <a href="{ Routing.generate('mws_offer_lookup', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
    }) }">
      <button class="btn btn-outline-primary p-1">Liste des offres.</button>
    </a>    
  </div>
  <div class="flex flex-wrap">
    <div class="label">
      Importer des offres via {format} :
    </div>
    <div class="detail">
      {@html uploadForm}
    </div>
    <div>
      {reportSummary}
    </div>
  </div>
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