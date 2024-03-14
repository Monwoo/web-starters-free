<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";

  export let timingSlot;
  export let isSelected = false;
  export let size = "50px";
  $: slotName = timingSlot.sourceStamp.split(/[\\/]/).pop();
  $: slotPath = timingSlot.source?.path
    ? Routing.generate("mws_timing_fetchMediatUrl", {
        // encodeURI('file://' + timingSlot.source.path)
        url: "file://" + timingSlot.source.path,
        // keepOriginalSize: null,
        // TODO : TOO Slow in dev, cache in dev only for thumbs ?
        keepOriginalSize: 1,
      })
    : null;

</script>

<!-- {JSON.stringify(timingSlot)} -->
<!-- https://svelte.dev/repl/cfcb6407b0c44b6298a4fd27f7aec109?version=3.35.0
  event forwarding : use on:click without values ?
-->
<div
  on:click
  class="mws-timing-slot
flex justify-center items-center
m-1
overflow-hidden border-solid border-4"
  class:border-gray-600={!isSelected}
  class:border-blue-600={isSelected}
  class:border-green-400={!isSelected && timingSlot.tags?.length}
  style:height={size}
  style:width={size}
>
  <!-- https://blog.sentry.io/fallbacks-for-http-404-images-in-html-and-javascript/#:~:text=Another%20way%20to%20provide%20an,the%20HTML%20element. -->
  <object
    data="http://localhost:8000/bundles/moonmanager/medias/MoonManagerLogo.png"
    type="image/png"
  >
    <img
      loading="lazy"
      class="object-contain"
      src={"screenshot" == timingSlot.source?.type ? slotPath : ""}
    />
  </object>

  <!-- <img
    src="https://somedomain.com/image.png"
    alt="This image should exist, but alas it does not"
    width="150"
    height="150"
    onerror="this.src='/path/to/fallback.png'"
  />

  or :
  const images = document.querySelectorAll("img");

  images.forEach((image) => {
    image.addEventListener("error", (event) => {
      image.src = "/path/to/fallback.png";
    });
  }); -->

  <!-- <span>{slotName}</span> -->
</div>
