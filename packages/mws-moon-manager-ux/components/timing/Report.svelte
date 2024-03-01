<script context="module">
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  // https://day.js.org/docs/en/timezone/set-default-timezone
  // https://day.js.org/docs/en/plugin/timezone
  var utc = require('dayjs/plugin/utc')
  var timezone = require('dayjs/plugin/timezone') // dependent on utc plugin
  dayjs.extend(utc);
  dayjs.extend(timezone); // TODO : user config for self timezone... (slot is computed on UTC date...)
  dayjs.tz.setDefault("Europe/Paris");
</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import MwsTimeSlotIndicator from "../layout/widgets/MwsTimeSlotIndicator.svelte";
  import ReportSummaryRows from "./ReportSummaryRows.svelte";
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
    path.forEach((key) => {
      if (!(key in obj)) {
        obj[key] = val;
      }
      obj = obj[key];
    });
  };

  dayjs.locale("fr"); // Fr locale // TODO : global config instead of per module ?

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
        sourceDate: tSum.sourceDate,
        sourceTime: tSum.sourceTime,
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
    subTags: [],
  };
  let summaryByDays = {};
  let summaryTotals = {
    sumOfBookedHrs: 0,
    sumOfMaxPPH: 0,
  };
  const bookedTimeSlotWithDate = {};
  timingsReport.forEach((tReport) => {
    const ids = tReport.ids.split(",");

    ids.forEach((tId) => {
      const t = timingsByIds[tId];
      const delta = 10 / 60.0;
      const deltaOfMaxPPH = delta * (t.maxPricePerHr ?? 0);

      // summaryByLevels
      let usedByReport = false;
      /*
      {
        subTags: [ // LVL 1
          {
            bookedTimeSlot:...,
            subTags: [ // LVL 2
              {
                subTags: [ // LVL 3
                  ... etc...
                ]
              }
            ]
          }
        ]
      }
      */
      // const currentSubTags = summaryByLevels.subTags;
      // let lvlStack = [];
      // [1, 2, 3, 4, 5].forEach((level) => {
      //   lvlStack = lvlStack.concat([]);
      //   jsonReport[`lvl${level}Tags`].forEach((tag, tagIdx) => {
      //     if (tag in t.tags) {
      //       ensurePath(currentSubTags, [tagIdx], {});
      //       const subTag = currentSubTags[tagIdx];

      // return false if want to keep outside of report :
      const loadUnclassified = (level, currentSubTags) => {
        // tagIdx = notClassifiedIdx;
        //         tag = `${t.tags[tag].label} - Non classé`;
        //         ensurePath(subTag.subTags, [tagIdx], {
        //           label: tag,
        //           deepLvl: level,
        //         });
        //         subTag = subTag.subTags[tagIdx];
        return false;
      };

      const loadLevel = (level, currentSubTags) => {
        // console.debug("Load lvl",level, jsonReport);
        const notClassifiedIdx = 7; // jsonReport[`lvl${level}Tags`]?.length ?? 0;
        let subLevelOk = !jsonReport[`lvl${level}Tags`].length;
        jsonReport[`lvl${level}Tags`].forEach((tag, tagIdx) => {
          if (tag in t.tags) {
            ensurePath(currentSubTags, [tagIdx], {
              deepLvl: level,
            });
            let subTag = currentSubTags[tagIdx];
            if (level <= 5) {
              ensurePath(subTag, ["subTags"], []);
              subLevelOk = loadLevel(level + 1, subTag.subTags);
              if (!subLevelOk) {
                subLevelOk = loadUnclassified(level, currentSubTags);
              }
            } else {
              subLevelOk = true;
            }
            if (subLevelOk) {
              ensurePath(subTag, ["bookedTimeSlot"], {});
              ensurePath(subTag, ["bookedTimeSlotWithDate"], {});
              // TODO : below init ok, but used at postprocess, so lazy init in postprocess only ?
              ensurePath(subTag, ["maxPPH"], 0);
              ensurePath(subTag, ["sumOfBookedHrs"], 0);
              ensurePath(subTag, ["sumOfMaxPPH"], 0);
              ensurePath(subTag, ["deepSumOfBookedHrs"], 0);
              ensurePath(subTag, ["deepSumOfMaxPPH"], 0);
              ensurePath(subTag, ["tags"], {});

              ensurePath(subTag, ["subTags"], []);

              const slotWithDate = t.sourceDate + "-" + t.rangeDayIdxBy10Min;
              // if (!(subTag.bookedTimeSlotWithDate[slotWithDate] ?? null)) {
              //   // TODO : save max one, post process.... ?
              subTag.bookedTimeSlotWithDate[slotWithDate] = {
                ...(subTag.bookedTimeSlotWithDate[slotWithDate] ?? {}),
                ...{ [t.id]: true },
              };
              //   subTag.deepSumOfBookedHrs += delta;
              //   t.usedForDeepTotal = true; // TODO : post process compute ?
              // }
              // if (!(bookedTimeSlotWithDate[slotWithDate] ?? null)) {
              //   // TODO : save max one, post process....
              bookedTimeSlotWithDate[slotWithDate] = {
                ...(bookedTimeSlotWithDate[slotWithDate] ?? {}),
                ...{ [t.id]: true },
              };
              //   t.usedForTotal = true; // TODO : post process compute ?
              //   // TIPS : below will be ok si childe level is previously loaded by recursion
              //   subTag.sumOfBookedHrs += delta;
              // }

              subTag.label = subTag.label ?? t.tags[tag].label; // TODO : slot count and how to reduce duplicated booked slot and extract maxPPH from it...
              if (!subTag.ids?.includes(t.id)) {
                // only add once
                subTag.ids = [...(subTag.ids ?? []), ...[t.id]];
              }
              subTag.tags = {
                ...(subTag.tags ?? {}),
                ...(t.tags ?? {}),
              };
              // subTag.maxPPH = Math.max(
              //   subTag?.maxPPH ?? 0,
              //   t.maxPricePerHr ?? 0
              // );
              subTag.bookedTimeSlot[t.rangeDayIdxBy10Min] = {
                ...(subTag.bookedTimeSlot[t.rangeDayIdxBy10Min] ?? {}),
                ...{ [t.id]: true },
              };
              usedByReport = true;
              subLevelOk = true;
            }
          } else {
            // console.debug('No tag found in ', t.tags, ' for ', tag)
          }
        });
        // currentSubTags = currentSubTags.filter(v => v !== null);
        // In place filter :
        // https://stackoverflow.com/questions/37318808/what-is-the-in-place-alternative-to-array-prototype-filter
        currentSubTags.splice(
          0,
          currentSubTags.length,
          ...currentSubTags.filter((v) => !!(v?.tags ?? false))
        );

        return subLevelOk;
      };
      loadLevel(1, summaryByLevels.subTags);
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
      // if (
      //   !(
      //     summaryByDays[tReport.sourceDate].bookedTimeSlot[
      //       t.rangeDayIdxBy10Min
      //     ] ?? null
      //   )
      // ) {
      //   summaryByDays[tReport.sourceDate].sumOfBookedHrs += delta;
      //   // TODO : wrong max, only max of fist booked slot :
      //   summaryByDays[tReport.sourceDate].sumOfMaxPPH += deltaOfMaxPPH;
      //   summaryTotals.sumOfBookedHrs += delta;
      //   summaryTotals.sumOfMaxPPH += deltaOfMaxPPH;
      // }
      if (!summaryByDays[tReport.sourceDate].ids?.includes(t.id)) {
        summaryByDays[tReport.sourceDate].ids = [
          ...(summaryByDays[tReport.sourceDate].ids ?? []),
          ...[t.id],
        ];
      }
      summaryByDays[tReport.sourceDate].tags = {
        ...(summaryByDays[tReport.sourceDate].tags ?? {}),
        ...(t.tags ?? {}),
      };
      // summaryByDays[tReport.sourceDate].maxPPH = Math.max(
      //   summaryByDays[tReport.sourceDate]?.maxPPH ?? 0,
      //   t.maxPricePerHr ?? 0
      // );
      summaryByDays[tReport.sourceDate].bookedTimeSlot[t.rangeDayIdxBy10Min] = {
        ...(summaryByDays[tReport.sourceDate].bookedTimeSlot[
          t.rangeDayIdxBy10Min
        ] ?? {}),
        ...{ [t.id]: true },
      };
    });
  });

  const postprocessLevel = (level, currentSubTags) => {
    currentSubTags.forEach((subTag) => {
      // TIPS : recursion first to have child result computed in parent recursion
      if (level <= 5) {
        postprocessLevel(level + 1, subTag.subTags);
      }

      Object.keys(bookedTimeSlotWithDate).forEach((slotSegment) => {
        const slotIds = bookedTimeSlotWithDate[slotSegment];
        let maxSlot = null;
        Object.keys(slotIds).forEach((slotId) => {
          const timeSlot = timingsByIds[slotId] ?? null;
          if ((timeSlot?.maxPricePerHr ?? 0) > (maxSlot?.maxPricePerHr ?? 0)) {
            maxSlot = timeSlot;
          }
        });
        // TODO : Opti : use object hashmap instead of includes ?
        if (maxSlot?.usedForTotal || !subTag.ids.includes(maxSlot.id)) {
          return; // Do not re-compute if already added for TOTAL
        }
        const delta = maxSlot ? 10 / 60 : 0; // TODO : const for segment config instead of '10'
        const maxPPH = (maxSlot?.maxPricePerHr ?? 0);
        const deltaPrice = maxPPH * delta;
        subTag.sumOfBookedHrs += delta;
        subTag.sumOfMaxPPH += deltaPrice;
        subTag.maxPPH = Math.max(subTag.maxPPH ?? 0, maxPPH);
        maxSlot?.usedForTotal = true;
      });
      Object.keys(subTag.bookedTimeSlotWithDate).forEach((slotSegment) => {
        const slotIds = subTag.bookedTimeSlotWithDate[slotSegment];
        let maxSlot = null;
        Object.keys(slotIds).forEach((slotId) => {
          const timeSlot = timingsByIds[slotId] ?? null;
          if ((timeSlot?.maxPricePerHr ?? 0) > (maxSlot?.maxPricePerHr ?? 0)) {
            maxSlot = timeSlot;
          }
        });
        // // TODO : Opti : use object hashmap instead of includes ?
        // if (maxSlot?.usedForDeepTotal || !subTag.ids.includes(maxSlot.id)) {
        //   return; // Do not re-compute if already added for deep TOTAL
        // }

        const delta = maxSlot ? 10 / 60 : 0; // TODO : const for segment config instead of '10'
        const maxPPH = (maxSlot?.maxPricePerHr ?? 0);
        const deltaPrice = maxPPH * delta;
        subTag.deepSumOfBookedHrs += delta;
        subTag.deepSumOfMaxPPH += deltaPrice;
        // TODO : deepMaxPPH <> of maxPPH or not usefull ?
        subTag.deepMaxPPH = Math.max(subTag.deepMaxPPH ?? 0, maxPPH);
        maxSlot?.usedForDeepTotal = true;
      });
      subTag.subTags.forEach(childSubTag => {
        // Add child TOTAL, ok since after recursion call, child are computed first
        subTag.sumOfBookedHrs += childSubTag.sumOfBookedHrs;
        subTag.sumOfMaxPPH += childSubTag.sumOfMaxPPH;
        subTag.maxPPH = Math.max(
          subTag.maxPPH ?? 0,
          childSubTag.maxPPH ?? 0,
        );

        // subTag.deepSumOfBookedHrs += childSubTag.deepSumOfBookedHrs;
        // subTag.deepSumOfMaxPPH += childSubTag.deepSumOfMaxPPH;
        // subTag.deepMaxPPH = Math.max(
        //   subTag.deepMaxPPH ?? 0,
        //   childSubTag.deepMaxPPH ?? 0,
        // );
      });

    });
  };

  postprocessLevel(1, summaryByLevels.subTags);

  summaryByLevels.subTags.forEach((subTag) => {
    summaryByLevels.sumOfBookedHrs += subTag.sumOfBookedHrs;
    summaryByLevels.sumOfMaxPPH += subTag.sumOfMaxPPH;
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
        deepSumOfBookedHrs: 0,
        deepSumOfMaxPPH: 0,
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
        deepSumOfBookedHrs: 0,
        deepSumOfMaxPPH: 0,
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

    Object.keys(summary.bookedTimeSlot).forEach((slotSegment) => {
      const slotIds = summary.bookedTimeSlot[slotSegment];
      let maxSlot = null;
      Object.keys(slotIds).forEach((slotId) => {
        const timeSlot = timingsByIds[slotId] ?? null;
        if ((timeSlot?.maxPricePerHr ?? 0) > (maxSlot?.maxPricePerHr ?? 0)) {
          maxSlot = timeSlot;
        }
      });
      // TODO : only count for not used time slot for regular price...
      const delta = maxSlot ? 10 / 60 : 0; // TODO : const for segment config instead of '10'
      const maxPPH = (maxSlot?.maxPricePerHr ?? 0);
      const deltaPrice = maxPPH * delta;

      summaryByYears[tYear].sumOfBookedHrs += delta;
      summaryByYears[tYear].sumOfMaxPPH += deltaPrice;
      summaryByYears[tYear].maxPPH = Math.max(
        summaryByYears[tYear].maxPPH ?? 0, maxPPH
      );
      summaryByYears[tYear].months[tMonth].sumOfBookedHrs += delta;
      summaryByYears[tYear].months[tMonth].sumOfMaxPPH += deltaPrice;
      summaryByYears[tYear].months[tMonth].maxPPH = Math.max(
        summaryByYears[tYear].months[tMonth].maxPPH ?? 0, maxPPH
      );

      summaryByYears[tYear].deepSumOfBookedHrs += delta;
      summaryByYears[tYear].deepSumOfMaxPPH += deltaPrice;
      summaryByYears[tYear].months[tMonth].deepSumOfBookedHrs += delta;
      summaryByYears[tYear].months[tMonth].deepSumOfMaxPPH += deltaPrice;
      // summaryByYears[tYear].months[tMonth].deepPaxPPH = Math.max(
      //   summaryByYears[tYear].months[tMonth].deepPaxPPH, maxPPH
      // );

      const daySummary = summaryByDays[tDay];
      ensurePath(daySummary, ["deepSumOfBookedHrs"], 0);
      ensurePath(daySummary, ["deepSumOfMaxPPH"], 0);
      daySummary.deepSumOfBookedHrs += delta;
      daySummary.deepSumOfMaxPPH += deltaPrice;
      daySummary.maxPPH = Math.max(daySummary.maxPPH, maxPPH);

      daySummary.sumOfBookedHrs += delta;
      daySummary.sumOfMaxPPH += deltaPrice;
      summaryTotals.sumOfBookedHrs += delta;
      summaryTotals.sumOfMaxPPH += deltaPrice;

      if (maxSlot) {
        maxSlot.usedForDeepTotal = true;
        maxSlot.usedForTotal = true;
      }
    });

  });

  console.debug(
    "timingsByIds[0] :",
    timingsByIds[Object.keys(timingsByIds)[0]] ?? null
  );
  console.debug("summaryByDays :", summaryByDays);
  console.debug("summaryByYears :", summaryByYears);
  console.debug("summaryByLevels :", summaryByLevels);

  const slotPath = (timingSlot) =>
    Routing.generate("mws_timing_fetchMediatUrl", {
      // encodeURI('file://' + timingSlot.source.path)
      url: "file://" + timingSlot.sourceStamp,
    });

  // Number.prototype.toPrettyNum = (length: number) => {
  Number.prototype.toPrettyNum = function (this: Number, length: number) {
    var s = this;
    return s
      .toFixed(length)
      .replace(".", ",")
      .replace(/\B(?=(\d{3})+(?!\d))/g, " ");
  };

  declare interface Number {
    toPrettyNum(length: number): string;
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
    <button> Qualification des temps </button>
  </a>
  <button on:click={() => (showDetails = !showDetails)}>
    {showDetails ? "Cacher" : "Voir"} les details
  </button>
  {#if showDetails}
    <button on:click={() => (showPictures = !showPictures)}>
      {showPictures ? "Cacher" : "Voir"} les screenshots
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
      </button>
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
      </button>
    </div>
    <div id="config-report" class="detail w-full hidden">
      {@html reportForm}
    </div>
  </div>
  <div>
    {#each [1, 2, 3, 4, 5] as lvl}
      {@html (jsonReport[`lvl${lvl}Tags`] ?? false) &&
      jsonReport[`lvl${lvl}Tags`].length
        ? `<strong>Tags du rapport de niveau ${lvl} : </strong>` +
          jsonReport[`lvl${lvl}Tags`].reduce(
            (acc, f) => `
            ${acc} [${f}]
          `,
            ``
          ) +
          "<br/>"
        : ""}
    {/each}
  </div>
  <div>
    {@html jsonLookup.customFilters && jsonLookup.customFilters.length
      ? "<strong>Filtres actifs : </strong>" +
        jsonLookup.customFilters.reduce(
          (acc, f) => `
          ${acc} [${f}]
        `,
          ``
        ) +
        "<br/>"
      : ""}
    {@html jsonLookup.searchTags && jsonLookup.searchTags.length
      ? "<strong>Tags à rechercher : </strong>" +
        jsonLookup.searchTags.reduce(
          (acc, f) => `
          ${acc} [${f}]
        `,
          ``
        ) +
        "<br/>"
      : ""}
    {@html jsonLookup.searchTagsToAvoid && jsonLookup.searchTagsToAvoid.length
      ? "<strong>Tags à éviter : </strong>" +
        jsonLookup.searchTagsToAvoid.reduce(
          (acc, f) => `
          ${acc} [${f}]
        `,
          ``
        ) +
        "<br/>"
      : ""}
    {@html jsonLookup.searchKeyword
      ? `<strong>Mots clefs : </strong>${jsonLookup.searchKeyword}`
      : ``}
  </div>

  <!-- {JSON.stringify(timings)} -->
  <!-- <div>{@html timingsPaginator}</div> // TODO : not used, synth all... -->
  <div>
    {Object.keys(timingsByIds).length} points de contrôles sur {Object.keys(
      summaryByDays
    ).length} jours
  </div>
  <br />
  <div class="text-lg">Rapport des temps via segmentations de 10 minutes.</div>
  <br />
  <div class="text-lg font-extrabold">
    {summaryByLevels.sumOfBookedHrs.toPrettyNum(2)} hours au total.
  </div>
  <div class="text-lg font-extrabold">
    {summaryByLevels.sumOfMaxPPH.toPrettyNum(2)} € en tout.
  </div>
  <br />
  <br />
  <div class="text-lg font-extrabold">
    {summaryTotals.sumOfBookedHrs.toPrettyNum(2)} hours annexes.
  </div>
  <div class="text-lg font-extrabold">
    {summaryTotals.sumOfMaxPPH.toPrettyNum(2)} € annexes.
  </div>
  <br />
  <br />

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
        {#each summaryByLevels.subTags ?? [] as subTag, tagIdx}
          {#if subTag ?? false}
            <ReportSummaryRows
              summary={subTag}
              label={subTag.label}
              subLevelKeys={Array.from(subTag.subTags?.keys() ?? [])}
              rowClass="bg-gray-300 font-bold"
              {showDetails}
              {showPictures}
              {summaryByDays}
              {timingsByIds}
            />
          {/if}
        {/each}
        {#if Object.keys(summaryByYears).length}
          <!-- // https://shuffle.dev/tailwind/classes/grid/col-span-1+%25_F+.col-span-* ? -->
          <tr class="border-2 border-blue-600">
            <td colspan="100%">
              <br /><br />
              Reste à qualifier :
            </td>
          </tr>
        {/if}
        {#each Object.keys(summaryByYears).sort() ?? [] as year, idx}
          <ReportSummaryRows
            summary={summaryByYears[year]}
            label={year}
            subLevelKeys={Object.keys(summaryByYears[year].months).sort() ?? []}
            {showDetails}
            {showPictures}
            {summaryByDays}
            {timingsByIds}
          />
        {/each}
      </tbody>
    </table>
  </div>
</div>
