<script context="module">
  // import "svelte-drag-drop-touch";
  // import DragDropTouch from 'svelte-drag-drop-touch'

</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  import Routing from "fos-router";
  import {
    state,
    stateGet,
    stateUpdate,
  } from "../../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import { MoveIcon, SortableItem } from "svelte-sortable-items";
  import { flip } from "svelte/animate";
  import ItemView from "./ItemView.svelte";
  import _ from "lodash";
  import ConfirmUpdateOrNew from "./ConfirmUpdateOrNew.svelte";
  // import "svelte-drag-drop-touch/dist/svelte-drag-drop-touch";
  // require("svelte-drag-drop-touch");

  export let locale;
  export let isHeaderExpanded = false;
  // // TIPS : MUST NOT be setup for top root binds
  // //         to be able to feed with initial values ?
  // //           => Did juste messup with component name,
  // //              reactivity ok event with initial value
  // export let isHeaderExpanded;

  export let qualifTemplates;
  export let quickQualifTemplates;
  export let allTagsList;
  // // STATES
  // let arrayUsers = [
  //   { id: 1, name: "John", age: 45 },
  //   { id: 2, name: "Mark", age: 33 },
  //   { id: 3, name: "Jonas", age: 56 },
  //   { id: 4, name: "Mary", age: 76 },
  // ];
  let numberHoveredItem;
  /////
  const defaultItemW = "w-3/12"; // TODO : from crm config or user config....
  const userConfig = stateGet(get(state), "timingQualifConfig");
  export let timingQualifConfig = userConfig?.timing?.quickQualif ?? {};
  export let maxLimit = timingQualifConfig?.maxLimit ?? 12;
  export let itemWidth = timingQualifConfig?.itemWidth ?? defaultItemW;
  export let sortOrder = timingQualifConfig?.sortOrder;

  export let allSizes = [
    {
      value: defaultItemW,
      label: "Taille de la liste (4 par lignes)",
    },
    {
      value: "w-1/12",
      label: "12 par lignes",
    },
    {
      value: "w-2/12",
      label: "6 par lignes",
      // }, { // Default value
      //   value: "w-3/12",
      //   label:"Taille de la liste",
    },
    {
      value: "w-4/12",
      label: "3 par lignes",
    },
    {
      value: "w-6/12",
      label: "2 par lignes",
    },
    {
      value: "w-full",
      label: "1 par lignes",
    },
  ];

  export let confirmUpdateOrNew;

  let configDidChange = false;
  let lastDataset = {};
  // const configDidChange = false;
  $: {
    console.debug('Will check', Object.keys(lastDataset));
    // TODO : $$props not covering all props, need to encapsulate ?
    // configDidChange = Object.keys(lastDataset).reduce((acc, k) => {
    //   return lastDataset[k] !== $$props[k] || acc;
    //   // return lastDataset[k] !== $$restProps[k] || acc;
    // }, !Object.keys(lastDataset).length)
    configDidChange = 
    // TODO : deep detect changes ?
    // lastDataset.quickQualifTemplates != quickQualifTemplates
    // || lastDataset.timingQualifConfig != timingQualifConfig // Will detect change if ref change not the content...
    lastDataset.quickQualifTemplates != quickQualifTemplates
    // || lastDataset.timingQualifConfig != timingQualifConfig
    || lastDataset.maxLimit != maxLimit
    || lastDataset.itemWidth != itemWidth
    || lastDataset.sortOrder != sortOrder
    if (configDidChange) {
      console.debug("Qualif templates will sync update :", quickQualifTemplates);
      // Lookup to ensure reactivity on below changes : (Svelte do not do deep detect...)
      timingQualifConfig, maxLimit, itemWidth, sortOrder,
      syncQualifConfigWithBackend();
      configDidChange = false; // NEEDED performance, can't call network each 10 milliseconds...
      lastDataset = {
        quickQualifTemplates, timingQualifConfig,
        maxLimit, itemWidth, sortOrder
      }
    }
  }
  quickQualifTemplates = qualifTemplates.slice(0, maxLimit);

  // $: {
  //   // Triky auto update from qualifTemplates (not ordered)
  //   // when sorted array did the job.... // TODO : less hacky ?
  //   if (quickQualifTemplates?.length < maxLimit) {
  //     quickQualifTemplates = qualifTemplates.slice(0, maxLimit);
  //   }
  // }

  // const onItemWChange = async (newW) => {
  // };
  const collator = new Intl.Collator(undefined, {
    numeric: true,
    sensitivity: "base",
  });
  // https://stackoverflow.com/questions/1885557/simplest-code-for-array-intersection-in-javascript
  const intersect = (a, b) => {
    // var setA = new Set(a);
    // var setB = new Set(b);
    // var intersection = new Set([...setA].filter((x) => setB.has(x)));
    // return Array.from(intersection);
    // https://stackoverflow.com/questions/44571115/passing-an-array-of-arrays-into-lodash-intersection/44571229#44571229
    return _.intersectionBy(a, b, "slug");
  };
  $: {
    if ("byLabel" == sortOrder) {
      // quickQualifTemplates.sort(function (a, b) {
      //   return strncmp(a.label, b.label);
      // });
      // https://stackoverflow.com/questions/2802341/natural-sort-of-alphanumerical-strings-in-javascript
      // '10'.localeCompare('2', undefined, {numeric: true, sensitivity: 'base'})
      // var collator = new Intl.Collator(undefined, {numeric: true, sensitivity: 'base'});
      // console.log(quickQualifTemplates.sort(collator.compare));
      quickQualifTemplates.sort(function (a, b) {
        return collator.compare(a.label, b.label);
      });
      qualifTemplates.sort(function (a, b) {
        return collator.compare(a.label, b.label);
      });
      quickQualifTemplates = quickQualifTemplates;
      sortOrder = null;
    }
    if ("byTagsSimilarity" == sortOrder) {
      // quickQualifTemplates.sort(function (a, b) {
      //   return strncmp(a.label, b.label);
      // });
      // https://stackoverflow.com/questions/2802341/natural-sort-of-alphanumerical-strings-in-javascript
      // '10'.localeCompare('2', undefined, {numeric: true, sensitivity: 'base'})
      // var collator = new Intl.Collator(undefined, {numeric: true, sensitivity: 'base'});
      // console.log(quickQualifTemplates.sort(collator.compare));
      quickQualifTemplates.sort(function (a, b) {
        const similar = intersect(a.timeTags, b.timeTags);
        const nbSimilar = similar.length;
        return -(
          a.timeTags.length -
          nbSimilar -
          (b.timeTags.length - nbSimilar)
        );
      });
      // TODO : trying to keep qualifTemplates in sync
      //      but lot of action may have change quickQualifTemplates ?
      qualifTemplates.sort(function (a, b) {
        const similar = intersect(a.timeTags, b.timeTags);
        const nbSimilar = similar.length;
        return -(
          a.timeTags.length -
          nbSimilar -
          (b.timeTags.length - nbSimilar)
        );
      });
      quickQualifTemplates = quickQualifTemplates;
      sortOrder = null;
    }
  }

  let needRefresh;
  export const syncQualifWithBackend = async (qualif) => {
    const data = {
      _csrf_token: stateGet(get(state), "csrfTimingQualifSync"),
      qualif: JSON.stringify(qualif),
    };
    let headers = {};
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_timing_qualif_sync", {
        _locale: locale,
      }),
      {
        method: "POST",
        headers,
        body: formData,
        credentials: "same-origin",
        redirect: "error",
      }
    )
      .then(async (resp) => {
        console.log(resp);
        if (!resp.ok) {
          // make the promise be rejected if we didn't get a 2xx response
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          // got the desired response
          const data = await resp.json();
          // qualif = data.qualif; // TODO : in case of backend updates ?
          // needRefresh = {}; // All new obj is uniq, force UI refresh on data inputs
          // BUT not enough for reactivity refresh, so foce with :
          // quickQualifTemplates = quickQualifTemplates; // TOO much
          // BUT WILL CLOSE current opened stuff + loose btn colors ?

          // WARN : below merge will not RESET fields
          //        BUT : will add to existing list...
          qualif.timeTags = []; // Reset list to ensure clean merge // TODO : review is _.merge is ok or use way to reset lists ?
          _.merge(qualif, data.sync);

          if (data.didDelete) {
            quickQualifTemplates = quickQualifTemplates.filter(
              (q) => q.id !== qualif.id
            );
            qualifTemplates = qualifTemplates.filter((q) => q.id !== qualif.id);
          }

          stateUpdate(state, {
            csrfTimingQualifSync: data.newCsrf,
          });
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
    return qualif;
  };

  export const syncQualifConfigWithBackend = async () => {
    timingQualifConfig.list = quickQualifTemplates.map((q) => q.label);
    timingQualifConfig.maxLimit = maxLimit;
    timingQualifConfig.sortOrder = sortOrder;
    timingQualifConfig.itemWidth = itemWidth;
    const data = {
      _csrf_token: stateGet(get(state), "csrfTimingQualifConfigSync"),
      config: JSON.stringify({
        timing: {
          quickQualif: timingQualifConfig,
        },
      }),
    };
    let headers = {};
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_timing_qualif_config_sync", {
        _locale: locale,
      }),
      {
        method: "POST",
        headers,
        body: formData,
        credentials: "same-origin",
        redirect: "error",
      }
    )
      .then(async (resp) => {
        console.log(resp);
        if (!resp.ok) {
          // make the promise be rejected if we didn't get a 2xx response
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          // got the desired response
          const data = await resp.json();

          timingQualifConfig = data.sync?.timing?.quickQualif;

          stateUpdate(state, {
            csrfTimingQualifConfigSync: data.newCsrf,
          });
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
    return timingQualifConfig;
  };

</script>

<!-- <svelte:head>
  <!-- MAKE IT WORK ON MOBILE DEVICES -- >
  <script src="https://unpkg.com/svelte-drag-drop-touch"></script>
  <!---- >
</svelte:head> -->
{#key needRefresh}
  <div class="float-right w-full flex flex-row flex-wrap">
    <div class="flex w-full flex-wrap justify-evenly">
      <!-- {#each arrayUsers as currentUser, numberCounter (currentUser.id)} -->
      {#each quickQualifTemplates as qualif, numberCounter (qualif.id)}
        <div animate:flip class="p-0 grow {itemWidth}">
          <SortableItem
            class="h-full w-full flex justify-center content-start"
            propItemNumber={numberCounter}
            bind:propData={quickQualifTemplates}
            bind:propHoveredItemNumber={numberHoveredItem}
          >
            <div
              _data-tooltip-target="tooltip-hover-{numberCounter}"
              _data-tooltip-trigger="hover"
              class="flex flex-row flex-wrap h-full w-full
              justify-center content-start align-middle hover:cursor-move"
              class:classHovered={numberHoveredItem === numberCounter}
            >
              <ItemView
                bind:qualif
                bind:quickQualifTemplates
                bind:maxItemsLimit={maxLimit}
                bind:isHeaderExpanded
                {syncQualifWithBackend}
                {locale}
                {confirmUpdateOrNew}
                {allTagsList}
                qualifLookups={qualifTemplates}
              />
            </div>
          </SortableItem>
          <!-- <div // TODO : strange pop-over showing and following move animation... 
            + shoud change message when hovering tag toggle button 
            or shortcut edit or listags edits ?
            
            id="tooltip-hover-{numberCounter}"
            role="tooltip"
            class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700"
          >
            <p class="bg-slate-700 fill-blue-400 flex">
              Utiliser <MoveIcon propSize={12} /> pour déplacer la qualif {qualif.label}.
            </p>
            <div class="tooltip-arrow" data-popper-arrow />
          </div> -->
        </div>
      {/each}
    </div>
    <div class="p-2">
      Taille de liste :
      <select
        name="itemWidth"
        bind:value={itemWidth}
        on:change={async () => {
          console.debug("New item width : ", itemWidth);
          // await onItemWChange(itemWidth);
        }}
        class="opacity-30 hover:opacity-100 
        bg-gray-50 border border-gray-300 text-gray-900 
          text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
          inline-flex w-[10rem] p-1 px-2 m-1 dark:bg-gray-700 dark:border-gray-600 
        dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
        dark:focus:border-blue-500"
      >
        {#each allSizes as size}
          <option value={size.value}>{size.label}</option>
        {/each}
      </select>
    </div>
    <div class="p-2">
      Trier par :
      <select
        bind:value={sortOrder}
        on:change={() => {
          console.debug("Will sort width : ", sortOrder);
        }}
        name="sortOrder"
        class="opacity-30 hover:opacity-100 
        bg-gray-50 border border-gray-300 text-gray-900 
          text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
          inline-flex w-[10rem] p-1 px-2 m-1 dark:bg-gray-700 dark:border-gray-600 
        dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
        dark:focus:border-blue-500"
      >
        <option value={null}>Chose a sort function</option>
        <option value={"byLabel"}>Par ordre alphabétique</option>
        <option value={"byTagsSimilarity"}>Par nombre de tags similaires</option
        >
      </select>
      <span>
        <label for="LimiteMax">Limite Max</label>
        <input
          class="text-black opacity-30 hover:opacity-100 w-[5rem]"
          bind:value={maxLimit}
          type="number"
          name="maxLimit"
          on:change={() => {
            // Since $: reactivity might be overloaded
            quickQualifTemplates = qualifTemplates.slice(0, maxLimit);
          }}
          on:keydown={(e) => {
            if ("Enter" == e.key) {
              // TODO : compatibility issues with other browsers than last chrome ?
              // https://stackoverflow.com/questions/2520650/how-do-you-clear-the-focus-in-javascript
              if (document.activeElement instanceof HTMLElement)
                document.activeElement.blur();
              e.target.blur();
            }
          }}
        />
      </span>
    </div>

    <ConfirmUpdateOrNew
      {syncQualifWithBackend}
      bind:this={confirmUpdateOrNew}
    />
  </div>
{/key}

<style>
  .classHovered {
    background-color: lightblue;
    color: white;
  }

</style>
