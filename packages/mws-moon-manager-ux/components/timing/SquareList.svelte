<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import SlotThumbnail from "./SlotThumbnail.svelte";
  import debounce from "lodash/debounce";
  //   import { initFlowbite } from 'flowbite';
  import { tick } from "svelte";
  import { Tooltip } from "flowbite";

  let classNames = "";
  export { classNames as class };
  export let style;
  export let timings;
  export let selectedSourceStamps = {};
  export let selectionStartIndex;
  export let lastSelectedIndex = 0;
  export let movePageIndex;
  export let isMobile;
  export let zoomStartRange = isMobile ? 37 : 15;
  export let zoomStartBaseSize = 5;
  export let zoomSquareRange = 5; // be square if lower than 50px
  export let listZoomRange = zoomStartRange;
  export let quickQualifTemplates;
  export let htmlRoot;
  // TIPS : opti by not following selection if list is hidden :
  export let followSelection = true;
  export let splitRange;
  export let computedSize;
  export let isFullScreen = false;

  $: zoomStartRange = isMobile ? 37 : 15;
  $: listZoomRange = zoomStartRange ? zoomStartRange : 5;

  // REAL screen width : (bigger than window)
  // let maxScreenW = window.screen.width;
  // https://stackoverflow.com/questions/3437786/get-the-size-of-the-screen-current-web-page-and-browser-window
  const doc = document,
    docElem = doc.documentElement,
    body = doc.getElementsByTagName("body")[0];
  // $: x = window.innerWidth || docElem.clientWidth || body.clientWidth;
  let x, maxScreenW;
  // y = win.innerHeight|| docElem.clientHeight|| body.clientHeight;
  // Is half screen
  // BAD idea, mulit refresh for nothing, debounce :
  // $: isMobile, x = window.innerWidth || docElem.clientWidth || body.clientWidth;
  // $: maxScreenW = isMobile ? x : (x ?? 0) * (100 - (splitRange ?? 0))/100;
  $: {
    // below is async ? TODO : ok with reactive refresh ?
    debounce(async () => {
      x = window.innerWidth || docElem.clientWidth || body.clientWidth;
      maxScreenW = isMobile ? x : (x ?? 0) * (100 - (splitRange ?? 0))/100;
    }, 300)();
  }

  $: console.debug("[timing/SquareList] Having timings :", timings);
  $: {
    const timingSlot = timings[lastSelectedIndex];
    console.debug(
      `[timing/SquareList] Selected timingSlot ${lastSelectedIndex} :`,
      timingSlot?.sourceStamp
    );
    selectedSourceStamps = {
      [timingSlot?.sourceStamp]: true,
    };
    if (undefined !== selectionStartIndex) {
      const syncStartIdx = lastSelectedIndex;
      let delta = selectionStartIndex - syncStartIdx;
      let step = delta > 0 ? -1 : 1;
      while (delta !== 0) {
        const timingTarget = timings[syncStartIdx + delta];
        selectedSourceStamps[timingTarget?.sourceStamp] = true;
        // console.log("Selection ok for " + timingTarget.sourceStamp);
        delta += step;
      }
    }
  }

  const Default = {
    placement: "top",
    triggerType: "hover",
    onShow: function () {},
    onHide: function () {},
    onToggle: function () {},
  };

  const refreshTooltips = () => {
    //   TODO : popover re-instanciation in one to one thumb scrolling
    // is slowing down everything due to some heavy scoll listeners
    // computes => Missing dispose or listeners cleanup for tooltip ?
    // For 120 items, will have 120 listeners for tooltip popover...
    return; // ignore refreshTooltips for square liste

    // let myDiv = getElementById("myDiv");
    // myDiv.querySelectorAll(":scope > .foo");
    // const tooltipElements = document.querySelectorAll(`[role="tooltip"]`);
    // const tooltipElements = htmlRoot?.querySelectorAll(`[role="tooltip"]`);
    // tooltipElements.forEach(t => {
    //   new Tooltip(t);
    // });

    // ./node_modules/flowbite/dist/flowbite.js:4269
    htmlRoot
      ?.querySelectorAll("[data-tooltip-target]")
      .forEach(function ($triggerEl) {
        var tooltipId = $triggerEl.getAttribute("data-tooltip-target");
        var $tooltipEl = document.getElementById(tooltipId);
        if ($tooltipEl) {
          var triggerType = $triggerEl.getAttribute("data-tooltip-trigger");
          var placement = $triggerEl.getAttribute("data-tooltip-placement");
          let t = $triggerEl.getAttribute('data-tooltip-ref');
            if (!t) {
              t = new Tooltip($tooltipEl, $triggerEl, {
                placement: placement ? placement : Default.placement,
                triggerType: triggerType
                    ? triggerType
                    : Default.triggerType,
              });
              $triggerEl.setAttribute('data-tooltip-ref', t);
              // https://github.com/themesberg/flowbite/issues/121
              // t.destroy(); ??
              // t.dispose(); ??
          }
        } else {
          console.error(
            'The tooltip element with id "'.concat(
              tooltipId,
              '" does not exist. Please check the data-tooltip-target attribute.'
            )
          );
        }
      });
  };

  export let thumbSize;
  $: thumbSize = (50 * (100 / zoomStartBaseSize) * listZoomRange) / 100;
  // TODO : opti, only for tooltips reloads...
  $: listZoomRange,
    debounce(async () => {
      // TIPS : tick() to wait for html changes
      await tick(); // First listZoomRange change to bigger size, no update otherwise (if test do Svelte rebuild ?)
      await tick();
      // initFlowbite();
      refreshTooltips();
    }, 300)();

  // TIPS : selectedSourceStamps MUST be an argument for
  //        svelte reactive to trigger...
  const isSlotSelected = (timingSlot, selectedSourceStamps) => {
    return selectedSourceStamps[timingSlot.sourceStamp] ?? false
      ? selectedSourceStamps[timingSlot.sourceStamp]
      : false;
  };

  const isKey = {
    up: (k) => k.keyCode == 38,
    down: (k) => k.keyCode == 40,
    right: (k) => k.keyCode == 39,
    left: (k) => k.keyCode == 37,
  };

  function onKeyDown(e) {
    // TODO : if other metaKey, etc... activated ?
    if (isKey.left(e) || isKey.up(e)) {
      lastSelectedIndex--; // TODO : if < 0 ? or elt exist ?
      if (e.shiftKey) {
        // Move last elt previous page :
        lastSelectedIndex = 123; // TODO : from CRM configs ...
        movePageIndex(-1);
      }
      e.preventDefault();
    }
    if (isKey.right(e) || isKey.down(e)) {
      if (e.shiftKey) {
        // Move 1st elt next page :
        lastSelectedIndex = 0;
        movePageIndex(1);
      }
      lastSelectedIndex++;
      e.preventDefault();
    }

    if (lastSelectedIndex < 0) {
      // lastSelectedIndex = timings.length - 1; // Loop
      lastSelectedIndex = 0; // Do not move
    } else if (lastSelectedIndex >= timings.length) {
      // lastSelectedIndex = 0; // Loop
      lastSelectedIndex = timings.length - 1; // Do not move
    }
  }
</script>

<!-- <svelte:window on:keydown|preventDefault={onKeyDown} /> -->
<svelte:window on:keydown={onKeyDown} />

<div
  bind:this={htmlRoot}
  class="mws-timing-square-list max-h-[100dvh]
  overflow-y-auto flex flex-wrap content-start justify-center {classNames}"
  {style}
>
  <!-- // TIPS :  justify-center will break image align if over sized -->
  <div
    class="overflow-x-auto max-w-[100dvw]
  flex flex-wrap content-start
  "
    class:justify-start={maxScreenW && computedSize > maxScreenW}
    class:justify-center={!maxScreenW || computedSize <= maxScreenW}
  >
    <!-- {computedSize} - {maxScreenW} - {splitRange} -->
    {#each timings ?? [] as timingSlot, idx}
      <SlotThumbnail
        bind:quickQualifTemplates
        bind:computedSize
        {followSelection}
        {timingSlot}
        size={`${thumbSize.toFixed(0)}px`}
        forceHeight={listZoomRange > zoomSquareRange ? "auto" : null}
        isSelected={isSlotSelected(timingSlot, selectedSourceStamps)}
        on:click={() => {
          lastSelectedIndex = idx;
          // TIPS : below not needed, since done with svelte reactive update of lastSelectedIndex
          // selectedSourceStamps[timingSlot.sourceStamp] =
          //   !selectedSourceStamps[timingSlot.sourceStamp];
          // selectedSourceStamps = {
          //   [timingSlot.sourceStamp]: true,
          // };
        }}
        on:dblclick={() => isFullScreen = true}
      />
    {/each}
  </div>

  <!-- TIPS : too slow to load lot of iùg, debounce to make it fluid -->
  <div class="flex items-start sticky bottom-0 z-30 w-full">
    <div class="fill-white/70 text-white/70 w-full">
      <input
        value={listZoomRange}
        on:change={debounce((e) => (listZoomRange = e.target.value), 400)}
        id="list-zoom-range"
        type="range"
        class="w-full h-2 bg-gray-200/50 rounded-lg
    appearance-none cursor-pointer outline-none
     "
      />
    </div>
  </div>
</div>
