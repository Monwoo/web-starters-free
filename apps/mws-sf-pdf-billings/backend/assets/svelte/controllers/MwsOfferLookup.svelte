<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // TODO : keep in MoonManager bundle :
  // "entrypoints": [ // No effect, building and using build output for the root one ?
  //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
  // ]

  import Lookup from "mws-moon-manager-ux/components/offer/Lookup.svelte";

  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let lookup = '{}';
  export let offers;
  export let messagesByProjectId;
  export let offersPaginator;
  export let offersHeaders = '{}'; // injected raw html
  export let viewTemplate;
  export let lookupForm;
  export let addMessageForm = '';

  // TODO : remove code duplication and put this in some 'mws-utils' package ?
  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }

  lookup = JSON.parse(decodeHtml(lookup));
  offers = JSON.parse(decodeHtml(offers));
  messagesByProjectId = JSON.parse(decodeHtml(messagesByProjectId));

  offersHeaders = JSON.parse(decodeHtml(offersHeaders));
  offersPaginator = decodeHtml(offersPaginator);
  lookupForm = decodeHtml(lookupForm);
  addMessageForm = decodeHtml(addMessageForm);

  console.debug(lookup);
</script>

<!-- <Liste {...$$props}></Liste> -->
<Lookup {lookup} {copyright} {messagesByProjectId}
{locale} {viewTemplate} {lookupForm} {offers}
{offersPaginator} {offersHeaders} {addMessageForm}
></Lookup>
