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
  import MwsTimeSlotIndicator from "../layout/widgets/MwsTimeSlotIndicator.svelte";
  export let locale;
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let lookup;
  export let timingsReport = [];
  // export let timings = [];
  export let timingsPaginator;
  export let timingsHeaders = {}; // injected raw html
  export let viewTemplate;
  export let lookupForm;
  export let showDetails = false; // TODO : CSV EXPORT instead, PDF print is too much pages... (might be ok per month, but not for one year of data...)
  export let showPictures = false;
  const urlParams = new URLSearchParams(window.location.search);
  const pageNumber = urlParams.get("page") ?? "1";

  console.debug("Report having timingsReport", timingsReport);
  // console.debug('Report having timings', timings);
  const timingsByIds = {};

  timingsReport.forEach((tSum) => {
    const ids = tSum.ids.split(",");
    const tagSlugs = tSum.tags?.split(",");
    const labels = tSum.labels?.split(",");
    const allRangeDayIdxBy10Min = tSum.allRangeDayIdxBy10Min.split(",");
    const pricesPerHr = tSum.pricesPerHr?.split(",");
    const sourceStamps = tSum.sourceStamps?.split(",");
    
    console.assert(
      !tagSlugs || tagSlugs.length == ids.length,
      "Wrong DATASET, <> tagSlugs found"
    );
    console.assert(
      allRangeDayIdxBy10Min.length == ids.length,
      "Wrong DATASET, <> allRangeDayIdxBy10Min found"
    );
    console.assert(
      !pricesPerHr || pricesPerHr.length == ids.length,
      "Wrong DATASET, <> pricesPerHr found"
    );
    
    console.assert(
      !sourceStamps || sourceStamps.length == ids.length,
      "Wrong DATASET, <> sourceStamps found"
    );
    // const srcStamps = tSum.srcStamps.split(',');
    ids.forEach((tId, idx) => {
      const tagSlug = tagSlugs ? tagSlugs[idx] ?? null : null;
      const rangeDayIdxBy10Min = allRangeDayIdxBy10Min[idx];
      const sourceStamp = sourceStamps ? sourceStamps[idx] ?? null : null;
      const pricePerHr = pricesPerHr
        ? parseFloat(pricesPerHr[idx]) ?? null
        : null;
      const maxPPH = Math.max(
        pricePerHr ?? 0,
        timingsByIds[tId]?.maxPricePerHr ?? 0
      );
      const label = labels ? labels[idx] ?? null : null;
      const tags = {
        ...(tagSlug
          ? {
              [tagSlug]: {
                label: label,
                pricePerHr: pricePerHr,
                // slug: tagSlug,
              },
            }
          : {}),
        // Keep last known tags
        ...(timingsByIds[tId]?.tags ?? {}),
      };

      timingsByIds[tId] = {
        id: tId,
        sourceStamp: sourceStamp,
        rangeDayIdxBy10Min: rangeDayIdxBy10Min,
        maxPricePerHr: maxPPH,
        tags: tags,
        // tags: timingsByIds[tId]?.tags ?? {},
      };
    });
    // if (timingsByIds[tId]) {
    //   console.debug(timingsByIds[tSum], tSum);
    //   console.assert(, "Wrong DATASET, <> timings found");
    // }
  });

  let summaryByDays = {};
  let summaryTotals = {
    sumOfBookedHrs: 0,
    sumOfMaxPPH: 0,
  };
  timingsReport.forEach((tReport) => {
    const ids = tReport.ids.split(",");

    ids.forEach((tId) => {
      const t = timingsByIds[tId];
      if (!(summaryByDays[tReport.sourceDate] ?? null)) {
        summaryByDays[tReport.sourceDate] = {
          bookedTimeSlot: {},
          sumOfBookedHrs: 0,
          sumOfMaxPPH: 0, // Sum of max price per slot
          maxPPH: 0,
          tags: {},
        };
      }
      if (
        !(
          summaryByDays[tReport.sourceDate].bookedTimeSlot[
            t.rangeDayIdxBy10Min
          ] ?? null
        )
      ) {
        const delta = 10 / 60.0;
        const deltaOfMaxPPH = delta * (t.maxPricePerHr ?? 0);
        summaryByDays[tReport.sourceDate].sumOfBookedHrs += delta;
        // TODO : wrong max, only max of fist booked slot :
        summaryByDays[tReport.sourceDate].sumOfMaxPPH += deltaOfMaxPPH;
        summaryTotals.sumOfBookedHrs += delta;
        summaryTotals.sumOfMaxPPH += deltaOfMaxPPH;
      }
      summaryByDays[tReport.sourceDate].ids = [
        ...(summaryByDays[tReport.sourceDate].ids ?? []),
        ...[ t.id ],
      ];
      summaryByDays[tReport.sourceDate].tags = {
        ...(summaryByDays[tReport.sourceDate].tags ?? {}),
        ...(t.tags ?? {}),
      };
      summaryByDays[tReport.sourceDate].maxPPH = Math.max(
        summaryByDays[tReport.sourceDate]?.maxPPH ?? 0,
        (t.maxPricePerHr ?? 0)
      );
      summaryByDays[tReport.sourceDate].bookedTimeSlot[t.rangeDayIdxBy10Min] =
        true;
    });
  });

  let summaryByYears = {};

  Object.keys(summaryByDays).forEach((tDay) => {
    const tDate = dayjs(tDay);
    const tMonth = tDate.format("MM");
    const tYear = tDate.format("YYYY");
    const summary = summaryByDays[tDay];
    if (!(summaryByYears[tYear] ?? null)) {
      summaryByYears[tYear] = {
        bookedTimeSlot: {},
        sumOfBookedHrs: 0,
        sumOfMaxPPH: 0,
        maxPPH: 0,
        tags: {},
        months: {},
      };
    }
    if (!(summaryByYears[tYear].months[tMonth] ?? null)) {
      summaryByYears[tYear].months[tMonth] = {
        bookedTimeSlot: {},
        sumOfBookedHrs: 0,
        sumOfMaxPPH: 0,
        maxPPH: 0,
        tags: {},
        days: {},
      };
    }

    summaryByYears[tYear].bookedTimeSlot = {
      ...summaryByYears[tYear].bookedTimeSlot,
      ...summary.bookedTimeSlot,
    };
    summaryByYears[tYear].sumOfBookedHrs += summary.sumOfBookedHrs;
    summaryByYears[tYear].sumOfMaxPPH += summary.sumOfMaxPPH;
    summaryByYears[tYear].maxPPH = Math.max(
      summaryByYears[tYear]?.maxPPH ?? 0,
      summary.maxPPH ?? 0
    );
    summaryByYears[tYear].tags = {
      ...(summaryByYears[tYear]?.tags ?? {}),
      ...(summary.tags ?? {}),
    };

    summaryByYears[tYear].months[tMonth].bookedTimeSlot = {
      ...summaryByYears[tYear].months[tMonth].bookedTimeSlot,
      ...summary.bookedTimeSlot,
    };
    summaryByYears[tYear].months[tMonth].sumOfBookedHrs +=
      summary.sumOfBookedHrs;
    summaryByYears[tYear].months[tMonth].sumOfMaxPPH += summary.sumOfMaxPPH;
    summaryByYears[tYear].months[tMonth].maxPPH = Math.max(
      summaryByYears[tYear]?.months[tMonth]?.maxPPH ?? 0,
      summary.maxPPH ?? 0
    );
    summaryByYears[tYear].months[tMonth].tags = {
      ...(summaryByYears[tYear]?.months[tMonth]?.tags ?? {}),
      ...(summary.tags ?? {}),
    };

    summaryByYears[tYear].months[tMonth].days[tDay] = true; //summary;
  });

  console.log(
    "timingsByIds :",
    timingsByIds[Object.keys(timingsByIds)[0]] ?? null
  );
  console.log("timingsByIds :", summaryByDays);

  const slotPath = (timingSlot) => Routing.generate("mws_timing_fetchMediatUrl", {
    // encodeURI('file://' + timingSlot.source.path)
    url: "file://" + timingSlot.sourceStamp,
  });

</script>

<a
  href={Routing.generate(
    "mws_timings_qualif",
    {
      _locale: locale ?? "",
    },
    true
  )}
  class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent"
>
  <button>Timings Qualifications</button>
</a>
<div class="mws-timing-report">
  <!-- {JSON.stringify(timings)} -->
  <div>{@html timingsPaginator}</div>
  <div>{summaryTotals.sumOfBookedHrs.toFixed(2)} hours for all</div>
  <div>{summaryTotals.sumOfMaxPPH.toFixed(2)} € for all</div>

  <div class="block w-full overflow-x-auto ">
    <table class="items-center w-full bg-transparent border-collapse">
      <thead class="sticky">
        <tr>
          <th
            class="px-6 align-middle border border-solid
          py-3 text-xs uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            Segment(s)
          </th>
          <th
            class="px-6 align-middle border border-solid
          py-3 text-xs uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            Tag(s)
          </th>
          <th
            class="px-6 align-middle border border-solid
          py-3 text-xs uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            Total en heure(s)
          </th>
          <th
            class="px-6 align-middle border border-solid
          py-3 text-xs uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            € max par heure(s)
          </th>
          <th
            class="px-6 align-middle border border-solid
          py-3 text-xs uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            Total en €
          </th>
        </tr>
      </thead>
      <tbody>
        {#each Object.keys(summaryByYears).sort() ?? [] as year, idx}
          <tr>
            <td
              class="border-t-0 px-6 align-middle
            border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
            >
              <MwsTimeSlotIndicator
                slots={summaryByYears[year].bookedTimeSlot}
              />
            </td>
            <td
              class="border-t-0 px-6 align-middle
              border-l-0 border-r-0 text-xs whitespace-break-spaces p-4"
            >
              [{year}]
              {#each Object.keys(summaryByYears[year].tags).sort() ??
                [] as tagSlug}
                {@const tag = summaryByYears[year].tags[tagSlug]}
                <span
                  class="inline-flex
                 text-base font-medium p-1 text-center
                 border border-blue-800"
                >
                  {tag.label} {
                    tag.pricePerHr ? `[${tag.pricePerHr.toFixed(2)} €]` : ''
                  }
                </span>
              {/each}
            </td>
            <td
              class="border-t-0 px-6 align-middle
              border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
            >
              {summaryByYears[year].sumOfBookedHrs.toFixed(2)} hr
            </td>
            <td
              class="border-t-0 px-6 align-middle
              border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
            >
              {summaryByYears[year].maxPPH.toFixed(2)} €
            </td>
            <td
              class="border-t-0 px-6 align-middle
              border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
            >
              {summaryByYears[year].sumOfMaxPPH.toFixed(2)} €
            </td>
          </tr>
          {#each Object.keys(summaryByYears[year].months).sort() ?? [] as month}
            {@const monthSummary = summaryByYears[year].months[month]}
            <tr>
              <td
                class="border-t-0 px-6 align-middle
              border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
              >
                <MwsTimeSlotIndicator
                  slots={monthSummary.bookedTimeSlot}
                />
              </td>
              <td
                class="border-t-0 px-6 align-middle
                border-l-0 border-r-0 text-xs whitespace-break-spaces p-4"
              >
                [{year}-{month}]
                {#each Object.keys(monthSummary.tags).sort() ??
                  [] as tagSlug}
                  {@const tag = monthSummary.tags[tagSlug]}
                  <span
                    class="inline-flex
                  text-base font-medium p-1 text-center
                  border border-blue-800"
                  >
                    {tag.label} {
                      tag.pricePerHr ? `[${tag.pricePerHr.toFixed(2)} €]` : ''
                    }
                  </span>
                {/each}
              </td>
              <td
                class="border-t-0 px-6 align-middle
                border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
              >
                {monthSummary.sumOfBookedHrs.toFixed(2)} hr
              </td>
              <td
                class="border-t-0 px-6 align-middle
                border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
              >
                {monthSummary.maxPPH.toFixed(2)} €
              </td>
              <td
                class="border-t-0 px-6 align-middle
                border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
              >
                {monthSummary.sumOfMaxPPH.toFixed(2)} €
              </td>
            </tr>

            {#each Object.keys(monthSummary.days).sort() ?? [] as day}
              {@const daySummary = summaryByDays[day]}
              <tr>
                <td
                  class="border-t-0 px-6 align-middle
                border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
                >
                  <MwsTimeSlotIndicator
                    slots={daySummary.bookedTimeSlot}
                  />
                </td>
                <td
                  class="border-t-0 px-6 align-middle
                  border-l-0 border-r-0 text-xs whitespace-break-spaces p-4"
                >
                  [{day}]
                  {#each Object.keys(daySummary.tags ?? {}).sort() ??
                    [] as tagSlug}
                    {@const tag = daySummary.tags[tagSlug]}
                    <span
                      class="inline-flex
                    text-base font-medium p-1 text-center
                    border border-blue-800"
                    >
                      {tag.label} {
                        tag.pricePerHr ? `[${tag.pricePerHr.toFixed(2)} €]` : ''
                      }
                    </span>
                  {/each}
                </td>
                <td
                  class="border-t-0 px-6 align-middle
                  border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
                >
                  {daySummary.sumOfBookedHrs?.toFixed(2)} hr
                </td>
                <td
                  class="border-t-0 px-6 align-middle
                  border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
                >
                  {daySummary.maxPPH?.toFixed(2)} €
                </td>
                <td
                  class="border-t-0 px-6 align-middle
                  border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
                >
                  {daySummary.sumOfMaxPPH?.toFixed(2)} €
                </td>
              </tr>

              <!-- TIPS : showing all timings for ONE years is TOO slow,
                only show in expanded mode ?
                + 60000 picture is too long to load from server...
                like some hours to display one page,
                that will be too heavy for pdf export ?
                Same base issue as for MoonManager 2018 (Angular),
                video might be solution... -->
              {#if showDetails }
                <!-- {#each daySummary.ids?.slice(0, 0) ?? [] as tId} -->
                {#each daySummary.ids?.slice(0) ?? [] as tId}
                  {@const timings = timingsByIds[tId]}
                  <tr>
                    <td
                      class="border-t-0 px-6 align-middle
                    border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
                    >
                      {timings.rangeDayIdxBy10Min}
                      {#if showPictures }
                        <img
                          class="object-contain border-solid border-4 max-w-[100px]"
                          class:border-gray-600={!timings.tags?.length}
                          class:border-green-400={timings.tags?.length}
                          src={slotPath(timings)}
                        />
                      {/if}            
                    </td>
                    <td
                      class="border-t-0 px-6 align-middle
                      border-l-0 border-r-0 text-xs whitespace-break-spaces p-4"
                    >
                      [{timings.sourceStamp?.split('/').slice(-1) ?? timings.id}]
                      {#each Object.keys(timings.tags ?? {}).sort() ??
                        [] as tagSlug}
                        {@const tag = timings.tags[tagSlug]}
                        <span
                          class="inline-flex
                        text-base font-medium p-1 text-center
                        border border-blue-800"
                        >
                          {tag.label} {
                            tag.pricePerHr ? `[${tag.pricePerHr.toFixed(2)} €]` : ''
                          }
                        </span>
                      {/each}
                    </td>
                    <td
                      class="border-t-0 px-6 align-middle
                      border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
                    >
                      {(10/60).toFixed(2)} hr
                    </td>
                    <td
                      class="border-t-0 px-6 align-middle
                      border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
                    >
                      {timings.maxPricePerHr?.toFixed(2)} €
                    </td>
                    <td
                      class="border-t-0 px-6 align-middle
                      border-l-0 border-r-0 text-xs whitespace-nowrap p-4"
                    >
                      {((timings.maxPricePerHr ?? 0) * (10/60)).toFixed(2)} €
                    </td>
                  </tr>
                {/each}
              {/if}            
            {/each}
          {/each}

        {/each}
      </tbody>
    </table>
  </div>
</div>
