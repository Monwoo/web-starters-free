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
  import Loader from "../../layout/widgets/Loader.svelte";
  import { get } from "svelte/store";

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
  export let isLoading = false;
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

  // TODO : centralize web services ? 
  // or differ since != usage even if similar to tags/TagsInput.svelte
  // export let removeTag = async (tag, comment = null) => {
    export let removeTag = async (offer, tagSlug, tagCategorySlug, comment = null) => {
    const data = {
      _csrf_token: stateGet(get(state), 'csrfOfferTagDelete'),
      offerSlug: offer.slug,
      tagSlug,
      tagCategorySlug,
      comment, // TODO : allow optional comment on status switch ?
    };
		let headers = {};
    const formData  = new FormData();      
    for(const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate('mws_offer_tag_delete', {
        _locale: locale,
      }), {
        method: "POST",
        headers,
        body: formData,
        credentials: "same-origin",
        redirect: 'error',
      }
    ).then(async resp => {
      console.log(resp.url, resp.ok, resp.status, resp.statusText);
      if (!resp.ok) {
        throw new Error("Not 2xx response", {cause: resp});
      } else {
        const data = await resp.json();
        offer.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
        // TODO : like for stateGet, use stateUpdate instead ? (for hidden merge or deepMerge adjustment)
        console.debug("Did remove tag", tagCategorySlug, tagSlug, offer.tags);
        stateUpdate(state, {
          csrfOfferTagDelete: data.newCsrf,
        });
      }
    }).catch(e => {
      console.error(e);
      // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
      const shouldWait = confirm("Echec de l'enregistrement.");
    });
  };

  export let addTag = async (offer, tagSlug, tagCategorySlug, comment = null) => {
    const data = {
      _csrf_token: stateGet(get(state), 'csrfOfferTagAdd'),
      offerSlug: offer.slug,
      tagSlug,
      tagCategorySlug,
      comment, // TODO : allow optional comment on status switch ?
    };
    const formData  = new FormData();      
    for(const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate('mws_offer_tag_add', {
        _locale: locale,
      }), {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        redirect: 'error',
      }
    ).then(async resp => {
      console.log(resp.url, resp.ok, resp.status, resp.statusText);
      if (!resp.ok) {
        throw new Error("Not 2xx response", {cause: resp});
      } else {
          const data = await resp.json();
          offer.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          console.debug("Did add tag", tagCategorySlug, tagSlug, offer.tags);
          stateUpdate(state, {
            csrfOfferTagAdd: data.newCsrf,
          });
      }
    }).catch(e => {
      console.error(e);
      // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
      const shouldWait = confirm("Echec de l'enregistrement.");
      addedTagKey = "null";
    });
  };

  let lastSelectedCategory;
  $: {
    selectedCategory = selectedCategory ?? urlParams.get(`selectedCategory`);

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
      searchLookup.searchTagsToInclude = searchLookup.searchTagsToInclude?.filter((t, idx) => {
        if (t === lastSelectedCategory) {
          urlParams.delete(
            `searchTagsToInclude[${idx}]`
          );
          return false;
        }
        return true;
      });
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
    lastSelectedCategory = selectedCategory;
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
  const handleOffersDndFinalize = async (e, column) => {
    isLoading = true;
    console.debug("handleOffersDndFinalize : ", e, column);
    const id = e.detail.info.id;
    if (('' + id).startsWith('column_')) {
      console.debug("Ignore handleOffersDndFinalize");
      column.offers = column.offers;
      columns = [...columns];
      isLoading = false;
      // scrElement of event is dest drop target, not source item...
      return; // Ignore drag and drop column inside tail list
    }
    const srcOffer = e.detail.items.filter((o) => {
      return o.id === id;
    })[0];
    // Update id state :
    // await removeTag(srcOffer.slug, ); // Should remove for non exclusive category ? or clone ? (but UI will note reflet cloning for now right ?)
    // In OUR CASE ONLY, below will work, since can't keep last tag of same category ;)
    await addTag(srcOffer, column.tag.slug, column.tag.categorySlug);
    column.offers = e.detail.items;
    columns = [...columns];
    isLoading = false;
  }
  // column.offers

</script>

<AddModal bind:this={addModal} {addMessageForm} />
<!--
  TODO : zoom is messing up drag & drop computations, like did not refresh zoomed size but initial one... 
style={`
  zoom: ${reportScale}%;
`}
 -->
<div class="w-full flex flex-wrap">
  <div class="w-full">
    <select
    value={selectedCategory}
    on:change={e => {
      selectedCategory = e.target.value;
    }}
    name="selectedCategory"
    class="opacity-30 hover:opacity-100 
    bg-gray-50 border border-gray-300 text-gray-900 
    text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
    inline-flex w-[10rem] p-1 m-2 dark:bg-gray-700 dark:border-gray-600 
    dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
    dark:focus:border-blue-500">
      <!-- <option value="null" selected>Type de backup</option> -->
      {#each Object.keys($state.offerTagsByCatSlugAndSlug).reduce(
        (acc, tIdx) => {
          const t = $state.offerTagsByCatSlugAndSlug[tIdx];
          if (!t.categorySlug) {
            acc.push({
              value:  tagSlugSep + t.slug,
              label:  t.slug,
            });
          }
          return acc;
        },
        []
      ) as opt}
        <option
        value={`${opt.value}`}
        selected={opt.selected}>
          {opt.label}
        </option>
      {/each}
    </select>
    <Loader {isLoading} />
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

<style lang="scss">
  [data-tail="1"] {
    word-break: break-word;
  }
</style>