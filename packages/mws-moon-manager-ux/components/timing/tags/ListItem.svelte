<script context="module">
  // TODO : remove code duplications, inside services ?
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  dayjs.locale("fr"); // Fr locale

</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../../stores/reduxStorage.mjs";
  import { tick } from "svelte";
  import { get } from "svelte/store";

  export let locale;
  export let viewTemplate;
  export let tag;
  export let migrateToTagKey;
  export let allTagsList;
  export let addModal;
  let isLoading = false;
  let isHidden = false;

  const deleteTag = async () => {
    if (isLoading) return;
    console.debug("Shoud delete with", tag);
    isLoading = true;
    // TODO : Wait for loading animation to show
    // await tick();
    // await new Promise(r => setTimeout(r, 500));
    // Or use HTML modal instead of native blocking UI alert
    await new Promise((r) => setTimeout(r, 100));

    if (
      confirm(
        "Are you sure you want to delete [" +
          tag.self.slug +
          "] and remove all " +
          (tag.categoriesCount + tag.tSlotCount + tag.tQualifCount) +
          " usages ?"
      )
    ) {
      const data = {
        _csrf_token: stateGet(get(state), "csrfTimingMigrateTo"),
        tagId: tag.self.id,
      };
      const formData = new FormData();
      for (const name in data) {
        formData.append(name, data[name]);
      }
      const resp = await fetch(
        Routing.generate("mws_timing_tag_delete_and_clean", {
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
          console.log(resp);
          if (!resp.ok) {
            throw new Error("Not 2xx response", { cause: resp });
          } else {
            const data = await resp.json();
            console.debug("Did remove tag, resp :", data);
            // TODO : remove self from DOM instead of isHidden ?
            isHidden = true;
            stateUpdate(state, {
              csrfTimingTagDeleteAndClean: data.newCsrf,
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

  const migrateToTag = async () => {
    if (isLoading) return;
    if (migrateToTagKey || "null" === migrateToTagKey) return;
    // TODO : await confirm and update tags dependencies if so...
    console.debug("Shoud migrateToTag with", migrateToTagKey);
    isLoading = true;
    const toTag = {
      slug: migrateToTagKey,
    };
    // TODO : Wait for loading animation to show
    // Or use HTML modal instead of native blocking UI alert
    await new Promise((r) => setTimeout(r, 100));

    if (
      confirm(
        "Are you sure you want to migrate [" +
          tag.self.slug +
          "] to [" +
          toTag.slug +
          "] ?"
      )
    ) {
      const data = {
        _csrf_token: stateGet(get(state), "csrfTimingMigrateTo"),
        tagFromId: tag.self.id,
        tagToId: tagTo.id,
      };
      const formData = new FormData();
      for (const name in data) {
        formData.append(name, data[name]);
      }
      const resp = await fetch(
        Routing.generate("mws_timing_tag_migrate_to", {
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
          console.log(resp);
          if (!resp.ok) {
            throw new Error("Not 2xx response", { cause: resp });
          } else {
            const data = await resp.json();
            console.debug("Did remove tag, resp :", data);
            // TODO : remove self from DOM instead of isHidden ?
            isHidden = true;
            // Need self refresh for merged data values :
            window.location.reload();

            stateUpdate(state, {
              csrfTimingMigrateTo: data.newCsrf,
            });
          }
        })
        .catch((e) => {
          console.error(e);
          // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
          const shouldWait = confirm("Echec de l'enregistrement.");
        });
    } else {
      migrateToTagKey = "null";
    }
    isLoading = false;
  };

  console.debug("Having Tag : ", tag);

</script>

<!-- // TIPS : is not categorySlug, this status is a category... -->
<tr
  class:bg-blue-100={!tag.self.categorySlug}
  class:opacity-40={isLoading}
  class:pointer-events-none={isLoading}
  class:hidden={isHidden}
>
  <td>
    <!-- <button class="btn btn-outline-primary p-1 m-1">Editer</button> -->
    <!-- <a href={ Routing.generate('mws_offer_tag_edit', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
      'slug': tag.self.slug,
      'categorySlug': tag.self.categorySlug,
    }) }>
      <button class="btn btn-outline-primary p-1 m-1">Supprimer</button>
    </a> -->
    <button
      class="btn btn-outline-primary p-1 m-1"
      on:click={() => {
        console.debug("Will show :", tag);
        addModal.surveyModel.data = tag;
        addModal.eltModal.show();
      }}
    >
      Editer
    </button>
    <button
      class="btn btn-outline-primary p-1 m-1"
      style="--mws-primary-rgb: 255, 0, 0"
      on:click={deleteTag}
    >
      Supprimer
    </button>
  </td>
  <th scope="row">
    <span>{tag.self.slug}</span>
    <!-- { JSON.stringify(tag.self.categoryOkWithMultiplesTags) } -->
    <span class:hidden={!tag.self.categoryOkWithMultiplesTags}> [MultiOk]</span>
  </th>
  <td class="text-left">
    <span
      class="p-2 rounded"
      style:color={tag.self.textColor || "black"}
      style:background-color={tag.self.bgColor || "lightgrey"}
    >
      {tag.self.label ?? ""}
    </span>
  </td>
  <td>
    <select
      bind:value={migrateToTagKey}
      on:change={migrateToTag}
      class="opacity-30 hover:opacity-100 
    bg-gray-50 border border-gray-300 text-gray-900 
    text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
    inline-flex w-[10rem] p-1 m-2 dark:bg-gray-700 dark:border-gray-600 
    dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
    dark:focus:border-blue-500"
    >
      <option value="null" selected>Migrer vers</option>
      {#each allTagsList as tag}
        <option value={`${tag.self.slug}`}>{tag.self.label}</option>
      {/each}
    </select>
  </td>
  <td class="text-center">{tag.categoriesCount}</td>
  <td class="text-center">{tag.tSlotCount}</td>
  <td class="text-center">{tag.tQualifCount}</td>
  <td>{dayjs(tag.self.createdAt).format("YYYY/MM/DD h:mm")}</td>
</tr>
