<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./ListItem.svelte";
  import AddModal from "./AddModal.svelte";

  export let locale;
  export let availableFormat = [
    { format:'json', label:'JSON' },
    { format:'yaml', label:'YAML' },
    { format:'csv', label:'CSV' },
  ];
  export let format = 'json';

  const submit = async (e) => {
    const formData = new FormData(e.target);
    const formJson = Object.fromEntries(formData.entries());
    console.log('Should import tags : ', formJson);
  }
</script>

<form on:submit|preventDefault={submit} class="mws-import-tags-form">
  <button type="submit" class="btn btn-outline-primary p-1 m-1">
    Importer les tags
  </button>
  <!-- <input type="submit" class="btn btn-outline-primary p-1 m-1"/>     -->
</form>
