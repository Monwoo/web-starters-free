<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";

  export let timingSlot;
  export let isSelected = false;
  $: slotName = timingSlot.sourceStamp.split(/[\\/]/).pop();
  $: slotPath = timingSlot.source?.path
    ? Routing.generate("mws_timing_fetchMediatUrl", {
        // encodeURI('file://' + timingSlot.source.path)
        url: "file://" + timingSlot.source.path,
      })
    : null;

</script>

<!-- {JSON.stringify(timingSlot)} -->
<div
  class="mws-timing-slot w-[50px] h-[50px]
flex justify-center items-center
overflow-hidden border-solid border-4"
  class:border-gray-600={!isSelected}
  class:border-blue-600={isSelected}
  on:click={() => {
    isSelected = true;
  }}
>
  <img
    class="object-contain"
    src={"screenshot" == timingSlot.source?.type ? slotPath : ""}
  />
  <!-- <span>{slotName}</span> -->
</div>
