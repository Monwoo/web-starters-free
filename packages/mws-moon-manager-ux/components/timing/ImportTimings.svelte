<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./tags/ListItem.svelte";
  import AddModal from "./tags/AddModal.svelte";
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";

  let importReport;
  export let importedTags;
  export let importedTagsGrouped;
  export let locale;
  export let availableFormat = [
    { format:'json', label:'JSON' },
    { format:'yaml', label:'YAML' },
    { format:'csv', label:'CSV' },
  ];
  export let format = 'yaml';
  // 'application/xml',
  // 'application/json',
  // 'application/yaml',
  // 'application/csv',
  // 'text/csv',
  export let formatToMime = {
    'json': 'application/json,text/plain',
    'yaml': 'application/yaml,text/plain,application/x-yaml',
    'csv': 'application/csv,text/csv',
  }

  export let importTags = async (inputData = {
    format: 'yaml', importFile: null, shouldOverwrite: null
  }) => {
    // shouldOverwrite is null or 'on'
    const data = {
      _csrf_token: stateGet(get(state), 'csrfTimingImport'),
      // format, shouldOverwrite,
      // importFile: importFile, // JSON.stringify(timeTag),
      ...inputData
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
      Routing.generate('mws_timing_import', {
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
          console.debug("Did import", data);
          // timings = Object.values(data.newTags); // A stringified obj with '1' as index...
          // TODO : force reload, but should recompute ?
          // importedTags = data.timings;
          // importedTagsGrouped = data.timingsGrouped;
          importReport = data.importReport;
          stateUpdate(state, {
            csrfTimingImport: data.newCsrf,
          });
      }
    }).catch(e => {
      console.error(e);
      // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
      const shouldWait = confirm("Echec de l'enregistrement.");
    });
  };


  const submit = async (e) => {
    const formData = new FormData(e.target);
    const formJson = Object.fromEntries(formData.entries());
    console.log('Should import timings : ', formJson);
    importTags(formJson);
  }
</script>

<form on:submit|preventDefault={submit} class="mws-import-timings-form">
  <input type="file" name="importFile" accept={formatToMime[format]} />
  <br />
  <select
  bind:value={format}
  name="format"
  class="opacity-30 hover:opacity-100 
  bg-gray-50 border border-gray-300 text-gray-900 
  text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
  inline-flex w-[10rem] p-1 m-2 dark:bg-gray-700 dark:border-gray-600 
  dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
  dark:focus:border-blue-500">
    <option value="null" selected>Format d'import</option>
    {#each availableFormat as fmt}
      <option value={`${fmt.format}`}>{fmt.label}</option>
    {/each}
  </select>  
  <!-- <input type="checkbox" name="shouldOverwrite" checked /> -->
  <span class="inline-flex flex-col">
    <span>
      <input type="checkbox" name="shouldOverwrite" />
      <label for="shouldOverwrite">Forcer la surcharge des temps</label>  
    </span>
    <span>
      <input type="checkbox" name="shouldOverwritePriceRules" />
      <label for="shouldOverwrite">Forcer la surcharge des règles de prix</label>  
    </span>
  </span>
  <span class="inline-flex flex-col">
    <span>
      <input type="checkbox" name="shouldRecomputeAllOtherTags" />
      <label for="shouldOverwrite">Mettre à jour les tags des autres temps</label>  
    </span>
    <span>
      <input type="checkbox" name="shouldIdentifyByFilename" />
      <label for="shouldIdentifyByFilename">Identifier par nom de fichier</label>  
    </span>
</span>

  <button type="submit" class="btn btn-outline-primary p-1 m-1">
    Importer les temps
  </button>
  <!-- <input type="submit" class="btn btn-outline-primary p-1 m-1"/>     -->
</form>
{#if importReport?.length}
<div>
  {@html importReport}
</div>
{/if}
