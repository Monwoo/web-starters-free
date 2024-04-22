<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // TODO : namespace
  import ListItem from "./ListItem.svelte";
  import AddModal from "../../message/AddModal.svelte";
  import { onMount } from "svelte";
  import { tick } from "svelte";
  import FunnelModal from "../tags/FunnelModal.svelte";
  import newUniqueId from "locally-unique-id-generator";

  export let locale;
  export let offers = [];
  export let messagesByProjectId = {};
  export let addMessageForm;
  export let offersHeaders = {}; // injected raw html
  export let viewTemplate;
  export let yScrollable;
  export let reportScale = 100;
  export let addModal;
  export let isMobile;

  let funnelComponent;

  // console.debug("messagesByProjectId :", messagesByProjectId);
  let isScrolling = false;
  let isFirstColVisible = false;
  let isSecondColVisible = false;
  let isThirdColVisible = false;
  // Svelte + JQuery way :
  let cleanup = null;
  const refreshScrollListeners = async (isMobile) => {
    const litenerUID = newUniqueId();
    if (cleanup) {
      cleanup();
      cleanup = null;
    };
    const jQuery = window.jQuery;
    const yScrollableSelector = isMobile ? 'body' : 'main'; // TODO : name main content scroll window ? instead of <main>
    yScrollable = jQuery(yScrollableSelector); // TODO : name main content scroll window ? instead of <main>
    // jQuery(() => {
    const scrollListener = (e) => {
      const target = jQuery(e.target);
      const fromStart = target.scrollLeft() / (reportScale/100);
      // https://stackoverflow.com/questions/10463518/converting-em-to-px-in-javascript-and-getting-default-font-size/10466205#10466205
      const emToPx = Number(
        getComputedStyle(target[0], null).fontSize.replace(/[^\d]/g, "")
      );
      // console.log("Did scroll", fromStart);
      isScrolling = fromStart > 0;

      isFirstColVisible = fromStart > 11 * emToPx;
      isSecondColVisible = fromStart > 7 * emToPx;
      isThirdColVisible = fromStart > 0;
      console.debug(
        "listScroll " + litenerUID + ' : ' + yScrollableSelector,
        fromStart, emToPx,
        isScrolling, isFirstColVisible, isSecondColVisible, isThirdColVisible
      );
    };

    // yScrollable =  jQuery('body'); // TODO : name main content scroll window ? instead of <main>

    // jQuery(".mws-data-list", htmlTable).on("scroll", scrollListener);
    // await tick();
    // $: {
    //   jQuery(".mws-data-list", htmlTable).off("scroll", scrollListener);
    //   jQuery(".mws-data-list", htmlTable).on("scroll", scrollListener);
    // }
    // jQuery(".mws-data-list", htmlTable).on("scroll", scrollListener);
    jQuery(yScrollable).on("scroll", scrollListener);

    cleanup = () => {
      console.debug("Cleanup list scroll " + litenerUID + ' : ' + yScrollableSelector);
      jQuery(yScrollable).off("scroll", scrollListener);
    };
    return cleanup;
  }

  $: refreshScrollListeners(isMobile);

  // onMount(async () => await refreshScrollListeners(isMobile));
  // Non bloquant :
  onMount(async () => refreshScrollListeners(isMobile));

</script>

<AddModal bind:this={addModal} {addMessageForm} />
<FunnelModal bind:this={funnelComponent} {locale} />

<!-- https://preline.co/docs/scrollspy.html -->
<!-- <div id="ctc-component-1-tab-1" role="tabpanel" aria-labelledby="ctc-component-1-tab-1-item" class="">
  <div class="bg-gradient-to-tl from-blue-600 to-purple-400 p-3 rounded-xl shadow-sm p-6 md:p-12">
    <div id="scrollspy-scrollable-parent-1" class="max-h-[340px] overflow-y-auto bg-white rounded-lg pb-4 px-6">
      <header class="sticky top-0 inset-x-0 flex flex-wrap sm:justify-start sm:flex-nowrap z-40 w-full bg-white text-sm py-4">
        <nav class="max-w-[85rem] w-full mx-auto sm:flex sm:items-center sm:justify-between" aria-label="Global">
          <div class="flex items-center justify-between">
            <a class="flex-none text-xl font-semibold" href="#">Brand</a>
            <div class="sm:hidden">
              <button type="button" class="hs-collapse-toggle p-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm" data-hs-collapse="#navbar-collapse-basic" aria-controls="navbar-collapse-basic" aria-label="Toggle navigation">
                <svg class="hs-collapse-open:hidden w-4 h-4" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"></path>
                </svg>
                <svg class="hs-collapse-open:block hidden w-4 h-4" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"></path>
                </svg>
              </button>
            </div>
          </div>
          <div id="navbar-collapse-basic" class="hidden overflow-hidden transition-all duration-300 basis-full grow sm:block">
            <div data-hs-scrollspy="#scrollspy-1" data-hs-scrollspy-scrollable-parent="#scrollspy-scrollable-parent-1" class="flex flex-col gap-5 mt-5 sm:flex-row sm:items-center sm:justify-end sm:mt-0 sm:ps-5 [--scrollspy-offset:220] md:[--scrollspy-offset:70]">
              <a class="hs-scrollspy-active:text-blue-600 text-sm text-gray-700 leading-6 hover:text-gray-500" href="#first">First</a>
              <a class="hs-scrollspy-active:text-blue-600 text-sm text-gray-700 leading-6 hover:text-gray-500" href="#second">Second</a>

              <div data-hs-scrollspy-group="" class="hs-dropdown [--adaptive:none] [--placement:bottom-right]">
                <button type="button" id="hs-mega-menu-basic-dr" class="group hs-scrollspy-active:text-blue-600 mb-3 sm:mb-0 inline-flex justify-center items-center gap-2 text-sm text-gray-700 leading-6 hover:text-gray-500">
                  Dropdown
                  <svg class="ms-2 w-4 h-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6"></path>
                  </svg>
                </button>

                <div class="hs-dropdown-menu transition-[opacity,margin] duration-[0.1ms] sm:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0 sm:w-48 z-10 bg-white sm:shadow-md rounded-lg p-2 before:absolute top-full sm:border before:-top-5 before:start-0 before:w-full before:h-5 hidden" style="">
                  <a class="hs-scrollspy-active:text-blue-600 flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-700 leading-6 hover:text-gray-500 active" href="#third">
                    Third
                  </a>
                  <a class="hs-scrollspy-active:text-blue-600 flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-700 leading-6 hover:text-gray-500 active" href="#fourth">
                    Fourth
                  </a>
                  <a class="hs-scrollspy-active:text-blue-600 flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-700 leading-6 hover:text-gray-500" href="#fifth">
                    Fifth
                  </a>
                </div>
              </div>
            </div>
          </div>
        </nav>
      </header>

      <div id="scrollspy-1" class="mt-3 space-y-4">
        <div id="first">
          <h3 class="text-lg font-semibold">First</h3>
          <p class="mt-1 text-sm leading-6 text-gray-600">This is some placeholder content for the scrollspy page. Note that as you scroll down the page, the appropriate navigation link is highlighted. It's repeated throughout the component example. We keep adding some more example copy here to emphasize the scrolling and highlighting.</p>
        </div>

        <div id="second">
          <h3 class="text-lg font-semibold">Second</h3>
          <p class="mt-1 text-sm leading-6 text-gray-600">This is some placeholder content for the scrollspy page. Note that as you scroll down the page, the appropriate navigation link is highlighted. It's repeated throughout the component example. We keep adding some more example copy here to emphasize the scrolling and highlighting.</p>
        </div>

        <div id="third">
          <h3 class="text-lg font-semibold">Third</h3>
          <p class="mt-1 text-sm leading-6 text-gray-600">This is some placeholder content for the scrollspy page. Note that as you scroll down the page, the appropriate navigation link is highlighted. It's repeated throughout the component example. We keep adding some more example copy here to emphasize the scrolling and highlighting.</p>
        </div>

        <div id="fourth">
          <h3 class="text-lg font-semibold">Fourth</h3>
          <p class="mt-1 text-sm leading-6 text-gray-600">This is some placeholder content for the scrollspy page. Note that as you scroll down the page, the appropriate navigation link is highlighted. It's repeated throughout the component example. We keep adding some more example copy here to emphasize the scrolling and highlighting.</p>
        </div>

        <div id="fifth">
          <h3 class="text-lg font-semibold">Fifth</h3>
          <p class="mt-1 text-sm leading-6 text-gray-600">This is some placeholder content for the scrollspy page. Note that as you scroll down the page, the appropriate navigation link is highlighted. It's repeated throughout the component example. We keep adding some more example copy here to emphasize the scrolling and highlighting.</p>
        </div>
      </div>
    </div>
  </div>
</div> -->
<!-- https://preline.co/docs/scrollspy.html -->

<!-- <div bind:this={htmlTable}> -->

<!-- TIPS : zoom should be done outside of modal views
AND outside of sticky elements -->
<div class="block w-full">
  <table
  class="items-center w-full bg-transparent border-collapse"
  >
    <!-- TODO : sticky top for title to stay on page ? -->
    <!-- <thead class="top-[-1px] md:top-[-24px] sticky z-40"> 
    top-[-1px] md:-top-6 sticky z-40 text-xs md:text-sm  
    -->
      
    <thead class="sticky top-[-1px] md:-top-6 z-40 text-xs md:text-sm">
        <tr class="users-table-info"
        style={`
          zoom: ${reportScale}%;
        `}>
        <th
          scope="col"
          class="sticky left-0 w-[3em] w-[3em]
        hover:bg-white/90 hover:opacity-100"
          class:opacity-0={isFirstColVisible}>Voir</th
        >
        <th
          scope="col"
          class="sticky left-[4em] w-[6em] w-[4em]
        hover:bg-white/90 hover:opacity-100"
          class:opacity-0={isSecondColVisible}>[Slug] Status</th
        >
        <th
          scope="col"
          class="sticky left-[9em] w-[6em] w-[6em]
        hover:bg-white/90 hover:opacity-100"
          class:opacity-0={isThirdColVisible}>Tags</th
        >
        <th scope="col">
          {@html offersHeaders.clientUsername ?? "Nom du client"}
        </th>
        <th scope="col">
          <div class="flex items-center">
            {@html offersHeaders.contact1 ?? "Contact"}
            {@html offersHeaders.contact2 ?? "(s)"}
          </div>
        </th>
        <th scope="col" class="min-w-[24rem]"> Messages </th>
        <th scope="col">
          {@html offersHeaders.leadStart ?? "Depuis le"}
        </th>
        <th scope="col">
          {@html offersHeaders.budget ?? "Budget"}
        </th>
        <th scope="col" class="min-w-[24rem]">Description</th>
        <th scope="col">Titre</th>
      </tr>
    </thead>
    <tbody
      style={`
        zoom: ${reportScale}%;
      `}
    >
      {#if yScrollable}
      {#each offers as offer}
        <!-- { JSON.stringify(offer) } -->
        <!-- {@debug offer} -->
        <ListItem
          {offer}
          {reportScale}
          {locale}
          {viewTemplate}
          {addModal}
          {isScrolling}
          {isFirstColVisible}
          {isSecondColVisible}
          {isThirdColVisible}
          funnelModal={funnelComponent?.funnelModal}
          yScrollable={yScrollable}
          messages={messagesByProjectId[
            offer.slug.split("-").slice(-1).join("")
          ] ?? null}
        />
        {/each}
        {/if}
      </tbody>
  </table>
</div>