<script context="module">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  // https://day.js.org/docs/en/timezone/set-default-timezone
  // https://day.js.org/docs/en/plugin/timezone
  var utc = require("dayjs/plugin/utc");
  var timezone = require("dayjs/plugin/timezone"); // dependent on utc plugin
  dayjs.extend(utc);
  dayjs.extend(timezone); // TODO : user config for self timezone... (slot is computed on UTC date...)
  dayjs.tz.setDefault("Europe/Paris");
  // dayjs.tz.setDefault("Europe/London");
</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import debounce from "lodash/debounce";
  import MwsTimeSlotIndicator from "../layout/widgets/MwsTimeSlotIndicator.svelte";
  import ReportSummaryRows from "./ReportSummaryRows.svelte";
  import ExportTags from "./tags/ExportTags.svelte";
  import ImportTimings from "./ImportTimings.svelte";
  import ExportTimings from "./ExportTimings.svelte";
  import _ from "lodash";
  import ConfidentialityStamp from "./ConfidentialityStamp.svelte";
  import Loader from "../layout/widgets/Loader.svelte";
  import AddModal from "./tags/AddModal.svelte";
  import { debug } from "svelte/internal";
  import Base from "../layout/Base.svelte";

  export let locale;
  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
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

  // TODO : from services ? reactive store ?
  // => in current configuration, no Svelte Base layout
  //    is used, base twig layout is used.... so need to compute is mobile
  //    below do not take resize stuff, etc, cf Svelte base layout
  const isMobileRule = "(max-width: 768px) and (min-height: 480px)";
  export let isMobile = window.matchMedia(isMobileRule)?.matches;
  const isWideRule =
    "only screen and (max-height: 480px) and (max-width: 960px)";
  export let isWide = window.matchMedia(isWideRule)?.matches;
  export let reportScale; // = isMobile ? 67 : 100;
  $: reportScale = isMobile ? 67 : isWide ? 69 : 100;

  const urlParams = new URLSearchParams(window.location.search);
  const pageNumber = urlParams.get("page") ?? "1";

  // DEV stuffs : TODO : from user confogs or app configs if build in debug mode ?
  const debugReport = false;

  const ensurePath = (obj, path, val = {}) => {
    path.forEach((key) => {
      if (!(key in obj)) {
        obj[key] = val;
      }
      obj = obj[key];
    });
  };

  // max between 2 object having maxPath object
  // TODO : maybe refactor and do max between path instead of parent object ?
  const pickMaxBetween = (a, b, maxAttribute = "maxPath") => {
    if (!a || !a[maxAttribute]) return b;
    if (!b || !b[maxAttribute]) return a;
    const aPriority = Number(a[maxAttribute].maxLimitPriority ?? 0);
    const bPriority = Number(b[maxAttribute].maxLimitPriority ?? 0);
    // console.debug('pickMaxBetween  : ', aPriority, bPriority)

    if (
      aPriority > bPriority ||
      (aPriority == bPriority &&
        a[maxAttribute].maxValue > b[maxAttribute].maxValue)
    ) {
      return a;
    } else {
      return b;
    }
  };

  const sumOfMaxPathPerHr = (p1, p2, p2hrDelta) => {
    return {
      sumPath: [
        // TODO : might be too heavy to track all sum path ? debug only ?
        ...(p1?.sumPath ?? p1 ? [p1] : []),
        ...(p2?.sumPath ?? p2 ? [p2] : []),
      ],
      maxValue: (p1?.maxValue ?? 0) + (p2?.maxValue ?? 0) * p2hrDelta,
    };
  };

  dayjs.locale("fr"); // Fr locale // TODO : global config instead of per module ?

  const searchLookup = JSON.parse(decodeURIComponent(lookup.jsonResult));

  console.debug("Report having timingsReport", timingsReport);
  // console.debug('Report having timings', timings);

  const jsonReport = JSON.parse(decodeURIComponent(report.jsonResult));
  console.debug("Report : ", jsonReport);

  const timingsByIds = {};

  let summaryByLevels;
  let summaryReportByDays;
  let summaryByDays;
  let summaryTotals;
  let summaryByYears;

  const loadReport = async () => {
    summaryByLevels = {
      sumOfBookedHrs: 0,
      deepSumOfBookedHrs: 0,
      subTags: [],
    };
    summaryReportByDays = {};
    summaryByDays = {};
    summaryTotals = {
      sumOfBookedHrs: 0,
      // sumOfMaxPPH: 0,
    };
    summaryByYears = {};

    timingsReport.forEach((tSum) => {
      const ids = tSum.ids.split(",");
      const tagSlugs = tSum.tags?.split(",");
      const labels = tSum.labels?.split(",");
      const allRangeDayIdxBy10Min = tSum.allRangeDayIdxBy10Min.split(",");
      // const maxPath = JSON.parse(tSum.maxPath);
      const maxPaths = tSum.maxPath?.split("#_;_#");
      // const maxPaths = tSum.maxPaths?.split(",");
      // const pricePerHr = maxPath ?? 0; // TODO: handle priority
      const sourceStamps = tSum.sourceStamps?.split(",");

      // console.debug(maxPath);

      console.assert(
        !tagSlugs || tagSlugs.length == ids.length,
        tagSlugs?.length + " Wrong DATASET, <> tagSlugs found " + ids.length
      );
      console.assert(
        allRangeDayIdxBy10Min.length == ids.length,
        allRangeDayIdxBy10Min?.length +
          " Wrong DATASET, <> allRangeDayIdxBy10Min found for " +
          ids.length
      );

      // console.assert( // replaced by maxPath system
      //   !pricesPerHr || pricesPerHr.length == ids.length,
      //   "Wrong DATASET, <> pricesPerHr found"
      // );
      console.assert(
        // replaced by maxPath system
        !maxPaths || maxPaths.length == ids.length,
        maxPaths?.length + " Wrong DATASET, <> maxPaths found " + ids.length
      );

      console.assert(
        !sourceStamps || sourceStamps.length == ids.length,
        sourceStamps?.length +
          " Wrong DATASET, <> sourceStamps found " +
          ids.length
      );
      // const srcStamps = tSum.srcStamps.split(',');
      ids.forEach((tId, idx) => {
        const tagSlug = tagSlugs ? tagSlugs[idx] ?? null : null;
        const rangeDayIdxBy10Min = allRangeDayIdxBy10Min[idx];
        const sourceStamp = sourceStamps ? sourceStamps[idx] ?? null : null;
        // const pricePerHr = maxPath
        //   ? parseFloat(pricesPerHr[idx]) ?? null
        //   : null;
        // const maxPPH = Math.max(
        //   pricePerHr ?? 0,
        //   timingsByIds[tId]?.maxPricePerHr ?? 0
        // );
        const maxPath = JSON.parse((maxPaths ?? {})[idx] ?? "{}");
        const label = labels ? labels[idx] ?? null : null;
        const tags = {
          ...(tagSlug
            ? {
                [tagSlug]: {
                  label: label,
                  // pricePerHr: pricePerHr,
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
          sourceTimeGMT: dayjs.unix(tSum.sourceTimeGMTstamp),
          sourceStamp: sourceStamp,
          rangeDayIdxBy10Min: rangeDayIdxBy10Min,
          // maxPricePerHr: maxPPH,
          maxPath: maxPath,
          tags: tags,
          // tags: timingsByIds[tId]?.tags ?? {},
        };
      });
      // if (timingsByIds[tId]) {
      //   console.debug(timingsByIds[tSum], tSum);
      //   console.assert(, "Wrong DATASET, <> timings found");
      // }
    });

    const bookedTimeSlotWithDate = {};
    timingsReport.forEach((tReport) => {
      const ids = tReport.ids.split(",");

      ids.forEach((tId) => {
        const t = timingsByIds[tId];
        const delta = 10 / 60.0;
        // const deltaOfMaxPPH = delta * (t.maxPricePerHr ?? 0);

        // summaryByLevels
        let usedByReport = false;
        const ensureLevelItem = (subTag, t, tag) => {
          ensurePath(subTag, ["bookedTimeSlot"], {});
          ensurePath(subTag, ["bookedTimeSlotWithDate"], {});
          // TODO : below init ok, but used at postprocess, so lazy init in postprocess only ?
          // ensurePath(subTag, ["maxPPH"], 0);
          ensurePath(subTag, ["sumOfBookedHrs"], 0);
          // ensurePath(subTag, ["sumOfMaxPPH"], 0);
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

          // TODO : why reusing existing label is messing up tags for childs for :
          // http://localhost:8000/mws/fr/mws-timings/report?page=1&tags%5B0%5D=miguel-monwoo&lvl1Tags%5B0%5D=miguel-monwoo&lvl2Tags%5B0%5D=swann&lvl3Tags%5B0%5D=suivi-des-formations-pour-swann&lvl3Tags%5B1%5D=es-google-meet ?
          subTag.label = subTag.label ?? t.tags[tag].label; // TODO : slot count and how to reduce duplicated booked slot and extract maxPPH from it...
          // subTag.label = t.tags[tag].label; // TODO : slot count and how to reduce duplicated booked slot and extract maxPPH from it...
          if (!subTag.haveIds) {
            subTag.haveIds =
              subTag.ids?.reduce((acc, tId) => {
                acc[tId] = true;
                return acc;
              }, {}) ?? {};
          }

          // if (!subTag.ids?.includes(t.id)) {
          if (!(subTag.haveIds[t.id] ?? null)) {
            // only add once
            subTag.ids = [...(subTag.ids ?? []), ...[t.id]];
            subTag.haveIds[t.id] = true;
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
        };
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
        //   jsonReport[`lvl${level}Tags`]?.forEach((tag, tagIdx) => {
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

          if (level === 1) {
            // Will go in by years/months/days left to qualify report :
            return false;
          }
          // TODO : bring sub labels up in hierarchy if exists (ex : etude-devis without devis number)
          // + use '--' tag if no sub tag for lefts ones
          // subTag.label = "--";

          // const notClassifiedIdx = 7; // jsonReport[`lvl${level}Tags`]?.length ?? 0;
          const notClassifiedIdx = jsonReport[`lvl${level}Tags`]?.length ?? 0;
          ensurePath(currentSubTags, [notClassifiedIdx], {
            label: "--",
            deepLvl: level,
            subTags: [],
          });
          const notClassifiedTag = currentSubTags[notClassifiedIdx];
          ensureLevelItem(notClassifiedTag, t, "--");
          if (notClassifiedTag.subTags.length) {
            // should not keeo ids for details of parent item, only for leafs nodes
            notClassifiedTag.ids = null;
          }

          currentSubTags = notClassifiedTag.subTags;
          level += 1;

          // TODO : why not simply reuse with extra params ? :
          // loadLevel(level + 1, notClassifiedTag.subTags);

          jsonReport[`lvl${level}Tags`]?.forEach((tag, tagIdx) => {
            if (tag in t.tags) {
              ensurePath(currentSubTags, [tagIdx], {
                deepLvl: level,
              });
              let subTag = currentSubTags[tagIdx];
              debugReport &&
                (console.debug(
                  `[loadUnclassified][${level}] at ${tagIdx}  for ${tag}`
                ) ||
                  console.debug(
                    `[loadUnclassified][${level}][${tagIdx}] ${tag} INIT`,
                    subTag
                  ));
              if (level <= 5) {
                ensurePath(subTag, ["subTags"], []);
                loadLevel(level + 1, subTag.subTags);
                // loadUnclassified(level + 1, subTag.subTags, subTag);
              }
              ensureLevelItem(subTag, t, tag);

              debugReport &&
                console.debug(
                  `[loadUnclassified][${level}][${tagIdx}] ${tag} DONE`,
                  subTag
                );
            } else {
              // Try one deep level
              if (level <= 5) {
                loadLevel(level, currentSubTags);
                // loadUnclassified(level, currentSubTags);
              }
              // debugReport && console.debug('No tag found in ', t.tags, ' for ', tag)
            }
          });
          // ensureLevelItem(notClassifiedTag, t, '--');

          return true;
        };

        const loadLevel = (level, currentSubTags) => {
          // console.debug("Load lvl",level, jsonReport);
          let subLevelOk = !jsonReport[`lvl${level}Tags`]?.length;
          jsonReport[`lvl${level}Tags`]?.forEach((tag, tagIdx) => {
            if (tag in t.tags) {
              ensurePath(currentSubTags, [tagIdx], {
                deepLvl: level,
              });
              let subTag = currentSubTags[tagIdx];
              debugReport &&
                (console.debug(
                  `[loadLevel][${level}] at ${tagIdx}  for ${tag}`
                ) ||
                  console.debug(
                    `[loadLevel][${level}][${tagIdx}] ${tag} INIT`,
                    subTag
                  ));
              if (level <= 5) {
                ensurePath(subTag, ["subTags"], []);
                subLevelOk = loadLevel(level + 1, subTag.subTags);
                if (!subLevelOk) {
                  subLevelOk = loadUnclassified(level + 1, subTag.subTags);
                }
              } else {
                subLevelOk = true;
              }
              if (subLevelOk) {
                ensureLevelItem(subTag, t, tag);
              }

              debugReport &&
                console.debug(
                  `[loadLevel][${level}][${tagIdx}] ${tag} DONE`,
                  subTag
                );
            } else {
              // debugReport && console.debug('No tag found in ', t.tags, ' for ', tag)
            }
          });
          // currentSubTags = currentSubTags.filter(v => v !== null);
          // In place filter :
          // https://stackoverflow.com/questions/37318808/what-is-the-in-place-alternative-to-array-prototype-filter

          // TIPS : BELOW will BREAK index order, do afterward instead
          // currentSubTags.splice(
          //   0,
          //   currentSubTags.length,
          //   ...currentSubTags.filter((v) => !!(v?.tags ?? false))
          // );

          return subLevelOk;
        };
        loadLevel(1, summaryByLevels.subTags);

        const fillSumByDays = (sumByDays) => {
          if (!(sumByDays[tReport.sourceDate] ?? null)) {
            sumByDays[tReport.sourceDate] = {
              bookedTimeSlot: {},
              sumOfBookedHrs: 0,
              tags: {},
            };
          }

          if (!sumByDays[tReport.sourceDate].haveIds) {
            sumByDays[tReport.sourceDate].haveIds =
              sumByDays[tReport.sourceDate].ids?.reduce((acc, tId) => {
                acc[tId] = true;
                return acc;
              }, {}) ?? {};
          }

          if (!(sumByDays[tReport.sourceDate].haveIds[t.id] ?? false)) {
            sumByDays[tReport.sourceDate].ids = [
              ...(sumByDays[tReport.sourceDate].ids ?? []),
              ...[t.id],
            ];
            sumByDays[tReport.sourceDate].haveIds[t.id] = true;
          }
          sumByDays[tReport.sourceDate].tags = {
            ...(sumByDays[tReport.sourceDate].tags ?? {}),
            ...(t.tags ?? {}),
          };
          sumByDays[tReport.sourceDate].bookedTimeSlot[t.rangeDayIdxBy10Min] = {
            ...(sumByDays[tReport.sourceDate].bookedTimeSlot[
              t.rangeDayIdxBy10Min
            ] ?? {}),
            ...{ [t.id]: true },
          };
        };

        if (usedByReport) {
          // do not summary in days or total report if already in configured report.
          fillSumByDays(summaryReportByDays);
        } else {
          fillSumByDays(summaryByDays);
        }
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
            // if (
            //   !maxSlot ||
            //   (timeSlot?.maxPricePerHr ?? 0) > (maxSlot?.maxPricePerHr ?? 0)
            // ) {
            //   maxSlot = timeSlot;
            // }
            maxSlot = pickMaxBetween(maxSlot, timeSlot);
          });
          // TIPS : failing to pick max priority rules at 3 ?
          // Was OK, hidden under another report tag qualif
          // console.log(slotSegment, 'did pick max : ', maxSlot);

          // TODO : Opti : use object hashmap instead of includes ?
          if (!subTag.haveIds) {
            subTag.haveIds =
              subTag.ids?.reduce((acc, tId) => {
                acc[tId] = true;
                return acc;
              }, {}) ?? {};
          }
          // if (maxSlot?.usedForTotal || !subTag.ids.includes(maxSlot.id)) {
          if (
            maxSlot?.usedForTotal ||
            !(subTag.haveIds[maxSlot?.id] ?? false)
          ) {
            return; // Do not re-compute if already added for TOTAL
          }
          // const delta = maxSlot ? 10 / 60 : 0; // TODO : const for segment config instead of '10'
          const delta = 10 / 60.0; // TODO : const for segment config instead of '10'
          // const maxPPH = maxSlot?.maxPricePerHr ?? 0;
          // const deltaPrice = maxPPH * delta;
          subTag.sumOfBookedHrs += delta;
          // subTag.sumOfMaxPPH += deltaPrice;
          // subTag.maxPPH = Math.max(subTag.maxPPH ?? 0, maxPPH);

          // sumOfMaxPPH => sumOfMaxPathPerHr.maxValue
          // + add 'sumHistory' : array of counted sums path ?
          //  => might be too heavy, ids of slots insteads ?
          // but no slot id for report tags levels...
          subTag.sumOfMaxPathPerHr = sumOfMaxPathPerHr(
            subTag.sumOfMaxPathPerHr,
            maxSlot?.maxPath,
            delta
          );
          subTag.maxPath = pickMaxBetween(subTag, maxSlot)?.maxPath ?? null;
          maxSlot?.usedForTotal = true;
        });
        Object.keys(subTag.bookedTimeSlotWithDate ?? {}).forEach(
          (slotSegment) => {
            const slotIds = subTag.bookedTimeSlotWithDate[slotSegment];
            let maxSlot = null;
            Object.keys(slotIds).forEach((slotId) => {
              const timeSlot = timingsByIds[slotId] ?? null;
              // TIPS : need loadash merge to keep reference to initial slot :
              // maxSlot = pickMaxBetween(maxSlot, {
              //   ...timeSlot, deepMaxPath: timeSlot.maxPath
              // }, 'deepMaxPath');
              maxSlot = pickMaxBetween(
                maxSlot,
                _.merge(timeSlot, {
                  deepMaxPath: timeSlot.maxPath,
                }),
                "deepMaxPath"
              );
            });

            if (maxSlot?.usedForDeepTotal) {
              return; // Do not re-compute if already added for TOTAL
            }

            const delta = 10 / 60; // TODO : const for segment config instead of '10'
            // const maxPPH = maxSlot?.maxPricePerHr ?? 0;
            // const deltaPrice = maxPPH * delta;
            subTag.deepSumOfBookedHrs += delta;
            // subTag.deepSumOfMaxPPH += deltaPrice;
            // // TODO : deepMaxPPH <> of maxPPH or not usefull ?
            // subTag.deepMaxPPH = Math.max(subTag.deepMaxPPH ?? 0, maxPPH);
            subTag.deepSumOfMaxPathPerHr = sumOfMaxPathPerHr(
              subTag.deepSumOfMaxPathPerHr,
              maxSlot?.deepMaxPath,
              delta
            );
            subTag.deepMaxPath =
              pickMaxBetween(subTag, maxSlot, "deepMaxPath")?.deepMaxPath ??
              null;

            maxSlot?.usedForDeepTotal = true;
          }
        );
        subTag.subTags.forEach((childSubTag) => {
          // Add child TOTAL, ok since after recursion call, child are computed first
          subTag.sumOfBookedHrs += childSubTag.sumOfBookedHrs;

          // subTag.sumOfMaxPPH += childSubTag.sumOfMaxPPH;
          // subTag.maxPPH = Math.max(subTag.maxPPH ?? 0, childSubTag.maxPPH ?? 0);
          subTag.sumOfMaxPathPerHr = sumOfMaxPathPerHr(
            subTag.sumOfMaxPathPerHr,
            childSubTag?.sumOfMaxPathPerHr,
            1
          );
          subTag.maxPath = pickMaxBetween(subTag, childSubTag)?.maxPath ?? null;

          subTag.deepSumOfBookedHrs += childSubTag.deepSumOfBookedHrs;
          // subTag.deepSumOfMaxPPH += childSubTag.deepSumOfMaxPPH;
          // subTag.deepMaxPPH = Math.max(
          //   subTag.deepMaxPPH ?? 0,
          //   childSubTag.deepMaxPPH ?? 0,
          // );
          subTag.deepSumOfMaxPathPerHr = sumOfMaxPathPerHr(
            subTag.deepSumOfMaxPathPerHr,
            childSubTag?.deepSumOfMaxPathPerHr,
            1
          );
          subTag.deepMaxPath =
            pickMaxBetween(subTag, childSubTag, "deepMaxPath")?.deepMaxPath ??
            null;
        });
      });
    };

    postprocessLevel(1, summaryByLevels.subTags);

    summaryByLevels.subTags.forEach((subTag) => {
      summaryByLevels.sumOfBookedHrs += subTag.sumOfBookedHrs;
      // summaryByLevels.sumOfMaxPPH += subTag.sumOfMaxPPH;
      summaryByLevels.sumOfMaxPathPerHr = sumOfMaxPathPerHr(
        summaryByLevels.sumOfMaxPathPerHr,
        subTag.sumOfMaxPathPerHr,
        1
      );
      summaryByLevels.maxPath =
        pickMaxBetween(summaryByLevels, subTag)?.maxPath ?? null;
      summaryByLevels.deepSumOfBookedHrs += subTag.deepSumOfBookedHrs;
      summaryByLevels.deepSumOfMaxPathPerHr = sumOfMaxPathPerHr(
        summaryByLevels.deepSumOfMaxPathPerHr,
        subTag.deepSumOfMaxPathPerHr,
        1
      );
      summaryByLevels.deepMaxPath =
        pickMaxBetween(summaryByLevels, subTag, "deepMaxPath")?.deepMaxPath ??
        null;
    });

    Object.keys(summaryByDays).forEach((tDay) => {
      const tDate = dayjs(tDay);
      const tMonth = tDate.format("MM");
      const tYear = tDate.format("YYYY");
      const daySummary = summaryByDays[tDay];
      // // TIPS : scan lvl + add + should filter detail tags....
      // //        might be better to scan at previous data transform ?
      // //        => summaryByDays will keep not matched time slots in summaryByLevels
      // if (!(summaryByLevels[lvlTag] ?? null)) {
      // }
      if (!(summaryByYears[tYear] ?? null)) {
        summaryByYears[tYear] = {
          bookedTimeSlot: {},
          sumOfBookedHrs: 0,
          // sumOfMaxPPH: 0,
          deepSumOfBookedHrs: 0,
          // deepSumOfMaxPPH: 0,
          // maxPPH: 0,
          tags: {},
          months: {},
        };
      }
      const yearSummary = summaryByYears[tYear];
      if (!(yearSummary?.months[tMonth] ?? null)) {
        yearSummary?.months[tMonth] = {
          bookedTimeSlot: {},
          sumOfBookedHrs: 0,
          // sumOfMaxPPH: 0,
          deepSumOfBookedHrs: 0,
          // deepSumOfMaxPPH: 0,
          // maxPPH: 0,
          tags: {},
          days: {},
        };
      }
      const monthSummary = yearSummary?.months[tMonth];
      Object.keys(daySummary.bookedTimeSlot).forEach((slotSegment) => {
        const slotIds = daySummary.bookedTimeSlot[slotSegment];
        let maxSlot = null;
        Object.keys(slotIds).forEach((slotId) => {
          const timeSlot = timingsByIds[slotId] ?? null;
          maxSlot = pickMaxBetween(maxSlot, timeSlot);
        });
        // TODO : only count for not used time slot for regular price...
        // const delta = maxSlot ? 10 / 60 : 0; // TODO : const for segment config instead of '10'
        const delta = 10 / 60; // TODO : const for segment config instead of '10'
        // const maxPPH = maxSlot?.maxPricePerHr ?? 0;
        // const deltaPrice = maxPPH * delta;

        yearSummary.sumOfBookedHrs += delta;
        // yearSummary.sumOfMaxPPH += deltaPrice;
        // yearSummary.maxPPH = Math.max(
        //   yearSummary.maxPPH ?? 0,
        //   maxPPH
        // );
        yearSummary.sumOfMaxPathPerHr = sumOfMaxPathPerHr(
          yearSummary.sumOfMaxPathPerHr,
          maxSlot?.maxPath,
          delta
        );
        yearSummary.maxPath =
          pickMaxBetween(yearSummary, maxSlot)?.maxPath ?? null;
        // Deep is same as normal since linear time view...
        yearSummary.deepSumOfMaxPathPerHr = yearSummary.sumOfMaxPathPerHr;
        yearSummary.deepSumOfBookedHrs = yearSummary.sumOfBookedHrs;
        yearSummary.deepMaxPath = yearSummary.maxPath;

        monthSummary.sumOfBookedHrs += delta;
        // monthSummary.sumOfMaxPPH += deltaPrice;
        // monthSummary.maxPPH = Math.max(
        //   monthSummary.maxPPH ?? 0,
        //   maxPPH
        // );
        monthSummary.sumOfMaxPathPerHr = sumOfMaxPathPerHr(
          monthSummary.sumOfMaxPathPerHr,
          maxSlot?.maxPath,
          delta
        );
        monthSummary.maxPath =
          pickMaxBetween(monthSummary, maxSlot)?.maxPath ?? null;
        // Deep is same as normal since linear time view...
        monthSummary.deepSumOfMaxPathPerHr = monthSummary.sumOfMaxPathPerHr;
        monthSummary.deepSumOfBookedHrs = monthSummary.sumOfBookedHrs;
        monthSummary.deepMaxPath = monthSummary.maxPath;

        // yearSummary.deepSumOfBookedHrs += delta;
        // yearSummary.deepSumOfMaxPPH += deltaPrice;
        // monthSummary.deepSumOfBookedHrs += delta;
        // monthSummary.deepSumOfMaxPPH += deltaPrice;

        // monthSummary.deepPaxPPH = Math.max(
        //   monthSummary.deepPaxPPH, maxPPH
        // );

        summaryTotals.sumOfBookedHrs += delta;
        // summaryTotals.sumOfMaxPPH += deltaPrice;
        summaryTotals.sumOfMaxPathPerHr = sumOfMaxPathPerHr(
          summaryTotals.sumOfMaxPathPerHr,
          maxSlot?.maxPath,
          delta
        );
        summaryTotals.maxPath =
          pickMaxBetween(summaryTotals, maxSlot)?.maxPath ?? null;

        if (maxSlot) {
          maxSlot.usedForDeepTotal = true;
          maxSlot.usedForTotal = true;
        }

        // yearSummary.sumOfMaxPathPerHr = sumOfMaxPathPerHr(yearSummary.sumOfMaxPathPerHr, maxSlot?.maxPath, delta);

        // monthSummary.sumOfMaxPathPerHr = sumOfMaxPathPerHr(monthSummary.sumOfMaxPathPerHr, maxSlot?.maxPath, 1);

        ensurePath(daySummary, ["deepSumOfBookedHrs"], 0);
        // ensurePath(daySummary, ["deepSumOfMaxPPH"], 0);
        daySummary.deepSumOfBookedHrs += delta;
        // daySummary.deepSumOfMaxPPH += deltaPrice;
        // daySummary.maxPPH = Math.max(daySummary.maxPPH, maxPPH);
        daySummary.sumOfBookedHrs += delta;
        // daySummary.sumOfMaxPPH += deltaPrice;

        daySummary.sumOfMaxPathPerHr = sumOfMaxPathPerHr(
          daySummary.sumOfMaxPathPerHr,
          maxSlot?.maxPath,
          delta
        );
        daySummary.maxPath =
          pickMaxBetween(daySummary, maxSlot)?.maxPath ?? null;
        // Deep is same as normal since linear time view...
        daySummary.deepSumOfMaxPathPerHr = daySummary.sumOfMaxPathPerHr;
        daySummary.deepMaxPath = daySummary.maxPath;
      });

      yearSummary.bookedTimeSlot = {
        ...yearSummary.bookedTimeSlot,
        ...daySummary.bookedTimeSlot,
      };
      // yearSummary.sumOfBookedHrs += daySummary.sumOfBookedHrs;
      // yearSummary.sumOfMaxPPH += daySummary.sumOfMaxPPH;
      // yearSummary.maxPPH = Math.max(
      //   yearSummary?.maxPPH ?? 0,
      //   daySummary.maxPPH ?? 0
      // );

      // WRONG : will count multiple times :
      // yearSummary.sumOfMaxPathPerHr = sumOfMaxPathPerHr(yearSummary.sumOfMaxPathPerHr, daySummary.sumOfMaxPathPerHr, 1);
      // yearSummary.maxPath = pickMaxBetween(yearSummary, daySummary)?.maxPath ?? null;
      // yearSummary.deepSumOfMaxPathPerHr = yearSummary.sumOfMaxPathPerHr
      // yearSummary.deepMaxPath = yearSummary.maxPath;

      yearSummary.tags = {
        ...(yearSummary?.tags ?? {}),
        ...(daySummary.tags ?? {}),
      };

      monthSummary.bookedTimeSlot = {
        ...monthSummary.bookedTimeSlot,
        ...daySummary.bookedTimeSlot,
      };
      // monthSummary.sumOfBookedHrs +=
      //   daySummary.sumOfBookedHrs;
      // monthSummary.sumOfMaxPPH += daySummary.sumOfMaxPPH;
      // monthSummary.maxPPH = Math.max(
      //   monthSummary?.maxPPH ?? 0,
      //   daySummary.maxPPH ?? 0
      // );
      // monthSummary.sumOfMaxPathPerHr = sumOfMaxPathPerHr(monthSummary.sumOfMaxPathPerHr, daySummary.sumOfMaxPathPerHr, 1);
      // monthSummary.maxPath = pickMaxBetween(monthSummary, daySummary)?.maxPath ?? null;
      // monthSummary.deepSumOfMaxPathPerHr = monthSummary.sumOfMaxPathPerHr;
      // monthSummary.deepMaxPath = monthSummary.maxPath;

      monthSummary.tags = {
        ...(monthSummary?.tags ?? {}),
        ...(daySummary.tags ?? {}),
      };

      monthSummary.days[tDay] = true; //daySummary;
    });

    console.debug(
      "timingsByIds[0] :",
      timingsByIds[Object.keys(timingsByIds)[0]] ?? null
    );
    console.debug("summaryByDays :", summaryByDays);
    console.debug("summaryByDays :", summaryByDays);
    console.debug("summaryByYears :", summaryByYears);
    console.debug("summaryByLevels :", summaryByLevels);
  };

  (async () => {
    // Async load to keep other UI builds as quick refresh interactions
    await loadReport();
  })();

  const slotPath = (timingSlot) =>
    Routing.generate("mws_timing_fetchMediatUrl", {
      // encodeURI('file://' + timingSlot.source.path)
      url: "file://" + timingSlot.sourceStamp,
    });

  // Number.prototype.toPrettyNum = (length: number) => {
  // TODO : buggy to overwrite with != code dups
  //     => factorize in one place ... (service/number.ts ?)
  Number.prototype.toPrettyNum = function (
    this: Number,
    length: number,
    maxLength = null
  ) {
    if (maxLength === null) maxLength = length;
    var s = this;
    const splited = s
      .toFixed(maxLength)
      .replace(new RegExp(`0{0,${maxLength - length}}$`), "")
      // https://stackoverflow.com/questions/5025166/javascript-number-formatting-min-max-decimals
      // .replace(/0{0,2}$/, "")
      // .toLocaleString('en-US', { // TODO : centralize toPrettyNum and use locals formatings ?
      //   minimumFractionDigits: 2,
      //   maximumFractionDigits: 4
      // })
      .replace(".", ",")
      .split(",");
    return (
      (splited[0] ?? "").replace(/\B(?=(\d{3})+(?!\d))/g, " ") +
      (length >= 1 ? "," : "") +
      (splited[1] ?? "")
    );
  };

  declare interface Number {
    toPrettyNum(length: number): string;
  }

  const deleteAllTimings = async () => {
    if (isLoading) return;
    isLoading = true;
    // TODO : Wait for loading animation to show
    // await tick();
    // await new Promise(r => setTimeout(r, 500));
    // Or use HTML modal instead of native blocking UI alert
    await new Promise((r) => setTimeout(r, 100));

    if (confirm("Are you sure you want to delete all timings ?")) {
      const data = {
        _csrf_token: stateGet(get(state), "csrfTimingDeleteAll"),
      };
      const formData = new FormData();
      for (const name in data) {
        formData.append(name, data[name]);
      }
      const resp = await fetch(
        Routing.generate("mws_timing_delete_all", {
          _locale: locale,
        }),
        {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          redirect: "error",
          headers: {
            Accept: "application/json",
          },
        }
      )
        .then(async (resp) => {
          console.log(resp.url, resp.ok, resp.status, resp.statusText);
          if (!resp.ok) {
            throw new Error("Not 2xx response", { cause: resp });
          } else {
            const data = await resp.json();
            console.debug("Did remove all tags, resp :", data);
            // TODO : remove self from DOM instead of isHidden ?
            // tags = [];
            stateUpdate(state, {
              csrfTimingDeleteAll: data.newCsrf,
            });
            window.location.reload();
          }
        })
        .catch((e) => {
          console.error(e);
          // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
          const shouldWait = confirm("Echec de l'enregistrement.");
        });
    }
    isLoading = false;
  };
</script>

<!-- // TODO : code factorization, inside component ? -->
<!-- TODO : no customFilters for timings ? {searchLookup.customFilters && searchLookup.customFilters.length
...  : ""} -->

<!-- TIPS : in JS only, for non empty strings
${acc} ${ idx > 0 && ',' || ''} ${f}
eq :
${acc} ${ idx > 0 ? ',' : ''} ${f} -->

<svelte:head>
  <title>
    Timings report
    {searchLookup.searchStart && searchLookup.searchStart.length
      ? "Début[" +
        dayjs(searchLookup.searchStart).format("YYYY-MM-DD HH:mm:ss") +
        "] "
      : ""}
    {searchLookup.searchEnd && searchLookup.searchEnd.length
      ? "Fin[" +
        dayjs(searchLookup.searchEnd).format("YYYY-MM-DD HH:mm:ss") +
        "]"
      : ""}
    {searchLookup.searchTags && searchLookup.searchTags.length
      ? "Tags[" +
        searchLookup.searchTags.reduce(
          (acc, f, idx) => `
          ${acc} ${(idx > 0 && ",") || ""} ${f}
        `,
          ``
        ) +
        "] "
      : ""}
    {searchLookup.searchTagsToInclude && searchLookup.searchTagsToInclude.length
      ? "Inclure[" +
        searchLookup.searchTagsToInclude.reduce(
          (acc, f, idx) => `
            ${acc} ${(idx > 0 && ",") || ""} ${f}
          `,
          ``
        ) +
        "]"
      : ""}
    {searchLookup.searchTagsToAvoid && searchLookup.searchTagsToAvoid.length
      ? "Exclure[" +
        searchLookup.searchTagsToAvoid.reduce(
          (acc, f, idx) => `
            ${acc} ${(idx > 0 && ",") || ""} ${f}
          `,
          ``
        ) +
        "]"
      : ""}
    {searchLookup.searchKeyword ? `[${searchLookup.searchKeyword}]` : ``}
  </title>
</svelte:head>

<div class="mws-timing-report flex flex-wrap items-center">
  <Loader {isLoading} />

  <a
    href={Routing.generate("mws_timings_qualif", {
      _locale: locale ?? "fr",
      ...Object.keys($state.mwsTimingLookupFields ?? [])
        .filter((lf) => $state.mwsTimingLookupFields[lf])
        .reduce((acc, lf) => {
          // TIPS :
          // https://stackoverflow.com/questions/64247315/svelte-get-all-properties-on-current-svelte-file
          // return $$props[lf];
          // return $$restProps[lf];
          console.debug("Search qualif link", searchLookup[lf], lf);
          acc[lf] = searchLookup[lf] ?? null;
          return acc;
        }, {}),
    })}
    class="pb-2 pr-2"
  >
    <button> Qualification des temps associé(s) </button>
  </a>
  <a
    class="pb-2 pr-2"
    href={Routing.generate("mws_timing_tag_list", {
      _locale: locale ?? "fr",
      viewTemplate: viewTemplate ?? "",
      ...searchLookup,
    })}
  >
    <button>Liste des tags</button>
  </a>
  <div class="p-3 w-full">
    <ImportTimings {locale} timingLookup={searchLookup} format="csv" />
    <ExportTimings {locale} timingLookup={searchLookup} format="csv" />
    <ExportTags {locale} timingLookup={searchLookup} format="csv" />
    <button
      on:click={deleteAllTimings}
      class=" m-1"
      style="--mws-primary-rgb: 255, 0, 0"
    >
      Supprimer tous les temps
    </button>
  </div>
  <button on:click={() => (showDetails = !showDetails)}>
    {showDetails ? "Cacher" : "Voir"} les details
  </button>
  {#if showDetails}
    <button on:click={() => (showPictures = !showPictures)}>
      {showPictures ? "Cacher" : "Voir"} les screenshots
    </button>
  {/if}
  <div class="w-full p-3 flex flex-wrap">
    <div class="w-full label pb-2">
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
    <div id="search-timing-lookup" class="detail w-full hidden z-50">
      {@html lookupForm}
    </div>
    <div class="w-full label pb-2">
      <button
        data-collapse-toggle="config-report"
        type="button"
        class="rounded-lg"
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
  <div class="w-full">
    {#each [1, 2, 3, 4, 5] as lvl}
      {@html (jsonReport[`lvl${lvl}Tags`] ?? false) &&
      jsonReport[`lvl${lvl}Tags`]?.length
        ? `<strong>Tags du rapport de niveau ${lvl} : </strong>` +
          jsonReport[`lvl${lvl}Tags`]?.reduce(
            (acc, f) => `
            ${acc} [${f}]
          `,
            ``
          ) +
          "<br/>"
        : ""}
    {/each}
  </div>
  <div class="pt-3 w-full">
    <!-- // TODO : code factorization, inside component ? -->
    {@html searchLookup.searchStart && searchLookup.searchStart.length
      ? "<strong>Depuis le : </strong>" +
        dayjs(searchLookup.searchStart).format("YYYY-MM-DD HH:mm:ss") +
        "<br/>"
      : ""}
    {@html searchLookup.searchEnd && searchLookup.searchEnd.length
      ? "<strong>Jusqu'au : </strong>" +
        dayjs(searchLookup.searchEnd).format("YYYY-MM-DD HH:mm:ss") +
        "<br/>"
      : ""}
    {@html searchLookup.customFilters && searchLookup.customFilters.length
      ? "<strong>Filtres actifs : </strong>" +
        searchLookup.customFilters.reduce(
          (acc, f) => `
          ${acc} [${f}]
        `,
          ``
        ) +
        "<br/>"
      : ""}
    {@html searchLookup.searchTags && searchLookup.searchTags.length
      ? "<strong>Tags à rechercher : </strong>" +
        searchLookup.searchTags.reduce(
          (acc, f) => `
          ${acc} [${f}]
        `,
          ``
        ) +
        "<br/>"
      : ""}
    <!-- // TODO : code factorization, indide component ? -->
    {@html searchLookup.searchTagsToInclude &&
    searchLookup.searchTagsToInclude.length
      ? "<strong>Tags à inclure : </strong>" +
        searchLookup.searchTagsToInclude.reduce(
          (acc, f) => `
                    ${acc} [${f}]
                  `,
          ``
        ) +
        "<br/>"
      : ""}
    {@html searchLookup.searchTagsToAvoid &&
    searchLookup.searchTagsToAvoid.length
      ? "<strong>Tags à éviter : </strong>" +
        searchLookup.searchTagsToAvoid.reduce(
          (acc, f) => `
          ${acc} [${f}]
        `,
          ``
        ) +
        "<br/>"
      : ""}
    {@html searchLookup.searchKeyword
      ? `<strong>Mots clefs : </strong>${searchLookup.searchKeyword}`
      : ``}
  </div>
  <div class="w-full h-4" />

  <!-- {JSON.stringify(timings)} -->
  <!-- <div>{@html timingsPaginator}</div> // TODO : not used, synth all... -->
  <div class="w-full">
    <strong>{Object.keys(timingsByIds).length.toPrettyNum(0)} points</strong> de
    contrôles sur
    <strong>
      {(
        Object.keys(summaryByDays).length +
        Object.keys(summaryReportByDays).length
      ).toPrettyNum(0)} jours</strong
    >
  </div>
  <div class="w-full h-4" />
  <div class="w-full text-lg">
    Rapport des temps via segmentations de 10 minutes.
  </div>
  <div class="w-full h-4" />
  <div class="w-full text-lg font-extrabold">
    {summaryByLevels.sumOfBookedHrs.toPrettyNum(2, 5)} heures au total si effectué
    par une personne.
  </div>
  <div class="w-full text-lg font-extrabold">
    <!-- {summaryByLevels.sumOfMaxPPH.toPrettyNum(2)} € en tout. -->
    {(summaryByLevels.sumOfMaxPathPerHr?.maxValue ?? 0).toPrettyNum(2)} € en tout.
  </div>
  <div class="w-full h-8" />
  <div class="w-full text-lg font-extrabold">
    <!-- summaryByLevels.deepSumOfMaxPathPerHr     deepSumOfMaxPathPerHr -->
    {summaryByLevels.deepSumOfBookedHrs.toPrettyNum(2, 5)} heures si charges non
    cumulables
    <span class="font-normal">
      (ex : rapport d'équipe avec changement de prix par expert associé).
    </span>
  </div>
  <div class="w-full text-lg">
    <!-- {summaryByLevels.sumOfMaxPPH.toPrettyNum(2)} € en tout. -->
    {(summaryByLevels.deepSumOfMaxPathPerHr?.maxValue ?? 0).toPrettyNum(2)} € si
    charges non cumulables.
  </div>
  <div class="w-full h-8" />
  <div class="w-full text-lg font-extrabold">
    {summaryTotals.sumOfBookedHrs.toPrettyNum(2, 5)} hours annexes.
  </div>
  <div class="w-full text-lg font-extrabold">
    <!-- {summaryTotals.sumOfMaxPPH.toPrettyNum(2)} € annexes. -->
    {(summaryTotals.sumOfMaxPathPerHr?.maxValue ?? 0).toPrettyNum(2)} € annexes.
  </div>
  <div class="w-full label py-2">
    <button on:click={() => window.print()}>
      Imprimer (Zoom {reportScale} %)
    </button>
  </div>
  <!-- <div class="w-full h-2" /> -->
  <div
    class="flex items-start w-full pt-3 pb-4 md:opacity-10 hover:opacity-100 print:hidden"
  >
    <div class="fill-white/70 text-white/70 w-full">
      <!-- // TODO : userDelay instead of 400 ? not same for all situation,
      //         might need bigDelay or short or medium ?
      //         or too specific, keep number easyer than multiples var or const ? -->
      <input
        value={reportScale}
        on:change={debounce((e) => (reportScale = e.target.value), 200)}
        id="report-scale"
        type="range"
        class="w-full h-8 bg-gray-200/50 rounded-lg
          appearance-none cursor-pointer outline-none
          "
      />
    </div>
  </div>

  <div class="block w-full">
    <!-- transform: scale(${(reportScale / 100).toFixed(2)}); -->
    <!-- // TIPS : will have align issue if zooming the container of 
    sticky element, position will not fit, so apply to each sub
    items instead...
    style={`
      zoom: ${reportScale}%;
    `} -->

    <!-- TODO : use https://tailwindcss.com/docs/grid-template-columns 
      https://medium.com/@snowleo208/how-to-create-responsive-table-d1662cb62075 ?
      instead of table ? table is better for old compatibility reports, 
      printings and emails embeddings or tailwind ok for all devices ok in 2024 ?
     -->
    <table
      class="table-auto flex-grow items-center w-full bg-transparent
      border-collapse"
    >
      <thead
        class="sticky top-[-1px] md:-top-6 wide:top-[-1px] z-40 text-xs md:text-sm"
      >
        <tr
          style={`
            zoom: ${reportScale}%;
          `}
        >
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
          bg-gray-600 text-white border-gray-800
          "
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
      <tbody
        style={`
          zoom: ${reportScale}%;
        `}
      >
        <!-- subLevelKeys={Array.from(subTag.subTags?.keys() ?? [])} -->
        {#each summaryByLevels.subTags ?? [] as subTag, tagIdx}
          {#if subTag ?? false}
            <ReportSummaryRows
              {locale}
              summary={subTag}
              label={subTag.label}
              subLevelKeys={Array.from(
                ((subTag.subTags?.length || null) && subTag.subTags.keys()) ??
                  subTag.ids ??
                  []
              )}
              rowClass="bg-gray-300 font-bold bg-none"
              {showDetails}
              {showPictures}
              {summaryByDays}
              {timingsByIds}
              {reportScale}
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
            {reportScale}
          />
        {/each}
      </tbody>
    </table>
  </div>

  <!-- // TODO : allow resize of detailed screen shoot to review screen per level ?
  + Auto stacked sticky titles.... -->

  <ConfidentialityStamp />
</div>
