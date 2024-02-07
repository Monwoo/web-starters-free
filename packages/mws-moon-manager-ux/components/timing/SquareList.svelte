<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import SlotThumbnail from "./SlotThumbnail.svelte";

  let classNames = "";
  export { classNames as class };
  export let timings;
  export let selectedSourceStamps = {};
  export let lastSelectedIndex = 0;

  $: console.debug("[timing/SquareList] Having timings :", timings);
  $: {
    const timingSlot = timings[lastSelectedIndex];
    console.debug(
      `[timing/SquareList] Selected timingSlot ${lastSelectedIndex} :`,
      timingSlot
    );
    selectedSourceStamps = {
      [timingSlot?.sourceStamp]: true,
    };
  }

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
      lastSelectedIndex--;
      e.preventDefault();
    }
    if (isKey.right(e) || isKey.down(e)) {
      lastSelectedIndex++;
      e.preventDefault();
    }

    if (lastSelectedIndex < 0) {
      lastSelectedIndex = timings.length - 1;
    } else if (lastSelectedIndex >= timings.length) {
      lastSelectedIndex = 0;
    }
  }

</script>

<!-- <svelte:window on:keydown|preventDefault={onKeyDown} /> -->
<svelte:window on:keydown={onKeyDown} />

<div class="mws-timing-square-list
overflow-y-auto flex flex-wrap content-start items-start {classNames}">
  {#each timings ?? [] as timingSlot, idx}
    <SlotThumbnail
      {timingSlot}
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
</div>
