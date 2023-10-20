<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";
  import ListCard from "./tags/ListCard.svelte";
  import { onMount } from "svelte";

  // export let users:any[] = []; // TODO : not Typescript ?
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let filters;
  export let tags;
  export let tagsPaginator;
  export let tagsHeaders = {}; // injected raw html
  export let viewTemplate;
  export let filtersForm;

  console.debug(locale);
  console.debug(filters);
  console.debug(tags);
  console.debug(tagsPaginator);
  console.debug(viewTemplate);
  console.debug(filtersForm);

  const jsonResult = JSON.parse(decodeURIComponent(filters.jsonResult));
  console.debug('jsonResult :', jsonResult);
  // TODO : basehref ? => NOP, use Routing from fos-routing instead...
  const baseHref = '/mws';
  const respUrl = `${baseHref}/${locale}/mws-offer/fetch-root-url?url=`
  + encodeURIComponent(jsonResult.sourceRootLookupUrl);

  onMount(async () => {
    // // NOP, not common, will have security errors this way :
    // const htmlResp = await fetch(respUrl);
    // console.debug(htmlResp);
    // // const win = window.open(jsonResult.sourceRootLookupUrl, "Title", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));
    // const win = window.open('', "Title", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));
    // // win.document.body.innerHTML = "HTML";
    // win.document.body.innerHTML = await htmlResp.text();
  });

  console.log(Routing.generate('mws_offer_import'));
</script>

<Base {copyright} {locale} {viewTemplate}>
  <div>
    <a href={ Routing.generate('mws_offer_import', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
    }) }>
      <button class="btn btn-outline-primary p-1">Importer des offres.</button>
    </a>    
  </div>
  <div class="flex flex-wrap">
    <div class="label">
      Recherche d'une offre via :
    </div>
    <div class="detail">
      {@html filtersForm}
    </div>
  </div>
  {@html tagsPaginator}

  <!-- { JSON.stringify(tags) } -->
  <div class="overflow-y-auto">
    <table>
      <thead>
        <tr class="users-table-info">
          <th scope="col">Actions</th>
          <th scope="col">[Id] Status</th>
          <th scope="col">
            {@html tagsHeaders.clientUsername ?? "Nom du client"}
          </th>
          <th scope="col">
            {@html tagsHeaders.contact1 ?? "Contact"}
          </th>
          <th scope="col">
            {@html tagsHeaders.contact2 ?? "Contact bis"}
          </th>
          <th scope="col">Titre</th>
          <th scope="col">Description</th>
        </tr>
      </thead>
      <tbody>
        {#each tags as offer}
          <!-- { JSON.stringify(offer) } -->
          <!-- {@debug offer} -->
          <ListCard {offer}></ListCard>
        {/each}
      </tbody>
    </table>
  </div>
  <div>{@html tagsPaginator}</div>
</Base>

<!-- <style lang="scss">
  // TODO : post CSS syntax allowed in svelte scss ?
  // Done in packages/mws-moon-manager/assets/styles/app.scss
  // .label {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
  // .detail {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
</style> -->