<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  export let qualif;
  let cssClass;
  export {cssClass as class};

  console.debug("qualif Item view ", qualif);
</script>
<div
class="mws-timing-qualif-view flex flex-wrap p-1 m-1
rounded-lg bg-white text-slate-700 fill-slate-700
cursor-pointer
{cssClass}"
>
  <div>
    {qualif?.label ?? '--'}
  </div>
  <div>
    {#each qualif?.tags ?? [] as tag, tagSlug (tag.slug)}
      {tagSlug}
    {/each}
  </div>
</div>