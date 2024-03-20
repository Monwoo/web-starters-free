<script context="module">
  // TODO : remove code duplications, inside services ?
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  dayjs.locale("fr"); // Fr locale
</script><script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { state, offerTagsByKey } from "../../../stores/reduxStorage.mjs";

  export let locale;
  export let viewTemplate;
  export let tag;
</script>
<!-- // TIPS : is not categorySlug, this status is a category... -->
<tr
  class:bg-blue-100={!tag.categorySlug}
>
  <td>
    <a href={ Routing.generate('mws_offer_tag_edit', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
      'slug': tag.slug,
      'categorySlug': tag.categorySlug,
    }) }>
      <button class="btn btn-outline-primary p-1 m-1">Editer</button>
    </a>
  </td>
  <th scope="row" class="text-left">
    <span>{tag.slug}</span>
    <!-- { JSON.stringify(tag.categoryOkWithMultiplesTags) } -->
    <span
      class:hidden={!tag.categoryOkWithMultiplesTags}
    > [MultiOk]</span>
  </th>
  <td>
    <span class="p-2 rounded"
    style:color={tag.textColor||"black"}
    style:background-color={tag.bgColor||"lightgrey"}
    >
      {tag.label ?? ''}
    </span>
  </td>
  <td>
    <span
      style:color={(offerTagsByKey($state, tag.categorySlug)?.textColor)||"black"}
      style:background-color={(offerTagsByKey($state, tag.categorySlug)?.bgColor)||"lightgrey"}
    >{tag.categorySlug ?? ''}</span>
  </td>
  <td>{dayjs(tag.createdAt).format('YYYY/MM/DD h:mm')}</td>
</tr>
