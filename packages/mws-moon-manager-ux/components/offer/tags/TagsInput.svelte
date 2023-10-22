<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";

  export let tagSlugs;
</script>

{#each (tagSlugs ?? []) as tSlug}
  {tSlug.label}
{/each}
