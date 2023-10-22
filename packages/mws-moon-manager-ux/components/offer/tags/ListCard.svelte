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

  export let tag;
</script>

<tr>
  <td>
    <a href="#see-card">
      <button class="btn btn-outline-primary p-1">Editer</button>
    </a>
  </td>
  <th scope="row">{tag.slug}</th>
  <td>{tag.label ?? ''}</td>
  <td>{tag.categorySlug ?? ''}</td>
  <td>{dayjs(tag.createdAt).format('YYYY/MM/DD h:mm')}</td>
</tr>
