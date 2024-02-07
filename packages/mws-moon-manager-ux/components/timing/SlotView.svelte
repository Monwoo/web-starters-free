<script context="module">
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  dayjs.locale("fr"); // Fr locale // TODO : global config instead of per module ?

</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";

  let classNames = "";
  export { classNames as class };
  export let timingSlot;
  export let isFullScreen = false;

  $: slotPath = timingSlot?.source?.path
    ? Routing.generate("mws_timing_fetchMediatUrl", {
        // encodeURI('file://' + timingSlot.source.path)
        url: "file://" + timingSlot.source.path,
      })
    : null;

</script>

<div
  class="mws-timing-slot-view overflow-y-auto
  flex flex-row flex-wrap content-start
  {classNames}"
>
  <!-- {JSON.stringify(timingSlot)} -->
  <div>
    {dayjs(timingSlot?.sourceTime).format("YYYY/MM/DD h:mm")}
  </div>
  <!-- {timingSlot?.sourceStamp} -->
  <div
    on:click={() => (isFullScreen = !isFullScreen)}
    class="full-screen-container bg-black"
    class:fixed={isFullScreen}
    class:top-0={isFullScreen}
    class:bottom-0={isFullScreen}
    class:left-0={isFullScreen}
    class:right-0={isFullScreen}
  >
    <img
      class="object-contain"
      class:w-full={isFullScreen}
      class:h-full={isFullScreen}
      src={"screenshot" == timingSlot?.source?.type ? slotPath : ""}
    />
  </div>
</div>
