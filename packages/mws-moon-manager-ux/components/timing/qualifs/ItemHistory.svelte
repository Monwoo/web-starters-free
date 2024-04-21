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
  export let isLoading = false;
  let cssClass;
  export { cssClass as class };

  onMount(() => {
  });
</script>

<div class="w-full flex flex-wrap justify-center
items-center
text-xs md:text-base">
  <button
    class="m-1 w-full mx-2 whitespace-nowrap overflow-hidden text-ellipsis
    flex justify-center items-center pl-1 pr-1
    "
    on:click|stopPropagation={debounce(
      async () => await history.replay(timingSlot), userDelay
    )}
  >
    <span class="mws-drop-shadow">
      {history.label}
      <!-- <HtmlIcon {history}></HtmlIcon> -->
    </span>
  </button>

  <Loader {isLoading} />
</div>
