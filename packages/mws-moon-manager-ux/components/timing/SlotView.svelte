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
  export let moveSelectedIndex;
  export let lastSelectedIndex = 0;

  $: slotPath = timingSlot?.source?.path
    ? Routing.generate("mws_timing_fetchMediatUrl", {
        // encodeURI('file://' + timingSlot.source.path)
        url: "file://" + timingSlot.source.path,
      })
    : null;

  let qualifTemplates = [
    {
      shortcut: "1".charCodeAt(0),
      label: "1",
      toggleQualif: () => {
        console.log("TODO : toggle qualif 1");
      },
    },
    {
      shortcut: "2".charCodeAt(0),
      label: "2",
      toggleQualif: () => {
        console.log("TODO : toggle qualif 2");
      },
    },
  ];
  let qualifShortcut = qualifTemplates.reduce((acc, qt) => {
    acc[qt.shortcut] = qt.toggleQualif;
    return acc;
  }, {});

  const isKey = {
    space: (k) => k.keyCode == 32,
    return: (k) => k.keyCode == 13,
    qualifShortcut: (k) => qualifShortcut[k.keyCode] ?? null,
  };

  function onKeyDown(e) {
    if (isKey.space(e)) {
      isFullScreen = !isFullScreen;
      e.preventDefault();
    }
    if (isKey.return(e)) {
      // isFullScreen = !isFullScreen; // TOGGLE QUALIF tags ?
      e.preventDefault();
    }
    if (isKey.qualifShortcut(e)) {
      isKey.qualifShortcut(e)();
      e.preventDefault();
    }
  }

  let moveResp = moveSelectedIndex(0);
  // TIPS : in conjunction with tests on lastSelectedIndex, will refresh move position:
  $: lastSelectedIndex, moveResp = moveSelectedIndex(0);

</script>

<svelte:window on:keydown={onKeyDown} />

<div
  class="mws-timing-slot-view overflow-y-auto
  flex flex-row flex-wrap content-start
  {classNames}"
>
  <!-- {JSON.stringify(timingSlot)} -->
  <div>
    [{timingSlot?.rangeDayIdxBy10Min}]
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
    {#each timingSlot?.tags ?? [] as tag}
        <span class="float-right m-1">
          {tag.label}
        </span>
    {/each}
    {#if isFullScreen}
      <button
        class="float-right m-1"
        style:opacity={!moveResp.isLast ? 1 : 0.7}
        on:click|stopPropagation={() => (moveResp = moveSelectedIndex(1))}
      >
        Next.
      </button>

      <button
        class="float-right m-1"
        style:opacity={!moveResp.isFirst ? 1 : 0.7}
        on:click|stopPropagation={() => (moveResp = moveSelectedIndex(-1))}
      >
        Prev.
      </button>
      {#each qualifTemplates ?? [] as qt, idx}
        <button
          class="float-right m-1"
          on:click|stopPropagation={qt.toggleQualif}
        >
          Qualif {qt.label}
        </button>
      {/each}
    {/if}
    <img
      class="object-contain"
      class:w-full={isFullScreen}
      class:h-full={isFullScreen}
      src={"screenshot" == timingSlot?.source?.type ? slotPath : ""}
    />
  </div>
</div>
