<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // TODO : keep in MoonManager bundle :
  // "entrypoints": [ // No effect, building and using build output for the root one ?
  //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
  // ]

  import Lookup from "mws-moon-manager-ux/components/timing/Lookup.svelte";

  export let locale;
  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let lookup = '{}';
  export let timings;
  export let timeQualifs;
  export let timingsPaginator;
  export let timingsHeaders = '{}'; // injected raw html
  export let viewTemplate;
  export let lookupForm;

  // TODO : remove code duplication and put this in some 'mws-utils' package ?
  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }

  lookup = JSON.parse(decodeHtml(lookup));
  timings = JSON.parse(decodeHtml(timings));
  timeQualifs = JSON.parse(decodeHtml(timeQualifs));

  timingsHeaders = JSON.parse(decodeHtml(timingsHeaders));
  timingsPaginator = decodeHtml(timingsPaginator);
  lookupForm = decodeHtml(lookupForm);

  console.debug(lookup);
  console.debug("Copyright", copyright);

</script>

<!-- <Liste {...$$props}></Liste> -->
<Lookup {lookup} {timeQualifs} {copyright}
{locale} {viewTemplate} {lookupForm} {timings}
{timingsPaginator} {timingsHeaders}
></Lookup>
