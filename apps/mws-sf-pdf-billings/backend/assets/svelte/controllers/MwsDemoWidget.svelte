<script>
  import MwsDemoWidget from "mws-demo/assets/svelte/controllers/MwsDemoWidget.svelte";
  export let locale;
  export let pageViews = 0;
</script>

<MwsDemoWidget {locale} {pageViews} />
