<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import ReportSummaryRows from "./ReportSummaryRows";
  import MwsTimeSlotIndicator from "../layout/widgets/MwsTimeSlotIndicator.svelte";
  import dayjs from "dayjs";

  export let rowClass = "bg-gray-400 font-extrabold bg-none";
  export let indent = 4;
  export let label = "";
  export let summary = {};
  export let summaryByDays = {};
  export let timingsByIds = {};
  export let locale;

  export let subLevelKeys = [];
  // CF .legacy files, for details and pictures loadings, but not efficient, rethink, expand/collapse with lazy loads ?
  export let showDetails = false; // TODO : CSV EXPORT instead, PDF print is too much pages... (might be ok per month, but not for one year of data...)
  export let showPictures = false;
  export let isLoading = false; // TODO : show loader when showDetails or showPictures is loading...
  export let reportScale;
  export let breadcrumb = [];
  const base = process.env.BASE_HREF_FULL ?? "";

  console.debug('[ReportSummaryRows] label', label);
  console.debug('[ReportSummaryRows] subLevelKeys', subLevelKeys);

  const slotPath = (timingSlot) => Routing.generate("mws_timing_fetchMediatUrl", {
    // encodeURI('file://' + timingSlot.source.path)
    url: "file://" + timingSlot.sourceStamp,
    timingId: timingSlot.id,
  });

  // Number.prototype.toPrettyNum = (length: number) => {
  Number.prototype.toPrettyNum = function (this: Number, length: number, maxLength = null) {
    if (maxLength === null) maxLength = length;
    var s = this;
    const splited = s
      .toFixed(maxLength).replace(new RegExp(`0{0,${maxLength - length}}$`), "")
      // https://stackoverflow.com/questions/5025166/javascript-number-formatting-min-max-decimals
      // .toLocaleString('en-US', { // TODO : centralize toPrettyNum and use locals formatings ?
      //   minimumFractionDigits: 2,
      //   maximumFractionDigits: 4
      // })
      .replace(".", ",")
      .split(',');
    return (splited[0] ?? '').replace(/\B(?=(\d{3})+(?!\d))/g, " ")
    + (length >= 1 ? "," : "")
    + (splited[1] ?? '');
  };

  declare interface Number {
    toPrettyNum(length : number) : string;
  }

  const haveSubPath = (subKey, showDetails) => {
    const exist = (summary.subTags ?? {})[subKey]
    ?? (summary.months ?? {})[subKey]
    ?? (summary.days ?? {})[subKey]
    // TIPS : inefficient to search in un-sorted array ? remove and directly search in timingsByIds ?
    // ?? (showDetails && (summary.ids ?? []).includes(subKey))
    // ?? (showDetails && (summary.ids ?? false) && (timingsByIds[subKey] ?? false))
    ?? (showDetails && (summary.ids ?? false) && (!Object.keys(summary.subTags ?? {}).length))
    ?? false;
    // console.debug('subKey', subKey, ' have path ', !!exist);
    return !!exist;
  };
  const getSubSummary = (subKey, showDetails) => {
    // TIPS : showDetails not used, but NEEDED for Svelte reactiveness
    //         to recompute function result on param change only...
    // console.debug('stub lbl',summary.days[subKey]);
    // return (summary.months ?? [])[subKey] ?? summary.days[subKey];
    if ((summary.subTags ?? {})[subKey]??false) {
      return summary.subTags[subKey];
    }

    if ((summary.days ?? {})[subKey]??false) {
      // { @ const daySummary = summaryByDays[day]}
      // console.debug('day summary', subKey, summaryByDays[subKey]);
      return summaryByDays[subKey];
    }
    // { @ const timings = timingsByIds[tId]}
    // TIPS : inefficient to search in un-sorted array ? remove and directly search in timingsByIds ?
    // if ((summary.ids ?? []).includes(subKey)) {
    if ((timingsByIds[subKey] ?? false)) {
      return timingsByIds[subKey];
    }
    return (summary.months ?? [])[subKey] ?? null;
  };
  const getSubLabel = (subKey, showDetails) => {
    if ((summary.subTags ?? {})[subKey]??false) {
      return summary.subTags[subKey].label ?? subKey;
      // return summary.label + ' | '
      // + (summary.subTags[subKey].label ?? subKey);
    }

    if ((summary.days ?? {})[subKey]??false) {
      return `${subKey}`;
    }
    // TIPS : inefficient to search in un-sorted array ? remove and directly search in timingsByIds ?
    // if ((summary.ids ?? []).includes(subKey)) {
    if ((timingsByIds[subKey] ?? false)) {
      const t = timingsByIds[subKey];
      // TODO : why timezone ok for reportt need to be forced like for lookup qualifs ?
      // no .tz ? : http://localhost:8000/mws/fr/mws-timings/report?page=1&tags%5B0%5D=joshcrm-v1&tags%5B1%5D=joshcrm-v2&lvl1Tags%5B0%5D=miguel-monwoo&lvl1Tags%5B1%5D=joshcrm-v1&lvl4Tags%5B0%5D=etude-devis
      // return dayjs(t.sourceTimeGMT).format("YYYY-MM-DD HH:mm:ss :)))").tz("Europe/London", true) + ' : '
      return dayjs(t.sourceTimeGMT).tz("Europe/London").format("YYYY-MM-DD HH:mm:ss") + ' : '
      + (t.sourceStamp?.split('/').slice(-1) ?? subKey);
    }
    return `${label}-${subKey}`;
  };
  const getSubLvlKeys = (subKey, showDetails) => {
    let subLvlKeys = [];
    if ((summary.subTags ?? {})[subKey]?.subTags?.length ?? false) {
      return Array.from(summary.subTags[subKey].subTags.keys());
    }
    if ((summary.months ?? [])[subKey]?.days ?? false) {
      subLvlKeys = Object.keys(summary.months[subKey].days).sort();
    } else if (
      // (summary.days ?? {})[subKey] ?? false
      summaryByDays[subKey] ?? false
    ) {
      subLvlKeys = summaryByDays[subKey]?.ids; //.slice(0,0);
    } else {
      subLvlKeys = (summary.subTags ?? {})[subKey]?.ids ?? [];
    }
    // console.debug('subKey', subKey, ' have sub keys ', subLvlKeys);
    return subLvlKeys;
  };
  const getSubRowClass = (subKey, showDetails) => {
    if ((summary.subTags ?? {})[subKey] ?? false) {
      if (summary.subTags[subKey].deepLvl == 1) {
        return "bg-gray-300 font-bold bg-none";
      }
      if (summary.subTags[subKey].deepLvl == 2) {
        return "bg-gray-100 font-bold bg-none";
      }
      if (summary.subTags[subKey].deepLvl == 3) {
        return "bg-gray-100 font-bold bg-none";
      }
      if (summary.subTags[subKey].deepLvl == 4) {
        return "bg-gray-100 font-bold bg-none";
      }
      return "bg-gray-100 font-bold bg-none";
    }
    if ((summary.months ?? {})[subKey]?? false) {
      return "bg-gray-200 font-bold bg-none";
    }
    if ((summary.days ?? {})[subKey]??false) {
      return "";
    }
    return "text-gray-600";
  };
  
  const tagSlugs = Object.keys(summary.tags ?? {}).sort();

  // post css will see this string and add associated level indent classes :
  const tailwindForceCss = "pl-4 pl-8 pl-12 pl-16 pl-20 pl-24 pl-28 pl-32 pl-36 pl-40";

  console.log('max path :', summary.maxPath);
</script>

<!-- // TODO : why sticky left-0  not working ? no sticky left, overflow issue in parent container hierachy ? -->


<!--
TIPS : use collspan to fill instead of fixed width that will fix first column width too...
<tr class="mws-default-bg border-l-0 border-b-0
sticky left-0 md:-left-5 wide:left-0
top-[3rem] md:top-[1rem] wide:top-[3rem] z-40
flex"
class:font-extrabold={summary.usedForTotal || summary.usedForDeepTotal}
style={`
  width: ${(100/Number(reportScale ?? 100) * 100).toFixed(0)}dvw;
`}
>
  class="grow border-t-0 px-6 text-middle {rowClass}"
  colspan="100%"


-->
<tr
class="mws-default-bg border-l-0 border-b-0
  sticky
  top-[3rem] md:top-[1rem] wide:top-[3rem] z-40
  {rowClass}"
>
  <td
  class="border-t-0 px-6 text-middle {rowClass}"
  >
  </td>
<!-- 
    class="border-t-0 px-6 text-middle sticky left-0 top-[3rem] md:top-[1rem] wide:top-[3rem] z-40 {rowClass}"
    colspan="100%" -->
  <td
  class="border-l-0 border-b-0
  sticky left-0 md:-left-5 wide:left-0
  z-40
  flex w-[60dvw] md:w-auto wide:w-[60dvw] print:w-auto print:max-w-[40dvw] {rowClass}"
  class:font-extrabold={summary.usedForTotal || summary.usedForDeepTotal}
    colspan="40%" 
  >
    <div class="text-lg pl-{indent} text-ellipsis	overflow-show
    break-keep whitespace-nowrap
    mws-default-bg {rowClass}
    flex items-center">
      <!-- { Array.apply(null, {length: indent / 4})
        .map(Number.call, (n) => '*').join(' ')
      } [{label}]</div> -->
      <!-- <span class="text-gray-500">[{indent / 4}]</span> -->
      <span class="grow break-keep w-max">[{label}]</span>
      {#each breadcrumb as crumb, idx}
        <span class="text-gray-500 font-normal text-sm pl-3 break-keep">
          <!-- { idx > 0
            ? ``
            : ` < `
          } -->

          <!-- // TIPS : without '@html', svelte will escape html display  -->
          { ` < ` }
          {crumb}
        </span>
      {/each}
    </div>
  </td>
  <!-- colspan={`
    ${(100/Number(reportScale ?? 100) * 100 - 100).toFixed(0)}%
  `}   -->
  <td
  class="border-t-0 px-6 text-middle {rowClass}"
  colspan={`
    100%
  `}  
  >
  </td>
</tr>
<tr class="{rowClass}  border-t-0"
class:font-extrabold={summary.usedForTotal || summary.usedForDeepTotal}
>
  <td
    class="border-t-0 px-6 text-middle
  border-l-0 border-r-0 text-lg whitespace-nowrap p-4 pl-{indent}"
  >
    {#if summary.bookedTimeSlot}
      <MwsTimeSlotIndicator
        slots={summary.bookedTimeSlot}
      />
    {/if}
    {summary.rangeDayIdxBy10Min ?? ''}
    {#if showPictures && summary.sourceStamp }
      <!--
        TIPS : too huge to import thumbnailJpeg for all summary, if huge amount of 
          time slots and using db B64 save mode instead of upload thumb mode...
        <object
        data={ summary?.thumbnailJpeg ?? "//=::NotAnUrlForPurposeFail**%%" }
        type="image/png"
        alt="screenshot"
        arial-label="screenshot"
        title="screenshot"
        class="object-contain border-solid border-4 max-w-[100px]"
        class:border-gray-600={!tagSlugs.length}
        class:border-green-400={tagSlugs.length}
      > -->
      <object
      data={ (summary?.thumbnailJpeg?.startsWith('/') ? base + summary?.thumbnailJpeg : summary?.thumbnailJpeg) ?? "//=::NotAnUrlForPurposeFail**%%" }
      type="image/png"
      alt="screenshot"
      arial-label="screenshot"
      title="screenshot"
      class="object-contain border-solid border-4 max-w-[100px]"
      class:border-gray-600={!tagSlugs.length}
      class:border-green-400={tagSlugs.length}
      >
        <img
          loading="lazy"
          class="object-contain border-solid border-4 max-w-[100px]"
          class:border-gray-600={!tagSlugs.length}
          class:border-green-400={tagSlugs.length}
          src={slotPath(summary)}
        />
      </object>
    {/if}
  </td>
  <td
    class="border-t-0 px-6 text-left
    border-l-0 border-r-0 text-lg
    whitespace-break-spaces p-4 pl-{indent}
    "
  >
  {#each tagSlugs as tagSlug}
    {@const tag = summary.tags[tagSlug]}
    {@const isMaxTag = tagSlug == (summary.maxPath?.maxTagSlug ?? null)}
    {@const isMaxSubTag = tagSlug in (summary.maxPath?.maxSubTags ?? {})}
    <a
        href={Routing.generate("mws_timings_qualif", {
          _locale: locale ?? "fr",
          searchTagsToInclude: [ tagSlug ],
        })}
        class="inline-flex
        text-xs font-medium p-1 text-center
        border border-blue-800 "
        class:border-2={isMaxTag || isMaxSubTag}
        class:border-green-400={isMaxTag}
        class:border-green-700={isMaxSubTag}
      >
        {tag.label} {
          tag.pricePerHr ? `[${tag.pricePerHr.toPrettyNum(2)} €/hr]` : ''
        }
      </a>
    {/each}
  </td>
  <td
    class="sticky top-[4rem] md:top-7 wide:top-[4rem] z-30 border-t-0 px-6 text-right flex flex-col
    border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
  >

    <span class="p-1 bg-white rounded-md rounded-b-none"
    class:border-2={summary.usedForTotal}
    class:border-green-400={summary.usedForTotal}
    >
      {(summary.sumOfBookedHrs ?? null) === null
      ?  (summary.usedForTotal
        ? (10/60).toPrettyNum(2)
        : '-'
      )
      : (summary.sumOfBookedHrs?.toPrettyNum(2, 5) ?? '-')} hr
    </span>

    <!-- <br /> -->
    <span class="text-gray-400 p-1 bg-white rounded-md rounded-t-none">
      <!-- {(summary.deepSumOfBookedHrs ?? null) === null && summary.usedForDeepTotal
        ? (10/60).toPrettyNum(2)
        : summary.deepSumOfBookedHrs?.toPrettyNum(2) ?? '-'} hr -->
      {(summary.deepSumOfBookedHrs ?? null) === null
        ?  (summary.usedForDeepTotal
          ? (10/60).toPrettyNum(2)
          : '--'
        )
        : (summary.deepSumOfBookedHrs?.toPrettyNum(2, 5) ?? '-')} hr  
    </span>
  </td>
  <td
    class="px-6 text-center
    align-top
    border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
  >
    <div class="sticky top-[5rem] md:top-12 wide:top-[5rem] z-30 border-b-0 border-t-1 border-t-0 
    mws-default-bg {rowClass}
    ">
    <!-- {(summary.maxPPH ?? null) === null
    ? summary.maxPricePerHr?.toPrettyNum(2) ?? '-'
    : (summary.maxPPH?.toPrettyNum(2) ?? '-')} € -->
    {(summary.maxPath?.maxValue?.toPrettyNum(2) ?? '--')} €
    </div>
  </td>
  <td
    class="sticky top-[4rem] md:top-7 wide:top-[4rem] z-30 border-t-0 px-6 text-right flex flex-col
    border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
  >
    <span class="p-1 bg-white rounded-md rounded-b-none"
    class:border-2={summary.usedForTotal}
    class:border-green-400={summary.usedForTotal}
    >
      <!-- {(summary.sumOfMaxPPH ?? null) === null
      ? (
        summary.usedForTotal
        ? ((summary.maxPricePerHr ?? 0) * (10/60)).toPrettyNum(2)
        : '-'
      )
      : (summary.sumOfMaxPPH?.toPrettyNum(2) ?? '-')} € -->
      {(summary.sumOfMaxPathPerHr?.maxValue ??
      (summary.maxPath?.maxValue ? summary.maxPath.maxValue * 10/60 : null)
      )?.toPrettyNum(2) ?? '--'} €
    </span>
      <!-- <br /> -->
    <span class="text-gray-400 p-1 bg-white rounded-md rounded-t-none">
      <!-- {(summary.deepSumOfMaxPPH ?? null) === null && summary.usedForDeepTotal
      ? ((summary.maxPricePerHr ?? 0) * (10/60)).toPrettyNum(2)
      : summary.deepSumOfMaxPPH?.toPrettyNum(2) ?? '-'} € -->
      <!-- {summary.deepSumOfMaxPathPerHr.maxValue.toPrettyNum(2)} € -->
      {(summary.deepSumOfMaxPathPerHr?.maxValue ??
        (summary.maxPath?.maxValue ? summary.maxPath.maxValue * 10/60 : null)
        )?.toPrettyNum(2) ?? '--'} €
      </span>
  </td>
</tr>

{#if subLevelKeys.length}
  {#each subLevelKeys as subKey}
    {#if haveSubPath(subKey, showDetails)}
      <ReportSummaryRows
      label={getSubLabel(subKey, showDetails)}
      summary={getSubSummary(subKey, showDetails)}
      subLevelKeys={getSubLvlKeys(subKey, showDetails)}
      rowClass={getSubRowClass(subKey, showDetails)}
      indent={indent + 4} {reportScale}
      breadcrumb={[label].concat(breadcrumb)}
      {showDetails} {showPictures}
      {summaryByDays} {timingsByIds}>
      </ReportSummaryRows>   
    {/if}
  {/each}
{/if}