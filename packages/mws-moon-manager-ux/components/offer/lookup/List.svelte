<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // TODO : namespace
  import ListItem from "./ListItem.svelte";
  import AddModal from '../../message/AddModal.svelte';

  export let locale;
  export let offers = [];
  export let addMessageForm;
  export let offersHeaders = {}; // injected raw html
  export let viewTemplate;

  let addModal;
</script>
<AddModal bind:this={addModal} {addMessageForm} />

<table>
  <thead>
    <tr class="users-table-info">
      <th scope="col">Voir</th>
      <th scope="col">[Slug] Status</th>
      <th scope="col">Tags</th>
      <th scope="col">
        {@html offersHeaders.clientUsername ?? "Nom du client"}
      </th>
      <th scope="col">
        {@html offersHeaders.contact1 ?? "Contact"}<br/>
        {@html offersHeaders.contact2 ?? "Contact bis"}
      </th>
      <th scope="col">
        Messages
      </th>
      <th scope="col">
        {@html offersHeaders.leadStart ?? "Depuis le"}
      </th>
      <th scope="col">
        {@html offersHeaders.budget ?? "Budget"}
      </th>
      <th scope="col">Titre</th>
      <th scope="col">Description</th>
    </tr>
  </thead>
  <tbody>
    {#each offers as offer}
      <!-- { JSON.stringify(offer) } -->
      <!-- {@debug offer} -->
      <ListItem {offer} {locale} {viewTemplate} {addModal}></ListItem>
    {/each}
  </tbody>
</table>