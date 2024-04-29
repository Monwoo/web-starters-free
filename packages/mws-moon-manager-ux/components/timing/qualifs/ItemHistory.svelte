<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  import _ from "lodash";
  import Routing from "fos-router";
  import Loader from "../../layout/widgets/Loader.svelte";
  import newUniqueId from "locally-unique-id-generator";
  import debounce from 'lodash/debounce';
import { onMount } from "svelte";
  const UID = newUniqueId();

  export let userDelay = 300;
  export let locale;
  export let history;
  export let timingSlot;
  export let lastSelectedIndex;
  export let timings;
  export let selectionStartIndex;
  export let isLoading;
  let cssClass;
  export { cssClass as class };

  onMount(() => {
  });
</script>
<!-- // TODO : cross to remove some elements from history
// + drag and drop re-order feature... -->
<div class="w-full flex flex-wrap justify-center
items-center
text-xs md:text-base">
  <button
    class="m-1 w-full mx-2 whitespace-nowrap overflow-hidden text-ellipsis
    flex justify-center items-center pl-1 pr-1
    "
    on:click|stopPropagation={debounce(
      async () => {
        isLoading = true;
        await history.replay(
          timingSlot, lastSelectedIndex, timings, selectionStartIndex
        );
        // lastSelectedIndex = lastSelectedIndex; // Force svelte reactivity, not working with number ?
        timingSlot = timingSlot; // Force svelte reactivity, OK
        isLoading = false;
      }, userDelay
    )}
  >
    <span class="mws-drop-shadow">
      {history.label}
      <!-- <HtmlIcon {history}></HtmlIcon> -->
    </span>
  </button>

</div>
