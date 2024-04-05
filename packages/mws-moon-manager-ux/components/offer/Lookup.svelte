<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";
  import List from "./lookup/List.svelte";
  import { onMount } from "svelte";

  // export let users:any[] = []; // TODO : not Typescript ?
  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let locale;
  export let lookup;
  export let offers = [];
  export let messagesByProjectId = {};
  export let offersPaginator;
  export let offersHeaders = {}; // injected raw html
  export let viewTemplate;
  export let lookupForm;
  export let addMessageForm = '';

  console.debug(locale);
  console.debug(lookup);
  console.debug(offers);
  console.debug(offersPaginator);
  console.debug(viewTemplate);
  console.debug(lookupForm);

  const jsonLookup = JSON.parse(decodeURIComponent(lookup.jsonResult));
  console.debug('jsonLookup :', jsonLookup);
  // TODO : basehref ? => NOP, use Routing from fos-routing instead...
  const baseHref = '/mws';
  // const respUrl = `${baseHref}/${locale}/mws-offer/fetch-root-url?url=`
  // + encodeURIComponent(jsonLookup.sourceRootLookupUrl);

  onMount(async () => {
    // // NOP, not common, will have security errors this way :
    // const htmlResp = await fetch(respUrl);
    // console.debug(htmlResp);
    // // const win = window.open(jsonLookup.sourceRootLookupUrl, "Title", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));
    // const win = window.open('', "Title", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));
    // // win.document.body.innerHTML = "HTML";
    // win.document.body.innerHTML = await htmlResp.text();
    const $ = window.$;
    // TIPS opti : use svelte html node ref and pass to jquery ?
    const htmlLookup = $(lookupForm); 
    // console.log(htmlLookup);
    const lookupSurveyJsFormData = Object.fromEntries((new FormData(htmlLookup[0])).entries());
    const lookupSurveyJsData = JSON.parse(decodeURIComponent(lookupSurveyJsFormData['mws_survey_js[jsonLookup]'] ?? '{}')); // TODO : from param or config
    // TIPS : same as jsonLookup, updated by survey js or other if using ref element instead of raw string... :
    console.log('lookupSurveyJsData : ', lookupSurveyJsData);
  });

  console.log(Routing.generate('mws_offer_import'));
</script>

<Base {copyright} {locale} {viewTemplate}>
  <div class="p-3 flex flex-wrap">
    <a href={ Routing.generate('mws_offer_import', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
    }) }>
      <button class="btn btn-outline-primary p-1">Importer des offres.</button>
    </a>    
  </div>
  <div class="p-3 flex flex-wrap">
    <div class="label">
      <button
      data-collapse-toggle="search-offer-lookup"
      type="button"
      class="rounded-lg "
      aria-controls="search-offer-lookup"
      aria-expanded="false"
    >
      Filtres de recherche
    </div>
    <div id="search-offer-lookup" class="detail w-full hidden z-50">
      {@html lookupForm}
    </div>
  </div>
  {@html jsonLookup.customFilters && jsonLookup.customFilters.length
    ? '<strong>Filtres actifs : </strong>' +
      jsonLookup.customFilters.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonLookup.searchTags && jsonLookup.searchTags.length
    ? '<strong>Tags : </strong>' +
      jsonLookup.searchTags.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonLookup.searchTagsToAvoid && jsonLookup.searchTagsToAvoid.length
    ? '<strong>Tags à éviter : </strong>' +
      jsonLookup.searchTagsToAvoid.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonLookup.searchKeyword
    ? `<strong>Mots clefs : </strong>${jsonLookup.searchKeyword}`
    : ``
  }
  {@html offersPaginator}

  <!-- { JSON.stringify(offers) } -->
  <div class="mws-offer-lookup">
    <List {locale} {offers} {offersHeaders} {viewTemplate}
    {addMessageForm} {messagesByProjectId}></List>
  </div>
  <div>{@html offersPaginator}</div>
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