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
  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";

  let classNames = "";
  export { classNames as class };
  export let timingSlot;
  export let isFullScreen = false;
  export let moveSelectedIndex;
  export let lastSelectedIndex = 0;
  export let timeQualifs = [];
  export let locale;

  export let toggleQualif = async (qualif) => {
    const data = {
      _csrf_token: stateGet(get(state), 'csrfTimingToggleQualif'),
      timeSlotId: timingSlot.id,
      qualifId: qualif.id,
    };
    const formData  = new FormData();      
    for(const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate('mws_timing_qualif_toggle', {
        _locale: locale,
      }), {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        redirect: 'error',
      }
    ).then(async resp => {
      console.log(resp);
      if (!resp.ok) {
        throw new Error("Not 2xx response", {cause: resp});
      } else {
          const data = await resp.json();
          // const data = await resp.text();
          // console.debug("resp", data);
          timingSlot.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          lastSelectedIndex = lastSelectedIndex; // Svelte reactive force reloads
          console.debug("Did toggle qualif, updated tags : ", timingSlot.tags);
          stateUpdate(state, {
            csrfTimingToggleQualif: data.newCsrf,
          });
      }
    }).catch(e => {
      console.error(e);
      // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
      const shouldWait = confirm("Echec de l'enregistrement.");
    });
  }

  export let addTag = async (tag) => {
    const data = {
      _csrf_token: stateGet(get(state), 'csrfTimingTagAdd'),
      timeSlotId: timingSlot.id,
      tagSlug: tag.slug,
    };
    const formData  = new FormData();      
    for(const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate('mws_timing_tag_add', {
        _locale: locale,
      }), {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        redirect: 'error',
      }
    ).then(async resp => {
      console.log(resp);
      if (!resp.ok) {
        throw new Error("Not 2xx response", {cause: resp});
      } else {
          const data = await resp.json();
          timingSlot.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          lastSelectedIndex = lastSelectedIndex; // Svelte reactive force reloads
          console.debug("Did add tag", timingSlot.tags);
          stateUpdate(state, {
            csrfTimingTagAdd: data.newCsrf,
          });
      }
    }).catch(e => {
      console.error(e);
      // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
      const shouldWait = confirm("Echec de l'enregistrement.");
    });
  };

  $: slotPath = timingSlot?.source?.path
    ? Routing.generate("mws_timing_fetchMediatUrl", {
        // encodeURI('file://' + timingSlot.source.path)
        url: "file://" + timingSlot.source.path,
      })
    : null;

  let qualifTemplates = timeQualifs.map(q => {
    q.toggleQualif = async () => {
        console.log("Toggle qualif " + q.label, q);
        // await q.timeTags.forEach(async t => {
        //   await addTag(t);
        // });
        await toggleQualif(q);
      };
    return q;
  });
  let qualifShortcut = qualifTemplates.reduce((acc, qt) => {
    acc[qt.shortcut] = qt.toggleQualif;
    return acc;
  }, {});

  const isKey = {
    space: (k) => k.keyCode == 32,
    return: (k) => k.keyCode == 13,
    qualifShortcut: (k) => qualifShortcut[k.keyCode] ?? null,
  };

  const onKeyDown = async (e) => {
    if (isKey.space(e)) {
      isFullScreen = !isFullScreen;
      e.preventDefault();
    }
    if (isKey.return(e)) {
      // isFullScreen = !isFullScreen; // TOGGLE QUALIF tags ?
      e.preventDefault();
    }
    if (isKey.qualifShortcut(e)) {
      await isKey.qualifShortcut(e)(); // TODO : should not block event flow ? no await ?
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
    [{timingSlot?.rangeDayIdxBy10Min ?? '--'}]
    [{timingSlot?.maxPricePerHr ?? '--'}]
    {dayjs(timingSlot?.sourceTime).format("YYYY/MM/DD H:mm")}
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
    {#each timingSlot?.tags ?? [] as tag}
        <span class="float-right m-1 text-white">
          {tag.label}
        </span>
    {/each}
    <img
      class="object-contain"
      class:w-full={isFullScreen}
      class:h-full={isFullScreen}
      src={"screenshot" == timingSlot?.source?.type ? slotPath : ""}
    />
  </div>
</div>
