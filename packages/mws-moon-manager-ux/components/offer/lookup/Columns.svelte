<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // TODO : namespace
  import ListItem from "./ListItem.svelte";
  import AddModal from "../../message/AddModal.svelte";
  import { MoveIcon, SortableItem } from "svelte-sortable-items";
  // TODO : use svelte-dnd-action instead of "svelte-sortable-items" limited to one list ?
  // https://dev.to/isaachagoel/drag-and-drop-with-svelte-using-svelte-dnd-action-4554
  // https://svelte.dev/repl/077cf720e2a6439caca5fb00d92d58a8?version=3.22.3
  import newUniqueId from "locally-unique-id-generator";
  import { flip } from "svelte/animate";
  import {
    state,
    stateGet,
    stateUpdate,
  } from "../../../stores/reduxStorage.mjs";

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
  export let isWide;
  // Offer tag main category to build table from
  export let selectedCategory;

  // fetch {offers} {offersHeaders} {messagesByProjectId}
  // With selectedCategory filters (keep num of page limits ?)
  // TIPS : could be ASYNC loaded from url page limits etc...
  //        BUT wrong for url rankings,
  //        will redirect with right filters instead....
  export let searchLookup;
  // TODO : can use unclassified Column instead of url redirect ?
  //        => will also need to send hideUnclassifiedColumn in url param to show back column view on page refresh ?
  // export let hideUnclassifiedColumn = true;

  // TODO : factorize, cf php controller :
  const tagSlugSep = " > ";

  const urlParams = new URLSearchParams(window.location.search);

  $: {
    selectedCategory = urlParams.get(`selectedCategory`);

    console.debug(
      "Columns searchLookup :",
      searchLookup,
      $state.offerTagsByCatSlugAndSlug
    );
    if (!selectedCategory || "null" == selectedCategory) {
      selectedCategory = Object.keys($state.offerTagsByCatSlugAndSlug).reduce(
        (acc, tIdx) => {
          const t = $state.offerTagsByCatSlugAndSlug[tIdx];
          if (!acc && !t.categorySlug) {
            acc = tagSlugSep + t.slug;
          }
          return acc;
        },
        null
      );
    }
    console.debug("Columns selectedCategory :", selectedCategory);
    if (
      selectedCategory &&
      "null" != selectedCategory &&
      !(searchLookup.searchTagsToInclude?.includes(selectedCategory) ?? false)
    ) {
      urlParams.set(
        `searchTagsToInclude[${searchLookup.searchTagsToInclude?.length ?? 0}]`,
        selectedCategory
      );
      urlParams.set(`showTableView`, "1");
      urlParams.set(`selectedCategory`, selectedCategory);
      const newUrl =
        window.location.origin + window.location.pathname + "?" + urlParams;
      // history.pushState({}, null, newUrl); // No redirect
      window.location = newUrl;
    }
  }

  // TODO : filter one tag ? nop => will have another props for custom tag by tag column..
  $: selectedCategorySlug = selectedCategory.split(tagSlugSep).slice(-1)[0];

  let numberHoveredItem;

  // TIPS : conditional 'selectedCategorySlug' for reactivity, inside subfunction, will not be detected
  $: selectedCategoryTags = selectedCategorySlug
    ? Object.keys($state.offerTagsByCatSlugAndSlug).reduce(
        (acc, tIdx) => {
          const t = $state.offerTagsByCatSlugAndSlug[tIdx];
          if (selectedCategorySlug === t.categorySlug) {
            const position = acc['__nextColumnIndex'] ?? 0;
            // acc.push(t);
            // TODO : ok to update tag source ? 
            // will keep column dragged order if switch category ?
            // => will need user setting sync of $state.offerTagsByCatSlugAndSlug to work over page refresh...
            t.columnIndex =
              (t.columnIndex ?? null) === null ? position : t.columnIndex;
            acc[t.slug] = t;
            acc['__nextColumnIndex'] = position + 1;
          }
          return acc;
        },
        {}
      )
    : {};

  $: columns = selectedCategoryTags && offers.reduce((acc, o) => {
    console.debug("Offer :", o);
    const matchTags = Object.keys(selectedCategoryTags).reduce((acc:any, matchTSlug) => {
      const matchT = selectedCategoryTags[matchTSlug];
      const matchTCat = matchT.categorySlug;
      if (
        o.tags
        .filter((t => t.categorySlug === matchTCat && t.slug === matchTSlug))
        .length
      ) {
        acc[matchTSlug] = matchT;
      }
      return acc;
    }, {});
    console.debug("matchTags :", matchTags);
    // Init empty columns for all tags :
    for (const selectedTSlug in selectedCategoryTags) {
      const selectedT = selectedCategoryTags[selectedTSlug];
      const pos = selectedT.columnIndex ?? 0;
      if (!(acc[pos] ?? false)) {
        acc[pos] = {
          tag: selectedT,
          offers: []
        };
      }
    }
    // Fill with matched offers :
    for (const matchTSlug in matchTags) {
      const matchT = matchTags[matchTSlug];
      const pos = matchT.columnIndex ?? 0;
      acc[pos].offers.push(o);
    }

    return acc;
  }, []);
  
  $: console.debug("Columns : ", columns);
</script>

<AddModal bind:this={addModal} {addMessageForm} />

{selectedCategoryTags.length}
TODO : columns with drag and drop {numberHoveredItem}
<div class="w-full flex flex-wrap">
  <div class="w-full">
    {selectedCategorySlug}
  </div>
  {#each columns as column, idx (idx)}
    <div class="p-0 w-min">
      <div>
        {column.tag.label}
      </div>
      {#each column.offers ?? [] as offer, idx (offer.id)}
        <div>
          {offer.id}
        </div>
      {/each}
      <!-- {#each [{id:'TODO 1'}, {id:'TODO 2'}] as offer, idx (offer.id)}
        <! -- TODO : bind:propData={offer} fail on wird error ypeError: Cannot read properties of undefined (reading '0') in compiled js catch block...  - ->
        <SortableItem
        class="h-full w-full flex justify-center content-start"
        propItemNumber={idx}
        bind:propHoveredItemNumber={numberHoveredItem}
        >
          <span>TEST</span>
        </SortableItem>
      {/each} -->
    </div>
  {/each}
</div>
