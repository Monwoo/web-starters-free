<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./ListItem.svelte";
  import AddModal from "./AddModal.svelte";
  import Routing from "fos-router";
  import {
    state,
    stateGet,
    stateUpdate,
  } from "../../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import Loader from "../../layout/widgets/Loader.svelte";

  export let importedTags;
  export let importedTagsGrouped;
  export let locale;
  export let availableFormat = [
    { format: "json", label: "JSON" },
    { format: "yaml", label: "YAML" },
    { format: "csv", label: "CSV" },
  ];
  export let format = "yaml";
  // 'application/xml',
  // 'application/json',
  // 'application/yaml',
  // 'application/csv',
  // 'text/csv',
  export let formatToMime = {
    json: "application/json,text/plain",
    yaml: "application/yaml,text/plain,application/x-yaml",
    csv: "application/csv,text/csv",
  };
  export let isLoading = false;

  export let importTags = async (input) => {
    const i = {
      format: "yaml",
      importFile: null,
      shouldOverwrite: null,
      ...input,
    };
    // shouldOverwrite is null or 'on'
    const data = {
      _csrf_token: stateGet(get(state), "csrfTimingTagImport"),
      ...i
    };
    // let headers:any = {}; // { 'Content-Type': 'application/octet-stream', 'Authorization': '' };
    let headers = {};
    // https://stackoverflow.com/questions/35192841/how-do-i-post-with-multipart-form-data-using-fetch
    // https://muffinman.io/uploading-files-using-fetch-multipart-form-data/
    // Per this article make sure to NOT set the Content-Type header.
    // headers['Content-Type'] = 'application/json';
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      // TODO : build back Api, will return new csrf to use on success, will error othewise,
      // if error, warn user with 'Fail to remove tag. You are disconnected, please refresh the page...'
      Routing.generate("mws_timing_tag_import", {
        _locale: locale,
      }),
      {
        method: "POST",
        headers,
        // body: JSON.stringify(data), // TODO : no automatic for SF to extract json in ->request ?
        body: formData,
        // https://stackoverflow.com/questions/34558264/fetch-api-with-cookie
        credentials: "same-origin",
        // https://javascript.info/fetch-api
        redirect: "error",
      }
    )
      .then(async (resp) => {
        console.log(resp.url, resp.ok, resp.status, resp.statusText);
        if (!resp.ok) {
          // make the promise be rejected if we didn't get a 2xx response
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          // got the desired response
          const data = await resp.json();
          console.debug("Did import", data);
          // tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          // TODO : like for stateGet, use stateUpdate instead ? (for hidden merge or deepMerge adjustment)
          importedTags = data.tags;
          importedTagsGrouped = data.tagsGrouped;
          stateUpdate(state, {
            csrfTimingTagImport: data.newCsrf,
          });
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
  };

  const submit = async (e) => {
    if (isLoading) return;
    isLoading = true;
    // Wait to show is loading feedback if fast answer :
    await new Promise((r) => setTimeout(r, 100));

    const formData = new FormData(e.target);
    const formJson = Object.fromEntries(formData.entries());
    console.log("Should import tags : ", formJson);
    await importTags(formJson);
    isLoading = false;
  };
</script>

<form on:submit|preventDefault={submit} class="mws-import-tags-form">
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
  dark:focus:border-blue-500"
  >
    <option value="null" selected>Format d'import</option>
    {#each availableFormat as fmt}
      <option value={`${fmt.format}`}>{fmt.label}</option>
    {/each}
  </select>
  <!-- <input type="checkbox" name="shouldOverwrite" checked /> -->
  <span class="inline-flex flex-col">
    <span>
      <input type="checkbox" name="shouldOverwrite" />
      <label for="shouldOverwrite">Forcer la surcharge</label>
    </span>
    <span>
      <input type="checkbox" name="shouldRecomputeAllOtherTags" />
      <label for="shouldOverwrite">Mettre à jour les tags des temps</label>
    </span>
  </span>

  <button type="submit" class=" m-1">
    Importer les tags
  </button>
  <!-- <input type="submit" class="btn btn-outline-primary p-1 m-1"/>     -->
  <Loader {isLoading} />
</form>
