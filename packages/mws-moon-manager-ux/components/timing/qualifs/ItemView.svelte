<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import { slide } from "svelte/transition";
  import { quintOut, quintIn } from "svelte/easing";
  import { fly } from "svelte/transition";
  import Typeahead from "svelte-typeahead";
import Loader from "../../layout/widgets/Loader.svelte";
import AddModal from "../tags/AddModal.svelte";

  export let qualif;
  export let qualifLookups;
  export let expandEdit = false;
  let cssClass;
  export { cssClass as class };
  export let typeAheadDetails;
  export let confirmUpdateOrNew;
  export let isLoading = false;

  console.debug("qualif Item view ", qualif);
  console.debug("Type ahead", qualifLookups);

  const collator = new Intl.Collator(undefined, {
    numeric: true,
    sensitivity: "base",
  });
  qualifLookups.sort(function (a, b) {
    return collator.compare(a.label, b.label);
  });

  export const data = qualifLookups;

  export const extract = (item) => item.label;

  export const sendConfirmUpdateOrNew = async (label) => {
    if (label && label.length) {
      confirmUpdateOrNew.eltModal.show();
    }
  };

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
        on:keydown={(e)=>{
          e.stopPropagation();
          // e.preventDefault();
        }}
>
    <!-- on:keydown|stopPropagation|preventDefault -->
      <Typeahead
        label="Libellé de qualification"
        showDropdownOnFocus
        showAllResultsOnFocus
        focusAfterSelect
        {data}
        {extract}
        let:result
        let:index
        on:select={async (e)=>{
          if (isLoading) return;
          isLoading = true;
          console.log('Did select', e);
          typeAheadDetails = e.detail;
          console.log('TODO just switch this qualif with selected one');
          qualif = typeAheadDetails.original;
          isLoading = false;
        }}
        on:clear={async (e)=>{
          if (isLoading) return;
          isLoading = true;
          console.log('Did clear', e);
          typeAheadDetails = null;
          isLoading = false;
        }}
        on:change={async (e)=>{
          if (isLoading) return;
          isLoading = true;
          console.log('Did change', e);
          const qualifLbl = e.target.value;
          const lastQualif = typeAheadDetails?.original;
          console.log(qualifLbl);
          if (qualifLbl && qualifLbl === lastQualif?.label) {
            console.warn('should not happen, catch by on:select ok ?');
          } else {
            typeAheadDetails = null;
            // console.log('TODO : Create new qualif item with this unused label');
            await sendConfirmUpdateOrNew(qualifLbl);
          }
          console.log('Form resp', e.target.form.dataset);
          await new Promise(resolve => setTimeout(resolve, 200)).then(()=>{
            // asyn unload to see animation.AddModal..
            isLoading = false;
          });
        }}
      >
        {@const qualif = result.original}
        <!-- {@const qualif = JSON.parse(result)} -->
        <!-- <strong>{@html JSON.stringify(result)}</strong>
        <strong>{@html qualif.label}</strong> -->
        <strong>{@html result.string}</strong>
        <!-- {qualif?.timeTags?.length} -->
      </Typeahead>
      <div>
        {#each qualif?.timeTags ?? [] as tag, tagSlug (tag.slug)}
          <!-- {tagSlug} -->
          {tag.label}
        {/each}
      </div>
    </div>
  {/if}

  <Loader {isLoading} />
</div>
<style>
  :global([data-svelte-typeahead]) {
    margin: 1rem;
  }
</style>