<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";
  import Header from "../layout/Header.svelte";
  // import List from "./lookup/List.svelte";
  import { onMount } from "svelte";
  import SquareList from "./SquareList.svelte";
  import SlotView from "./SlotView.svelte";
  import { state, stateGet } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";

  export let locale;
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let lookup;
  export let timings = [];
  export let timeQualifs = [];
  export let timingsPaginator;
  export let timingsHeaders = {}; // injected raw html
  export let viewTemplate;
  export let lookupForm;
  const urlParams = new URLSearchParams(window.location.search);
  export let lastSelectedIndex = parseInt(urlParams.get("lastSelectedIndex") ?? "0");
  const pageNumber = urlParams.get("page") ?? "1";

  const movePageIndex = (delta) => {
    const newPageNum  = parseInt(pageNumber) + delta;
    // TODO : how to know max page num ? data.length / pageLimit, need to know details...
    urlParams.set("page",  newPageNum < 1 ? 1 : newPageNum);
    window.location.search = urlParams;
  };

  console.debug(lookupForm);
  $: {
    // https://stackoverflow.com/questions/1090948/change-url-parameters-and-specify-defaults-using-javascript
    // window.location.search = jQuery.query.set("lastSelectedIndex", lastSelectedIndex);    
    if (lastSelectedIndex != parseInt(urlParams.get("lastSelectedIndex") ?? "0")) {
      urlParams.set("lastSelectedIndex", lastSelectedIndex);
      // window.location.search = urlParams; // Force page reload
      // https://stackoverflow.com/questions/824349/how-do-i-modify-the-url-without-reloading-the-page
      const newUrl = window.location.origin + window.location.pathname + "?" + urlParams;
      history.pushState({}, null, newUrl);
    }
  }
  
  const jsonResult = JSON.parse(decodeURIComponent(lookup.jsonResult));
  console.debug("jsonResult :", jsonResult);
  
  let csrfTimingDelete = stateGet(get(state), 'csrfTimingDeleteAll');

  const moveSelectedIndex = (delta = 1) => {
    const lastValue = lastSelectedIndex;
    lastSelectedIndex += delta;
    if (lastSelectedIndex >= timings.length) {
      lastSelectedIndex = timings.length - 1;
    } else if (lastSelectedIndex < 0) {
      lastSelectedIndex = 0;
    }
    return {
      didChange: lastValue != lastSelectedIndex,
      isFirst: lastSelectedIndex == 0,
      isLast: lastSelectedIndex == timings.length - 1,
    };
  }

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

<Base {copyright} {locale} {viewTemplate} mainClass="" footerClass="py-2">
  <div slot="mws-header-container"></div>
  <div class="mws-timing-qualif">
    <div class="mws-menu-wrapper">
      <div class="flex">
        <button
          data-collapse-toggle="menu-timing"
          type="button"
          class="rounded-lg "
          aria-controls="search-timing-lookup"
          aria-expanded="false"
        >
          Menu
        </button>
        <div class="summary">
          {@html jsonResult.searchTags && jsonResult.searchTags.length
            ? "<strong>Tags : </strong>" +
              jsonResult.searchTags.reduce(
                (acc, f) => `
                ${acc} [${f}]
              `,
                ``
              ) +
              "<br/>"
            : ""}
          {@html jsonResult.searchTagsToAvoid &&
          jsonResult.searchTagsToAvoid.length
            ? "<strong>Tags à éviter : </strong>" +
              jsonResult.searchTagsToAvoid.reduce(
                (acc, f) => `
                ${acc} [${f}]
              `,
                ``
              ) +
              "<br/>"
            : ""}
          {@html jsonResult.searchKeyword
            ? `<strong>Mots clefs : </strong>${jsonResult.searchKeyword}`
            : ``}
          {@html timingsPaginator}
        </div>
        <!-- <div>
          <select>
            <option>Auto qualif type 1</option>
          </select>
        </div> -->
      </div>
      <div id="menu-timing" class="detail w-full hidden">
        <header class="bg-gray-700 text-white text-center">
          <Header />
        </header>
        <div class="p-3 flex flex-wrap">
          <a
            href={Routing.generate("mws_offer_import", {
              _locale: locale ?? "",
              viewTemplate: viewTemplate ?? "",
            })}
          >
            <button class="btn btn-outline-primary p-1"
              >Exporter des timings.</button
            >
          </a>
          <a
            href={Routing.generate("mws_offer_import", {
              _locale: locale ?? "",
              viewTemplate: viewTemplate ?? "",
            })}
          >
            <button class="btn btn-outline-primary p-1"
              >Importer des timings.</button
            >
          </a>
          <form action="{ Routing.generate('mws_timing_delete_all', {
            '_locale': locale ?? '',
            'viewTemplate': viewTemplate ?? '',
          }) }"
          method="post"
          onsubmit="return confirm('Êtes vous sur de vouloir supprimer définitivement tous les suivi des temps ?');"
          >
            <input type="hidden" name="_csrf_token" value="{ csrfTimingDelete }" />
            <button 
            class="btn btn-outline-primary p-1 m-2"
            type="submit">Supprimer les timings</button>
          </form>
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
            </button>
          </div>
          <div id="search-timing-lookup" class="detail w-full hidden">
            {@html lookupForm}
          </div>
        </div>
      </div>
    </div>

    <button
      class="float-right m-1"
      style:opacity={lastSelectedIndex < timings.length - 1 ? 1 : 0.7}
      on:click={() => moveSelectedIndex(1)}
    >
      Next.
    </button>

    <button
      class="float-right m-1"
      style:opacity={lastSelectedIndex > 0 ? 1 : 0.7}
      on:click={() => moveSelectedIndex(-1)}
    >
      Prev.
    </button>

    <button
      class="float-right m-1"
      on:click|stopPropagation={() => movePageIndex(1)}
    >
      Next. Page
    </button>
    {#if pageNumber > 1}
      <button
        class="float-right m-1"
        on:click|stopPropagation={() => movePageIndex(-1)}
      >
      Prev. Page
      </button>
    {/if}
    <span class="float-right m-1 text-black">
      [{pageNumber}-{lastSelectedIndex}]
    </span>

    <div class="flex flex-col h-[90vh] w-[100vw] md:flex-row">
      <!-- { JSON.stringify(timings) } -->
      <SlotView
        bind:lastSelectedIndex
        {moveSelectedIndex} {timeQualifs} {locale}
        timingSlot={timings[lastSelectedIndex] ?? null}
        class="h-[50%] w-[100%] md:w-[50%] md:h-[100%]"
      />
      <SquareList
        bind:lastSelectedIndex
        {timings}
        class="h-[50%] w-[100%] md:w-[50%] md:h-[100%]"
      />
    </div>
    <div>{@html timingsPaginator}</div>
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
