<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  export let locale;
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let lookup;
  export let timingsReport = [];
  // export let timings = [];
  export let timingsPaginator;
  export let timingsHeaders = {}; // injected raw html
  export let viewTemplate;
  export let lookupForm;
  const urlParams = new URLSearchParams(window.location.search);
  const pageNumber = urlParams.get("page") ?? "1";

  console.debug('Report having timingsReport', timingsReport);
  // console.debug('Report having timings', timings);
  const timingsByIds = {};

  timingsReport.forEach(tSum => {
    const ids = tSum.ids.split(',');
    const tags = tSum.tags?.split(',');
    const labels = tSum.labels?.split(',');
    const allRangeDayIdxBy10Min = tSum.allRangeDayIdxBy10Min.split(',');
    const pricesPerHr = tSum.pricesPerHr?.split(',');
    console.assert(!tags || tags.length == ids.length, "Wrong DATASET, <> tags found");
    console.assert(allRangeDayIdxBy10Min.length == ids.length, "Wrong DATASET, <> allRangeDayIdxBy10Min found");
    console.assert(!pricesPerHr || pricesPerHr.length == ids.length, "Wrong DATASET, <> pricesPerHr found");
    // const srcStamps = tSum.srcStamps.split(',');
    ids.forEach((tId, idx) => {
      const tagSlug = tags ? tags[idx] ?? null : null;
      const rangeDayIdxBy10Min = allRangeDayIdxBy10Min[idx];
      const pricePerHr = pricesPerHr ? pricesPerHr[idx] ?? null : null;
      const label = labels ? labels[idx] ?? null : null;
      
      timingsByIds[tId] = {
        id: tId,
        rangeDayIdxBy10Min: rangeDayIdxBy10Min,
        tags: {
          ... (tagSlug ? {[tagSlug]:{
            label: label,
            pricePerHr: pricePerHr,
            // slug: tagSlug,
          }} : {}),
          // Keep last known tags
          ... (timingsByIds[tId]?.tags ?? {})
        }
        // tags: timingsByIds[tId]?.tags ?? {},
      };
    });
    // if (timingsByIds[tId]) {
    //   console.debug(timingsByIds[tSum], tSum);
    //   console.assert(, "Wrong DATASET, <> timings found");
    // }
  });

  console.log("timingsByIds :", timingsByIds);
</script>

<a
href={Routing.generate("mws_timings_qualif", {
  '_locale': locale ?? '',
}, true)}
class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent"
>
<button>Timings Qualifications</button>
</a>
<div class="mws-timing-report">
  <!-- {JSON.stringify(timings)} -->
  <div>{@html timingsPaginator}</div>
  {#each timingsReport ?? [] as tReport, idx}
    <div>
      [{tReport.sourceDate}] 
      {tReport.count}
    </div>
  {/each}
</div>