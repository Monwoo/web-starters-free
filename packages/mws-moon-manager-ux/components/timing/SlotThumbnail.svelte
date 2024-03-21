<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { onMount } from "svelte";

  export let htmlRoot;
  export let timingSlot;
  export let isSelected = false;
  export let size = "50px";
  export let forceHeight;

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

  // onMount(() => { // Only once at load...
  //   if (isSelected) {
  //     htmlRoot?.scrollIntoView();
  //   }
  // });

  $: {
    if (isSelected) {
      // Below will also sroll parent's to fit the element at top of screen
      // htmlRoot?.scrollIntoView();

      // https://stackoverflow.com/questions/24665602/scrollintoview-scrolls-just-too-far
      // https://stackoverflow.com/questions/56688002/javascript-scrollintoview-only-in-immediate-parent
      htmlRoot?.scrollIntoView({
        // block: "start", // Big parent hierarchy scroll
        // block: "center", // Small parent hierarchy scroll
        block: "nearest", // No parent hierarchy scroll
        behavior: "smooth",
        inline: "nearest"
      });
      // TIPS : CSS scroll-margin and scroll-padding
      // https://stackoverflow.com/questions/24665602/scrollintoview-scrolls-just-too-far
      // el.scrollIntoView({block: "start", behavior: "smooth"});
      // .example {
      //   scroll-margin-top: 10px;
      // }
      // => align the top border of the viewport with the top border of the element,
      //    but with 10px of additional space
      // these properties of the element are taken into account:
      // padding-top border-top scroll-margin-top (and not margin-top)
      // scroll-padding-top (if set)
    }
  }
</script>

<!-- {JSON.stringify(timingSlot)} -->
<!-- https://svelte.dev/repl/cfcb6407b0c44b6298a4fd27f7aec109?version=3.35.0
  event forwarding : use on:click without values ?
-->
<div
  bind:this={htmlRoot}
  on:click
  class="mws-timing-slot
flex justify-center items-center
m-1 rounded-md
overflow-hidden border-solid border-4"
  class:border-gray-600={!isSelected}
  class:border-blue-600={isSelected}
  class:border-green-400={!isSelected && timingSlot.tags?.length}
  style:height={forceHeight ? forceHeight : size}
  style:width={size}
>
  <!--
        data="{timingSlot.thumbnailJpeg ?? '#404'}" won't work, will sub load current page
        data="{timingSlot.thumbnailJpeg ?? '/404'}" ok, but eat one fail request on our server
        data="{timingSlot.thumbnailJpeg ?? 'https://wrong-host.localhost/404'}" // ok, but network error message
        data={timingSlot.thumbnailJpeg ?? "//=::NotAnUrlForPurposeFail**%%"} // ok, fail silently
        data={timingSlot.thumbnailJpeg ?? null} // no effect

        // TODO : SeoManager for translated alt / arial-label / title / meta data etc...
    https://blog.sentry.io/fallbacks-for-http-404-images-in-html-and-javascript/#:~:text=Another%20way%20to%20provide%20an,the%20HTML%20element. -->
  <object
    class="w-full h-full"
    data={timingSlot.thumbnailJpeg ?? "//=::NotAnUrlForPurposeFail**%%"}
    type="image/png"
    alt="screenshot"
    arial-label="screenshot"
    title="screenshot"
  >
    <img
      loading="lazy"
      alt="screenshot"
      arial-label="screenshot"
      class="object-contain w-full h-full"
      src={"screenshot" == timingSlot?.source?.type ? slotPath : ""}
    />
    <!-- 
      TODO : generate thumb ? but eat spaces for slow rendering...
      src={"screenshot" == timingSlot.source?.type ? slotPath : ""} -->
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
