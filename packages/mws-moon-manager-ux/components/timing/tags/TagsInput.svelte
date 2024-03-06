<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  // import { locale } from "dayjs";
  // import newUniqueId from 'locally-unique-id-generator';

  // TODO : need to fix Svelte ts config for this one to work : (adding svelte-kit ?)
  // import { Badge } from 'flowbite-svelte';
  // import { Badge } from 'flowbite-svelte/dist/badge/Badge.svelte';
  // import * as fbs from 'flowbite-svelte';

  export let locale;
  export let timing;
  export let allTagsList;
  export let tags;
  export let modalId;

  allTagsList = allTagsList ?? stateGet(get(state), 'allTagsList');

  let addedTagKey;
  export let removeTag = async (tag, comment = null) => {
    const data = {
      _csrf_token: stateGet(get(state), 'csrfTimingTagDelete'),
      timeSlotId: timing.id,
      tagSlug: tag.slug,
      comment, // TODO : allow optional comment on status switch ?
    };
		// let headers:any = {}; // { 'Content-Type': 'application/octet-stream', 'Authorization': '' };
		let headers = {};
    // https://stackoverflow.com/questions/35192841/how-do-i-post-with-multipart-form-data-using-fetch
    // https://muffinman.io/uploading-files-using-fetch-multipart-form-data/
    // Per this article make sure to NOT set the Content-Type header. 
		// headers['Content-Type'] = 'application/json';
    const formData  = new FormData();      
    for(const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      // TODO : build back Api, will return new csrf to use on success, will error othewise,
      // if error, warn user with 'Fail to remove tag. You are disconnected, please refresh the page...'
      Routing.generate('mws_timing_tag_delete', {
        _locale: locale,
      }), {
        method: "POST",
        headers,
        // body: JSON.stringify(data), // TODO : no automatic for SF to extract json in ->request ?
        body: formData,
        // https://stackoverflow.com/questions/34558264/fetch-api-with-cookie
        credentials: "same-origin",
        // https://javascript.info/fetch-api
        redirect: 'error',
      }
    ).then(async resp => {
      console.log(resp);
      if (!resp.ok) {
        // make the promise be rejected if we didn't get a 2xx response
        throw new Error("Not 2xx response", {cause: resp});
      } else {
          // got the desired response
          const data = await resp.json();
          tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          // TODO : like for stateGet, use stateUpdate instead ? (for hidden merge or deepMerge adjustment)
          stateUpdate(state, {
            csrfTimingTagDelete: data.newCsrf,
          });
      }
    }).catch(e => {
      console.error(e);
      // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
      const shouldWait = confirm("Echec de l'enregistrement.");
    });
  };

  export let addTag = async (tagSlug, tagCategorySlug, comment = null) => {
    // TODO : fetch modal response
    const $ = window.$;
    const modalBtn = $(`[data-modal-target="${modalId}"]`);
    console.log(modalBtn);
    modalBtn.click();

    const data = {
      _csrf_token: stateGet(get(state), 'csrfTimingTagAdd'),
      timeSlotId: timing.id,
      tagSlug: tagSlug,
      comment, // TODO : allow optional comment on status switch ?
      tagCategorySlug: tagCategorySlug,
    };
    const formData  = new FormData();      
    for(const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate('mws_timing_tag_add', {
        _locale: locale,
      }), {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        redirect: 'error',
      }
    ).then(async resp => {
      console.log(resp);
      addedTagKey = "null";
      if (!resp.ok) {
        throw new Error("Not 2xx response", {cause: resp});
      } else {
          const data = await resp.json();
          tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          console.debug("Did add tag", tags);
          stateUpdate(state, {
            csrfTimingTagAdd: data.newCsrf,
          });
      }
    }).catch(e => {
      console.error(e);
      // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
      const shouldWait = confirm("Echec de l'enregistrement.");
      addedTagKey = "null";
    });
  };

  console.debug('allTagsList', allTagsList);
</script>


{#each (tags ?? []) as tag, idx}
  <!-- {@const UID = newUniqueId()} -->

  <!-- https://flowbite-svelte.com/docs/components/badge -->
  <!-- <Badge>{tag.label}</Badge> -->

  <!-- // TODO : use css var and css class INSTEAD of hard style injections ? -->
  <!-- https://flowbite.com/docs/components/badge/ -->
  <span
  style:color={"black"}
  style:background-color={"lightgrey"}
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
    on:click|stopPropagation={() => removeTag(tag) }
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

<select
bind:value={addedTagKey} on:change={() => {
  if ('null' === addedTagKey) return;

  const [tagCategorySlug, tagSlug] = addedTagKey.split('|');
  addTag(tagSlug, tagCategorySlug);
}}
class="opacity-30 hover:opacity-100 
bg-gray-50 border border-gray-300 text-gray-900 
text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
inline-flex w-[10rem] p-1 m-2 dark:bg-gray-700 dark:border-gray-600 
dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
dark:focus:border-blue-500">
  <option value="null" selected>Ajouter un tag</option>
  {#each allTagsList as tag}
    <option value={`${tag.categorySlug}|${tag.slug}`}>{tag.label}</option>
  {/each}
</select>
