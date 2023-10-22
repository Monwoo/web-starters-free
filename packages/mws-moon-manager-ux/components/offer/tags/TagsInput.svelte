<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { state, slugToOfferTag } from "../../../stores/reduxStorage.mjs";
  // import { locale } from "dayjs";
  // import newUniqueId from 'locally-unique-id-generator';

  // TODO : need to fix Svelte ts config for this one to work : (adding svelte-kit ?)
  // import { Badge } from 'flowbite-svelte';
  // import { Badge } from 'flowbite-svelte/dist/badge/Badge.svelte';
  // import * as fbs from 'flowbite-svelte';

  export let locale;
  export let offer;
  export let tags;

  export let removeTag = async (tag) => {
    const resp = await fetch(
      // TODO : build back Api, will return new csrf to use on success, will error othewise,
      // if error, warn user with 'Fail to remove tag. You are disconnected, please refresh the page...'
      Routing.generate('mws_offer_tag_delete', {
        _locale: locale,
        offerSlug: offer.slug,
        tagSlug: tag.slug,
        // _csrf: ..., // TODO : Auth secu... jwt auto-connect if already logged with regular sf ? OR only need csrf validation with credentials transmits ?
      }), {
        // https://stackoverflow.com/questions/34558264/fetch-api-with-cookie
        credentials: "same-origin"
      }
    )
  };

</script>

{#each (tags ?? []) as tag, idx}
  <!-- {@const UID = newUniqueId()} -->

  <!-- https://flowbite-svelte.com/docs/components/badge -->
  <!-- <Badge>{tag.label}</Badge> -->

  <!-- // TODO : use css var and css class INSTEAD of hard style injections ? -->
  <!-- https://flowbite.com/docs/components/badge/ -->
  <span
  style:color={(slugToOfferTag($state, tag.slug)?.textColor)||"black"}
  style:background-color={(slugToOfferTag($state, tag.slug)?.bgColor)||"lightgrey"}
  class="inline-flex items-center
  px-2 py-1 mr-2 text-sm font-medium 
  opacity-75 hover:opacity-100">
    <!-- // TODO : add i18n translations and keep in Sync with Symfony translation,
    // OR : add translatable entity ways with FOS and always serve the right text ?
      | trans({}, 'my_domaine_name') } forced in all view for auto-gen ?
      + add some 'trans-code' language feature that show the full website with IDs 
      instead of default selected language...
    -->
    {tag.label}
    <button
    on:click={() => removeTag(tag) }
    type="button" class="inline-flex items-center p-1 ml-2 text-sm
    text-pink-400 bg-transparent rounded-sm hover:bg-pink-200
     hover:text-pink-900 dark:hover:bg-pink-800 dark:hover:text-pink-300"
    aria-label="Remove">
      <svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
      </svg>
      <span class="sr-only">Remove tag {tag.label}</span>
    </button>
  </span>

  <!-- // TIPS : if not using onclick, just for visual effect ? :
  <span
  id="badge-dismiss-{UID}"
  ......
    <button
      data-dismiss-target="#badge-dismiss-{UID}"
  -->
{/each}