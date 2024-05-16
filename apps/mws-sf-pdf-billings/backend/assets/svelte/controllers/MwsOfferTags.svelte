<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // TODO : keep in MoonManager bundle :
  // "entrypoints": [ // No effect, building and using build output for the root one ?
  //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
  // ]

  import Tags from "mws-moon-manager-ux/components/offer/Tags.svelte";

  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let locale;
  export let filters = '{}';
  export let tags;
  export let tagsPaginator;
  export let tagsHeaders = '{}'; // injected raw html
  export let viewTemplate;
  export let filtersForm;

  // TODO : remove code duplication and put this in some 'mws-utils' package ?
  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }

  filters = JSON.parse(decodeHtml(filters));
  tags = JSON.parse(decodeHtml(tags));
  tagsHeaders = JSON.parse(decodeHtml(tagsHeaders));
  tagsPaginator = decodeHtml(tagsPaginator);
  filtersForm = decodeHtml(filtersForm);
  console.debug(filters);
</script>

<!-- <Liste {...$$props}></Liste> -->
<Tags {filters} {copyright}
{locale} {viewTemplate} {filtersForm} {tags}
{tagsPaginator} {tagsHeaders}
></Tags>
