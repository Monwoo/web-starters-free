<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./tags/ListItem.svelte";
  import AddModal from "./tags/AddModal.svelte";
  import Routing from "fos-router";

  export let locale;
  export let availableFormat = [
    { format:'json', label:'JSON' },
    { format:'yaml', label:'YAML' },
    { format:'csv', label:'CSV' },
  ];
  export let format = 'yaml';
  export let timingLookup = null;

  const submit = async (e) => {
    const formData = new FormData(e.target);
    if (timingLookup) {
      console.debug('Will export timings with timing filters :', timingLookup);
      formData.append(`timingLookup`, JSON.stringify(timingLookup));
    }

    const formJson = Object.fromEntries(formData.entries());
    console.log('Should export timings : ', formJson);

    var file_path = Routing.generate("mws_timing_export", {
        _locale: locale,
        ...formJson,
    }),

    var a = document.createElement('A');
    a.href = file_path;
    a.download = file_path.substr(file_path.lastIndexOf('/') + 1);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }
</script>

<form on:submit|preventDefault={submit} class="mws-export-timings-form">
  <select
  bind:value={format}
  name="format"
  class="opacity-30 hover:opacity-100 
  bg-gray-50 border border-gray-300 text-gray-900 
  text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
  inline-flex w-[10rem] p-1 m-2 dark:bg-gray-700 dark:border-gray-600 
  dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
  dark:focus:border-blue-500">
    <option value="null" selected>Format d'export</option>
    {#each availableFormat as fmt}
      <option value={`${fmt.format}`}>{fmt.label}</option>
    {/each}
  </select>  

  <button type="submit" class="btn btn-outline-primary p-1 m-1">
    Exporter les temps
  </button>
  <!-- <input type="submit" class="btn btn-outline-primary p-1 m-1"/>     -->
</form>
