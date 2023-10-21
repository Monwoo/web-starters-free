<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";

  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let viewTemplate;
  export let offer;

  console.debug(offer);

  // TODO : configurable services, use generic data connectors instead of :
  let myOfferId = offer.sourceDetail?.monwooOfferId ?? null;
  myOfferId = myOfferId ? 'offers/' + myOfferId.split('_').slice(-1)[0] : null;

</script>

<Base {copyright} {locale} {viewTemplate}>
  <div>
    <a href="#back" on:click={() => history.back()}>
      <button class="btn btn-outline-primary p-1">Revenir à la page précédente</button>
    </a>    
  </div>
  <div class="flex flex-wrap flex-col">
    <h1 class="font-bold p-6 text-center">
      <a href="{ offer.sourceUrl ?? "#not-found"}" target="_blank" rel="noopener">
        {offer.title}
      </a>
    </h1>
    <a href="{ offer.clientUrl ?? "#not-found"}" target="_blank" rel="noopener">
      <button class="btn btn-outline-primary p-1">Publié par : {offer.clientUsername}</button>
    </a>    
    
    <div class="detail">
      {@html offer.description}
    </div>
    <div>
      {#if myOfferId && offer.sourceUrl}
        <a href="{`${offer.sourceUrl}/${myOfferId}`}" target="_blank" rel="noopener">
          <button class="btn btn-outline-primary p-1">Accéder aux messages</button>
        </a>      
      {/if}
    </div>
    <div>
      {#each offer.sourceDetail?.messages ?? [] as msg}
        {@html msg.replace('src="', `src="https://${offer.sourceName}/`)}
      {/each}
    </div>
  </div>
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