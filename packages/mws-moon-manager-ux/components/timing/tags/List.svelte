<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./ListItem.svelte";
  import AddModal from "./AddModal.svelte";
  import ExportTags from "./ExportTags.svelte";
  import ImportTags from "./ImportTags.svelte";
  import Routing from "fos-router";
  import {
    state,
    stateGet,
    stateUpdate,
  } from "../../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import ConfidentialityStamp from "../ConfidentialityStamp.svelte";

  export let locale;
  export let viewTemplate;
  export let tags = [];
  export let tagsHeaders = {};
  export let addModal;
  let isLoading = false;

  const deleteAllTags = async () => {
    if (isLoading) return;
    isLoading = true;
    // TODO : Wait for loading animation to show
    // await tick();
    // await new Promise(r => setTimeout(r, 500));
    // Or use HTML modal instead of native blocking UI alert
    await new Promise((r) => setTimeout(r, 100));

    if (confirm("Are you sure you want to delete all timing tags ?")) {
      const data = {
        _csrf_token: stateGet(get(state), "csrfTimingTagDeleteAll"),
      };
      const formData = new FormData();
      for (const name in data) {
        formData.append(name, data[name]);
      }
      const resp = await fetch(
        Routing.generate("mws_timing_tag_delete_all", {
          _locale: locale,
        }),
        {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          redirect: "error",
        }
      )
        .then(async (resp) => {
          console.log(resp.url, resp.ok, resp.status, resp.statusText);
          if (!resp.ok) {
            throw new Error("Not 2xx response", { cause: resp });
          } else {
            const data = await resp.json();
            console.debug("Did remove all tags, resp :", data);
            // TODO : remove self from DOM instead of isHidden ?
            tags = [];
            stateUpdate(state, {
              csrfTimingTagDeleteAll: data.newCsrf,
            });
          }
        })
        .catch((e) => {
          console.error(e);
          // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
          const shouldWait = confirm("Echec de l'enregistrement.");
        });
    }
    isLoading = false;
  };

</script>

<div class="timing-tag-list">
  <AddModal bind:this={addModal} allTags={tags} {locale} />

  <p>
    Attention, les prix ne sont pas directement re-calculé pour des raisons d'optimisations. Pensez à cocher 'Mettre à jour les tags des temps' et importez sans fichiers pour mettre à jour le prix des temps segmentés.
  </p>

  <ImportTags {locale} bind:importedTagsGrouped={tags} />
  <ExportTags {locale} />

  <button
    on:click={deleteAllTags}
    class=" m-1"
    style="--mws-primary-rgb: 255, 0, 0"
  >
    Supprimer tous les tags
  </button>

  <button
    on:click={() => {
      addModal.surveyModel.data = null; // Ensure data is empty before show...
      addModal.eltModal.show();
    }}
    class=" m-1"
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
          {"Prix(s)"}
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
        <!-- <th scope="col">
          {@html tagsHeaders.createdAt ?? "Created at"}
        </th> -->
      </tr>
    </thead>
    <tbody>
      {#each tags as tag}
        <ListItem {locale} {viewTemplate} {tag} {addModal} allTagsList={tags} />
      {/each}
    </tbody>
  </table>
  <ConfidentialityStamp />
</div>
