<svelte:options customElement="s-mws-msg-template-choice-item" />

<script>
  // https://svelte.dev/docs/custom-elements-api
	export let name = 'world';
	export let question;
	export let item;

  item = (item && item != 'undefined') ? JSON.parse(decodeURIComponent(item)) : null;
  console.log('Custom view for : ', question, item);

  // TODO : below on:click is called when dropdown open itself too
  
</script>
<div class="sv-tagbox__item-text">
  <span class="sv-string-viewer" on:click={alert('test')}>
    {#if item}
      {item?.templateCategorySlug} &gt; {item?.templateNameSlug}
    {:else}
      --
    {/if}
    <slot />
  </span>
</div>
