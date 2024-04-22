<script context="module" lang="ts">
  import _ from "lodash";
  // https://reallifeglobal.com/history-story/
  // History: uncountable, more factual, non-fiction, academic, it really happened.
  // Story: Imaginary, fiction, narrative, it often didn't really happen. 
  // But there are moments where history, which is not countable,
  // and story, which is countable, actually can be the same thing,
  // and this is when there are anecdotes, anecdotes or biographies,
  // documentaries, or historical movies.
  // https://grammarbrain.com/plural-of-history/
  // TODO : sync server side inside connected user configs ?
  // let qualifHistories = {
  //   indexByTimingId : {},
  //   stack: [],
  // };

  // const getQualifHistories = async () => {
  //   return qualifHistories;
  // }
  // const updateQualifHistories = async (newHistories) => {
  //   qualifHistories = newHistories;
  // }

  // export type History = {
  //   // Not exact replay, replay to another timing :
  //   replay: (timing:any) => void,
  //   // ordered list of actions to replay
  //   actions: ((timing) => void)[],
  // }

  // export const historyInit:History = {
  //   replay: (timing) => {
  //     this.actions.forEach(a => {
  //       await a(timing);
  //     });
  //   },
  //   actions: [],
  // }

  export class History {
    constructor(
      public label = null,
      protected actions = []
    ) {
    }

    public replay = async (timing, lastSelectedIndex, timings, selectionStartIndex) => {
      // TODO : advanced history without timing target ?
      !timing && console.warn("Missing timing for history")
      for (const a of this.actions ?? []) {
        await a(timing, lastSelectedIndex, timings, selectionStartIndex);
      }
    }

    toData() {
      return {
        ...this,
        actions: this.actions.map(a => a.toData ? a.toData() : a),
      };
    }
    fromData(data) {
      if (!data) return this;
      // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/fromEntries
      // const o = new RemoveTagCallable();
      // TODO : type cast not enough for all usecase ?
      // Object.fromEntries(data) as RemoveTagCallable;
      
      // const newEntries = Object.fromEntries(data);
      const newEntries = data;
      _.merge(this, newEntries, {
        actions: newEntries.actions?.map(a => this.dataAsAction(a)),
      });
      return this;
    }

    dataAsAction(dataEntries) {
      // https://dev.to/alesm0101/how-to-check-if-a-value-is-an-object-in-javascript-3pin
      // const isObject = (value) => {
      //   return typeof value === 'object'
      //   && value !== null
      //   && !Array.isArray(value)
      //   && !(value instanceof RegExp)
      //   && !(value instanceof Date)
      //   && !(value instanceof Set)
      //   && !(value instanceof Map)
      // }
      // Already translated :
      if (dataEntries instanceof Function) return dataEntries;

      let data = Object.fromEntries(dataEntries);
      if ('RemoveTagCallable' === data.actionName) {
        data = new RemoveTagCallable();
        // TODO : ok or over opti to avoid double ' Object.fromEntries' extractions
        data.fromData(dataEntries);
      }
      if ('AddTagCallable' === data.actionName) {
        data = new AddTagCallable();
        // TODO : ok or over opti to avoid double ' Object.fromEntries' extractions
        data.fromData(dataEntries);
      }
      
      return data;
    }

  }
  export const emptyHistories = {
    indexByLabel : {} as any,
    maxSize: 7,
    stack: [
      // new History("Test", [async(t) => alert('Test ok')]),
    ] as History[],
  };

  // TODO : load from user configs and sync :
  //        cf mws_config_user_sync + serialize actions...
  export const qualifHistories = writable(emptyHistories);

  export const actionRepository = {
    toString: {},
    toCallable: {},
  } // TODO : init action repository, sound like redux, time to migrate to strong redux model ?

  const actionToString = (a) => { 
    // a.actions = a.actions.map(a => a.toData ? a.toData() : a);
    return a.toData ? a.toData() : a;
    // return actionRepository.toString[a] ?? a;
  };

  export const syncHistoryWithBackend = async (histories, locale) => {
    const data = {
      _csrf_token: stateGet(get(state), "csrfConfigUserSync"),
      config: JSON.stringify({
        timingHistories: {
          indexByLabel: histories.indexByLabel,
          maxSize: histories.maxSize,
          // https://stackoverflow.com/questions/7395686/how-can-i-serialize-a-function-in-javascript
          // stack: histories.stack.map(a => a.toString()), // NOP : with compile stuff, give [Object Object] string instead...
          stack: histories.stack.map(a => actionToString(a)),
        },
      }),
    };
    let headers = {};
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_config_user_sync", {
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
        console.log(resp.url, resp.ok, resp.status, resp.statusText);
        if (!resp.ok) {
          throw new Error("Not 2xx response");
        } else {
          const data = await resp.json();

          // histories = data.sync?.timingHistories
          stateUpdate(state, {
            csrfConfigUserSync: data.newCsrf,
          });
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        // const shouldWait = confirm("Echec de l'enregistrement.");
      });
    return histories;
  };
  
  export const addHistory = async (h:History) => {
    const histories = get(qualifHistories);
    // TODO : opti : use inversed list and histories.indexByLabel will not move on new history inputs...
    const historyIdx = histories.indexByLabel[h.label] ?? null;

    if (null !== historyIdx) {
      histories.stack
      .splice(historyIdx, 1); // IN place update
      // histories.stack = histories.stack
      // .toSpliced(historyIdx, 1); // copy update
      delete histories.indexByLabel[h.label];
    }

    histories.stack.unshift(h);
    histories.stack = histories.stack
    .slice(0, histories.maxSize);

    histories.indexByLabel = histories.stack
    .reduce((acc, h, idx) => {
      acc[h.label] = idx;
      return acc;
    }, {});

    const locale = 'fr'; // TODO : from redux store or other ways ? (as param is heavy to put every where ? but good for separations and storybook testings ?)
    const historiesSync = await syncHistoryWithBackend(histories, locale);

    qualifHistories.set(historiesSync);
  };

  export const loadHistoriesFromUser = async (user) => {
    // Deserialize user config save :
    if(!user) {
      return;
    }
    const userHistories = user.config?.timingHistories ?? emptyHistories;
    console.debug("loadHistoriesFromUser", user, userHistories);
    userHistories.stack = userHistories.stack.map(
      h => (new History()).fromData(h)
    )
    qualifHistories.set(userHistories);
  }

</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  import Routing from "fos-router";
  import {
    state,
    stateGet,
    stateUpdate,
  } from "../../../stores/reduxStorage.mjs";
  import { get, writable } from "svelte/store";
  import { MoveIcon, SortableItem } from "svelte-sortable-items";
  import { flip } from "svelte/animate";
  import ItemView from "./ItemView.svelte";
  import ConfirmUpdateOrNew from "./ConfirmUpdateOrNew.svelte";
  import ExportQualifs from "./ExportQualifs.svelte";
  import debounce from 'lodash/debounce';
  import KeyboardShortcutModal from "./KeyboardShortcutModal.svelte";
  import TailwindDefaults from "../../layout/widgets/TailwindDefaults.svelte";
  import ItemHistory from "./ItemHistory.svelte";
  import { AddTagCallable, RemoveTagCallable } from "../tags/TagsInput.svelte";

  // import "svelte-drag-drop-touch/dist/svelte-drag-drop-touch";
  // require("svelte-drag-drop-touch");

  // TIPS : avoid concurency infinit loop refresh with debounce :
  // 400ms for user input debounce
  export let userDelay = 300;

  export let locale;
  export let isHeaderExpanded = false;
  export let confirmUpdateOrNew;
  export let keyboardShortcutModal;
  export let showColors = false;

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
  const userConfig = stateGet(get(state), "userConfig");
  export let timingQualifConfig = userConfig?.timing?.quickQualif ?? {};
  export let maxLimit = timingQualifConfig?.maxLimit ?? 12;
  export let itemWidth = timingQualifConfig?.itemWidth ?? defaultItemW;
  export let timingSlot;
  export let lastSelectedIndex;
  export let timings;
  export let selectionStartIndex;

  // TIPS : do not refresh sortOrder to avoid infinit loop
  // since will trigger list sort when list might be in custom user mode
  const quickQLookupInit = Object.values(timingQualifConfig?.list ?? {})
  .reduce((acc, qLabel, idx) => {
    acc[qLabel] = idx;
    return acc;
  }, {});
  quickQualifTemplates = Object.values(qualifTemplates?.reduce((acc, q) => {
    // .filter((q) => q.label in quickQLookupInit) // Will not keep order...
    if (q.label in quickQLookupInit) {
      acc[quickQLookupInit[q.label]] = q;
    }
    return acc;
  }, []));

  export let sortOrder = null; // timingQualifConfig?.sortOrder;

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

  let configDidChange = false;
  let lastDataset = {};
  // const configDidChange = false;
  // TODO : opti with event system better than ordered arrays parses ?
  const arrObjComp = (arr1, arr2) => {
    let succed = true;
    // TIPS : only check for ID or Label changes,
    //        other props are saved with another Api for qualif sync
    for (let idx = 0; idx < (arr1?.length ?? 0); idx++) {
      const e1 = arr1[idx];
      const e2 = arr2[idx] ?? undefined;
      if (
        // e1.id !== e2?.id
        // ! _.isEqual(e1, e2) // 1st level compare, then refs...
        e1?.id !== e2?.id ||
        e1?.label !== e2?.label
      ) {
        succed = false;
        break;
      }
    }

    // // TIPS : deep compare, will update on colors changes too :
    // // arr1: JSON.stringify(arr1 ?? [])
    // arr2 = JSON.stringify(arr2 ?? []);
    // succed = arr1 === arr2;
    return succed;
  };
  $: {
    console.debug("Will check timing config sync");
    if (!qualifTemplates || qualifTemplates.length < 1) {
      qualifTemplates = [{
        label: '+',
        useForQualifAdd: true,
      }];
    }
  }
  $: {
    console.debug("Will check timing config sync");
    // if (!qualifTemplates || qualifTemplates.length < 1) {
    //   qualifTemplates = [{ // TIPS : Cycle detection if done this place...
    //     label: null
    //   }];
    // }

    // console.debug("Will check timing config sync", lastDataset.quickQualifTemplates, quickQualifTemplates);

    // TODO : $$props not covering all props, need to encapsulate ?
    // configDidChange = Object.keys(lastDataset).reduce((acc, k) => {
    //   return lastDataset[k] !== $$props[k] || acc;
    //   // return lastDataset[k] !== $$restProps[k] || acc;
    // }, !Object.keys(lastDataset).length)
    configDidChange =
      // TODO : deep detect changes ? or notif system ?
      // lastDataset.quickQualifTemplates != quickQualifTemplates
      // || lastDataset.timingQualifConfig != timingQualifConfig // Will detect change if ref change not the content...
      // lastDataset.quickQualifTemplates?.length != quickQualifTemplates?.length
      !arrObjComp(lastDataset.quickQualifTemplates, quickQualifTemplates) ||
      // || lastDataset.timingQualifConfig != timingQualifConfig
      lastDataset.maxLimit != maxLimit ||
      lastDataset.itemWidth != itemWidth ||
      lastDataset.sortOrder != sortOrder;
    if (configDidChange) {
      console.debug(
        "Qualif templates will sync update :",
        quickQualifTemplates
      );
      // Lookup to ensure reactivity on below changes : (Svelte do not do deep detect...)
      timingQualifConfig,
        maxLimit,
        itemWidth,
        sortOrder,
        syncQualifConfigWithBackend();
      configDidChange = false; // NEEDED performance, can't call network each 10 milliseconds...
      lastDataset = {
        // Do not keep same ref or svelte reactivity will update values
        // only array name and order changes :
        quickQualifTemplates: quickQualifTemplates?.slice().map((q) => ({
          // ...q,
          // Keep copy of needed compare elt
          id: q.id,
          label: q.label,
        })),
        // TIPS : DEEP test
        // quickQualifTemplates: JSON.stringify(quickQualifTemplates ?? []),
        maxLimit,
        itemWidth,
        sortOrder,
      };
      // TIPS : force quickQualifTemplates refresh for parend binded value to refresh :
      quickQualifTemplates = quickQualifTemplates;
    }
  }

  // // from all crm ordered templates
  // quickQualifTemplates = qualifTemplates.slice(0, maxLimit);
  $: {
    // on timingQualifConfig, update inner props :
    if (timingQualifConfig) {
      console.debug("Update quicklist from config");
      // .list is the old one to update...
      // let quickQLookup = timingQualifConfig?.list?.reduce((acc, qLabel, idx) => {
      let quickQLookup = quickQualifTemplates?.reduce((acc, q, idx) => {
        acc[q.label] = idx;
        return acc;
      }, {});
      // let historyQualif = ([...quickQualifTemplates ?? []])
      let historyQualif = quickQualifTemplates?.reduce((acc, q) => {
        // .filter((q) => q.label in quickQLookup) // Will not keep order...
        if (q.label in quickQLookup) {
          acc[quickQLookup[q.label]] = q;
        }
        return acc;
      }, []);
      quickQualifTemplates = Object.values((
        historyQualif?.length
          ? historyQualif
          : quickQualifTemplates?.length
          ? quickQualifTemplates
          : qualifTemplates
      ).slice(0, maxLimit));
      maxLimit = timingQualifConfig?.maxLimit;
      itemWidth = timingQualifConfig?.itemWidth;
      // TIPS : do not refresh sortOrder to avoid infinit loop
      // since will trigger list sort when list might be in custom user mode
      // sortOrder = timingQualifConfig?.sortOrder;

      // set null to refresh only on next config set
      // allowing regular behavior
      // if not in setup config mode '! timingQualifConfig'
      timingQualifConfig = null;
    }
  }
  // TIPS : opti : could also get it from store with 'userConfig' ?
  // syncQualifConfigWithBackend(); // will update timingQualifConfig
  // // Already setupWith :
  // export let timingQualifConfig = userConfig?.timing?.quickQualif ?? {};

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
          (a.timeTags?.length ?? 0) -
          nbSimilar -
          ((b.timeTags?.length ?? 0) - nbSimilar)
        );
      });
      // TODO : trying to keep qualifTemplates in sync
      //      but lot of action may have change quickQualifTemplates ?
      qualifTemplates.sort(function (a, b) {
        const similar = intersect(a.timeTags, b.timeTags);
        const nbSimilar = similar.length;
        return -(
          (a.timeTags?.length ?? 0) -
          nbSimilar -
          ((b.timeTags?.length ?? 0) - nbSimilar)
        );
      });
      quickQualifTemplates = quickQualifTemplates;
      sortOrder = null;
    }
  }

  let needRefresh;
  export const syncQualifWithBackend = async (qualif) => {
    if (qualif?.useForQualifAdd) {
      return qualif; // Ignore sync for useForQualifAdd, will only ADD...
    }
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
        console.log(resp.url, resp.ok, resp.status, resp.statusText);
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
          // qualif.timeTags = []; // Reset list to ensure clean merge // TODO : review is _.merge is ok or use way to reset lists ?
          // qualif = _.merge({}, qualif, data.sync);
          // _.merge(qualif, data.sync);
          const newQualif = {
            ...qualif,
            ...data.sync,
          }
          
          // TODO : more sync ? other side effect updates ? 
          // Refacor all call to update from resonse instead of inside custom stuff ??
          // ++ USE Redux EFFECTs etc, will remove all those 
          //    kind of side effects hacky codes :
          qualif.timeTags = data.sync.timeTags;
          if (data.didDelete) {
            quickQualifTemplates = quickQualifTemplates.filter(
              (q) => q.id !== qualif.id
            );
            qualifTemplates = qualifTemplates.filter((q) => q.id !== qualif.id);
          }

          stateUpdate(state, {
            csrfTimingQualifSync: data.newCsrf,
          });
          return newQualif;
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
        return null
      });
    return resp;
  };

  export const syncQualifConfigWithBackend = async () => {
    timingQualifConfig = {};
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
        console.log(resp.url, resp.ok, resp.status, resp.statusText);
        if (!resp.ok) {
          // make the promise be rejected if we didn't get a 2xx response
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          // got the desired response
          const data = await resp.json();

          timingQualifConfig = data.sync?.timing?.quickQualif;

          stateUpdate(state, {
            csrfTimingQualifConfigSync: data.newCsrf,
            // TODO : full configs set or partial Merge or property path setter ?
            userConfig: data.sync,
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

  $: loadHistoriesFromUser($state.user);

</script>

<!-- <svelte:head>
  <!-- MAKE IT WORK ON MOBILE DEVICES -- >
  <script src="https://unpkg.com/svelte-drag-drop-touch"></script>
  <!---- >
</svelte:head> -->
{#key needRefresh}
  <div class="float-right w-full flex flex-row flex-wrap">
    <ConfirmUpdateOrNew
      {syncQualifWithBackend}
      bind:this={confirmUpdateOrNew}
    />
    <KeyboardShortcutModal
      {syncQualifWithBackend}
      bind:this={keyboardShortcutModal}
    />
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
                bind:showColors
                {syncQualifWithBackend}
                {locale}
                {confirmUpdateOrNew}
                {keyboardShortcutModal}
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
    <div class="flex w-full flex-wrap justify-evenly">
      {#each $qualifHistories.stack as history }
        <ItemHistory
          {history}
          bind:timingSlot
          bind:lastSelectedIndex
          bind:timings
          {selectionStartIndex}
          {syncQualifWithBackend}
          {locale}
        />
      {/each}
    </div>
    <div class="p-2">
      Taille de liste :
      <select
        name="itemWidth"
        value={itemWidth}
        on:change={debounce(async (e) => {
          itemWidth = e.target.value;
          console.debug("New item width : ", itemWidth);
          // await onItemWChange(itemWidth);
        }, userDelay)}
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
        value={sortOrder}
        on:change={debounce(async (e) => {
          sortOrder = e.target.value;
          console.debug("Will sort width : ", sortOrder);
        }, userDelay)}
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
      <!-- TIPS : protect from root keydown catches :
        |preventDefault is too much, prevent input types too ?
      on:keydown|stopPropagation|preventDefault -->
      <span
      on:keydown|stopPropagation
      >
        <label for="LimiteMax">Limite Max</label>
        <!-- TIPS : protect from root keydown catches :
        on:keydown|stopPropagation|preventDefault 
        BUT : NOT ON INPUT : will catch keyboard key for input too otherwise -->
        <input
          class="text-black opacity-30 hover:opacity-100 w-[5rem]"
          value={maxLimit}
          type="number"
          name="maxLimit"
          on:change={debounce(async (e) => {
            // bind:value={maxLimit} // BIND will BREAK debounce,
            //           svelte reactivity will flow instead
            // Since $: reactivity might be overloaded
            // quickQualifTemplates = qualifTemplates.slice(0, maxLimit);
            maxLimit = e.target.valueAsNumber;
            if (
              maxLimit > quickQualifTemplates.length &&
              qualifTemplates.length > quickQualifTemplates.length
            ) {
              quickQualifTemplates = quickQualifTemplates.concat(
                qualifTemplates.filter(
                  (q) => q &&
                    quickQualifTemplates.filter((qq) => qq.label === q.label)
                      .length === 0
                )
              );
            }
            quickQualifTemplates = quickQualifTemplates.slice(0, maxLimit);
          }, userDelay * 4)}
          on:keydown={debounce(async (e) => {
            if ("Enter" == e.key) {
              // TODO : compatibility issues with other browsers than last chrome ?
              // https://stackoverflow.com/questions/2520650/how-do-you-clear-the-focus-in-javascript
              if (document.activeElement instanceof HTMLElement)
                document.activeElement.blur();
              e.target.blur();
            }
          }, userDelay)}
        />
      </span>
    </div>

    <!-- // TODO : if expanded, add import/export user timing config history
    Ok for now, sqlite DB backup or easy to redo with full qualif bckup...
    
    // TODO : link user maps to qualif or useless mapping ?
    // TODO : modal should be loaded by services and transfered by redux store..
    this z-index will not go over parent(s) z-index...
    -->
    <ExportQualifs {locale} />
    <div class="fixed top-0 bottom-0 left-0 right-0
      overflow-auto
      flex flex-wrap justify-center items-center
      touch-auto text-black z-[99]
    "
    class:hidden={!showColors}
    on:dblclick|stopPropagation={() => showColors = false}
    >
      <TailwindDefaults></TailwindDefaults>
    </div>
  </div>
{/key}

<style>
  .classHovered {
    background-color: lightblue;
    color: white;
  }

</style>
