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
  export let report;
  export let reportForm;

  export let showDetails = false; // TODO : CSV EXPORT instead, PDF print is too much pages... (might be ok per month, but not for one year of data...)
  export let showPictures = false;
  export let isLoading = false; // TODO : show loader when showDetails or showPictures is loading...
  const urlParams = new URLSearchParams(window.location.search);
  const pageNumber = urlParams.get("page") ?? "1";

  const ensurePath = (obj, path, val = {}) => {
    path.forEach(key => {
        if (! (key in obj)) {
            obj[key] = val;
        }
        obj = obj[key];
    });
  }

  const jsonLookup = JSON.parse(decodeURIComponent(lookup.jsonResult));

  console.debug("Report having timingsReport", timingsReport);
  // console.debug('Report having timings', timings);

  const jsonReport = JSON.parse(decodeURIComponent(report.jsonResult));
  console.debug("Report : ", jsonReport);

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

  let summaryByLevels = {
    sumOfBookedHrs: 0,
    sumOfMaxPPH: 0,
    levels: []
  };
  let summaryByDays = {};
  let summaryTotals = {
    sumOfBookedHrs: 0,
    sumOfMaxPPH: 0,
  };
  timingsReport.forEach((tReport) => {
    const ids = tReport.ids.split(",");

    ids.forEach((tId) => {
      const t = timingsByIds[tId];
      const delta = 10 / 60.0;
      const deltaOfMaxPPH = delta * (t.maxPricePerHr ?? 0);

      // summaryByLevels
      let usedByReport = false;
      jsonReport.lvl1Tags.forEach((tag, idx) => {
        if (tag in t.tags) {
          ensurePath(summaryByLevels, ["levels", 1], []);
          ensurePath(summaryByLevels, ["levels", 1, idx], {});
          ensurePath(summaryByLevels, ["levels", 1, idx, "bookedTimeSlot"], []);
          ensurePath(summaryByLevels, ["levels", 1, idx, "maxPPH"], 0);
          ensurePath(summaryByLevels, ["levels", 1, idx, "sumOfBookedHrs"], 0);
          ensurePath(summaryByLevels, ["levels", 1, idx, "sumOfMaxPPH"], 0);
          ensurePath(summaryByLevels, ["levels", 1, idx, "tags"], {});
          // TODO : reduce by time point + sub levels.
          summaryByLevels.levels[1][idx].sumOfBookedHrs += delta;
          // TODO : wrong max, only max of fist booked slot :
          summaryByLevels.levels[1][idx].sumOfMaxPPH += deltaOfMaxPPH;
          summaryByLevels.levels[1][idx].sumOfBookedHrs += delta;
          summaryByLevels.levels[1][idx].sumOfMaxPPH += deltaOfMaxPPH;

          summaryByLevels.levels[1][idx].ids = [
            ...(summaryByLevels.levels[1][idx].ids ?? []),
            ...[ t.id ],
          ];
          summaryByLevels.levels[1][idx].tags = {
            ...(summaryByLevels.levels[1][idx].tags ?? {}),
            ...(t.tags ?? {}),
          };
          summaryByLevels.levels[1][idx].maxPPH = Math.max(
            summaryByLevels.levels[1][idx]?.maxPPH ?? 0,
            (t.maxPricePerHr ?? 0)
          );
          summaryByLevels.levels[1][idx].bookedTimeSlot[t.rangeDayIdxBy10Min] =
            true;

          usedByReport = true;
        }        
      });

      if (usedByReport) {
        // do not summary in days or total report if already in configured report.
        return;
      }

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
    // // TIPS : scan lvl + add + should filter detail tags....
    // //        might be better to scan at previous data transform ?
    // //        => summaryByDays will keep not matched time slots in summaryByLevels
    // if (!(summaryByLevels[lvlTag] ?? null)) {
    // }
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
    "timingsByIds[0] :",
    timingsByIds[Object.keys(timingsByIds)[0]] ?? null
  );
  console.log("summaryByYears :", summaryByYears);
  console.log("summaryByLevels :", summaryByLevels);

  const slotPath = (timingSlot) => Routing.generate("mws_timing_fetchMediatUrl", {
    // encodeURI('file://' + timingSlot.source.path)
    url: "file://" + timingSlot.sourceStamp,
  });

  // Number.prototype.toPrettyNum = (length: number) => {
  Number.prototype.toPrettyNum = function (this : Number, length: number) {
    var s = this;
    return s.toFixed(length).replace('.', ',')
    .replace(/\B(?=(\d{3})+(?!\d))/g, " ");
  };

  declare interface Number {
    toPrettyNum(length : number) : string;
  }
</script>


<div class="mws-timing-report">
    <a
      href={Routing.generate(
        "mws_timings_qualif",
        {
          _locale: locale ?? "",
        },
        true
      )}
      class=""
    >
      <button>
        Qualification des temps
      </button>
    </a>
  <button on:click={() => showDetails = !showDetails}>
    { showDetails ? 'Cacher' : 'Show'} les details
  </button>
  {#if showDetails }
    <button on:click={() => showPictures = !showPictures}>
      { showPictures ? 'Cacher' : 'Voir'} les screenshots
    </button>
  {/if}

  <div class="p-3 flex flex-wrap">
    <div class="label">
      <button
      data-collapse-toggle="search-timing-lookup"
      type="button"
      class="rounded-lg "
      aria-controls="search-timing-lookup"
      aria-expanded="false"
    >
      Filtres de recherche
    </div>
    <div id="search-timing-lookup" class="detail w-full hidden">
      {@html lookupForm}
    </div>
    <div class="label">
      <button
      data-collapse-toggle="config-report"
      type="button"
      class="rounded-lg ml-3"
      aria-controls="config-report"
      aria-expanded="false"
    >
      Configuration du rapport
    </div>
    <div id="config-report" class="detail w-full hidden">
      {@html reportForm}
    </div>
  </div>
  {@html jsonLookup.customFilters && jsonLookup.customFilters.length
    ? '<strong>Filtres actifs : </strong>' +
      jsonLookup.customFilters.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonLookup.searchTags && jsonLookup.searchTags.length
    ? '<strong>Tags : </strong>' +
      jsonLookup.searchTags.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonLookup.searchTagsToAvoid && jsonLookup.searchTagsToAvoid.length
    ? '<strong>Tags à éviter : </strong>' +
      jsonLookup.searchTagsToAvoid.reduce((acc, f) => `
        ${acc} [${f}]
      `, ``) + '<br/>'
    : ''
  }
  {@html jsonLookup.searchKeyword
    ? `<strong>Mots clefs : </strong>${jsonLookup.searchKeyword}`
    : ``
  }

  <!-- {JSON.stringify(timings)} -->
  <div>{@html timingsPaginator}</div>
  <br/>
  <div class="text-lg">Rapport des temps via segmentations de 10 minutes.</div>
  <br/>
  <div class="text-lg font-extrabold">{summaryTotals.sumOfBookedHrs.toPrettyNum(2)} hours au total.</div>
  <div class="text-lg font-extrabold">{summaryTotals.sumOfMaxPPH.toPrettyNum(2)} € en tout.</div>
  <br/>
  <br/>

  <div class="block w-full overflow-x-auto ">
    <table class="items-center w-full bg-transparent border-collapse">
      <thead class="sticky">
        <tr>
          <th
            class="px-6 text-middle border border-solid
          py-3 text-lg uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            Segment(s)
          </th>
          <th
            class="px-6 text-left border border-solid
          py-3 text-lg uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            Tag(s)
          </th>
          <th
            class="px-6 text-right border border-solid
          py-3 text-lg uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            Total en heure(s)
          </th>
          <th
            class="px-6 text-right border border-solid
          py-3 text-lg uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            € max par heure(s)
          </th>
          <th
            class="px-6 text-right border border-solid
          py-3 text-lg uppercase border-l-0 border-r-0
          whitespace-nowrap font-semibold text-left
          bg-gray-600 text-white border-gray-800"
          >
            Total en €
          </th>
        </tr>
      </thead>
      <tbody>
        {#each Object.keys(summaryByYears).sort() ?? [] as year, idx}
          <tr class="bg-gray-400 font-extrabold">
            <td
              class="border-t-0 px-6 text-middle
            border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
            >
              <MwsTimeSlotIndicator
                slots={summaryByYears[year].bookedTimeSlot}
              />
            </td>
            <td
              class="border-t-0 px-6 text-left
              border-l-0 border-r-0 text-lg whitespace-break-spaces p-4"
            >
              <div class="text-lg">[{year}]</div>
              {#each Object.keys(summaryByYears[year].tags).sort() ??
                [] as tagSlug}
                {@const tag = summaryByYears[year].tags[tagSlug]}
                <span
                  class="inline-flex
                  text-xs font-medium p-1 text-center
                 border border-blue-800"
                >
                  {tag.label} {
                    tag.pricePerHr ? `[${tag.pricePerHr.toPrettyNum(2)} €/hr]` : ''
                  }
                </span>
              {/each}
            </td>
            <td
              class="border-t-0 px-6 text-right
              border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
            >
              {summaryByYears[year].sumOfBookedHrs.toPrettyNum(2)} hr
            </td>
            <td
              class="border-t-0 px-6 text-right
              border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
            >
              {summaryByYears[year].maxPPH.toPrettyNum(2)} €
            </td>
            <td
              class="border-t-0 px-6 text-right
              border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
            >
              {summaryByYears[year].sumOfMaxPPH.toPrettyNum(2)} €
            </td>
          </tr>
          {#each Object.keys(summaryByYears[year].months).sort() ?? [] as month}
            {@const monthSummary = summaryByYears[year].months[month]}
            <tr class="bg-gray-200 font-bold">
              <td
                class="border-t-0 px-6 text-middle
              border-l-0 border-r-0 text-lg whitespace-nowrap p-4 pl-8"
              >
                <MwsTimeSlotIndicator
                  slots={monthSummary.bookedTimeSlot}
                />
              </td>
              <td
                class="border-t-0 px-6 text-left
                border-l-0 border-r-0 text-lg whitespace-break-spaces p-4 pl-8"
              >
                <div class="text-lg">[{year}-{month}]</div>
                {#each Object.keys(monthSummary.tags).sort() ??
                  [] as tagSlug}
                  {@const tag = monthSummary.tags[tagSlug]}
                  <span
                    class="inline-flex
                  text-xs font-medium p-1 text-center
                  border border-blue-800"
                  >
                    {tag.label} {
                      tag.pricePerHr ? `[${tag.pricePerHr.toPrettyNum(2)} €/hr]` : ''
                    }
                  </span>
                {/each}
              </td>
              <td
                class="border-t-0 px-6 text-right
                border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
              >
                {monthSummary.sumOfBookedHrs.toPrettyNum(2)} hr
              </td>
              <td
                class="border-t-0 px-6 text-right
                border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
              >
                {monthSummary.maxPPH.toPrettyNum(2)} €
              </td>
              <td
                class="border-t-0 px-6 text-right
                border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
              >
                {monthSummary.sumOfMaxPPH.toPrettyNum(2)} €
              </td>
            </tr>

            {#each Object.keys(monthSummary.days).sort() ?? [] as day}
              {@const daySummary = summaryByDays[day]}
              <tr class="font-semibold">
                <td
                  class="border-t-0 px-6 text-middle
                border-l-0 border-r-0 text-lg whitespace-nowrap p-4 pl-12"
                >
                  <MwsTimeSlotIndicator
                    slots={daySummary.bookedTimeSlot}
                  />
                </td>
                <td
                  class="border-t-0 px-6 text-left
                  border-l-0 border-r-0 text-lg whitespace-break-spaces p-4 pl-12"
                >
                  <div class="text-lg">[{day}]</div>
                  {#each Object.keys(daySummary.tags ?? {}).sort() ??
                    [] as tagSlug}
                    {@const tag = daySummary.tags[tagSlug]}
                    <span
                      class="inline-flex
                    text-xs font-medium p-1 text-center
                    border border-blue-800"
                    >
                      {tag.label} {
                        tag.pricePerHr ? `[${tag.pricePerHr.toPrettyNum(2)} €/hr]` : ''
                      }
                    </span>
                  {/each}
                </td>
                <td
                  class="border-t-0 px-6 text-right
                  border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
                >
                  {daySummary.sumOfBookedHrs?.toPrettyNum(2)} hr
                </td>
                <td
                  class="border-t-0 px-6 text-right
                  border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
                >
                  {daySummary.maxPPH?.toPrettyNum(2)} €
                </td>
                <td
                  class="border-t-0 px-6 text-right
                  border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
                >
                  {daySummary.sumOfMaxPPH?.toPrettyNum(2)} €
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
                  <tr class="text-gray-600">
                    <td
                      class="border-t-0 px-6 text-middle text-right
                    border-l-0 border-r-0 text-lg whitespace-nowrap p-4 pl-16"
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
                      class="border-t-0 px-6 text-left
                      border-l-0 border-r-0 text-lg whitespace-break-spaces p-4 pl-16"
                    >
                      <div class="text-lg">
                        [{timings.sourceStamp?.split('/').slice(-1) ?? timings.id}]
                      </div>
                      {#each Object.keys(timings.tags ?? {}).sort() ??
                        [] as tagSlug}
                        {@const tag = timings.tags[tagSlug]}
                        <span
                          class="inline-flex
                          text-xs font-medium p-1 text-center
                        border border-blue-800"
                        >
                          {tag.label} {
                            tag.pricePerHr ? `[${tag.pricePerHr.toPrettyNum(2)} €/hr]` : ''
                          }
                        </span>
                      {/each}
                    </td>
                    <td
                      class="border-t-0 px-6 text-right
                      border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
                    >
                      {(10/60).toPrettyNum(2)} hr
                    </td>
                    <td
                      class="border-t-0 px-6 text-right
                      border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
                    >
                      {timings.maxPricePerHr?.toPrettyNum(2)} €
                    </td>
                    <td
                      class="border-t-0 px-6 text-right
                      border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
                    >
                      {((timings.maxPricePerHr ?? 0) * (10/60)).toPrettyNum(2)} €
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
