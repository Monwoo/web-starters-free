<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // TODO : namespace
  import ListItem from "./ListItem.svelte";
  import dayjs from "dayjs";
  import AddModal from "../../message/AddModal.svelte";
  import { MoveIcon, SortableItem } from "svelte-sortable-items";
  // TODO : use svelte-dnd-action instead of "svelte-sortable-items" limited to one list ?
  // https://dev.to/isaachagoel/drag-and-drop-with-svelte-using-svelte-dnd-action-4554
  // https://dev.to/isaachagoel/svelte-now-has-an-accessible-drag-and-drop-library-15p
  // https://svelte.dev/repl/077cf720e2a6439caca5fb00d92d58a8?version=3.22.3
  // https://github.com/isaacHagoel/svelte-dnd-action
  import newUniqueId from "locally-unique-id-generator";
  import { flip } from "svelte/animate";
  import {
    state,
    stateGet,
    stateUpdate,
  } from "../../../stores/reduxStorage.mjs";
  import ContactLink from "../../layout/widgets/ContactLink.svelte";
  import Routing from "fos-router";
  import {dndzone} from "svelte-dnd-action";

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
    ? Object.keys($state.offerTagsByCatSlugAndSlug).reduce((acc, tIdx) => {
        const t = $state.offerTagsByCatSlugAndSlug[tIdx];
        if (selectedCategorySlug === t.categorySlug) {
          const position = acc["__nextColumnIndex"] ?? 0;
          // acc.push(t);
          // TODO : ok to update tag source ?
          // will keep column dragged order if switch category ?
          // => will need user setting sync of $state.offerTagsByCatSlugAndSlug to work over page refresh...
          t.columnIndex =
            (t.columnIndex ?? null) === null ? position : t.columnIndex;
          acc[t.slug] = t;
          acc["__nextColumnIndex"] = position + 1;
        }
        return acc;
      }, {})
    : {};

  $: columns =
    selectedCategoryTags &&
    offers.reduce((acc, o) => {
      console.debug("Offer :", o);
      const matchTags = Object.keys(selectedCategoryTags).reduce(
        (acc: any, matchTSlug) => {
          const matchT = selectedCategoryTags[matchTSlug];
          const matchTCat = matchT.categorySlug;
          if (
            o.tags.filter(
              (t) => t.categorySlug === matchTCat && t.slug === matchTSlug
            ).length
          ) {
            acc[matchTSlug] = matchT;
          }
          return acc;
        },
        {}
      );
      console.debug("matchTags :", matchTags);
      // Init empty columns for all tags :
      for (const selectedTSlug in selectedCategoryTags) {
        const selectedT = selectedCategoryTags[selectedTSlug];
        const pos = selectedT.columnIndex ?? 0;
        if (!(acc[pos] ?? false)) {
          acc[pos] = {
            // Id needed for https://dev.to/isaachagoel/drag-and-drop-with-svelte-using-svelte-dnd-action-4554 ?
            id: 'column_' + selectedT.categorySlug + tagSlugSep + selectedT.slug,
            tag: selectedT,
            offers: [],
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

  // https://github.com/isaacHagoel/svelte-dnd-action?tab=readme-ov-file#basic-example
  const flipDurationMs = 300;

  function handleDndConsider(e) {
    console.debug("handleDndConsider : ", e);
    const id = e.detail.info.id;
    // if (!e.srcElement.hasAttribute('data-column')) {
    //   // scrElement of event is dest drop target, not source item...
    //   return; // Ignore drag and drop column inside tail list
    // }
    if (!('' + id).startsWith('column_')) {
      console.debug("Ignore handleDndConsider");
      columns = [...columns];
      // scrElement of event is dest drop target, not source item...
      return; // Ignore drag and drop column inside tail list
    }

    columns = e.detail.items;
    // e.stopPropagation();
    // e.preventDefault();
  }
  function handleDndFinalize(e) {
    console.debug("handleDndFinalize : ", e);
    const id = e.detail.info.id;
    // if (!e.srcElement.hasAttribute('data-column')) {
    //   // scrElement of event is dest drop target, not source item...
    //   return; // Ignore drag and drop column inside tail list
    // }
    if (!('' + id).startsWith('column_')) {
      console.debug("Ignore handleDndFinalize");
      columns = [...columns];
      // scrElement of event is dest drop target, not source item...
      return; // Ignore drag and drop column inside tail list
    }

    columns = e.detail.items;
    // e.stopPropagation();
    // e.preventDefault();
  }

  function handleOffersDndConsider(e, column) {
    console.debug("handleOffersDndConsider : ", e, column);
    // if (!e.srcElement.hasAttribute('data-tail')) {
    //   // scrElement of event is dest drop target, not source item...
    //   return; // Ignore drag and drop column inside tail list
    // }
    const id = e.detail.info.id;
    // if (('' + id).startsWith('tail_')) { // Offers already have ids systems
    if (('' + id).startsWith('column_')) {
      console.debug("Ignore handleOffersDndConsider");
      column.offers = column.offers;
      columns = [...columns];
      // scrElement of event is dest drop target, not source item...
      return; // Ignore drag and drop column inside tail list
    }
    column.offers = e.detail.items;
    columns = [...columns];
  }
  function handleOffersDndFinalize(e, column) {
    console.debug("handleOffersDndFinalize : ", e, column);
    const id = e.detail.info.id;
    if (('' + id).startsWith('column_')) {
      console.debug("Ignore handleOffersDndFinalize");
      column.offers = column.offers;
      columns = [...columns];
      // scrElement of event is dest drop target, not source item...
      return; // Ignore drag and drop column inside tail list
    }
    column.offers = e.detail.items;
    columns = [...columns];
  }
  // column.offers

</script>

<AddModal bind:this={addModal} {addMessageForm} />
<div class="w-full flex flex-wrap"
style={`
  zoom: ${reportScale}%;
`}
>
  <div class="w-full">
    <select
    value={selectedCategorySlug}
    name="selectedCategorySlug"
    class="opacity-30 hover:opacity-100 
    bg-gray-50 border border-gray-300 text-gray-900 
    text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
    inline-flex w-[10rem] p-1 m-2 dark:bg-gray-700 dark:border-gray-600 
    dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
    dark:focus:border-blue-500">
      <!-- <option value="null" selected>Type de backup</option> -->
      {#each [
        {value:selectedCategorySlug, label:selectedCategorySlug},
        // {format:'full', label:'Tout le CRM'},
      ] as opt}
        <option
        value={`${opt.value}`}
        selected={opt.selected}>
          {opt.label}
        </option>
      {/each}
    </select>  
  </div>
  <div class="w-full flex overflow-scroll"
  data-column="1"
  use:dndzone="{{
    items: columns,
    // https://github.com/isaacHagoel/svelte-dnd-action?tab=readme-ov-file#input
    type: 'column',
    // ...otherOptions
    flipDurationMs,
  }}"
  on:consider="{handleDndConsider}"
  on:finalize="{handleDndFinalize}">
    {#each columns as column, idx (column.id)}
      <div class="flex p-2"
      animate:flip="{{
        duration: flipDurationMs
      }}"
      style="width: {(100 / columns.length).toFixed(2)}%">
        <div class="w-full h-full p-2 border-2 border-gray-400 rounded-md">
          <div>
            {column.tag.label}
          </div>
          <div class="w-full flex flex-wrap overflow-scroll min-h-[80%] content-start"
          data-tail="1"
          use:dndzone="{{
            items: column.offers,
            // https://github.com/isaacHagoel/svelte-dnd-action?tab=readme-ov-file#input
            type: 'tail',
            // ...otherOptions
            flipDurationMs,
          }}"
          on:consider="{(e) => handleOffersDndConsider(e, column)}"
          on:finalize="{(e) => handleOffersDndFinalize(e, column)}">
            {#each column.offers ?? [] as offer, idx (offer.id)}
              {@const trackings = offer?.mwsOfferTrackings?.toReversed() ?? []}
              <div
              animate:flip="{{duration: flipDurationMs}}"
              class="m-2 p-2 border-2 border-gray-700 rounded-md">
                <p>{dayjs(offer.leadStart).format("YYYY/MM/DD HH:mm")}</p>  
                <a
                  href={Routing.generate("mws_offer_view", {
                    _locale: locale ?? "fr",
                    viewTemplate: viewTemplate ?? "",
                    offerSlug: offer.slug,
                  })}
                  target="_blank"
                >
                  {offer.clientUsername}
                </a>
                <br />
                ${offer.budget ?? ""} <br />

                <h1 class="font-bold text-lg">
                  <a href={`${offer.sourceUrl}`} target="_blank" rel="noreferrer">
                    {offer.sourceDetail?.title ?? "Voir l'offre"}
                  </a>  
                </h1>

                <div class="w-full font-bold text-gray-500">
                  [{(trackings ?? [])[0]?.updatedAt
                    ? dayjs(trackings[0].updatedAt).format("YYYY/MM/DD")
                    : "--"}] {(trackings ?? [])[0]?.comment ?? "--"}
                </div>
                <ContactLink
                source={offer.slug}
                name={offer.clientUsername}
                title={offer.title}
                contact={offer.contact1 ?? ""}
                ></ContactLink>
                <ContactLink
                source={offer.slug}
                name={offer.clientUsername}
                title={offer.title}
                contact={offer.contact2 ?? ""}
                ></ContactLink>
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
        </div>
      </div>
    {/each}
  </div>
</div>
