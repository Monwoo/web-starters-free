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
  const startZoom = 5;
  export let zoomRange = startZoom;
  export let quickQualifTemplates;
  export let htmlRoot;
  // TIPS : opti by not following selection if list is hidden :
  export let followSelection = true;

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
          new Tooltip($tooltipEl, $triggerEl, {
            placement: placement ? placement : Default.placement,
            triggerType: triggerType ? triggerType : Default.triggerType,
          });
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
  $: thumbSize = (50 * (100 / startZoom) * zoomRange) / 100;
  // TODO : opti, only for tooltips reloads...
  $: zoomRange,
    (async () => {
      // TIPS : tick() to wait for html changes
      await tick(); // First zoomRange change to bigger size, no update otherwise (if test do Svelte rebuild ?)
      await tick();
      // initFlowbite();
      refreshTooltips();
    })();

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
  class="mws-timing-square-list max-h-[80dvh]
  overflow-y-auto flex flex-wrap content-start justify-center {classNames}"
  {style}
>
  {#each timings ?? [] as timingSlot, idx}
    <SlotThumbnail
      bind:quickQualifTemplates
      {followSelection}
      {timingSlot}
      size={`${thumbSize.toFixed(0)}px`}
      forceHeight={zoomRange > startZoom ? "auto" : null}
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
    />
  {/each}

  <!-- TIPS : too slow to load lot of iùg, debounce to make it fluid -->
  <div class="flex items-start sticky bottom-0 z-30 w-full">
    <div class="fill-white/70 text-white/70 w-full">
      <input
        value={zoomRange}
        on:change={debounce((e) => (zoomRange = e.target.value), 400)}
        id="list-zoom-range"
        type="range"
        class="w-full h-2 bg-gray-200/50 rounded-lg
    appearance-none cursor-pointer outline-none
     "
      />
    </div>
  </div>
</div>
