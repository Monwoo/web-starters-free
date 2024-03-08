<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./ListItem.svelte";
  import AddModal from "./AddModal.svelte";
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";

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

  const submit = async (e) => {
    const formData = new FormData(e.target);
    const formJson = Object.fromEntries(formData.entries());
    console.log('Should import tags : ', formJson);
  }
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
  dark:focus:border-blue-500">
    <option value="null" selected>Format d'import</option>
    {#each availableFormat as fmt}
      <option value={`${fmt.format}`}>{fmt.label}</option>
    {/each}
  </select>  

  <button type="submit" class="btn btn-outline-primary p-1 m-1">
    Importer les tags
  </button>
  <!-- <input type="submit" class="btn btn-outline-primary p-1 m-1"/>     -->
</form>
