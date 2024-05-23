<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // TODO : namespace
  import ListItem from "./ListItem.svelte";
  import AddModal from "../../message/AddModal.svelte";
  import { MoveIcon, SortableItem } from "svelte-sortable-items";
  // TODO : use svelte-dnd-action instead of "svelte-sortable-items" limited to one list ?
  // https://dev.to/isaachagoel/drag-and-drop-with-svelte-using-svelte-dnd-action-4554
  // https://svelte.dev/repl/077cf720e2a6439caca5fb00d92d58a8?version=3.22.3
  import newUniqueId from "locally-unique-id-generator";
  import { flip } from "svelte/animate";

  export let locale;
  export let offers = [];
  export let messagesByProjectId = {};
  export let addMessageForm;
  export let offersHeaders = {}; // injected raw html
  export let viewTemplate;
  export let yScrollable;
  export let reportScale = 100;
  export let addModal;
  export let isMobile;
  export let isWide;

  let numberHoveredItem;

</script>

<AddModal bind:this={addModal} {addMessageForm} />

TODO : columns with drag and drop {numberHoveredItem}
<div class="w-full flex flex-wrap">
  {#each [{id:'TODO 1'}, {id:'TODO 2'}] as column, idx (column.id)}
    <div animate:flip class="p-0 w-6">
      {#each [{id:'TODO 1'}, {id:'TODO 2'}] as offer, idx (offer.id)}
        <!-- TODO : bind:propData={offer} fail on wird error ypeError: Cannot read properties of undefined (reading '0') in compiled js catch block...  -->
        <SortableItem
        class="h-full w-full flex justify-center content-start"
        propItemNumber={idx}
        bind:propHoveredItemNumber={numberHoveredItem}
        >
          <span>TEST</span>
        </SortableItem>
      {/each}
    </div>
  {/each}
</div>