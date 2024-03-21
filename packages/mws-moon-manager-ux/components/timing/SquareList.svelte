<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import SlotThumbnail from "./SlotThumbnail.svelte";
  import debounce from 'lodash/debounce';

  let classNames = "";
  export { classNames as class };
  export let style;
  export let timings;
  export let selectedSourceStamps = {};
  export let lastSelectedIndex = 0;
  export let movePageIndex;
  const startZoom = 5;
  export let zoomRange = startZoom;

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
  }

  export let thumbSize;
  $: thumbSize = ((50 * (100 / startZoom) * zoomRange) / 100);

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
  class="mws-timing-square-list max-h-[80vh]
overflow-y-auto flex flex-wrap content-start justify-center {classNames}"
  {style}  
>
  {#each timings ?? [] as timingSlot, idx}
    <SlotThumbnail
      {timingSlot}
      size={`${thumbSize.toFixed(0)}px`}
      forceHeight={(zoomRange > startZoom) ? 'auto' : null}
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
        on:change={debounce((e)=> (zoomRange = e.target.value), 400)}
        id="list-zoom-range"
        type="range"
        class="w-full h-2 bg-gray-200/50 rounded-lg
    appearance-none cursor-pointer outline-none
     "
      />
    </div>
  </div>
</div>
