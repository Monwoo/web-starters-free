<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import { slide } from "svelte/transition";
  import { quintOut, quintIn } from "svelte/easing";
  import { fly } from "svelte/transition";
  import Typeahead from "svelte-typeahead";
  import Loader from "../../layout/widgets/Loader.svelte";
  import AddModal from "../tags/AddModal.svelte";
  import TagsInput from "./TagsInput.svelte";
  import Base from "../../layout/Base.svelte";
  import ColorPicker from 'svelte-awesome-color-picker';
  import newUniqueId from "locally-unique-id-generator";
  // import { copy } from 'svelte-copy';// TODO : same issue as for svelte-flowbite : fail copy.d.t autoload
  const UID = newUniqueId();

  export let qualif;
  export let qualifLookups;
  export let allTagsList;
  export let expandEdit = false;
  let cssClass;
  export { cssClass as class };
  export let typeAheadDetails;
  export let confirmUpdateOrNew;
  export let isLoading = false;
  export let typeAheadValue;
  export let newTagLabel;
  export let rgb;
  export let hex = "#AAAAAA";
  export let itemSuggestionTagsId = `ItemViewTags-${UID}`;
	let copyBuffer;

  // TIPS : not on data change, only for init
  // $: typeAheadValue = qualif.label;
  typeAheadValue = qualif.label;
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

  export const openConfirmUpdateOrNew = async (label) => {
    if (label && label.length) {
      confirmUpdateOrNew.eltModal.show();
    }
    // TODO : wait for response ?
    return true;
  };

  $: rgbTxt = rgb
  ? `${rgb.r}, ${rgb.g}, ${rgb.b}, ${rgb.a}`
  : null;
  $: hexTxt = hex;

  let copyStatus = 'Copiez';
</script>

<div class="w-full flex flex-wrap justify-center">
  <button
    class="m-1 w-full mx-2 whitespace-nowrap overflow-hidden text-ellipsis
    "
    style={rgbTxt ? `--mws-primary-rgb: ${rgbTxt}`: ``}
    on:click|stopPropagation={qualif.toggleQualif}
  >
    <!--
      TODO : tailwind multiple text shadow ?
      https://stackoverflow.com/questions/34931463/how-to-make-multiple-drop-shadow 
      <span class="drop-shadow-[0_1.2px_1.2px_rgba(0,0,0,0.8) 0_-1.2px_1.2px_rgba(0,0,0,0.8)]"> -->
    <span class="mws-drop-shadow">
      {qualif.label}
    </span>
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
        cursor-pointer justify-center
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
    <!-- <ColorPicker
      bind:hex
      bind:rgb
      bind:hsv
      bind:color
    /> -->
    <ColorPicker
      bind:rgb
      bind:hex
    />
    <!-- <button use:copy={'Hello World'}
    style="--mws-primary-rgb: {rgb}"
    >
      {rgb}
    </button> -->
    <button on:click={async ()=>{
      try {
        // https://sentry.io/answers/how-do-i-copy-to-the-clipboard-in-javascript/
        if (navigator?.clipboard?.writeText) {
          await navigator.clipboard.writeText(hexTxt);
          copyStatus = 'OK :';
        }
      } catch (err) {
        console.error(err);
        copyBuffer.select();
        const successCopy = document.execCommand('copy');
        if(successCopy) {
          copyStatus = 'Ok :';
        } else {
          copyStatus = 'Fail :';
        }
      }
      await new Promise(resolve => setTimeout(resolve, 800)).then(()=>{
        // asyn unload to see animation.AddModal..
        copyStatus = 'Copier';
      });
    }}
    class="hover:bg-slate-400 font-light"
    style="--mws-primary-rgb: {rgbTxt}"
    >
      <span class="text-sm mws-drop-shadow">
        {copyStatus} {hexTxt}
      </span>
    </button>
    <textarea
    class="absolute pointer-events-none opacity-0"
     bind:value={hexTxt} bind:this={copyBuffer}></textarea>

     <button on:click={async ()=>{
    }}
    class="hover:bg-slate-400 font-light text-sm mt-2"
    style="--mws-primary-rgb: 0, 200, 0"
    >
      Raccourci [{String.fromCharCode(qualif.shortcut)}]
    </button>
    <!-- on:keydown|stopPropagation|preventDefault -->
      <Typeahead
        label="Qualification"
        showDropdownOnFocus
        showAllResultsOnFocus
        focusAfterSelect
        bind:value={typeAheadValue}
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
          typeAheadValue = ''; // Clean used selection
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
            if (await openConfirmUpdateOrNew(qualifLbl)) {
              typeAheadValue = ''; // Clean used selection
            }
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
      <!-- <div>
        {#each qualif?.timeTags ?? [] as tag, tagSlug (tag.slug)}
          <!-- {tagSlug} -- >
          {tag.label}
        {/each}
      </div> -->
      <TagsInput
      {qualif} bind:tags={qualif.timeTags} {allTagsList} />
      <span class="m-3">
        <input
          class="text-black opacity-30 hover:opacity-100 w-full"
          bind:value={newTagLabel}
          type="text"
          placeholder="Nouveau Tag"
          name="maxLimit"
          on:change={() => {
            // Since $: reactivity might be overloaded
            console.debug('Add tag to qualif', newTagLabel);
          }}
          list={itemSuggestionTagsId}
        />
        <datalist id={itemSuggestionTagsId}>
          {#each allTagsList as tag}
            <option value={tag.label} />
          {/each}
        </datalist>        
        {#if newTagLabel?.length}
          <button
            class="p-2 m-3 text-sm font-medium text-center 
            text-white bg-green-700 rounded-lg hover:bg-red-700 
            focus:ring-4 focus:outline-none focus:ring-red-300
            dark:bg-red-500 dark:hover:bg-red-600 
            dark:focus:ring-red-900"
            style="--mws-primary-rgb: 0, 142, 0"
          >
            Créer le Tag
          </button>
        {/if}
      </span>  

      <button
      class="p-2 m-3 text-sm font-medium text-center 
      text-white bg-red-600 rounded-lg hover:bg-red-700 
      focus:ring-4 focus:outline-none focus:ring-red-300
      dark:bg-red-500 dark:hover:bg-red-600 
      dark:focus:ring-red-900"
      style="--mws-primary-rgb: 255, 0, 0"
    >
      Supprimer '{qualif?.label ?? ""}'
    </button>

    </div>
  {/if}

  <Loader {isLoading} />
</div>
<style>
  :global([data-svelte-typeahead]) {
    margin: 1rem;
  }
  :global(.mws-drop-shadow) {
    /* filter: drop-shadow(3px 3px 5px #000000) drop-shadow(2px 2px 2px #ffcc00); */
    filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.5))
    drop-shadow(1px -1px 2px rgba(0,0,0,0.5))
    drop-shadow(-1px 1px 2px rgba(0,0,0,0.5))
    drop-shadow(-1px -1px 2px rgba(0,0,0,0.5))
    ;
  }
</style>