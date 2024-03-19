<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import { tweened } from "svelte/motion";
  import ProgressIndicator from "../layout/widgets/ProgressIndicator.svelte";
  // import dayjs from "dayjs"; // TIPS : MODULE import will be useless if double import... ( not configured new instance)
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  import TagsInput from "./tags/TagsInput.svelte";
  import AddModal from "../message/AddModal.svelte";
  import _ from "lodash";

  import PhotoSwipeGallery from "svelte-photoswipe";
  import Loader from "../layout/widgets/Loader.svelte";
  import QuickList from "./qualifs/QuickList.svelte";
  import { draggable } from "svelte-agnostic-draggable";
  import mapTouchToMouseFor from "svelte-touch-to-mouse";
  import { onMount } from "svelte";
  import Base from "../layout/Base.svelte";

  // https://day.js.org/docs/en/timezone/set-default-timezone
  // https://day.js.org/docs/en/plugin/timezone
  var utc = require("dayjs/plugin/utc");
  var timezone = require("dayjs/plugin/timezone"); // dependent on utc plugin
  dayjs.extend(utc);
  dayjs.extend(timezone); // TODO : user config for self timezone... (slot is computed on UTC date...)
  // dayjs.tz.setDefault("Europe/Paris");
  dayjs.tz.setDefault("Europe/Paris");

  let classNames = "";
  export { classNames as class };
  export let timingSlot;
  export let isFullScreen = false;
  export let moveSelectedIndex;
  export let lastSelectedIndex = 0;
  export let timeQualifs = [];
  export let locale;
  export let isHeaderExpanded = false;
  export let fullscreenClass = "";
  export let allTagsList;
  export let slotHeader;
  export let slotView;
  export let zoomRange = 50;

  allTagsList = allTagsList ?? stateGet(get(state), "allTagsList");

  // Timer start time. Use it to ensure delay,
  // example : 507 page of 124 items
  //          => 10 minutes per page = 5070 minutes for all items
  //          = 5070/60 = 84.5 hours
  //          => SMIC fr 35hr/week
  //          = 84.5 / 35 = 2.4 week for SMIC peoples
  //
  // BUT ALL this is based on 10 minutes per page :
  //          => May be SMIC peoples will not have skills to qualify
  //          => 35hr/week with human life need time to go toilette etc...
  //          => 1 page is 124 items to qualify in 10 minutes
  //          = 124 / 10 = 12.4 items per minutes
  //          = 12.4 / 60 = 0.21 items per secondes
  //          => around 5 secondes to qualify one item...
  export let timerStart = 5;
  let timer = tweened(timerStart);

  let tInterval = null;
  const startTimer = () => {
    tInterval && clearInterval(tInterval);
    $timer = timerStart;
    tInterval = setInterval(() => {
      if ($timer > 0) {
        $timer--;
      } else {
        $timer = 0;
        clearInterval(tInterval);
      }
    }, 1000);
  };
  // startTimer();
  dayjs.locale("fr"); // Fr locale // TODO : global config instead of per module ?
  dayjs.tz.setDefault("Europe/Paris");

  // $: minutes = Math.floor($timer / 60);
  $: lastSelectedIndex, startTimer();

  let isLoading = false;
  const urlParams = new URLSearchParams(window.location.search);
  const pageNumber = urlParams.get("page") ?? "1";

  Number.prototype.toPrettyNum = function (this: Number, length: number) {
    var s = this;
    return s
      .toFixed(length)
      .replace(".", ",")
      .replace(/\B(?=(\d{3})+(?!\d))/g, " ");
  };

  declare interface Number {
    toPrettyNum(length: number): string;
  }

  const movePageIndex = (delta) => {
    const newPageNum = parseInt(pageNumber) + delta;
    // TODO : how to know max page num ? data.length / pageLimit, need to know details...
    urlParams.set("page", newPageNum < 1 ? 1 : newPageNum);
    window.location.search = urlParams;
  };

  const hackyRefresh = (data) => {
    // TODO : better sync all in-coming props from 'needSync' attr ?
    // TODO : + why not working simpliy with :
    // timingSlot?.maxPath = data.sync.maxPath;
    // timingSlot?.maxPriceTag = data.sync.maxPriceTag;
    // => might be related to bind:timeQualifs ? bind for update
    //    from view is wrong way ? always need to update :
    //      - self for self view
    //      - parent list to propagate outside (parent + children + etc...)

    // Hacky or regular solution ? :
    // TIPS : USING _.merge keep existing references and avoid
    // messing up Svelte reactivity like above ? (BUT OK for TagsInput component, why ?)
    if (timingSlot?.maxPath) {
      // Clean initial values 'inPlace' :
      // https://stackoverflow.com/questions/1232040/how-do-i-empty-an-array-in-javascript
      // timingSlot?.maxPath.length = 0;
      // https://stackoverflow.com/questions/19316857/removing-all-properties-from-a-object
      Object.keys(timingSlot?.maxPath).forEach(
        (key) => delete timingSlot.maxPath[key]
      );

      //   timingSlot?.maxPath = _.merge(timingSlot?.maxPath, data.sync.maxPath);
      //   timingSlot?.maxPriceTag = _.merge(
      //     timingSlot?.maxPriceTag,
      //     data.sync.maxPriceTag
      //   );
      //   // if (data.sync.maxPath) {
      //   //   _.merge(timingSlot?.maxPath, data.sync.maxPath);
      //   //   _.merge(timingSlot?.maxPriceTag, data.sync.maxPriceTag);
      //   // } else {
      //   //   timingSlot?.maxPath = {};
      //   //   timingSlot?.maxPriceTag = data.sync.maxPriceTag;
      //   // }
      // } else {
      //   timingSlot?.maxPath = data.sync.maxPath;
      //   timingSlot?.maxPriceTag = data.sync.maxPriceTag;
    }
    timingSlot?.maxPath = _.merge(timingSlot?.maxPath, data.sync.maxPath);
    timingSlot?.maxPriceTag = _.merge(
      timingSlot?.maxPriceTag,
      data.sync.maxPriceTag
    );
  };

  export let toggleQualif = async (qualif) => {
    isLoading = true;
    const data = {
      _csrf_token: stateGet(get(state), "csrfTimingToggleQualif"),
      timeSlotId: timingSlot?.id,
      qualifId: qualif.id,
    };
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_timing_qualif_toggle", {
        _locale: locale,
      }),
      {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        redirect: "error",
      }
    )
      .then(async (resp) => {
        console.log(resp.url, resp.ok, resp.status, resp.statusText);
        if (!resp.ok) {
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          const data = await resp.json();
          // const data = await resp.text();
          // console.debug("resp", data);
          timingSlot?.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          // // TODO : better sync all in-coming props from 'needSync' attr ?
          // timingSlot?.maxPath = data.sync.maxPath;
          // timingSlot?.maxPriceTag = data.sync.maxPriceTag;
          hackyRefresh(data);

          // TODO : NO reactivity for timingSlot?.maxPath ?
          //        => missing live price lookup update at top of SlotView when changing tags,
          //           but : ok on full page refresh...
          // timingSlot = {...timingSlot}; // TIPS : FORCE Svelte reactivity rebuild, since props check is not deep checked
          lastSelectedIndex = lastSelectedIndex; // Svelte reactive force reloads
          console.debug("Did toggle qualif, updated tags : ", timingSlot?.tags);
          stateUpdate(state, {
            csrfTimingToggleQualif: data.newCsrf,
          });
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
    isLoading = false;
  };

  export let removeAllTags = async () => {
    isLoading = true;
    const data = {
      _csrf_token: stateGet(get(state), "csrfTimingTagRemoveAll"),
      timeSlotId: timingSlot?.id,
    };
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_timing_tag_remove_all", {
        _locale: locale,
      }),
      {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        redirect: "error",
      }
    )
      .then(async (resp) => {
        console.log(resp.url, resp.ok, resp.status, resp.statusText);
        if (!resp.ok) {
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          const data = await resp.json();
          timingSlot?.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          // // TODO : better sync all in-coming props from 'needSync' attr ?
          // timingSlot?.maxPath = data.sync.maxPath;
          // timingSlot?.maxPriceTag = data.sync.maxPriceTag;
          hackyRefresh(data);

          lastSelectedIndex = lastSelectedIndex; // Svelte reactive force reloads
          console.debug("Did add tag", timingSlot?.tags);
          stateUpdate(state, {
            csrfTimingTagRemoveAll: data.newCsrf,
          });
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
    isLoading = false;
  };

  $: slotPath = timingSlot?.source?.path
    ? Routing.generate("mws_timing_fetchMediatUrl", {
        // encodeURI('file://' + timingSlot?.source.path)
        url: "file://" + timingSlot?.source.path,
        keepOriginalSize: true,
      })
    : null;

  let qualifTemplates = timeQualifs.map((q) => {
    q.toggleQualif = async () => {
      console.log("Toggle qualif " + q.label, q);
      // await q.timeTags.forEach(async t => {
      //   await addTag(t);
      // });
      await toggleQualif(q);
    };
    return q;
  });
  $: qualifShortcut = (qualifTemplates ?? {}).reduce(
    (acc, qt) => {
      acc[String.fromCharCode(qt.shortcut).charCodeAt(0)] = qt.toggleQualif;
      return acc;
    },
    {
      ["d".charCodeAt(0)]: removeAllTags,
    }
  );

  // TODO : refactor meanings : will add shortcut to 'GROUPS of Qualifs'
  // => allow end user to select shortcut himself
  // TODO : config backend for backup upload /download + save connected user shortcuts...

  const isKey = {
    space: (k) => k.keyCode == 32,
    return: (k) => k.keyCode == 13,
    zoomLower: (k) => k.key == "<",
    zoomHigher: (k) => k.key == ">",
    qualifShortcut: (k) => qualifShortcut[k.key.charCodeAt(0)] ?? null,
  };

  const onKeyDown = async (e) => {
    // console.debug("Key down : ", e);
    console.debug("Key down : ", e.code, e);
    if (isKey.space(e)) {
      isFullScreen = !isFullScreen;
      e.preventDefault();
    }
    const zoomStep = 5;
    if (isKey.zoomLower(e)) {
      zoomRange = zoomRange >= zoomStep ? zoomRange - zoomStep : zoomRange;
      e.preventDefault();
    }
    if (isKey.zoomHigher(e)) {
      zoomRange =
        zoomRange <= 100 - zoomStep ? zoomRange + zoomStep : zoomRange;
      e.preventDefault();
    }
    if (isKey.return(e)) {
      // isFullScreen = !isFullScreen; // TOGGLE QUALIF tags ?
      e.preventDefault();
    }
    if (isKey.qualifShortcut(e)) {
      await isKey.qualifShortcut(e)(); // TODO : should not block event flow ? no await ?
      e.preventDefault();
    }
  };

  let moveResp = moveSelectedIndex(0);
  // TIPS : in conjunction with tests on lastSelectedIndex, will refresh move position:
  $: lastSelectedIndex, (moveResp = moveSelectedIndex(0));

  // https://svelte.dev/repl/cfd1b8c9faf94ad5b7ca035a21f4dbd1?version=4.2.12
  mapTouchToMouseFor(".draggable");
  // compute from element size at onMount
  let Height, minHeight, maxHeight;
  let initialHeight, initialY;
  let resizing = false;

  // {left: 0, top: -28}left: 0top: -28[[Prototype]]: Object
  let avoidFirstStrangeTop = false; // In fullscreen, before move, wrong -20 position...

  function onDragMove(Event) {
    // https://stackoverflow.com/questions/5429827/how-can-i-prevent-text-element-selection-with-cursor-drag
    // Event.stopPropagation(); // Try to avoid text selections
    // Event.preventDefault();
    // This one is CUSTOM event, catch from parent is ok with :
    // on:mousedown|stopPropagation|preventDefault

    const deltaY = Event.detail.position.top - initialY;
    console.debug("Drag pos", Event.detail.position);
    if (avoidFirstStrangeTop) {
      avoidFirstStrangeTop = false;
      // Might not be a bug ? because my data go over screen height
      // and first height set move all pos ? (in fullscreen mode only)
      initialY = Event.detail.position.top;
      return;
    }
    console.debug("Drag to", deltaY, "from", initialHeight);
    if (resizing) {
      Height = Math.max(minHeight, Math.min(maxHeight, initialHeight + deltaY));
    } else {
      console.debug("Drag start", Event);
      initialY = Event.detail.position.top;
      // initialHeight = slotHeader.getBoundingClientRect().height; // slotHeader.offsetHeight;
      initialHeight = slotHeader.offsetHeight;
      console.debug("Init height", initialHeight);
      avoidFirstStrangeTop = true;
      resizing = true;
    }
  }
  function onDragStop(Event) {
    // // https://stackoverflow.com/questions/5429827/how-can-i-prevent-text-element-selection-with-cursor-drag
    // Event.stopPropagation(); // Try to avoid text selections (on:mousemove etc...)
    // Event.preventDefault();
    // This one is CUSTOM event, catch from parent is ok with :
    // on:mousedown|stopPropagation|preventDefault
    resizing = false;
    console.debug("Stop height", slotHeader.offsetHeight);
  }

  // compute from element size at onMount
  let slotHeight, slotMinHeight, slotMaxHeight;
  let slotInitialHeight, slotInitialY;
  let slotResizing = false;

  // {left: 0, top: -28}left: 0top: -28[[Prototype]]: Object
  let slotAvoidFirstStrangeTop = false; // In fullscreen, before move, wrong -20 position...

  const onSlotDragMove = (e) => {
    const deltaY = e.detail.position.top - slotInitialY;
    console.debug("Drag pos", e.detail.position);
    if (slotAvoidFirstStrangeTop) {
      slotAvoidFirstStrangeTop = false;
      // Might not be a bug ? because my data go over screen height
      // and first height set move all pos ? (in fullscreen mode only)
      slotInitialY = e.detail.position.top;
      return;
    }
    console.debug("Drag to", deltaY, "from", slotInitialHeight);
    if (slotResizing) {
      zoomRange = 50; // Only att range 50 for scrolls
      slotHeight = Math.max(
        slotMinHeight,
        Math.min(slotMaxHeight, slotInitialHeight + deltaY)
      );
    } else {
      console.debug("Drag start", e);
      slotInitialY = e.detail.position.top;
      // slotInitialHeight = slotView.getBoundingClientRect().height; // slotView.offsetHeight;
      slotInitialHeight = slotView.offsetHeight;
      console.debug("Init height", slotInitialHeight);
      slotAvoidFirstStrangeTop = true;
      slotResizing = true;
    }
  };
  const onSlotDragStop = (e) => {
    slotResizing = false;
    console.debug("Stop height", slotView.offsetHeight);
  };

  onMount(() => {
    // Height = slotHeader.offsetHeight; // TIPS : do not setup on mount, will have fixed size otherwise
    minHeight = 12;
    // TIPS : ok to go over screen size since we have multi-scrolls
    maxHeight = Infinity; // window.screen.height;

    // slotHeight = slotView.offsetHeight; // TIPS : do not setup on mount, will have fixed size otherwise
    slotMinHeight = 12;
    slotMaxHeight = Infinity; // window.screen.height;

    // TIPS : for computed height after css transformations :
    // Height = slotHeader.getBoundingClientRect().height;
    // DOMRect {
    //   bottom: 177,
    //   height: 54.7,
    //   left: 278.5,​
    //   right: 909.5,
    //   top: 122.3,
    //   width: 631,
    //   x: 278.5,
    //   y: 122.3,
    // }
  });

</script>

<svelte:window on:keydown={onKeyDown} />

<!-- class:opacity-80={isLoading}
style:opacity={isLoading ? 0.8 : 1} -->
<div
  class="mws-timing-slot-view overflow-y-auto
  flex flex-row flex-wrap content-start max-h-full
  {classNames}"
  class:pointer-events-none={isLoading}
  style::pointer-events={isLoading ? "none" : "auto"}
>
  <!-- {JSON.stringify(timingSlot)} -->
  <div class="pr-2">
    {dayjs(timingSlot?.sourceTimeGMT)
      .tz("Europe/Paris")
      .format("YYYY/MM/DD H:mm:ss")}
    <!-- // TODO : strange : tags are reactive, but not the maxPath etc ? -->
    <!-- {#if timingSlot?.tags?.length} -->
    <span
      class="border"
      class:border-gray-600={!timingSlot?.tags?.length}
      class:border-green-400={timingSlot?.tags?.length}
    >
      {timingSlot.maxPath?.maxValue ?? 0}/hr [{(timingSlot.maxPath &&
        (timingSlot.maxPath?.maxValue
          ? (timingSlot.maxPath.maxValue * 10) / 60
          : null
        )?.toPrettyNum(2)) ??
        "--"} €]
    </span>
    <!-- {/if} -->
  </div>
  <div>
    [{timingSlot?.rangeDayIdxBy10Min ?? "--"}]
    {timingSlot?.sourceStamp?.split("/").slice(-1) ?? "--"}
  </div>

  <!-- {timingSlot?.sourceStamp} -->
  <div
    class="full-screen-container bg-black text-white fill-white
    rounded-se-lg overflow-scroll
    {fullscreenClass}"
    class:fixed={isFullScreen}
    class:top-0={isFullScreen}
    class:bottom-0={isFullScreen}
    class:left-0={isFullScreen}
    class:right-0={isFullScreen}
  >
    <!-- <div class="max-h-[7rem] overflow-hidden
  hover:max-h-fit hover:overflow-scroll"> -->
    <div
      on:click|stopPropagation
      bind:this={slotHeader}
      class="overflow-scroll relative"
      class:h-[7rem]={!isHeaderExpanded}
      style={Height
        ? `
        height: ${Height}px;
      `
        : ""}
    >
      <!-- <span class="float-right right-0 top-0 m-1 sticky
    pointer-events-none opacity-75 hover:opacity-100"> -->
      <span
        class="float-right m-1 cursor-context-menu hover:opacity-90"
        on:click|stopPropagation={() => {
          Height = null;
          slotHeight = null;
          isHeaderExpanded = !isHeaderExpanded;
          zoomRange = 50;
        }}
      >
        <!-- TIPS : why $timer is tweended and will have FLOAT values : -->
        <!-- {$timer} -->
        <ProgressIndicator
          percent={1 - $timer / timerStart}
          textRenderer={(percent) => `${$timer.toFixed(0)}`}
        />
        <span class="flex w-[6em]">
          {dayjs(timingSlot?.sourceTimeGMT)
            .tz("Europe/Paris")
            .format("YYYY/MM/DD H:mm:ss")}
        </span>
      </span>
      <span class="float-right right-0 top-0 z-30 sticky">
        <span
          class="float-right right-0 top-0 w-[6em] sticky pointer-events-none"
        >
          [{pageNumber}-{lastSelectedIndex}]
        </span>
        {#if isFullScreen}
          <button
            class="float-right m-1 top-0 sticky"
            style:opacity={!moveResp.isLast ? 1 : 0.7}
            on:click|stopPropagation={() => (moveResp = moveSelectedIndex(1))}
          >
            Next.
          </button>
          {#if moveResp.isLast}
            <button
              class="float-right m-1 top-0 sticky"
              on:click|stopPropagation={() => movePageIndex(1)}
            >
              Next. Page
            </button>
          {/if}
          <button
            class="float-right m-1 top-0 sticky"
            style:opacity={!moveResp.isFirst ? 1 : 0.7}
            on:click|stopPropagation={() => (moveResp = moveSelectedIndex(-1))}
          >
            Prev.
          </button>
          {#if moveResp.isFirst && pageNumber > 1}
            <button
              class="float-right m-1 top-0 sticky"
              on:click|stopPropagation={() => movePageIndex(-1)}
            >
              Prev. Page
            </button>
          {/if}
        {/if}
      </span>
      <span
        class="float-right max-w-[75%] bg-black/80 rounded-md"
        class:!max-w-[50%]={isFullScreen}
        class:fixed={isFullScreen}
        class:bottom-0={isFullScreen}
        class:left-0={isFullScreen}
      >
        {#each timingSlot?.tags ?? [] as tag}
          <span
            class="m-1 text-white
          border-blue-600 border rounded-sm p-1"
          >
            {tag.label}
          </span>
        {/each}
      </span>
      <button
        class="bg-red-500 float-right m-2"
        on:click|stopPropagation={removeAllTags}
      >
        [d] Supprimer tous les tags
      </button>
      <span class="float-right flex flex-wrap justify-end p-2">
        <TagsInput bind:tags={timingSlot.tags} timing={timingSlot} {locale} />
      </span>

      <!-- {#each qualifTemplates ?? [] as qt, idx}
        <button
          class="float-right m-1"
          on:click|stopPropagation={qt.toggleQualif}
        >
          [{String.fromCharCode(qt.shortcut)}] {qt.label}
        </button>
      {/each} -->
      <QuickList
        {allTagsList}
        bind:isHeaderExpanded
        bind:qualifTemplates
        {locale}
      />

      <Loader {isLoading} />

      <!-- 
        class:hidden={!isHeaderExpanded}
      -->
      <!-- 
        TOO tricky with inner auto scroll at bottom ?
        <div class="draggable absolute bottom-0 left-0
       fill-white text-white bg-black/50 z-40 p-2"
      use:draggable={{
        helper:'clone', revert:true
      }}
        on:drag:move={onDragMove} on:drag:stop={onDragStop}
      >
        <svg class="w-7 h-7" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 20V7m0 13-4-4m4 4 4-4m4-12v13m0-13 4 4m-4-4-4 4"/>
        </svg>      
      </div>   -->
    </div>
    <!-- TODO : solve clone or elmt get crasy, for now hide to hide bug with :
    class:hidden={resizing} -->
    <div
      class="overflow-visible sticky top-0 h-[0px] flex items-end
    fill-white/70 text-white/70 bg-black/50 z-40"
      class:hidden={resizing}
      on:click|stopPropagation|preventDefault
      on:mousedown|stopPropagation|preventDefault
      on:mousemove|stopPropagation|preventDefault
      on:mouseup|stopPropagation|preventDefault
      on:touchstart|stopPropagation|preventDefault
      on:touchmove|stopPropagation|preventDefault
      on:touchend|stopPropagation|preventDefault
    >
      <div
        class="draggable"
        use:draggable={{
          helper: "clone", // TODO: clone is going faster than mouse on Y...?
          revert: true,
        }}
        on:drag:move={onDragMove}
        on:drag:stop={onDragStop}
        on:click|stopPropagation|preventDefault
      >
        <svg
          class="w-7 h-7"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 20V7m0 13-4-4m4 4 4-4m4-12v13m0-13 4 4m-4-4-4 4"
          />
        </svg>
      </div>
    </div>
    <!-- // TODO : remove max-h-[85%]={isFullScreen} + work with flex grow ?
    + https://stackoverflow.com/questions/15999760/load-image-asynchronous
    (but load this one first...)

      class:h-[95vh]={isFullScreen && isHeaderExpanded}
      class:mb-[1rem]={isFullScreen} // bp : will add double scroll

      // => not needed any more, use full width for image
      // user can resize part of screen if want to fit part of it...
      class:h-[95vh]={isFullScreen && isHeaderExpanded}

      use:panzoom={{ render, width: image.width, height: image.height }}
    -->
    <div class="overflow-visible flex items-end sticky top-0
    z-40 w-full">
      <div class="fill-white/70 text-white/70 bg-black/50 w-full">
        <input
          bind:value={zoomRange}
          id="zoom-range"
          type="range"
          class="w-full h-1 bg-gray-200/50 rounded-lg
          appearance-none cursor-pointer outline-none
           "
        />
      </div>
    </div>
    <object
      bind:this={slotView}
      on:click={() => (resizing ? null : (isFullScreen = !isFullScreen))}
      class="object-contain border-solid border-4 w-full"
      class:h-[80vh]={!slotHeight &&
        zoomRange == 50 &&
        isFullScreen &&
        !isHeaderExpanded}
      class:border-gray-600={!timingSlot?.tags?.length}
      class:border-green-400={timingSlot?.tags?.length}
      data={"screenshot" == timingSlot?.source?.type ? slotPath : ""}
      type="image/png"
      style={`
      ${
        slotHeight && zoomRange == 50
          ? `
          height: ${slotHeight}px;
        `
          : ""
      } /* transform: scale(${(2 * zoomRange) / 100}); */
      width: ${2 * zoomRange}%;
      `}
    >
      <img class="w-full" loading="eager" src={timingSlot.thumbnailJpeg} />
    </object>
    <div
      class="overflow-visible sticky top-0 flex items-end h-[0px]
    fill-white/70 text-white/70 bg-black/50 z-40"
      class:hidden={slotResizing}
      on:click|stopPropagation|preventDefault
      on:mousedown|stopPropagation|preventDefault
      on:mousemove|stopPropagation|preventDefault
      on:mouseup|stopPropagation|preventDefault
      on:touchstart|stopPropagation|preventDefault
      on:touchmove|stopPropagation|preventDefault
      on:touchend|stopPropagation|preventDefault
    >
      <div
        class="draggable"
        use:draggable={{
          helper: "clone", // TODO: clone is going faster than mouse on Y...?
          revert: true,
        }}
        on:drag:move={onSlotDragMove}
        on:drag:stop={onSlotDragStop}
        on:click|stopPropagation|preventDefault
      >
        <svg
          class="w-7 h-7"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 20V7m0 13-4-4m4 4 4-4m4-12v13m0-13 4 4m-4-4-4 4"
          />
        </svg>
      </div>
    </div>
    <div class="overflow-visible flex items-end
    z-40 w-full">
      <div class="fill-white text-white bg-black w-full">
        <label for="default-range" class="block mb-2 text-sm font-medium"
          >Zoom range {zoomRange}</label
        >
      </div>
    </div>

    <!-- TIPS : add bottom margin to allow height size increase
     (other wise, no bottom space to scroll up to...) -->
    <div class:mb-[5rem]={isHeaderExpanded} />

    <!-- <div
    class="object-contain border-solid border-4"
    class:w-full={isFullScreen}
    class:max-h-[85%]={isFullScreen && !isHeaderExpanded}
    class:border-gray-600={!timingSlot?.tags?.length}
    class:border-green-400={timingSlot?.tags?.length}
    >
      <!-- https://mvolfik.github.io/svelte-photoswipe/ -- >
      <PhotoSwipeGallery images={[
        {
          src: "screenshot" == timingSlot?.source?.type ? slotPath : "",
          // width: 3000,
          // height: 4000,
          alt: "Screenshot", // optional
          cropped: false, // optional, default=false; see https://photoswipe.com/v5/docs/ 
          // thumbnail: { src: slotPath, width: 300, height: 400 },
        }
      ]} styling="flex" />    
    </div> -->
  </div>
</div>

<!-- <styles>
  object {
    transition: transform .2s;
  }
</styles> -->
