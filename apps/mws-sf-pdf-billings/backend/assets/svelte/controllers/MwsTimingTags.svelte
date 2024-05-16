<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // TODO : keep in MoonManager bundle :
  // "entrypoints": [ // No effect, building and using build output for the root one ?
  //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
  // ]

  import List from "mws-moon-manager-ux/components/timing/tags/List.svelte";

  export let locale;
  export let tags = '[]';
  export let viewTemplate;

  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }

  tags = JSON.parse(decodeHtml(tags));
</script>

<!-- <List {...$$props}></List> -->
<List {tags}
{locale} {viewTemplate}
></List>
