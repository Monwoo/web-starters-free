<script context="module">
  // import "svelte-drag-drop-touch";
  // import DragDropTouch from 'svelte-drag-drop-touch'

</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  import { MoveIcon, SortableItem } from "svelte-sortable-items";
  import { flip } from "svelte/animate";
  // import "svelte-drag-drop-touch/dist/svelte-drag-drop-touch";
  // require("svelte-drag-drop-touch");

  // STATES
  let arrayUsers = [
    { id: 1, name: "John", age: 45 },
    { id: 2, name: "Mark", age: 33 },
    { id: 3, name: "Jonas", age: 56 },
    { id: 4, name: "Mary", age: 76 },
  ];
  let numberHoveredItem;
  /////

</script>

<!-- <svelte:head>
  <!-- MAKE IT WORK ON MOBILE DEVICES -- >
  <script src="https://unpkg.com/svelte-drag-drop-touch"></script>
  <!---- >
</svelte:head> -->

<div class="float-right w-full flex flex-row flex-wrap">
  <div class="flex w-full flex-wrap justify-evenly">
    {#each arrayUsers as currentUser, numberCounter (currentUser.id)}
      <div animate:flip class='grow'>
        <SortableItem
          propItemNumber={numberCounter}
          bind:propData={arrayUsers}
          bind:propHoveredItemNumber={numberHoveredItem}
        >
          <div
          class="flex flex-wrap justify-evenly"
          class:classHovered={numberHoveredItem === numberCounter}>
            <MoveIcon propSize={12} />
            {currentUser.name}
          </div>
        </SortableItem>
      </div>
    {/each}
  </div>
  <p class='bg-slate-700 fill-blue-400'>MOVE THE <MoveIcon propSize={12} /> ICON TO REORDER ELEMENTS.</p>
</div>

<style>
  .classHovered {
    background-color: lightblue;
    color: white;
  }

</style>
