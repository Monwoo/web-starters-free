<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./ListItem.svelte";
  import AddModal from "./AddModal.svelte";
  import Routing from "fos-router";
  import Loader from "../../layout/widgets/Loader.svelte";

  export let locale;
  export let availableFormat = [
    { format:'json', label:'JSON' },
    { format:'yaml', label:'YAML' },
    { format:'csv', label:'CSV' },
  ];
  export let format = 'yaml';
  export let timingLookup = null;
  export let isLoading = false;

  const submit = async (e) => {
    if (isLoading) return;
    isLoading = true;
    // Wait to show is loading feedback if fast answer :
    await new Promise((r) => setTimeout(r, 100));

    const formData = new FormData(e.target);
    if (timingLookup) {
      console.debug('Will export tags with timing filters :', timingLookup);
      formData.append(`timingLookup`, JSON.stringify(timingLookup));
    }
    const formJson = Object.fromEntries(formData.entries());
    console.log('Should export tags : ', formJson);

    var file_path = Routing.generate("mws_timing_tag_export", {
        _locale: locale,
        ...formJson,
    }),

    // TODO : like for timings to wait for full download before moving to next action ?
    var a = document.createElement('A');
    a.href = file_path;
    a.download = file_path.substr(file_path.lastIndexOf('/') + 1);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    isLoading = false;
  }
</script>

<form on:submit|preventDefault={submit} class="mws-export-tags-form">
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

  <button type="submit" class=" m-1">
    Exporter les tags
  </button>
  <!-- <input type="submit" class="btn btn-outline-primary p-1 m-1"/>     -->
  <Loader {isLoading} />
</form>
