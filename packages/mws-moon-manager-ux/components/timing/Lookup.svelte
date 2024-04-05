<!-- <script context="module" ✂prettier:content✂="CiAgLy8gaHR0cHM6Ly93d3cubnBtanMuY29tL3BhY2thZ2Uvc3ZlbHRlLXRpbWU/YWN0aXZlVGFiPXJlYWRtZSNjdXN0b20tbG9jYWxlCiAgLy8gaW1wb3J0ICJkYXlqcy9lc20vbG9jYWxlL2ZyIjsKICAvLyBpbXBvcnQgZGF5anMgZnJvbSAiZGF5anMvZXNtIjsKICBpbXBvcnQgImRheWpzL2xvY2FsZS9mciI7CiAgLy8gaW1wb3J0ICJkYXlqcy9sb2NhbGUvZW4iOwogIGRheWpzLmxvY2FsZSgiZnIiKTsgLy8gRnIgbG9jYWxlIC8vIFRPRE8gOiBnbG9iYWwgY29uZmlnIGluc3RlYWQgb2YgcGVyIG1vZHVsZSA/CiAgdmFyIHV0YyA9IHJlcXVpcmUoJ2RheWpzL3BsdWdpbi91dGMnKQogIHZhciB0aW1lem9uZSA9IHJlcXVpcmUoJ2RheWpzL3BsdWdpbi90aW1lem9uZScpIC8vIGRlcGVuZGVudCBvbiB1dGMgcGx1Z2luCiAgZGF5anMuZXh0ZW5kKHV0Yyk7CiAgZGF5anMuZXh0ZW5kKHRpbWV6b25lKTsgLy8gVE9ETyA6IHVzZXIgY29uZmlnIGZvciBzZWxmIHRpbWV6b25lLi4uIChzbG90IGlzIGNvbXB1dGVkIG9uIFVUQyBkYXRlLi4uKQogIC8vIGRheWpzLnR6LnNldERlZmF1bHQoIkV1cm9wZS9QYXJpcyIpOwogIC8vIGh0dHBzOi8vd3d3LnRpbWVhbmRkYXRlLmNvbS90aW1lL21hcC8jIWNpdGllcz0xMzYKICAvLyBodHRwczovL3d3dy50aW1lYW5kZGF0ZS5jb20vd29ybGRjbG9jay91ay9sb25kb24KICAvLyBodHRwczovL2VuLndpa2lwZWRpYS5vcmcvd2lraS9MaXN0X29mX3R6X2RhdGFiYXNlX3RpbWVfem9uZXMjTE9ORE9OCiAgZGF5anMudHouc2V0RGVmYXVsdCgiRXVyb3BlL0xvbmRvbiIpOwo=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=">{}</script> -->
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
  import ConfidentialityStamp from "./ConfidentialityStamp.svelte";
  import { fade } from "svelte/transition";
  import { slide } from "svelte/transition";
  import { quintOut } from "svelte/easing";
  import { fly } from "svelte/transition";
  import { create_in_transition, create_out_transition } from "svelte/internal";
  import { Collapse } from "flowbite";
  import debounce from "lodash/debounce";
  import dayjs from "dayjs";

  export let locale;
  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let lookup;
  export let timings = [];
  export let timeQualifs = [];
  export let quickQualifTemplates;
  export let timingsPaginator;
  export let timingsHeaders = {}; // injected raw html
  export let viewTemplate;
  export let lookupForm;
  export let isFullScreen = false;
  export let splitRange = 50;
  export let thumbSize;

  export let selectionStartIndex;
  const urlParams = new URLSearchParams(window.location.search);
  export let lastSelectedIndex = parseInt(
    urlParams.get("lastSelectedIndex") ?? "0"
  );
  const pageNumber = urlParams.get("page") ?? "1";
  // https://stackoverflow.com/questions/59062025/is-there-a-way-to-perform-svelte-transition-without-a-if-block
  // every {} is unique, {} === {} evaluates to false
  let uniqueKey = {};

  let isMobile;

  const movePageIndex = (delta) => {
    const newPageNum = parseInt(pageNumber) + delta;
    // TODO : how to know max page num ? data.length / pageLimit, need to know details...
    urlParams.set("page", newPageNum < 1 ? 1 : newPageNum);
    // // TIPS : or redux sync or component props ?
    // const urlParams = new URLSearchParams(window.location.search);
    // const lastSelectedIndex = urlParams.get("lastSelectedIndex") ?? "1";
    // lastSelectedIndex = 123; // TODO : from CRM configs ...
    const newSelectedIndex =
      delta < 0 ? 123 : 0 === delta ? lastSelectedIndex : 0;
    urlParams.set("lastSelectedIndex", "" + newSelectedIndex);
    window.location.search = urlParams;
  };
  // dayjs.locale("fr"); // Fr locale // TODO : global config instead of per module ?

  console.debug(lookupForm);
  $: {
    // https://stackoverflow.com/questions/1090948/change-url-parameters-and-specify-defaults-using-javascript
    // window.location.search = jQuery.query.set("lastSelectedIndex", lastSelectedIndex);
    if (
      lastSelectedIndex != parseInt(urlParams.get("lastSelectedIndex") ?? "0")
    ) {
      urlParams.set("lastSelectedIndex", lastSelectedIndex);
      // window.location.search = urlParams; // Force page reload
      // https://stackoverflow.com/questions/824349/how-do-i-modify-the-url-without-reloading-the-page
      const newUrl =
        window.location.origin + window.location.pathname + "?" + urlParams;
      history.pushState({}, null, newUrl);
    }
  }

  const jsonLookup = JSON.parse(decodeURIComponent(lookup.jsonResult));
  console.debug("jsonLookup :", jsonLookup);

  let csrfTimingDelete = stateGet(get(state), "csrfTimingDeleteAll");
  let moveResp;
  // TODO : debounce until view get's loaded, to ensure img did show before next step comes...
  const moveSelectedIndex = (delta = 1) => {
    const lastValue = lastSelectedIndex;
    lastSelectedIndex += delta;
    if (lastSelectedIndex >= timings.length) {
      lastSelectedIndex = timings.length - 1;
    } else if (lastSelectedIndex < 0) {
      lastSelectedIndex = 0;
    }
    moveResp = {
      didChange: lastValue != lastSelectedIndex,
      isFirst: lastSelectedIndex == 0,
      isLast: lastSelectedIndex == timings.length - 1,
    };
    return moveResp;
  };
  moveResp = moveSelectedIndex(0);
  let menuIsOpen = false;

  onMount(async () => {
    //   uniqueKey = {};
    // });
    //   $: {
    const $ = window.$;
    // // TIPS opti : use svelte html node ref and pass to jquery ?
    // const htmlLookup = $(lookupForm);
    // // console.log(htmlLookup);
    // const lookupSurveyJsFormData = Object.fromEntries((new FormData(htmlLookup[0])).entries());
    // const lookupSurveyJsData = JSON.parse(decodeURIComponent(lookupSurveyJsFormData['mws_survey_js[jsonLookup]'] ?? '{}')); // TODO : from param or config
    // // TIPS : same as jsonLookup, updated by survey js or other if using ref element instead of raw string... :
    // console.log('lookupSurveyJsData : ', lookupSurveyJsData);

    // TODO : avoid id and use svelte bind...
    // https://flowbite.com/docs/components/navbar/#example
    let intro;

    const targetEl = document.getElementById("menu-timing");
    if (targetEl) {
      const triggerEl = document.getElementById("menu-timing-opener");
      const options = {
        onCollapse: () => {
          console.log("element has been collapsed");
          // TODO js create_out_transition buggy ? even with  !block...
          // if (!intro) {
          //     intro = create_out_transition(targetEl, fly, {y: 100});
          // }
          // intro.start();
          // intro = null; // redo animation on each expand
          menuIsOpen = false;
          uniqueKey = {};
        },
        onExpand: () => {
          console.log("element has been expanded");
          // https://stackoverflow.com/questions/59062025/is-there-a-way-to-perform-svelte-transition-without-a-if-block
          if (!intro) {
            intro = create_in_transition(targetEl, fly, { y: -100 });
          }
          intro.start();
          intro = null; // redo animation on each expand
          menuIsOpen = true;
          uniqueKey = {};
        },
        onToggle: () => {
          // console.log("element has been toggled");
        },
      };

      const instanceOptions = {
        id: "menu-timing",
        override: true,
      };

      /*
       * $targetEl: required
       * $triggerEl: optional
       * options: optional
       */
      const collapse = new Collapse(
        targetEl,
        triggerEl,
        options,
        instanceOptions
      );
    }
  });
  // }

  $: {
    quickQualifTemplates,
      console.debug(
        "Lookup : Qualif templates did sync update :",
        quickQualifTemplates
      );
  }
</script>

<Base
  bind:isMobile
  {copyright}
  {locale}
  {viewTemplate}
  mainClass=""
  footerClass="py-2"
>
  <div slot="mws-header-container" />
  <div class="mws-timing-qualif">
    <!-- <div class="mws-menu-wrapper inline-flex flex-col sticky top-0 z-40 bg-yellow-100"> -->
    <div class="mws-menu-wrapper inline-flex flex-col bg-yellow-100">
      <!-- https://flowbite.com/docs/components/navbar/#example -->
      <!-- {#key uniqueKey} // TODO : needed for out to work... but 
      better make work create_out_transition ? -->
      <div
        id="menu-timing"
        class="detail w-[100vw] hidden"
        out:slide={{
          delay: 0,
          duration: 3000,
          easing: quintOut,
          axis: "y",
        }}
      >
        <!-- out:fade={{
        delay: 0, duration: 3000        
        }} -->
        <header class="bg-gray-700 text-white text-center">
          <Header {locale} />
        </header>
        <div class="p-3 flex flex-wrap">
          <a
            href={Routing.generate("mws_timing_tag_list", {
              _locale: locale ?? "fr",
              viewTemplate: viewTemplate ?? "",
              ...jsonLookup,
            })}
          >
            <button>Liste des tags</button>
          </a>
          <!-- TODO : only remove current filtered query items instead of all ? -->
          <!-- <form // TODO : no need in qualif stress time, delete from report only is more ok ?
            action={Routing.generate("mws_timing_delete_all", {
              _locale: locale ?? "fr",
              viewTemplate: viewTemplate ?? "",
              ...jsonLookup,
            })}
            method="post"
            onsubmit="return confirm('Êtes vous sur de vouloir supprimer définitivement tous les suivi des temps ?');"
          >
            <input type="hidden" name="_csrf_token" value={csrfTimingDelete} />
            <button
              class="btn btn-outline-primary"
              style="--mws-primary-rgb: 255, 0, 0"
              type="submit">Supprimer les timings</button
            >
          </form> -->
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
          <div id="search-timing-lookup" class="detail w-full hidden z-50">
            {@html lookupForm}
          </div>
        </div>
      </div>
      <!-- {/key} -->
      <div class="inline-flex text-xs md:text-base pr-2">
        <button
          id="menu-timing-opener"
          data-collapse-toggle="menu-timing"
          type="button"
          class="rounded-lg m-2"
          aria-controls="search-timing-lookup"
          aria-expanded="false"
        >
          Menu
        </button>
        <div class="summary">
          <!-- // TODO : code factorization, indide component ? -->
          {@html jsonLookup.searchStart &&
            jsonLookup.searchStart.length
              ? "<strong>Depuis le : </strong>" +
                dayjs(jsonLookup.searchStart).format("YYYY/MM/DD H:mm:ss") +
                "<br/>"
              : ""}
          {@html jsonLookup.searchEnd &&
            jsonLookup.searchEnd.length
              ? "<strong>Jusqu'au : </strong>" +
                dayjs(jsonLookup.searchEnd).format("YYYY/MM/DD H:mm:ss") +
                "<br/>"
              : ""}
          {@html jsonLookup.searchTags && jsonLookup.searchTags.length
            ? "<strong>Tags : </strong>" +
              jsonLookup.searchTags.reduce(
                (acc, f) => `
                ${acc} [${f}]
              `,
                ``
              ) +
              "<br/>"
            : ""}
          <!-- // TODO : code factorization, indide component ? -->
          {@html jsonLookup.searchTagsToInclude &&
          jsonLookup.searchTagsToInclude.length
            ? "<strong>Tags à inclure : </strong>" +
              jsonLookup.searchTagsToInclude.reduce(
                (acc, f) => `
                  ${acc} [${f}]
                `,
                ``
              ) +
              "<br/>"
            : ""}
          {@html jsonLookup.searchTagsToAvoid &&
          jsonLookup.searchTagsToAvoid.length
            ? "<strong>Tags à éviter : </strong>" +
              jsonLookup.searchTagsToAvoid.reduce(
                (acc, f) => `
                  ${acc} [${f}]
                `,
                ``
              ) +
              "<br/>"
            : ""}
          {@html jsonLookup.searchKeyword
            ? `<strong>Mots clefs : </strong>${jsonLookup.searchKeyword}`
            : ``}
          {@html timingsPaginator}
        </div>
        <!-- <div>
          <select>
            <option>Auto qualif type 1</option>
          </select>
        </div> -->
      </div>
    </div>

    <span
      class="float-right m-1 text-black sticky z-30
    bg-white/70 text-xs md:text-base p-1
    top-1 pointer-events-none"
    >
      [{pageNumber}-{lastSelectedIndex}]
    </span>
    <span
      class="text-xs md:text-base float-right 
    right-14 top-0 z-30 sticky"
    >
      <button
        class="float-right m-1 sticky top-0"
        style:opacity={!moveResp.isLast ? 1 : 0.7}
        on:click={() => moveSelectedIndex(1)}
      >
        Next.
      </button>
      {#if moveResp && moveResp.isLast}
        <button
          class="float-right m-1 sticky top-0"
          on:click|stopPropagation={() => movePageIndex(1)}
        >
          Next. Page
        </button>
      {/if}
      <button
        class="float-right m-1 sticky top-0"
        style:opacity={lastSelectedIndex > 0 ? 1 : 0.7}
        on:click={() => moveSelectedIndex(-1)}
      >
        Prev.
      </button>
      {#if moveResp.isFirst && pageNumber > 1}
        <button
          class="float-right m-1 sticky top-0"
          on:click|stopPropagation={() => movePageIndex(-1)}
        >
          Prev. Page
        </button>
      {/if}
    </span>
    <!-- // TODO : same height as fixed nav if fixed nav ? -->
    <!-- <span class="h-7 w-full"></span> -->

    <div
      class="flex flex-wrap w-[100vw] h-[95vh] md:flex-row"
      style="
      {thumbSize > 50 ? `min-width: ${thumbSize}px` : ``}
    "
    >
      <!-- { JSON.stringify(timings) } -->
      {#if timings[lastSelectedIndex] ?? false}
        <!-- TIPS : use margin to allow space for drag and drop or finger scroll
        by putting the mouse or draging from that empty margin space... -->
        <SlotView
          bind:isFullScreen
          bind:lastSelectedIndex
          bind:selectionStartIndex
          bind:timeQualifs
          bind:quickQualifTemplates
          bind:timings
          {moveSelectedIndex}
          {movePageIndex}
          {locale}
          bind:timingSlot={timings[lastSelectedIndex]}
          class="h-[50%] w-[100%] md:w-[50%] md:h-[100%]
          mr-0 md:mr-[0.5%] mb-[2%] md:mb-0"
          sizeStyle={`
            ${
              isMobile
                ? // ? `height: ${splitRange}%` // TODO : % instead of vh ?
                  // : `width: ${splitRange}%`
                  `height: ${((splitRange * 2 * 48) / 100).toFixed(2)}%`
                : `width: ${((splitRange * 2 * 49.5) / 100).toFixed(2)}%`
            }
          `}
          fullscreenClass={isFullScreen ? "pb-8" : ""}
        />
      {:else}
        <div class="w-50">Sélectionner un temps pour voir son détail.</div>
      {/if}
      {#if isMobile}
        <div
          class="h-2 w-full
          bg-gradient-to-r from-indigo-500 from-10%
          via-sky-500 via-30% 
          to-emerald-500 to-90%
          shadow-lg	border rounded-md border-black
        "
        />
      {/if}
      <SquareList
        bind:lastSelectedIndex
        bind:selectionStartIndex
        bind:thumbSize
        followSelection={!isFullScreen}
        {quickQualifTemplates}
        {timings}
        {movePageIndex}
        class="h-[50%] w-[100%] md:w-[50%] md:h-[100%]
        ml-0 md:ml-[0.5%] mt-[2%] md:mt-0"
        style={`
          ${
            isMobile
              ? `height: ${(((100 - splitRange) * 2 * 48) / 100).toFixed(2)}%`
              : `width: ${(((100 - splitRange) * 2 * 49.5) / 100).toFixed(2)}%`
          }
        `}
      />
      <div class="flex items-start w-full pt-3 pb-4 z-30">
        <div class="fill-white/70 text-white/70 w-full">
          <!-- // TODO : userDelay instead of 400 ? not same for all situation,
          //         might need bigDelay or short or medium ?
          //         or too specific, keep number easyer than multiples var or const ? -->
          <input
            value={splitRange}
            on:change={debounce((e) => (splitRange = e.target.value), 400)}
            id="split-range"
            type="range"
            class="w-full h-2 bg-gray-200/50 rounded-lg
              appearance-none cursor-pointer outline-none
              "
          />
        </div>
      </div>
      <div>{@html timingsPaginator}</div>
    </div>
  </div>
  <ConfidentialityStamp
    class={isFullScreen ? "opacity-90 !fixed" : ""}
    right="right-0"
    bottom="bottom-0"
    fixedBottom={isMobile}
  />
</Base>

<style lang="scss">
  // TODO : post CSS syntax allowed in svelte scss ?
  // Done in packages/mws-moon-manager/assets/styles/app.scss
  // .label {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
  // .detail {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
  // https://stackoverflow.com/questions/68527235/add-both-important-selector-strategy-for-tailwind-configuration
  // .fixed-important {
  //   @apply fixed #{!important};
  // }
</style>
