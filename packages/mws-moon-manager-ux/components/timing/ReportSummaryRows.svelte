<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import ReportSummaryRows from "./ReportSummaryRows";
  import MwsTimeSlotIndicator from "../layout/widgets/MwsTimeSlotIndicator.svelte";

  export let rowClass = "bg-gray-400 font-extrabold";
  export let indent = 4;
  export let label = "";
  export let summary = {};
  export let subLevelKeys = [];
  export let haveChild = true;

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
    return (summary.months ?? [])[subKey] ?? summary.days[subKey];
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
      indent={indent + 4}>
      </ReportSummaryRows>   
    {/if}
  {/each}
{/if}