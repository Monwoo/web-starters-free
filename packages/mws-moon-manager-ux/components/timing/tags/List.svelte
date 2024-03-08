<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./ListItem.svelte";
  import AddModal from "./AddModal.svelte";
import ExportTags from "./ExportTags.svelte";
import ImportTags from "./ImportTags.svelte";

  export let locale;
  export let viewTemplate;
  export let tags = [];
  export let tagsHeaders = {};
  export let addModal;

</script>

<div class="timing-tag-list">
  <AddModal bind:this={addModal} allTags={tags} {locale} />

  <ImportTags></ImportTags>
  <ExportTags></ExportTags>

  <button
    on:click={() => {
      addModal.surveyModel.data = null; // Ensure data is empty before show...
      addModal.eltModal.show();
    }}
    class="btn btn-outline-primary p-1 m-1"
  >
    Importer des tags
  </button>

  <button
    on:click={() => {
      addModal.surveyModel.data = null; // Ensure data is empty before show...
      addModal.eltModal.show();
    }}
    class="btn btn-outline-primary p-1 m-1"
  >
    Ajouter un tag
  </button>

  <table>
    <thead class="top-[-21px] sticky z-40">
      <tr class="users-table-info">
        <th scope="col">Actions</th>
        <th scope="col">
          {@html tagsHeaders.slug ?? "Slug"}
        </th>
        <th scope="col">
          {@html tagsHeaders.label ?? "Libellé"}
        </th>
        <th scope="col">
          <!-- TODO : translate... from app local provided by SF or local file from .env config ? -->
          {"Migrer vers"}
        </th>
        <th scope="col">
          <!-- TODO : translate... from app local provided by SF or local file from .env config ? -->
          {"Nb Category"}
        </th>
        <th scope="col">
          <!-- TODO : translate... from app local provided by SF or local file from .env config ? -->
          {"Nb TimeSlot"}
        </th>
        <th scope="col">
          <!-- TODO : translate... from app local provided by SF or local file from .env config ? -->
          {"Nb TimeQualif"}
        </th>
        <th scope="col">
          {@html tagsHeaders.createdAt ?? "Created at"}
        </th>
      </tr>
    </thead>
    <tbody>
      {#each tags as tag}
        <ListItem {locale} {viewTemplate} {tag} {addModal} allTagsList={tags} />
      {/each}
    </tbody>
  </table>
</div>
