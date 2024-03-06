<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import { tweened } from "svelte/motion";
  import ProgressIndicator from "../layout/widgets/ProgressIndicator.svelte";
  // import dayjs from "dayjs"; // TIPS : MODULE import will be useless if double import... ( not configured new instance)
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  import TagsInput from "./tags/TagsInput.svelte";
import AddModal from "../message/AddModal.svelte";
  // https://day.js.org/docs/en/timezone/set-default-timezone
  // https://day.js.org/docs/en/plugin/timezone
  var utc = require("dayjs/plugin/utc");
  var timezone = require("dayjs/plugin/timezone"); // dependent on utc plugin
  dayjs.extend(utc);
  dayjs.extend(timezone); // TODO : user config for self timezone... (slot is computed on UTC date...)
  // dayjs.tz.setDefault("Europe/Paris");
  dayjs.tz.setDefault("Europe/Paris");

  let classNames = "";
  export { classNames as class };
  export let timingSlot;
  export let isFullScreen = false;
  export let moveSelectedIndex;
  export let lastSelectedIndex = 0;
  export let timeQualifs = [];
  export let locale;
  // Timer start time. Use it to ensure delay,
  // example : 507 page of 124 items
  //          => 10 minutes per page = 5070 minutes for all items
  //          = 5070/60 = 84.5 hours
  //          => SMIC fr 35hr/week
  //          = 84.5 / 35 = 2.4 week for SMIC peoples
  //
  // BUT ALL this is based on 10 minutes per page :
  //          => May be SMIC peoples will not have skills to qualify
  //          => 35hr/week with human life need time to go toilette etc...
  //          => 1 page is 124 items to qualify in 10 minutes
  //          = 124 / 10 = 12.4 items per minutes
  //          = 12.4 / 60 = 0.21 items per secondes
  //          => around 5 secondes to qualify one item...
  export let timerStart = 5;
  let timer = tweened(timerStart);

  let tInterval = null;
  const startTimer = () => {
    tInterval && clearInterval(tInterval);
    $timer = timerStart;
    tInterval = setInterval(() => {
      if ($timer > 0) {
        $timer--;
      } else {
        $timer = 0;
        clearInterval(tInterval);
      }
    }, 1000);
  };
  // startTimer();
  dayjs.locale("fr"); // Fr locale // TODO : global config instead of per module ?
  dayjs.tz.setDefault("Europe/Paris");

  // $: minutes = Math.floor($timer / 60);
  $: lastSelectedIndex, startTimer();

  let isLoading = false;
  const urlParams = new URLSearchParams(window.location.search);
  const pageNumber = urlParams.get("page") ?? "1";

  const movePageIndex = (delta) => {
    const newPageNum = parseInt(pageNumber) + delta;
    // TODO : how to know max page num ? data.length / pageLimit, need to know details...
    urlParams.set("page", newPageNum < 1 ? 1 : newPageNum);
    window.location.search = urlParams;
  };

  export let toggleQualif = async (qualif) => {
    isLoading = true;
    const data = {
      _csrf_token: stateGet(get(state), "csrfTimingToggleQualif"),
      timeSlotId: timingSlot?.id,
      qualifId: qualif.id,
    };
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_timing_qualif_toggle", {
        _locale: locale,
      }),
      {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        redirect: "error",
      }
    )
      .then(async (resp) => {
        console.log(resp);
        if (!resp.ok) {
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          const data = await resp.json();
          // const data = await resp.text();
          // console.debug("resp", data);
          timingSlot?.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          lastSelectedIndex = lastSelectedIndex; // Svelte reactive force reloads
          console.debug("Did toggle qualif, updated tags : ", timingSlot?.tags);
          stateUpdate(state, {
            csrfTimingToggleQualif: data.newCsrf,
          });
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
    isLoading = false;
  };

  export let removeAllTags = async () => {
    isLoading = true;
    const data = {
      _csrf_token: stateGet(get(state), "csrfTimingTagRemoveAll"),
      timeSlotId: timingSlot?.id,
    };
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_timing_tag_remove_all", {
        _locale: locale,
      }),
      {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        redirect: "error",
      }
    )
      .then(async (resp) => {
        console.log(resp);
        if (!resp.ok) {
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          const data = await resp.json();
          timingSlot?.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          lastSelectedIndex = lastSelectedIndex; // Svelte reactive force reloads
          console.debug("Did add tag", timingSlot?.tags);
          stateUpdate(state, {
            csrfTimingTagRemoveAll: data.newCsrf,
          });
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
    isLoading = false;
  };

  $: slotPath = timingSlot?.source?.path
    ? Routing.generate("mws_timing_fetchMediatUrl", {
        // encodeURI('file://' + timingSlot?.source.path)
        url: "file://" + timingSlot?.source.path,
        keepOriginalSize: true,
      })
    : null;

  let qualifTemplates = timeQualifs.map((q) => {
    q.toggleQualif = async () => {
      console.log("Toggle qualif " + q.label, q);
      // await q.timeTags.forEach(async t => {
      //   await addTag(t);
      // });
      await toggleQualif(q);
    };
    return q;
  });
  let qualifShortcut = qualifTemplates.reduce(
    (acc, qt) => {
      acc[String.fromCharCode(qt.shortcut).charCodeAt(0)] = qt.toggleQualif;
      return acc;
    },
    {
      ["d".charCodeAt(0)]: removeAllTags,
    }
  );

  const isKey = {
    space: (k) => k.keyCode == 32,
    return: (k) => k.keyCode == 13,
    qualifShortcut: (k) => qualifShortcut[k.key.charCodeAt(0)] ?? null,
  };

  const onKeyDown = async (e) => {
    console.debug("Key down : ", e);
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
  };

  let moveResp = moveSelectedIndex(0);
  // TIPS : in conjunction with tests on lastSelectedIndex, will refresh move position:
  $: lastSelectedIndex, (moveResp = moveSelectedIndex(0));

</script>

<svelte:window on:keydown={onKeyDown} />

<!-- class:opacity-80={isLoading}
style:opacity={isLoading ? 0.8 : 1} -->
<div
  class="mws-timing-slot-view overflow-y-auto
  flex flex-row flex-wrap content-start max-h-full
  {classNames}"
  class:pointer-events-none={isLoading}
  style::pointer-events={isLoading ? "none" : "auto"}
>
  <!-- {JSON.stringify(timingSlot)} -->
  <div>
    [{timingSlot?.rangeDayIdxBy10Min ?? "--"}] [{timingSlot?.maxPricePerHr ??
      "--"}]
    {dayjs(timingSlot?.sourceTimeGMT)
      .tz("Europe/Paris")
      .format("YYYY/MM/DD H:mm:ss")}
    {timingSlot?.sourceStamp?.split("/").slice(-1) ?? "--"}
  </div>
  <!-- {timingSlot?.sourceStamp} -->
  <div
    on:click={() => (isFullScreen = !isFullScreen)}
    class="full-screen-container bg-black text-white overflow-scroll"
    class:fixed={isFullScreen}
    class:top-0={isFullScreen}
    class:bottom-0={isFullScreen}
    class:left-0={isFullScreen}
    class:right-0={isFullScreen}
  >
    <!-- <div class="max-h-[7rem] overflow-hidden
  hover:max-h-fit hover:overflow-scroll"> -->
    <div
    on:click|stopPropagation
    class="max-h-[7rem] overflow-scroll">
      <!-- <span class="float-right right-0 top-0 m-1 sticky
    pointer-events-none opacity-75 hover:opacity-100"> -->
      <span class="float-right m-1">
        <!-- TIPS : why $timer is tweended and will have FLOAT values : -->
        <!-- {$timer} -->
        <ProgressIndicator
          percent={1 - $timer / timerStart}
          textRenderer={(percent) => `${$timer.toFixed(0)}`}
        />
        <span class="flex w-[6em]">
          {dayjs(timingSlot?.sourceTimeGMT)
            .tz("Europe/Paris")
            .format("YYYY/MM/DD H:mm:ss")}
        </span>
      </span>
      <span
        class="float-right right-0 top-0 w-[6em] sticky pointer-events-none"
      >
        [{pageNumber}-{lastSelectedIndex}]
      </span>
      {#if isFullScreen}
        <button
          class="float-right m-1 top-0 sticky"
          style:opacity={!moveResp.isLast ? 1 : 0.7}
          on:click|stopPropagation={() => (moveResp = moveSelectedIndex(1))}
        >
          Next.
        </button>
        {#if moveResp.isLast}
          <button
            class="float-right m-1 top-0 sticky"
            on:click|stopPropagation={() => movePageIndex(1)}
          >
            Next. Page
          </button>
        {/if}
        <button
          class="float-right m-1 top-0 sticky"
          style:opacity={!moveResp.isFirst ? 1 : 0.7}
          on:click|stopPropagation={() => (moveResp = moveSelectedIndex(-1))}
        >
          Prev.
        </button>
        {#if moveResp.isFirst && pageNumber > 1}
          <button
            class="float-right m-1 top-0 sticky"
            on:click|stopPropagation={() => movePageIndex(-1)}
          >
            Prev. Page
          </button>
        {/if}
      {/if}
      <button
        class="bg-red-500 float-right m-1"
        on:click|stopPropagation={removeAllTags}
      >
        [d] Supprimer tous les tags
      </button>
      <span class="float-right">
        {#each timingSlot?.tags ?? [] as tag}
          <span class="m-1 text-white
          border-blue-600 border rounded-sm p-1">
            {tag.label}
          </span>
        {/each}
      </span>

      {#each qualifTemplates ?? [] as qt, idx}
        <button
          class="float-right m-1"
          on:click|stopPropagation={qt.toggleQualif}
        >
          [{String.fromCharCode(qt.shortcut)}] {qt.label}
        </button>
      {/each}
      <span class="float-right">
        <TagsInput tags={timingSlot?.tags} timing={timingSlot} {locale} />
      </span>
      {#if isLoading}
        <span role="status">
          <svg
            aria-hidden="true"
            class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600"
            viewBox="0 0 100 101"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
              fill="currentColor"
            />
            <path
              d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
              fill="currentFill"
            />
          </svg>
          <span class="sr-only">Loading...</span>
        </span>
      {/if}
    </div>
    <!-- // TODO : remove max-h-[85%]={isFullScreen} + work with flex grow ?
    + https://stackoverflow.com/questions/15999760/load-image-asynchronous
    (but load this one first...)
    -->
    <img
      loading="eager"
      class="object-contain border-solid border-4"
      class:w-full={isFullScreen}
      class:max-h-[85%]={isFullScreen}
      class:border-gray-600={!timingSlot?.tags?.length}
      class:border-green-400={timingSlot?.tags?.length}
      src={"screenshot" == timingSlot?.source?.type ? slotPath : ""}
    />
  </div>
</div>
