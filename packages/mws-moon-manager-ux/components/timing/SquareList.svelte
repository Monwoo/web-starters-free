<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import SlotThumbnail from "./SlotThumbnail.svelte";

  let classNames = "";
  export { classNames as class };
  export let timings;
  export let selectedSourceStamps = {};

  $: console.debug("[timing/SquareList] Having timings :", timings);


  // TIPS : selectedSourceStamps MUST be an argument for
  //        svelte reactive to trigger...
  const isSlotSelected = (timingSlot, selectedSourceStamps) => {
    return (selectedSourceStamps[timingSlot.sourceStamp] ?? false)
    ? selectedSourceStamps[timingSlot.sourceStamp]
    : false;
  };
</script>

<div class="mws-timing-square-list
overflow-y-auto flex {classNames}">
  {#each timings ?? [] as timingSlot}
    <SlotThumbnail
      {timingSlot}
      isSelected={isSlotSelected(timingSlot, selectedSourceStamps)}
      on:click={() => {
        selectedSourceStamps[timingSlot.sourceStamp]
        = !selectedSourceStamps[timingSlot.sourceStamp];
      }}
    />
  {/each}
</div>
