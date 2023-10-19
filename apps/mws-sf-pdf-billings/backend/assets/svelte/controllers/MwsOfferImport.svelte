<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // TODO : keep in MoonManager bundle :
  // "entrypoints": [ // No effect, building and using build output for the root one ?
  //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
  // ]

  import Import from "mws-moon-manager-ux/components/offer/Import.svelte";

  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let reportSummary = '';
  export let format;
  export let uploadForm;
  export let viewTemplate;

  // TODO : remove code duplication and put this in some 'mws-utils' package ?
  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }

  reportSummary = decodeHtml(reportSummary);
  uploadForm = decodeHtml(uploadForm);
  console.debug(uploadForm);
</script>

<!-- <Liste {...$$props}></Liste> -->
<Import {copyright} {reportSummary}
{locale} {viewTemplate} {uploadForm} {format}
></Import>
