<script context="module">
  // TODO : remove code duplications, inside services ?
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  dayjs.locale("fr"); // Fr locale
</script><script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";
  import List from "./lookup/List.svelte";

  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let locale;
  export let viewTemplate;
  export let offer;
  export let addMessageForm = "";

  console.debug(offer);

  // TODO : configurable services, use generic data connectors instead of :
  // TODO : remove code duplication :
  let myOfferId = offer.sourceDetail?.monwooOfferId ?? null;
  myOfferId = myOfferId ? 'offers/' + myOfferId.split('_').slice(-1)[0] : null;

</script>

<Base {copyright} {locale} {viewTemplate}>
  <div>
    <a href={Routing.generate('mws_offer_lookup', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? null,
    }) }>
      <button class="">Liste des offres</button>
    </a>    
  </div>
  <div class="flex flex-wrap flex-col">
    <h1 class="font-bold p-6 text-center">
      <a href={ offer.sourceUrl ?? "#not-found"} target="_blank" rel="noreferrer">
        {offer.title}
      </a>
    </h1>
    <table class="m-3 text-center">
      <thead>
        <tr>
          <th scope="col">Contact 1</th>
          <th scope="col">Contact 2</th>
          <th scope="col">Budget</th>
          <th scope="col">Depuis le</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{offer.contact1 ?? ''}</td>
          <td>{offer.contact2 ?? ''}</td>      
          <td>{offer.budget ?? ''}</td>      
          <td>{dayjs(offer.leadStart).format('YYYY/MM/DD HH:mm')}</td>      
        </tr>  
      </tbody>
    </table>
    <a href="{ offer.clientUrl ?? "#not-found"}" target="_blank" rel="noreferrer">
      <button class="">Publié par : {offer.clientUsername}</button>
    </a>    
    
    <div class="detail">
      {@html offer.description}
    </div>
    <div>
      {#if myOfferId && offer.sourceUrl}
        <a href="{`${offer.sourceUrl}/${myOfferId}`}" target="_blank" rel="noreferrer">
          <button class="">Accéder aux messages</button>
        </a>      
      {/if}
    </div>
    <div>
      {#each offer.sourceDetail?.messages ?? [] as msg}
        {@html msg.replaceAll('src="/', `src="https://${offer.sourceName}/`)
          .replaceAll('href="/', `href="https://${offer.sourceName}/`)
          .replaceAll(`https://${offer.sourceName}/http`, `http`)
        }
      {/each}
    </div>
  </div>
  <div class="mws-offer-detail w-full overflow-auto">
    <List {locale} offers={[offer]} {viewTemplate} {addMessageForm}></List>
  </div>
</Base>

<style lang="scss">
  // TODO : post CSS syntax allowed in svelte scss ?
  // Done in packages/mws-moon-manager/assets/styles/app.scss
  // .label {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
  // .detail {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
  .mws-offer-detail {
    // .sticky {
    :global(.sticky) {
      // position: inherit !important;
      position: inherit;
    }
  }
</style>