<script>
import { onMount } from 'svelte';


  // import MwsDemoWidget from "mws-demo/assets/svelte/controllers/MwsDemoWidget.svelte";
  export let locale;
  export let pageViews = 0;

  let MwsDemoWidget;
  onMount(async () => {
    // https://stackoverflow.com/questions/63859576/sapper-svelte-possible-to-conditionally-import-components
    // TODO : from .env ?
    console.log("Mws demo : ", process.env.HAVE_MWS_DEMO);
    if (process.env.HAVE_MWS_DEMO ?? false) {
    // if (false) {
      MwsDemoWidget = (await import('mws-demo/assets/svelte/controllers/MwsDemoWidget.svelte').catch((r) => {
        console.warn('ERROR', r);
        return null;
      }))?.default ?? null;
      // MwsDemoWidget = require('mws-demo/assets/svelte/controllers/MwsDemoWidget.svelte').default ?? null;
    }
  });

</script>

<!-- <MwsDemoWidget {locale} {pageViews} /> -->
<svelte:component this={MwsDemoWidget} {locale} {pageViews}>
  <p>some slotted content</p>
</svelte:component>