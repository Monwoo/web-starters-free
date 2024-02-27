<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import ReportSummaryRows from "./ReportSummaryRows";
  import MwsTimeSlotIndicator from "../layout/widgets/MwsTimeSlotIndicator.svelte";

  export let rowClass = "bg-gray-400 font-extrabold";
  export let indent = 4;
  export let label = "";
  export let summary = {};
  export let summaryByDays = {};
  export let timingsByIds = {};

  export let subLevelKeys = [];
  // CF .legacy files, for details and pictures loadings, but not efficient, rethink, expand/collapse with lazy loads ?
  export let showDetails = false; // TODO : CSV EXPORT instead, PDF print is too much pages... (might be ok per month, but not for one year of data...)
  export let showPictures = false;
  export let isLoading = false; // TODO : show loader when showDetails or showPictures is loading...

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

  const haveSubPath = (subKey, showDetails) => {
    const exist = (summary.months ?? [])[subKey]
    ?? (summary.days ?? [])[subKey]
    // TIPS : inefficient to search in un-sorted array ? remove and directly search in timingsByIds ?
    // ?? (showDetails && (summary.ids ?? []).includes(subKey))
    ?? (showDetails && (summary.ids ?? false) && (timingsByIds[subKey] ?? false))
    ?? false;
    // console.debug('subKey', subKey, ' have path ', !!exist);
    return !!exist;
  };
  const getSubSummary = (subKey, showDetails) => {
    // TIPS : showDetails not used, but NEEDED for Svelte reactiveness
    //         to recompute function result on param change only...
    // console.debug('stub lbl',summary.days[subKey]);
    // return (summary.months ?? [])[subKey] ?? summary.days[subKey];

    if ((summary.days ?? [])[subKey]??false) {
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
    if ((summary.days ?? {})[subKey]??false) {
      return `${subKey}`;
    }
    // TIPS : inefficient to search in un-sorted array ? remove and directly search in timingsByIds ?
    // if ((summary.ids ?? []).includes(subKey)) {
    if ((timingsByIds[subKey] ?? false)) {
      return timingsByIds[subKey]
      .sourceStamp?.split('/').slice(-1) ?? subKey;
    }
    return `${label}-${subKey}`;
  };
  const getSubLvlKeys = (subKey, showDetails) => {
    let subLvlKeys = [];
    if ((summary.months ?? [])[subKey]?.days ?? false) {
      subLvlKeys = Object.keys(summary.months[subKey].days).sort();
    } else if (
      (summary.days ?? {})[subKey] ?? false
    ) {
      subLvlKeys = summaryByDays[subKey]?.ids; //.slice(0,0);
    }
    // console.debug('subKey', subKey, ' have sub keys ', subLvlKeys);
    return subLvlKeys;
  };
  const getSubRowClass = (subKey, showDetails) => {
    if ((summary.months ?? [])[subKey]?? false) {
      return "bg-gray-200 font-bold";
    }
    if ((summary.days ?? [])[subKey]??false) {
      return "";
    }
    return "text-gray-600";
  };
  
  const tagSlugs = Object.keys(summary.tags ?? {}).sort();
</script>

<tr class="{rowClass}">
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
      <img
        class="object-contain border-solid border-4 max-w-[100px]"
        class:border-gray-600={!tagSlugs.length}
        class:border-green-400={tagSlugs.length}
        src={slotPath(summary)}
      />
    {/if}
  </td>
  <td
    class="border-t-0 px-6 text-left
    border-l-0 border-r-0 text-lg whitespace-break-spaces p-4 pl-{indent}"
  >
    <div class="text-lg">[{label}]</div>
    {#each tagSlugs as tagSlug}
      {@const tag = summary.tags[tagSlug]}
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
    {(summary.sumOfBookedHrs ?? null) === null
    ? (10/60).toPrettyNum(2)
    : (summary.sumOfBookedHrs?.toPrettyNum(2) ?? '-')} hr
  </td>
  <td
    class="border-t-0 px-6 text-right
    border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
  >
    {(summary.maxPPH ?? null) === null
    ? summary.maxPricePerHr?.toPrettyNum(2) ?? '-'
    : (summary.maxPPH?.toPrettyNum(2) ?? '-')} €
  </td>
  <td
    class="border-t-0 px-6 text-right
    border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
  >
    {(summary.sumOfMaxPPH ?? null) === null
    ? ((summary.maxPricePerHr ?? 0) * (10/60)).toPrettyNum(2)
    : (summary.sumOfMaxPPH?.toPrettyNum(2) ?? '-')} €
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
      indent={indent + 4}
      {showDetails} {showPictures}
      {summaryByDays} {timingsByIds}>
      </ReportSummaryRows>   
    {/if}
  {/each}
{/if}