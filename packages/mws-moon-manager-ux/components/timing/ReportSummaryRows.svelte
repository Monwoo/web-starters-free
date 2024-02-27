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

  const haveSubPath = (subKey) => {
    return (summary.months ?? [])[subKey] ?? summary.days[subKey] ?? false;
  };
  const getSubSummary = (subKey) => {
    // console.debug('stub lbl',summary.days[subKey]);
    // return (summary.months ?? [])[subKey] ?? summary.days[subKey];

    if ((summary.days ?? [])[subKey]??false) {
      // { @ const daySummary = summaryByDays[day]}
      console.debug('day summary', subKey, summaryByDays[subKey]);
      return summaryByDays[subKey];
    }
    // { @ const timings = timingsByIds[tId]}
    return (summary.months ?? [])[subKey] ?? null;
  };
  const getSubLabel = (subKey) => {
    if ((summary.days ?? [])[subKey]??false) {
      return `${subKey}`;
    }
    return `${label}-${subKey}`;
  };
  const getSubKeys = (subKey) => {
    if ((summary.months ?? [])[subKey]?.days) {
      return Object.keys(summary.months[subKey].days).sort();
    }
    if (summary.days[subKey]?.ids) {
      return summary.days[subKey]?.ids?.slice(0,0);
    }
    return [];
  };
  const getSubRowClass = (subKey) => {
    if ((summary.months ?? [])[subKey]?.days) {
      return "bg-gray-200 font-bold";
    }
    if (summary.days[subKey]?.ids) {
      return "";
    }
    return "";
  };
  
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
  </td>
  <td
    class="border-t-0 px-6 text-left
    border-l-0 border-r-0 text-lg whitespace-break-spaces p-4 pl-{indent}"
  >
    <div class="text-lg">[{label}]</div>
    {#each Object.keys(summary.tags ?? {}).sort() ??
      [] as tagSlug}
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
    {summary.sumOfBookedHrs?.toPrettyNum(2) ?? '-'} hr
  </td>
  <td
    class="border-t-0 px-6 text-right
    border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
  >
    {summary.maxPPH?.toPrettyNum(2) ?? '-'} €
  </td>
  <td
    class="border-t-0 px-6 text-right
    border-l-0 border-r-0 text-lg whitespace-nowrap p-4"
  >
    {summary.sumOfMaxPPH?.toPrettyNum(2) ?? '-'} €
  </td>
</tr>

{#if subLevelKeys.length}
  {#each subLevelKeys as subKey}
    {#if haveSubPath(subKey)}
      <ReportSummaryRows
      label={getSubLabel(subKey)}
      summary={getSubSummary(subKey)}
      subLevelKeys={getSubKeys(subKey)}
      rowClass={getSubRowClass(subKey)}
      indent={indent + 4}
      {summaryByDays} {timingsByIds}>
      </ReportSummaryRows>   
    {/if}
  {/each}
{/if}