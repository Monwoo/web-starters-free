<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";
  // import List from "./lookup/List.svelte";
  import { onMount } from "svelte";

  export let locale;
  export let lookup;
  export let timings = [];
  export let timingsPaginator;
  export let timingsHeaders = {}; // injected raw html
  export let viewTemplate;
  export let lookupForm;

  console.debug(lookupForm);

  const jsonResult = JSON.parse(decodeURIComponent(lookup.jsonResult));
  console.debug('jsonResult :', jsonResult);

  onMount(async () => {
    const $ = window.$;
    // // TIPS opti : use svelte html node ref and pass to jquery ?
    // const htmlLookup = $(lookupForm); 
    // // console.log(htmlLookup);
    // const lookupSurveyJsFormData = Object.fromEntries((new FormData(htmlLookup[0])).entries());
    // const lookupSurveyJsData = JSON.parse(decodeURIComponent(lookupSurveyJsFormData['mws_survey_js[jsonResult]'])); // TODO : from param or config
    // // TIPS : same as jsonResult, updated by survey js or other if using ref element instead of raw string... :
    // console.log('lookupSurveyJsData : ', lookupSurveyJsData);
  });
</script>
<div class="mws-timing-qualif">
  <div class="p-3 flex flex-wrap">
    <a href={ Routing.generate('mws_offer_import', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
    }) }>
      <button class="btn btn-outline-primary p-1">Exporter des timings.</button>
    </a>    
    <a href={ Routing.generate('mws_offer_import', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
    }) }>
      <button class="btn btn-outline-primary p-1">Importer des timings.</button>
    </a>    
  </div>
  <div class="p-3 flex flex-wrap">
    <div class="label">
      <button
      data-collapse-toggle="search-timing-lookup"
      type="button"
      class="rounded-lg "
      aria-controls="search-timing-lookup"
      aria-expanded="false"
    >
      Filtres de recherche
    </div>
    <div id="search-timing-lookup" class="detail w-full hidden">
      {@html lookupForm}
    </div>
  </div>
  {@html jsonResult.searchTags && jsonResult.searchTags.length
    ? '<strong>Tags : </strong>' +
      jsonResult.searchTags.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonResult.searchTagsToAvoid && jsonResult.searchTagsToAvoid.length
    ? '<strong>Tags à éviter : </strong>' +
      jsonResult.searchTagsToAvoid.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonResult.searchKeyword
    ? `<strong>Mots clefs : </strong>${jsonResult.searchKeyword}`
    : ``
  }
  {@html timingsPaginator}

  <!-- { JSON.stringify(timings) } -->
  <div class="mws-timing-qualif-board overflow-y-auto">
    TODO : 28 * 16
  </div>
  <div>{@html timingsPaginator}</div>
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