<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import { slide } from "svelte/transition";
  import { quintOut, quintIn } from "svelte/easing";
  import { fly } from "svelte/transition";

  export let qualif;
  export let expandEdit = false;
  let cssClass;
  export { cssClass as class };

  console.debug("qualif Item view ", qualif);

</script>

<div class="w-full flex flex-wrap">
  <button
    class="m-1 w-full mx-2 whitespace-nowrap overflow-hidden text-ellipsis"
    on:click|stopPropagation={qualif.toggleQualif}
  >
    {qualif.label}
  </button>
  <!-- <MoveIcon propSize={12} /> -->
  <did
    on:click|stopPropagation={() => (expandEdit = !expandEdit)}
    class="w-full text-center border border-purple-700 m-1 cursor-pointer"
  >
    <span class="text-slate-400">
      [{String.fromCharCode(qualif.shortcut)}]
    </span>
    {qualif.label}
  </did>
  {#if expandEdit}
    <div
      class="mws-timing-qualif-view flex flex-wrap p-1 m-1
        rounded-lg bg-white text-slate-700 fill-slate-700
        cursor-pointer
        {cssClass}"
      in:slide={{
        delay: 0,
        duration: 200,
        easing: quintIn,
        axis: "y",
      }}
      out:slide={{
        delay: 0,
        duration: 200,
        easing: quintOut,
        axis: "y",
      }}
    >
      <div>
        {#each qualif?.timeTags ?? [] as tag, tagSlug (tag.slug)}
          <!-- {tagSlug} -->
          {tag.label}
        {/each}
      </div>
    </div>
  {/if}
</div>
