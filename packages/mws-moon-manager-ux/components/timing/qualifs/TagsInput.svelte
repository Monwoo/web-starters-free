<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import { tick } from "svelte";
  import _ from "lodash";

  export let locale;
  export let qualif;
  export let allTagsList;
  export let timeTags;
  export let modalId;

  allTagsList = allTagsList ?? stateGet(get(state), 'allTagsList');

  $: qualif?.timeTags = timeTags;

  let addedTagKey;
  export const syncQualifWithBackend = async (qualif) => {
    const data = {
      _csrf_token: stateGet(get(state), 'csrfTimingQualifSync'),
      qualif: JSON.stringify(qualif),
    };
		let headers = {};
    const formData  = new FormData();      
    for(const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate('mws_timing_qualif_sync', {
        _locale: locale,
      }), {
        method: "POST",
        headers,
        body: formData,
        credentials: "same-origin",
        redirect: 'error',
      }
    ).then(async resp => {
      console.log(resp.url, resp.ok, resp.status, resp.statusText);
      if (!resp.ok) {
        // make the promise be rejected if we didn't get a 2xx response
        throw new Error("Not 2xx response", {cause: resp});
      } else {
          // got the desired response
          const data = await resp.json();
          // qualif = data.qualif; // TODO : in case of backend updates ?
          // needRefresh = {}; // All new obj is uniq, force UI refresh on data inputs
          // BUT not enough for reactivity refresh, so foce with :
          // quickQualifTemplates = quickQualifTemplates; // TOO much
          // BUT WILL CLOSE current opened stuff + loose btn colors ?

          // qualif.id = data.sync.id;

          // WARN : below merge will not RESET fields
          //        BUT : will add to existing list...
          qualif.timeTags = []; // Reset list to ensure clean merge
          _.merge(qualif, data.sync);
          timeTags = qualif.timeTags;
          stateUpdate(state, {
            csrfTimingQualifSync: data.newCsrf,
          });
      }
    }).catch(e => {
      console.error(e);
      // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
      const shouldWait = confirm("Echec de l'enregistrement.");
    });
    return qualif;
  };

  console.debug('allTagsList', allTagsList);
</script>


{#each (timeTags ?? []) as tag, idx}
  <!-- {@const UID = newUniqueId()} -->

  <!-- https://flowbite-svelte.com/docs/components/badge -->
  <!-- <Badge>{tag.label}</Badge> -->

  <!-- // TODO : use css var and css class INSTEAD of hard style injections ? -->
  <!-- https://flowbite.com/docs/components/badge/ -->
  <span
  style:color={"black"}
  style:background-color={"lightgrey"}
  class="inline-flex items-center
  p-1 px-2 m-1 text-sm font-medium 
  opacity-75 hover:opacity-100">
    <!-- // TODO : add i18n translations and keep in Sync with Symfony translation,
    // OR : add translatable entity ways with FOS and always serve the right text ?
      | trans({}, 'my_domaine_name') } forced in all view for auto-gen ?
      + add some 'trans-code' language feature that show the full website with IDs 
      instead of default selected language...
    -->
    {tag.label}
    <button
    on:click|stopPropagation={async () => {
      timeTags = timeTags.filter((t) => t.slug !== tag.slug);
      // await tick(); // wait for qualif to update from svelte reactivity, cf $:
      console.debug('TimeTagsInput delete will update with', qualif);
      await syncQualifWithBackend(qualif);
    }}
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
bind:value={addedTagKey} on:change={async () => {
  if ('null' === addedTagKey) return;

  // timeTags.push({
  //   slug: addedTagKey
  // });
  // await tick(); // wait for qualif to update from svelte reactivity, cf $:
  console.debug('TimeTagsInput add will update with', qualif);
  await syncQualifWithBackend({
    ...qualif,
    timeTags: (qualif.timeTags ?? []).concat([{
      slug: addedTagKey
    }]),
  });
}}
class="opacity-30 hover:opacity-100 
bg-gray-50 border border-gray-300 text-gray-900 
text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
inline-flex w-[10rem] p-1 px-2 m-1 dark:bg-gray-700 dark:border-gray-600 
dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
dark:focus:border-blue-500">
  <option value="null" selected>Insérer un tag</option>
  {#each allTagsList as tag}
    <option value={`${tag.slug}`}>{tag.label}</option>
  {/each}
</select>
