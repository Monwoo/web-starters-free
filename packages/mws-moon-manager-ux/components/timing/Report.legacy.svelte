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
    // const srcStamps = tSum.srcStamps.split(',');
    ids.forEach((tId, idx) => {
      const tagSlug = tagSlugs ? tagSlugs[idx] ?? null : null;
      const rangeDayIdxBy10Min = allRangeDayIdxBy10Min[idx];
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
        summaryByDays[tReport.sourceDate].sumOfMaxPPH += deltaOfMaxPPH;
        summaryTotals.sumOfBookedHrs += delta;
        summaryTotals.sumOfMaxPPH += deltaOfMaxPPH;
      }
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
        months: {},
      };
    }
    if (!(summaryByYears[tYear].months[tMonth] ?? null)) {
      summaryByYears[tYear].months[tMonth] = {
        bookedTimeSlot: {},
        sumOfBookedHrs: 0,
        sumOfMaxPPH: 0,
        days: {},
      };
    }

    summaryByYears[tYear].bookedTimeSlot = {
      ...summaryByYears[tYear].bookedTimeSlot,
      ...summary.bookedTimeSlot,
    };
    summaryByYears[tYear].sumOfBookedHrs += summary.sumOfBookedHrs;
    summaryByYears[tYear].sumOfMaxPPH += summary.sumOfMaxPPH;

    summaryByYears[tYear].months[tMonth].bookedTimeSlot = {
      ...summaryByYears[tYear].months[tMonth].bookedTimeSlot,
      ...summary.bookedTimeSlot,
    };
    summaryByYears[tYear].months[tMonth].sumOfBookedHrs +=
      summary.sumOfBookedHrs;
    summaryByYears[tYear].months[tMonth].sumOfMaxPPH += summary.sumOfMaxPPH;

    summaryByYears[tYear].months[tMonth].days[tDay] = true; //summary;
  });

  console.log(
    "timingsByIds :",
    timingsByIds[Object.keys(timingsByIds)[0]] ?? null
  );
  console.log("timingsByIds :", summaryByDays);

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

  {#each Object.keys(summaryByYears).sort() ?? [] as year, idx}
    <div class="m-3">
      <MwsTimeSlotIndicator slots={summaryByYears[year].bookedTimeSlot} />
      [{year}]
      {summaryByYears[year].sumOfBookedHrs.toFixed(2)} hour(s) for {year}
      {summaryByYears[year].sumOfMaxPPH.toFixed(2)} € for {year}

      {#each Object.keys(summaryByYears[year].months).sort() ??
        [] as month, idx}
        <div>
          <MwsTimeSlotIndicator slots={summaryByYears[year].months[month].bookedTimeSlot} />
          [{month}]
          {summaryByYears[year].months[month].sumOfBookedHrs.toFixed(2)} hour(s)
          for {year}-{month}
          {summaryByYears[year].months[month].sumOfMaxPPH.toFixed(2)} € for {year}-{month}

          {#each Object.keys(summaryByYears[year].months[month].days).sort() ??
            [] as day, idx}
            <div>
              <MwsTimeSlotIndicator slots={summaryByDays[day].bookedTimeSlot} />
              [{day}]
              {summaryByDays[day].sumOfBookedHrs.toFixed(2)} hour(s) for slots :
              {JSON.stringify(summaryByDays[day].bookedTimeSlot)}
            </div>
          {/each}
        </div>
      {/each}
    </div>
  {/each}

  <!-- {#each Object.keys(summaryByDays) ?? [] as day, idx}
    <div>
      [{day}]
      {summaryByDays[day].sumOfBookedHrs.toFixed(2)} hour(s) for slots :
      {JSON.stringify(summaryByDays[day].bookedTimeSlot)}
    </div>
  {/each} -->
</div>
