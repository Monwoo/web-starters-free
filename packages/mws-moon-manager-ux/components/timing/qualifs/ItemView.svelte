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
  import ColorPicker, { ChromeVariant, A11yVariant,  } from 'svelte-awesome-color-picker';
  import newUniqueId from "locally-unique-id-generator";
import { onMount, tick } from "svelte";
  // import { copy } from 'svelte-copy';// TODO : same issue as for svelte-flowbite : fail copy.d.t autoload
  const UID = newUniqueId();

  export let qualif;
  export let qualifLookups;
  export let selectedTarget;
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
  export let quickQualifTemplates;
  export let maxItemsLimit;
	let copyBuffer;
  let typeahead;
  let confirmInProgress = false;

  // TIPS : not on data change, only for init
  // $: typeAheadValue = qualif.label;
  // typeAheadValue = qualif.label;
  // typeahead.close(), // only in onMount... bind:this=t...

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
    if (label && label.length
    && qualif.label != label) {
      confirmUpdateOrNew.srcQualif = qualif;
      confirmUpdateOrNew.newName = label;
      confirmUpdateOrNew.eltModal.show();
      return true;
    }
    // TODO : wait for response ?
    return false;
  };

  $: rgbTxt = rgb
  ? `${rgb.r}, ${rgb.g}, ${rgb.b}, ${rgb.a}`
  : null;
  $: hexTxt = hex;

  let copyStatus = 'Copiez';

  typeAheadValue = qualif.label;

  // let needClose = true;
  // $: {
  //   if (expandEdit && needClose && typeahead) {
  //     // typeahead.close(); // only in onMount... bind:this=t...
  //     needClose = true;
  //   }
  // }

  onMount(() => {
  });

  const syncQualifWithBackend = () => {
    console.log("TODO sync", qualif);
  }
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
      class="mws-timing-qualif-view flex flex-wrap border border-slate-700
      rounded-es-lg rounded-se-lg bg-white text-slate-700 fill-slate-700
        cursor-pointer justify-center
        {cssClass ?? ''}"
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
      isDialog={false}
      sliderDirection="horizontal"
      --picker-height="100px"
      --picker-width="100%"
      --slider-width="25px"
      --picker-indicator-size="25px"
      --picker-z-index="10"
      --input-size="100px"
    /> -->
    <span class="p-2">
      <ColorPicker
        bind:rgb
        bind:hex
        --focus-color="green"
        label="Couleur"
        sliderDirection="horizontal"
        on:input={async (event) => {
          // https://svelte-awesome-color-picker.vercel.app/#bind-event-oninput
          // historyHex = [...historyHex, event.detail.hex];
          console.debug('TODO : sync new color', hex, rgb, ' for id : ', qualif.id);
          qualif.primaryColorHex = hex;
          qualif.primaryColorRgb = rgb;
          await syncQualifWithBackend();
        }}
      />
    </span>
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
    class="hover:bg-slate-400 font-light p-1 m-2"
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
    class="hover:bg-slate-400 font-light text-sm m-2"
    style="--mws-primary-rgb: 0, 200, 0"
    >
      Raccourci [{String.fromCharCode(qualif.shortcut)}]
    </button>
    <!--
      on:keydown|stopPropagation|preventDefault
        showDropdownOnFocus={null}
        showAllResultsOnFocus={null}
        focusAfterSelect={null}
        autoselect={false}
        focusAfterSelect={false}
    -->
      <Typeahead
      bind:this={typeahead}
      label="Qualification"
        autoselect={false}
        focusAfterSelect={true}
        showDropdownOnFocus={true}
        showAllResultsOnFocus={true}
        bind:value={typeAheadValue}
        {data}
        {extract}
        let:result
        let:index
        on:focus={()=>{
          console.log('Type ahead focus for', qualif);
        }}
        on:blur={async (e)=>{
          if ((e.target.value === selectedTarget?.label)
          || selectedTarget) {
            // update from JS side, not a new entry
            // selectedTarget = null ?
            return;
          }
          // TRICKY wait since select trigged come before or after this one
          // With timeout, next line (after await) is done AFTER select updates
          await new Promise(resolve => setTimeout(resolve, 400));
          console.log('Blur Should close dropdown after select or handle new', e);
          if (isLoading) return;
          if(selectedTarget) {
            // was pre-processed by on:select, clean up :
            selectedTarget = null;
            isLoading = false
            return; // keep select box flow
          }
          isLoading = true;
          const qualifLbl = e.target.value;
          const lastQualif = typeAheadDetails?.original;
          console.log(qualifLbl);
          if (qualifLbl && qualifLbl === lastQualif?.label) {
            console.warn('should not happen, catch by on:select ok ? or user rewrite exact same name ?');
          } else {
            // console.log('TODO : Create new qualif item with this unused label');
            confirmInProgress = openConfirmUpdateOrNew(qualifLbl);
            if (confirmInProgress) {
              // NOP : below not accruate, qualif update might comme with user interaction, reactive power ok for refresh ?
              // typeAheadValue = ''; // Clean used selection
              // typeAheadDetails = null;
            }
          }
          // console.log('Form resp', e.target.form.dataset); // Nop, too late ? use typeAheadValue instead ? form is not for api usage ?

          // await new Promise(resolve => setTimeout(resolve, 200)).then(()=>{
          //   // asyn unload to see animation.AddModal..
          //   isLoading = false;
          // });
          console.log('Type ahead onBlur cleanup for', qualif);

          if (!confirmInProgress) {
            // TIPS : ensure typeahead get closed
            // typeahead.results = []; // nice try, but not enough...
            typeAheadValue = qualif.label; // Clean used selection
          } else {
            // TODO : ok to reset from component ? not the full window open wait, only to check values updates ?
            confirmInProgress = false;
          }
          // typeahead.close(); // not public...
          // cf : https://github.com/metonym/svelte-typeahead/blob/master/src/Typeahead.svelte#L142
          // typeahead.hideDropdown = true; // NOP : private props, will juste add new not usefull props :
          // typeahead.isFocused = false;
          // cf https://github.com/metonym/svelte-typeahead/blob/master/src/Typeahead.svelte#L170
          // Will be closed on any click OUTSIDE of container (container is wrongly configured to tail ?)

          await tick(); await tick(); await tick(); await tick(); 
          await tick(); // Wait for value set of 'typeAheadValue = qualif.label;' to propagate
          document.body.click(); // OK, but will close ALL menus, with timeout, strange if we switch from one input to other too fast (direct click for example)
          // await tick(); // Wait for onClick to propagate ? no effect when modal is shown ?

          isLoading = false;
        }}
        on:select={async (e)=>{
          if (isLoading) return;
          isLoading = true;
          console.log('Did select', e);
          typeAheadDetails = e.detail;
          selectedTarget = typeAheadDetails.original;
          const src = qualif;
          // qualif = typeAheadDetails.original;
          // const srcIdx = quickQualifTemplates.indexOf(src);
          let targetIdx = -1;
          let srcIdx = -1;
          for (let idx = 0; idx < quickQualifTemplates.length; idx++) {
            const qQualif = quickQualifTemplates[idx];
            if (qQualif.label === selectedTarget.label) {
              srcIdx = idx;
            }
            if (qQualif.label === qualif.label) {
              targetIdx = idx;
            }
            if (srcIdx >= 0 && targetIdx >= 0) {
              break;
            }
          }
          // const targetIdx = quickQualifTemplates.indexOf(selectedTarget);
          console.log('Switch qualif ', // targetIdx,
          ' just before the selected one at ', targetIdx, "for", selectedTarget);
          if(srcIdx >= 0) {
            // delete quickQualifTemplates[srcIdx];
          }
          // quickQualifTemplates = quickQualifTemplates.slice(0, targetIdx > 1 ? targetIdx - 1 : 0)
          quickQualifTemplates = quickQualifTemplates.slice(0, targetIdx > 0 ? targetIdx : 0)
          .concat([selectedTarget], quickQualifTemplates.slice(targetIdx)).slice(0, maxItemsLimit)

          // WARNING : below will trigger a did changed
          // so check against typeAheadValue !== qualif.label only
          typeAheadValue = qualif.label; // Clean used selection
          // typeahead.value = qualif.label;
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
          // if (e.target.value === selectedTarget?.label) {
          //   // update from JS side, no a new entry
          //   return;
          // }
          // // TRICKY wait since select trigged come before or after this one
          // // With timeout, nextline is done AFTER select updates
          // await new Promise(resolve => setTimeout(resolve, 400));
          // console.log('Blur Should have close dropdown', e);
          // if (isLoading) return;
          // isLoading = true;
          // console.log('Did change', e);
          // const qualifLbl = e.target.value;
          // const lastQualif = typeAheadDetails?.original;
          // console.log(qualifLbl);
          // if (qualifLbl && qualifLbl === lastQualif?.label) {
          //   console.warn('should not happen, catch by on:select ok ?');
          // } else {
          //   typeAheadDetails = null;
          //   // console.log('TODO : Create new qualif item with this unused label');
          //   if (await openConfirmUpdateOrNew(qualifLbl)) {
          //     typeAheadValue = ''; // Clean used selection
          //   }
          // }
          // console.log('Form resp', e.target.form.dataset);
          // // await new Promise(resolve => setTimeout(resolve, 200)).then(()=>{
          // //   // asyn unload to see animation.AddModal..
          // //   isLoading = false;
          // // });
          // isLoading = false;
          // typeahead.results = []; // TIPS : ensure typeahead get closed
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
          name="newTag"
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
    @apply w-full;
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