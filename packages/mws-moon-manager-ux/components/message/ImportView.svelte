<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";

  export let viewTemplate;
  export let locale;
  export let format;
  export let reportSummary = '';
  export let uploadForm;

  console.debug(locale);
</script>

<div>
  <div>
    <a href="{ Routing.generate('mws_message_list', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
    }) }">
      <button class="btn btn-outline-primary p-1">Liste des messages.</button>
    </a>    
  </div>
  <div class="flex flex-wrap">
    <div class="label">
      Importer des messages via {format} :
    </div>
    <div class="detail">
      {@html uploadForm}
    </div>
    <div>
      {@html reportSummary}
    </div>
  </div>
</div>

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