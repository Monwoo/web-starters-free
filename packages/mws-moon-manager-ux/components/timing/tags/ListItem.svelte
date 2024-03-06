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
  import { state, offerTagsByKey } from "../../../stores/reduxStorage.mjs";

  export let locale;
  export let viewTemplate;
  export let tag;
  export let mergeToTagKey;
  export let allTagsList;
  export let addModal;

  const mergeToTag = () => {
    if ("null" === mergeToTagKey) return;
    // TODO : await confirm and update tags dependencies if so...
    console.debug("Shoud mergeToTagKey with", mergeToTagKey);

    const toTag = {
      slug: mergeToTagKey,
    };

    if (
      confirm(
        "Are you sure you want to merge [" + tag.slug + "] to [" + toTag.slug + "]"
      )
    ) {
      alert("The tag is successfully updated, with XXX timeSlots updated");
    }
  };

  console.debug('Having Tag : ', tag);
</script>

<!-- // TIPS : is not categorySlug, this status is a category... -->
<tr class:bg-blue-100={!tag.categorySlug}>
  <td>
    <!-- <button class="btn btn-outline-primary p-1 m-1">Editer</button> -->
    <!-- <a href={ Routing.generate('mws_offer_tag_edit', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
      'slug': tag.slug,
      'categorySlug': tag.categorySlug,
    }) }>
      <button class="btn btn-outline-primary p-1 m-1">Supprimer</button>
    </a> -->
    <button class="btn btn-outline-primary p-1 m-1"
    on:click={
      () => {
        console.debug("Will show :", tag);
        addModal.surveyModel.data = tag;
        addModal.eltModal.show();
      }
    }> Editer </button>
    <button
      class="btn btn-outline-primary p-1 m-1"
      style="--mws-primary-rgb: 255, 0, 0"
    >
      Supprimer
    </button>
  </td>
  <th scope="row">
    <span>{tag.slug}</span>
    <!-- { JSON.stringify(tag.categoryOkWithMultiplesTags) } -->
    <span class:hidden={!tag.categoryOkWithMultiplesTags}> [MultiOk]</span>
  </th>
  <td class="text-left">
    <span
      class="p-2 rounded"
      style:color={tag.textColor || "black"}
      style:background-color={tag.bgColor || "lightgrey"}
    >
      {tag.label ?? ""}
    </span>
  </td>
  <td>
    <select
      bind:value={mergeToTagKey}
      on:change={mergeToTag}
      class="opacity-30 hover:opacity-100 
    bg-gray-50 border border-gray-300 text-gray-900 
    text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
    inline-flex w-[10rem] p-1 m-2 dark:bg-gray-700 dark:border-gray-600 
    dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
    dark:focus:border-blue-500"
    >
      <option value="null" selected>Migrer vers</option>
      {#each allTagsList as tag}
        <option value={`${tag.slug}`}>{tag.label}</option>
      {/each}
    </select>
  </td>
  <td>{tag.mwsTimeTags?.length}</td>
  <td>{tag.mwsTimeTags?.length}</td>
  <td>{tag.mwsTimeTags?.length}</td>
  <td>{dayjs(tag.createdAt).format("YYYY/MM/DD h:mm")}</td>
</tr>
