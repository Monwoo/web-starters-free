<script context="module" lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // TODO : media credit in credits page :
  //
  const baseHref = window && window.baseHref;

  export let randomEmptyPicture = () => {
    // TODO : CRM configured default user empty pict
    //        then default CRM empty pict from .env if none
    const rands = [
      // baseHref + `/bundles/moonmanager/medias/pixabay/ai-generated-8702813_1280-BandWCenter.jpg`,
      baseHref +
        `/bundles/moonmanager/medias/pixabay/ibiza-2954994_1280-BandWSky.jpg`,
    ];
    // https://futurestud.io/tutorials/generate-a-random-number-in-range-with-javascript-node-js
    function between(min, max) {
      return Math.round(
        // Math.floor(
        Math.random() * (max - min) + min
      );
    }

    const r = between(0, rands.length - 1);
    return rands[r];
  };
</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { onMount, tick } from "svelte";
  import HtmlIcon from "./qualifs/HtmlIcon.svelte";
  import newUniqueId from "locally-unique-id-generator";
  const UID = newUniqueId();

  export let htmlRoot;
  export let timingSlot;
  export let isSelected = false;
  export let size = "50px";
  export let forceHeight;
  export let followSelection = true;
  export let quickQualifTemplates;
  export let slotIndex;
  export let lastSelectedIndex;
  export let selectionStartIndex;
  const base = process.env.BASE_HREF_FULL ?? "";

  // TODO : crop tool to resize privacy frog ?
  // + easy crop mask history and save/reload privacy frog parameters...
  // + add export option : 'Thumbs without frogs'
  // + add export option : 'Include privacy frogs'

  $: thumbtitle =
    (currentTimeSlotQualifs?.reduce((acc, q) => {
      return acc + q.label + " ";
    }, "") ?? "") + (timingSlot?.sourceStamp ?? "");

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

  $: {
    // TIPS : opti for fullscreen next img shift :
    //       avoid sublist not seen animation eating loading time
    //       with followSelection sentinel
    if (followSelection && isSelected && lastSelectedIndex === slotIndex) {
      // Below will also sroll parent's to fit the element at top of screen
      // htmlRoot?.scrollIntoView();

      // https://stackoverflow.com/questions/24665602/scrollintoview-scrolls-just-too-far
      // https://stackoverflow.com/questions/56688002/javascript-scrollintoview-only-in-immediate-parent
      htmlRoot?.scrollIntoView({
        // block: "start",
        // block: "end",
        // block: "center",
        block: "nearest",
        // https://developer.mozilla.org/en-US/docs/Web/API/Element/scrollIntoView
        // block: "end",
        // TODO : smooth scroll get's interrupted on history update ?
        //        => should re-pop scroll event in 'onPopstate' ? cf packages/mws-moon-manager-ux/components/timing/Lookup.svelte
        // behavior: "smooth",
        // TIPS : half solve, no scroll animation to avoid error on scroll anim interruptions
        // https://kilianvalkhof.com/2022/css-html/preventing-smooth-scrolling-with-javascript/
        behavior: "instant",
        inline: "nearest", // Parent hierarchy scroll
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

  // $: {
  //   lastSelectedIndex,
  //   // TIPS : opti for fullscreen next img shift :
  //   //       avoid sublist not seen animation eating loading time
  //   //       with followSelection sentinel
  //   (followSelection && isSelected) ? (
  //     // Below will also sroll parent's to fit the element at top of screen
  //     // htmlRoot?.scrollIntoView();
  //     // https://stackoverflow.com/questions/24665602/scrollintoview-scrolls-just-too-far
  //     // https://stackoverflow.com/questions/56688002/javascript-scrollintoview-only-in-immediate-parent
  //     htmlRoot?.scrollIntoView({
  //       // block: "start", // Big parent hierarchy scroll
  //       // block: "center", // Small parent hierarchy scroll
  //       block: "nearest", // No parent hierarchy scroll
  //       behavior: "smooth",
  //       inline: "nearest",
  //     })
  //     // TIPS : CSS scroll-margin and scroll-padding
  //     // https://stackoverflow.com/questions/24665602/scrollintoview-scrolls-just-too-far
  //     // el.scrollIntoView({block: "start", behavior: "smooth"});
  //     // .example {
  //     //   scroll-margin-top: 10px;
  //     // }
  //     // => align the top border of the viewport with the top border of the element,
  //     //    but with 10px of additional space
  //     // these properties of the element are taken into account:
  //     // padding-top border-top scroll-margin-top (and not margin-top)
  //     // scroll-padding-top (if set)
  //     ) : null;
  //   }

  // onMount(() => { // Only once at load...
  //   if (isSelected) {
  //     htmlRoot?.scrollIntoView();
  //   }
  // });

  // TODO : detect thumb width, use detail picture first if > à some size

  // TODO : factorize code with SlotView etc...
  let fetchQualifsFor = (timing) => {
    let allQualifsFor = [];
    console.debug("SlotThumbnail quickQualifTemplates", quickQualifTemplates);
    quickQualifTemplates?.forEach((q) => {
      const filteredArray = timing.tags?.filter(
        (tt) => q.timeTags?.filter((qt) => tt.slug === qt.slug).length > 0
      );
      if (filteredArray.length === q.timeTags?.length) {
        allQualifsFor.push(q);
      }
    });
    return allQualifsFor;
  };

  let currentTimeSlotQualifs;
  // TIPS, use 'quickQualifTemplates, ' to ensure currentTimeSlotQualifs
  //       also get refreshed if quickQualifTemplates did change ?
  $: quickQualifTemplates,
    (currentTimeSlotQualifs = fetchQualifsFor(timingSlot));

  // $: currentTimeSlotQualifs?.forEach(q => {
  //   q.tooltipId = `slotThumbQualifTooltip-${UID}`;
  // });

  $: tooltipIdsByQId = currentTimeSlotQualifs?.reduce((acc, q) => {
    acc[q.id] = `slotThumbQualifTooltip-${UID}`;
    return acc;
  }, {});

  export let computedSize;
  $: {
    // TIPS : 'size,' to force refresh from html after size changes :
    // TODO : debounce and wait for call ends ? well, fast to assign only one props...
    size,
      (async () => {
        // TIPS : tick() to wait for html changes
        await tick();
        // TODO : ok if out of lifecycle ? async call to wait for UI refresh and new computed size
        if (computedSize !== htmlRoot?.offsetWidth) {
          // TIPS : check changes before assign to avoid useless refresh
          computedSize = htmlRoot?.offsetWidth;
        }
      })();
  }
  onMount(() => {
    // Only once at load... but NEEDED, for first init other than null
    computedSize = htmlRoot.offsetWidth; // htmlRoot is now != of null...
  });
</script>

<!-- {JSON.stringify(timingSlot)} -->
<!-- https://svelte.dev/repl/cfcb6407b0c44b6298a4fd27f7aec109?version=3.35.0
  event forwarding : use on:click without values ?

  TIPS : use min-width to force width over flex container
  and trigger scrolls :
-->
<div
  bind:this={htmlRoot}
  on:click
  on:dblclick
  class="mws-timing-slot relative
flex justify-center items-center
m-1 rounded-md
overflow-visible border-solid border-4"
  class:border-gray-600={selectionStartIndex === undefined && !isSelected}
  class:border-blue-600={selectionStartIndex === undefined && isSelected}
  class:border-red-600={selectionStartIndex !== undefined && isSelected}
  
  class:border-green-400={selectionStartIndex === undefined && !isSelected && timingSlot.tags?.length}
  style:height={forceHeight ? forceHeight : size}
  style:min-height={forceHeight
    ? computedSize > 120
      ? "7rem"
      : forceHeight
    : size}
  style:width={size}
  style:min-width={size}
>
  <!--
        data="{timingSlot.thumbnailJpeg ?? '#404'}" won't work, will sub load current page
        data="{timingSlot.thumbnailJpeg ?? '/404'}" ok, but eat one fail request on our server
        data="{timingSlot.thumbnailJpeg ?? 'https://wrong-host.localhost/404'}" // ok, but network error message
        data={timingSlot.thumbnailJpeg ?? "//=::NotAnUrlForPurposeFail**%%"} // ok, fail silently
        data={timingSlot.thumbnailJpeg ?? null} // no effect

        // TODO : SeoManager for translated alt / arial-label / title / meta data etc...
    https://blog.sentry.io/fallbacks-for-http-404-images-in-html-and-javascript/#:~:text=Another%20way%20to%20provide%20an,the%20HTML%20element. -->
  <!-- {#if computedSize } // TODO strange : object will no reload right content ?
    <object
      class="w-full h-full"
      data={ computedSize < 120
      ? timingSlot?.thumbnailJpeg ?? "//=::NotAnUrlForPurposeFail**%%"
      : "screenshot" == timingSlot?.source?.type ? slotPath : ""}
      type="image/png"
      alt="screenshot"
      arial-label="screenshot"
      title="screenshot"
    >
      {#if !(timingSlot?.thumbnailJpeg ?? false) }
        <!-- // event : without if, will not preload image event if object has data... !-- >
        <img
          loading="lazy"
          alt="screenshot"
          arial-label="screenshot"
          class="object-contain w-full h-full"
          src={ computedSize < 120
          ? ("screenshot" == timingSlot?.source?.type ? slotPath : "")
          : timingSlot?.thumbnailJpeg}
        />
        <!-- 
          TODO : generate thumb ? but eat spaces for slow rendering...
          src={"screenshot" == timingSlot.source?.type ? slotPath : ""} -- >
      {/if}
    </object>
  {/if} -->

  {#if (timingSlot?.thumbnailJpeg ?? false) && computedSize < 120}
    <img
      loading="lazy"
      alt={thumbtitle}
      arial-label="screenshot"
      class="object-contain w-full h-full"
      src={"screenshot" == timingSlot?.source?.type
        ? (timingSlot?.thumbnailJpeg?.startsWith('/') ? base + timingSlot?.thumbnailJpeg : timingSlot?.thumbnailJpeg)
        : ""}
    />
  {:else}
    <!-- type="image/png" -->
    <object
      on:load
      on:error
      loading="lazy"
      class="object-contain w-full h-full"
      data={"screenshot" == timingSlot?.source?.type ? slotPath : ("//=::NotAnUrlForPurposeFail**%%" + timingSlot?.source?.type)}
      type={timingSlot?.source?.metas?.mimeType ?? "image/png"}
      role="presentation"
      title={thumbtitle}
    >
      <img
        loading="lazy"
        alt={thumbtitle}
        arial-label="screenshot"
        class="object-contain w-full h-full"
        src={ (timingSlot?.thumbnailJpeg?.startsWith('/') ? base + timingSlot?.thumbnailJpeg : timingSlot?.thumbnailJpeg) ?? randomEmptyPicture()}
      />
    </object>
  {/if}

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

  <!-- <span>{slotName}</span> 
  
  style={`
    width: ${(computedSize * 0.2).toFixed(0)}px;
    height: ${(computedSize * 0.2).toFixed(0)}px;
  `}
  // TIPS : m-auto to center absolut item at bottom of container ;)
  -->
  <div
    class="absolute z-20 bottom-1 m-auto bg-white
  flex
  hover:opacity-100"
    class:opacity-90={!isSelected}
  >
    <!-- // ONLY first qualif for thumbs... 
    TODO : refactor ? primaryColorRgb is a class or a String, depending of color select picker...
    -->
    {#each (currentTimeSlotQualifs ?? []).slice(0, computedSize > 120 ? 7 : 2) as q}
      {#if computedSize > 120}
        <!-- data-tooltip-target={tooltipIdsByQId[q.id]}
        m-1 w-full mx-2 whitespace-nowrap overflow-hidden text-ellipsis flex justify-center items-center pl-1 pr-1 
        -->
        <div
          class="inline-flex  min-h-[2.4rem] min-w-[2.4rem]
          border-b-4 border-t-4 object-contain w-full h-full
          justify-center items-center"
          style={`
          border-color: ${q.primaryColorHex};
        `}
        >
          <HtmlIcon qualif={q} height={"h-full"} width={"w-full"} />
        </div>
      {:else}
        <!-- data-tooltip-target={tooltipIdsByQId[q.id]} -->
        <div
          class="min-h-3 min-w-3 w-full h-full"
          style={`
          background-color: ${q.primaryColorHex};
        `}
        />
      {/if}
    {/each}
  </div>
  <!-- 
  TODO : popover re-instanciation in one to one thumb scrolling
  is slowing down everything due to some heavy scoll listeners
  computes => Missing dispose or listeners cleanup for tooltip ?
  // TODO : code factorisation to Svelte / Flowbite node js will solve this buggy stuff waiting for global structure instead of local components thinks
  {#each (currentTimeSlotQualifs?? []).slice(0,1) as q}
    <! -- // TODO : why HtmlIcon inner tooltip not working? quick hack not working too : -- >
    <div id={tooltipIdsByQId[q.id]} role="tooltip"
    class="absolute z-50 invisible inline-block
    px-3 py-2 text-sm font-medium text-white
    transition-opacity duration-300 bg-gray-900
    rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
      {q.label}
      <div class="tooltip-arrow" data-popper-arrow></div>
    </div>
  {/each} -->
</div>
