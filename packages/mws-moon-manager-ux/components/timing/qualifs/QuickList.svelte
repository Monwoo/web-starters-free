<script context="module">
  // import "svelte-drag-drop-touch";
  // import DragDropTouch from 'svelte-drag-drop-touch'

</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  import { MoveIcon, SortableItem } from "svelte-sortable-items";
  import { flip } from "svelte/animate";
  import ItemView from "./ItemView.svelte";
  // import "svelte-drag-drop-touch/dist/svelte-drag-drop-touch";
  // require("svelte-drag-drop-touch");

  export let qualifTemplates;
  // // STATES
  // let arrayUsers = [
  //   { id: 1, name: "John", age: 45 },
  //   { id: 2, name: "Mark", age: 33 },
  //   { id: 3, name: "Jonas", age: 56 },
  //   { id: 4, name: "Mary", age: 76 },
  // ];
  let numberHoveredItem;
  /////
  export let itemWidth = "w-3/12";
  export let maxLimit = 7;

  $: {
    console.debug("Qualif templates update :", qualifTemplates);
  }

</script>

<!-- <svelte:head>
  <!-- MAKE IT WORK ON MOBILE DEVICES -- >
  <script src="https://unpkg.com/svelte-drag-drop-touch"></script>
  <!---- >
</svelte:head> -->

<div class="float-right w-full flex flex-row flex-wrap">
  <div class="flex w-full flex-wrap justify-evenly">
    <!-- {#each arrayUsers as currentUser, numberCounter (currentUser.id)} -->
    {#each qualifTemplates as qualif, numberCounter (qualif.id)}
      <div animate:flip class="p-0 grow {itemWidth}">
        <SortableItem
          class="h-full w-full flex justify-center content-start"
          propItemNumber={numberCounter}
          bind:propData={qualifTemplates}
          bind:propHoveredItemNumber={numberHoveredItem}
        >
          <div
            _data-tooltip-target="tooltip-hover-{numberCounter}"
            _data-tooltip-trigger="hover"
            class="flex flex-row flex-wrap h-full w-full
            justify-center content-start align-middle hover:cursor-move"
            class:classHovered={numberHoveredItem === numberCounter}
          >
            <ItemView bind:qualif />
          </div>
        </SortableItem>
        <!-- <div // TODO : strange pop-over showing and following move animation... 
          + shoud change message when hovering tag toggle button 
          or shortcut edit or listags edits ?
          
          id="tooltip-hover-{numberCounter}"
          role="tooltip"
          class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700"
        >
          <p class="bg-slate-700 fill-blue-400 flex">
            Utiliser <MoveIcon propSize={12} /> pour déplacer la qualif {qualif.label}.
          </p>
          <div class="tooltip-arrow" data-popper-arrow />
        </div> -->
      </div>
    {/each}
  </div>
</div>

<style>
  .classHovered {
    background-color: lightblue;
    color: white;
  }

</style>
