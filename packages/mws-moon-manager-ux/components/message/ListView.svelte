<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import MessageView from './MessageView.svelte';
  import AddModal from './AddModal.svelte';
  import Routing from "fos-router";
  export let locale;
  export let viewTemplate;
  // export let title = null;
  // export let currentLanguage = null;
  export let messages = [];
  export let messagesHeaders = {}; // injected raw html
  export let addMessageForm;
  export let offersPaginator = null;
  export let csrfMessageDelete;

  let addModal;

  console.debug("Having msg : ", messages);
</script>

<AddModal bind:this={addModal} {addMessageForm} />

<div>
  <a href={ Routing.generate('mws_message_import', {
    '_locale': locale ?? '',
    'viewTemplate': viewTemplate ?? '',
  }) }>
    <button class=" m-2">Importer des messages.</button>
  </a>    
  <a href={ Routing.generate('mws_message_export', {
    '_locale': locale ?? '',
    'viewTemplate': viewTemplate ?? '',
  }) }>
    <button class=" m-2">Exporter les messages.</button>
  </a>    
  <!-- <a href={ Routing.generate('mws_message_import', {
    '_locale': locale ?? '',
    'viewTemplate': viewTemplate ?? '',
  }) }>
    <button class="">Supprimer les messages.</button>
  </a>     -->
  <form action="{ Routing.generate('mws_message_delete_all', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
    }) }"
    method="post"
    onsubmit="return confirm('Êtes vous sur de vouloir supprimer définitivement tous les messages ?');"
  >
    <input type="hidden" name="_csrf_token" value="{ csrfMessageDelete }" />
    <button 
    class=" m-2"
    style:--tw-shadow-color="#FF0000"
    type="submit">Supprimer les messages</button>
  </form>

  <button
  class=""
  on:click={() => {
    addModal.surveyModel.data = null; // Ensure data is empty before show...
    addModal.eltModal.show();
  }}
  >Ajouter un message.</button>
</div>

<div>{@html offersPaginator}</div>
<table>
  <thead class="sticky top-[-1px] md:-top-6 wide:top-[-1px] z-40 text-xs md:text-sm">
    <tr class="users-table-info">
      <th scope="col">Actions</th>
      <th scope="col">
        {@html messagesHeaders.projectId ?? "projectId"}
      </th>
      <th scope="col">
        {@html messagesHeaders.destId ?? "destId"}
      </th>
      <th scope="col">
        {@html messagesHeaders.templateNameSlug ?? "templateNameSlug"}
      </th>
      <th scope="col">
        {@html messagesHeaders.monwooAmount ?? "monwooAmount"}
      </th>
      <th scope="col">
        {@html messagesHeaders.monwooAmountType ?? "monwooAmountType"}
      </th>
      <th scope="col">
        {@html messagesHeaders.projectDelayInOpenDays ?? "projectDelayInOpenDays"}
      </th>
      <th scope="col">
        {@html messagesHeaders.asNewOffer ?? "asNewOffer"}
      </th>
      <th scope="col">
        {@html messagesHeaders.isDraft ?? "isDraft"}
      </th>
      <th scope="col">
        {@html messagesHeaders.sourceId ?? "sourceId"}
      </th>
      <th scope="col">
        {@html messagesHeaders.crmLogs ?? "crmLogs"}
      </th>
      <th scope="col">
        {@html messagesHeaders.messages ?? "messages"}
      </th>
      <th scope="col">
        {@html messagesHeaders.owner ?? "owner"}
      </th>
      <th scope="col">
        {@html messagesHeaders.updatedAt ?? "updatedAt"}
      </th>
    </tr>
  </thead>
  <tbody>
    {#each messages as message}
      <MessageView {message} addModal={addModal}></MessageView>
    {/each}
  </tbody>
</table>
<div>{@html offersPaginator}</div>

