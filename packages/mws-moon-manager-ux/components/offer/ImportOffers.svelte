<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./tags/ListItem.svelte";
  import ImportReportModal from "./ImportReportModal.svelte";
  import Loader from "../layout/widgets/Loader.svelte";
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  // TODO : solve ts issue for type.d.ts from js file :
  // import { Toast, Button } from 'flowbite-svelte';
  // import { CameraFotoOutline } from 'flowbite-svelte-icons';

  let importReport;
  let cssClass = "";
  export {cssClass as class};
  export let importedTags;
  export let importedTagsGrouped;
  export let locale;
  export let availableFormat = [
    { format: "monwoo-extractor-export", label: "Monwoo Extractor" },
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
    "monwoo-extractor-export": "application/json,text/plain",
    json: "application/json,text/plain",
    yaml: "application/yaml,text/plain,application/x-yaml",
    csv: "application/csv,text/csv",
  };

  export let reportModal;
  export let isLoading = false;

  export let shouldOverwrite;

  export let importTags = async (
    inputData = {
      format: "yaml",
      importFile: null,
      shouldOverwrite: null,
    }
  ) => {
    // shouldOverwrite is null or 'on'
    const data = {
      _csrf_token: stateGet(get(state), "csrfOfferImport"),
      // format, shouldOverwrite,
      // importFile: importFile, // JSON.stringify(timeTag),
      ...inputData,
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
      Routing.generate("mws_offer_import", {
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
          // offers = Object.values(data.newTags); // A stringified obj with '1' as index...
          // TODO : force reload, but should recompute ?
          // importedTags = data.offers;
          // importedTagsGrouped = data.offersGrouped;
          importReport = data.importReport;
          reportModal.eltModal.show();
          stateUpdate(state, {
            csrfOfferImport: data.newCsrf,
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
    console.log("Should import offers : ", formJson);
    await importTags(formJson);
    isLoading = false;
  };

  // https://stackoverflow.com/questions/69913703/processing-files-from-an-input-element
  // https://svelte.dev/examples/file-inputs
  function handleFileChange(event) {
    let files = event.target.files;
    for (let f of files) {
      const ext = f.name.split('.').slice(-1)[0];
      console.log(f, ext);
      if (format != 'monwoo-extractor-export'
        && format != ext && (formatToMime[ext] ?? null)) {
        format = ext;
      }
    }
  }

</script>

<form on:submit|preventDefault={submit}
class="mws-import-offers-form {cssClass}">
  {#if format}
    <input
      type="file"
      name="importFile"
      accept={formatToMime[format]}
      on:change={handleFileChange}
    />
  {/if}
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
    <!-- <option value="null" selected>Format d'import</option> -->
    {#each availableFormat as fmt}
      <option value={`${fmt.format}`}>{fmt.label}</option>
    {/each}
  </select>
  <!-- <input type="checkbox" name="shouldOverwrite" checked /> -->
  <span class="inline-flex flex-col">
    <span>
      <!-- https://learn.svelte.dev/tutorial/checkbox-inputs -->
      <input type="checkbox" 
      bind:checked={shouldOverwrite}
      name="shouldOverwrite" />
      <label for="shouldOverwrite">Forcer la surcharge des offres</label>
    </span>
    <span class:opacity-70={!shouldOverwrite}>
      <input type="checkbox" name="forceCurrentStatusSlugRewrite" checked
      disabled={!shouldOverwrite}
      />
      <label
      for="forceCurrentStatusSlugRewrite">Forcer la surcharge du statut principal</label
      >
    </span>
  </span>
  <span class="inline-flex flex-col">
    <span>
      <input type="checkbox" name="forceCleanTags" />
      <label for="forceCleanTags"
        >Effacer les tags existant avant import</label
      >
    </span>
    <span>
      <input type="checkbox" name="forceCleanContacts" />
      <label for="forceCleanContacts">Effacer les contacts existant avant import</label
      >
    </span>
  </span>

  <button type="submit" class=" m-1">
    Importer les offres
  </button>
  <!-- <input type="submit" class="btn btn-outline-primary p-1 m-1"/>     -->
  <Loader {isLoading} />
</form>

<ImportReportModal bind:this={reportModal} htmlReport={importReport}/>
{#if importReport?.length}
  <!-- TODO : should show report in modal, with page refresh on modal close... -->
  <!-- <div>
    {@html importReport}
  </div> -->
  <!-- <Toast align={false}>
    <CameraFotoOutline slot="icon" class="w-5 h-5" />
  
    <span class="font-semibold text-gray-900 dark:text-white">
      {@html importReport}  
    </span>
    <div class="mt-3">
      <div class="mb-2 text-sm font-normal">A new software version is available for download.</div>
      <div class="grid grid-cols-2 gap-2">
        <Button size="xs" class="w-full">Update</Button>
        <Button size="xs" class="w-full" color="dark">Not now</Button>
      </div>
    </div>
  </Toast> -->
  <!-- <div id="toast-interactive" class="fixed w-full max-w-xs p-4 text-gray-500 bg-white rounded-lg shadow dark:bg-gray-800 dark:text-gray-400" role="alert">
    <div class="flex">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-blue-500 bg-blue-100 rounded-lg dark:text-blue-300 dark:bg-blue-900">
            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 20">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 1v5h-5M2 19v-5h5m10-4a8 8 0 0 1-14.947 3.97M1 10a8 8 0 0 1 14.947-3.97"/>
            </svg>
            <span class="sr-only">Refresh icon</span>
        </div>
        <div class="ms-3 text-sm font-normal">
            <span class="mb-1 text-sm font-semibold text-gray-900 dark:text-white">Import Ok</span>
            <div class="mb-2 text-sm font-normal">{@html importReport} .</div> 
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <a href="#" class="inline-flex justify-center w-full px-2 py-1.5 text-xs font-medium text-center text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800">Update</a>
                </div>
                <div>
                    <a href="#" class="inline-flex justify-center w-full px-2 py-1.5 text-xs font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 dark:bg-gray-600 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-700 dark:focus:ring-gray-700">Not now</a> 
                </div>
            </div>    
        </div>
        <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-white items-center justify-center flex-shrink-0 text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" data-dismiss-target="#toast-interactive" aria-label="Close">
            <span class="sr-only">Close</span>
            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
            </svg>
        </button>
    </div>
  </div> -->
{/if}
