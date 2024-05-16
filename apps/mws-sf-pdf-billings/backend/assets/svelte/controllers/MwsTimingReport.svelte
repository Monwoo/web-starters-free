<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // TODO : keep in MoonManager bundle :
  // "entrypoints": [ // No effect, building and using build output for the root one ?
  //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
  // ]

  import Report from "mws-moon-manager-ux/components/timing/Report.svelte";

  export let locale;
  export let lookup = '{}';
  export let timingsReport;
  // export let timings;
  export let timingsPaginator;
  export let timingsHeaders = '{}'; // injected raw html
  export let viewTemplate;
  export let lookupForm;
  export let report = '{}';
  export let reportForm;

  // TODO : remove code duplication and put this in some 'mws-utils' package ?
  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }

  lookup = JSON.parse(decodeHtml(lookup));
  timingsReport = JSON.parse(decodeHtml(timingsReport));
  // timings = JSON.parse(decodeHtml(timings));

  timingsHeaders = JSON.parse(decodeHtml(timingsHeaders));
  timingsPaginator = decodeHtml(timingsPaginator);
  lookupForm = decodeHtml(lookupForm);

  report = JSON.parse(decodeHtml(report));
  reportForm = decodeHtml(reportForm);

  console.debug(lookup);
</script>

<!-- <Liste {...$$props}></Liste> -->
<Report {lookup} {report} {reportForm}
{locale} {viewTemplate} {lookupForm} {timingsReport}
{timingsPaginator} {timingsHeaders}
></Report>
