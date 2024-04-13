<script context="module">
  // TODO : remove code duplications, inside services ?
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import sanitizeHtml from 'sanitize-html';
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

  // TODO : centralize sanitizer inside service or lib or...
  export let sanitizeClientHtml = (i) => {
    console.debug(i); // return i;
    // https://www.npmjs.com/package/sanitize-html
    const clean = sanitizeHtml(i, {
      allowedTags: sanitizeHtml.defaults
      .allowedTags.concat([ 'img' ])
      .concat([
        // SVG
        'svg', 'g', 'defs', 'linearGradient', 'stop', 'circle',
        'path'
      ]),
      // allowedAttributes: false, // For SVG
      allowedAttributes: {
        ...sanitizeHtml.defaults.allowedAttributes,
        '*': [
          'href', 'align', 'alt', 'center', 'bgcolor',
          'src', 'class', 'role', 'xmlns',
          'data*', 'aria*',
          'focusable', 'viewBox', 'd', 'fill',
        ],
        iframe: [
          {
            name: 'sandbox',
            multiple: true,
            values: ['allow-popups', 'allow-same-origin', 'allow-scripts']
          }
        ],
      },
      parser: {
        // SVG elements like linearGradient into your content and 
        // notice that they're not rendering as expected
        // due to case sensitivity issues without below option :
        lowerCaseTags: false,
        lowerCaseAttributeNames: false
      },
    });

    return clean;
  };

  console.debug(offer);

  // TODO : configurable services, use generic data connectors instead of :
  // TODO : remove code duplication :
  let myOfferId = offer.sourceDetail?.monwooOfferId ?? null;
  myOfferId = myOfferId ? 'offers/' + myOfferId.split('_').slice(-1)[0] : null;

</script>

<Base {copyright} {locale} {viewTemplate}>
  <div class="w-full flex flex-wrap
  items-center justify-start p-2">
  <div class="w-full">
    <a href={Routing.generate('mws_offer_lookup', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? null,
    }) }>
      <button class="">Liste des offres</button>
    </a>    
  </div>
  <div class="flex flex-wrap flex-col w-full
  break-words whitespace-break-spaces">
    <h1 class="font-bold p-6 text-center">
      <a href={ offer.sourceUrl ?? "#not-found"} target="_blank" rel="noreferrer">
        {offer.title}
      </a>
    </h1>
    <div class="w-full overflow-x-auto">
      <table class="m-3 text-center w-full">
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
    </div>

    <a href="{ offer.clientUrl ?? "#not-found"}"
    class="w-full"
    target="_blank" rel="noreferrer">
      <button class="">Publié par : {offer.clientUsername}</button>
    </a>    
    
    <div class="offer-description w-full">
      {@html sanitizeClientHtml(offer.description)}
    </div>
    <div class="w-full">
      {#if myOfferId && offer.sourceUrl}
        <a href="{`${offer.sourceUrl}/${myOfferId}`}" target="_blank" rel="noreferrer">
          <button class="">Accéder aux messages</button>
        </a>      
      {/if}
    </div>
    <div class="offer-messages w-full break-words whitespace-break-spaces">
      {#each offer.sourceDetail?.messages ?? [] as msg}
        {@html sanitizeClientHtml(msg.replaceAll('src="/', `src="https://${offer.sourceName}/`)
          .replaceAll('href="/', `href="https://${offer.sourceName}/`)
          .replaceAll(`https://${offer.sourceName}/http`, `http`))
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
    // TIPS : removing sticky feature of list view :
    :global(.sticky) {
      // position: inherit !important;
      position: inherit;
    }
  }
  .offer-description,
  .offer-messages {
    :global(*) {
      // @apply break-all;
      @apply break-words; // NOT same as  word-break: break-word;...
      word-break: break-word;
      @apply whitespace-break-spaces;
    }
  }
</style>