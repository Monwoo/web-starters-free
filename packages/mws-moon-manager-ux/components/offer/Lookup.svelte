<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";
  import List from "./lookup/List.svelte";
  import { onMount } from "svelte";

  // export let users:any[] = []; // TODO : not Typescript ?
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let lookup;
  export let offers = [];
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

  const jsonResult = JSON.parse(decodeURIComponent(lookup.jsonResult));
  console.debug('jsonResult :', jsonResult);
  // TODO : basehref ? => NOP, use Routing from fos-routing instead...
  const baseHref = '/mws';
  const respUrl = `${baseHref}/${locale}/mws-offer/fetch-root-url?url=`
  + encodeURIComponent(jsonResult.sourceRootLookupUrl);

  onMount(async () => {
    // // NOP, not common, will have security errors this way :
    // const htmlResp = await fetch(respUrl);
    // console.debug(htmlResp);
    // // const win = window.open(jsonResult.sourceRootLookupUrl, "Title", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));
    // const win = window.open('', "Title", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));
    // // win.document.body.innerHTML = "HTML";
    // win.document.body.innerHTML = await htmlResp.text();
    const $ = window.$;
    // TIPS opti : use svelte html node ref and pass to jquery ?
    const htmlLookup = $(lookupForm); 
    // console.log(htmlLookup);
    const lookupSurveyJsFormData = Object.fromEntries((new FormData(htmlLookup[0])).entries());
    const lookupSurveyJsData = JSON.parse(decodeURIComponent(lookupSurveyJsFormData['mws_survey_js[jsonResult]'])); // TODO : from param or config
    // TIPS : same as jsonResult, updated by survey js or other if using ref element instead of raw string... :
    console.log('lookupSurveyJsData : ', lookupSurveyJsData);
  });

  console.log(Routing.generate('mws_offer_import'));
</script>

<Base {copyright} {locale} {viewTemplate}>
  <div>
    <a href={ Routing.generate('mws_offer_import', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
    }) }>
      <button class="btn btn-outline-primary p-1">Importer des offres.</button>
    </a>    
  </div>
  <div class="flex flex-wrap">
    <div class="label">
      Recherche d'une offre via :
    </div>
    <div class="detail w-full">
      {@html lookupForm}
    </div>
  </div>
  {@html jsonResult.customFilters
    ? '<strong>Filtres actifs : </strong>' +
      jsonResult.customFilters.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonResult.searchTags
    ? '<strong>Tags : </strong>' +
      jsonResult.searchTags.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonResult.searchKeyword
    ? `<strong>Mots clefs : </strong>${jsonResult.searchKeyword}`
    : ``
  }
  {@html offersPaginator}

  <!-- { JSON.stringify(offers) } -->
  <div class="overflow-y-auto">
    <List {locale} {offers} {offersHeaders} {viewTemplate} {addMessageForm}></List>
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