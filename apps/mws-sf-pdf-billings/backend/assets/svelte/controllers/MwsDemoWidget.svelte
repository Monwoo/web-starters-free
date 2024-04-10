<script>
import { onMount } from 'svelte';


  // import MwsDemoWidget from "mws-demo/assets/svelte/controllers/MwsDemoWidget.svelte";
  export let locale;
  export let pageViews = 0;

  let MwsDemoWidget;
  onMount(async () => {
    MwsDemoWidget = (await import('mws-demo/assets/svelte/controllers/MwsDemoWidget.svelte')).default ?? null;
  });

</script>

<!-- <MwsDemoWidget {locale} {pageViews} /> -->
<svelte:component this={MwsDemoWidget} {locale} {pageViews}>
  <p>some slotted content</p>
</svelte:component>