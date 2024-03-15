<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // TODO : Will fail with UX bundle same filename... using MWSOffer prefix for now






  // TODO : keep in MoonManager bundle :
  // "entrypoints": [ // No effect, building and using build output for the root one ?
  //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
  // ]

  import Lookup from "mws-moon-manager-ux/components/offer/Lookup.svelte";

  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let locale;
  export let lookup = '{}';
  export let offers;
  export let offersPaginator;
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

  lookup = decodeHtml(lookup);
  offers = decodeHtml(offers);
  offersPaginator = decodeHtml(offersPaginator);
  lookupForm = decodeHtml(lookupForm);
  console.debug(lookup);
</script>

<!-- <Liste {...$$props}></Liste> -->
<Lookup lookup={JSON.parse(lookup)} {copyright}
{locale} {viewTemplate} {lookupForm} {offers}
{offersPaginator}
></Lookup>
