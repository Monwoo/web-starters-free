<svelte:options customElement="s-mws-msg-template-choice-item" />

<script>
  import { onMount } from "svelte";
  // https://svelte.dev/docs/custom-elements-api
	export let name = 'world';
	export let question;
	export let item;
	// export let template; TODO : code some 'survey-svelte'
  // or do with 'survey-jquery' using 'knockout' template patterns...
  // RAW template export props is not enough to be a knockout template...
  //  bind:this={template} on html root element is not enough

  let templateHtmlView;
  item = (item && item != 'undefined') ? JSON.parse(decodeURIComponent(item)) : null;
  console.log('Custom view for : ', question, item);

  // TODO : below on:click is called when dropdown open itself too
  const reloadOffer = () => {
    // https://stackoverflow.com/questions/19669786/check-if-element-is-visible-in-dom
    // const isHidden = (templateHtmlView.offsetParent === null); // Work only if no fixed element in parents...
    const isHidden = !templateHtmlView
    || (window.getComputedStyle(templateHtmlView).display === 'none');
    if (!isHidden) {
      alert('Click visible choice');
    } else {
      console.debug("Reload invisible : ", templateHtmlView);
    }
  }
  onMount(async () => {
    console.debug("Having templateHtmlView : ", templateHtmlView);
  });
</script>

<div bind:this={templateHtmlView} class="sv-tagbox__item-text">
  <span class="sv-string-viewer" on:mousedown={reloadOffer()}>
    {#if item}
      {item?.templateCategorySlug} &gt; {item?.templateNameSlug}
    {:else}
      --
    {/if}
    <slot />
  </span>
</div>
