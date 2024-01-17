<svelte:options customElement="s-mws-msg-template-choice-item" accessors />

<script>
  import { onMount } from "svelte";
  import { createEventDispatcher } from 'svelte';
  import { get_current_component } from 'svelte/internal';

  // https://svelte.dev/docs/custom-elements-api
	export let name = 'world';
	export let question;
	export let item;

  // https://github.com/sveltejs/svelte/issues/3119#issuecomment-658671940
  const svelteDispatch = createEventDispatcher();
  const component = get_current_component();
  const dispatch = (name, detail) => {
    // TIPS : component is a SVELTE component
    // component?.dispatchEvent(new CustomEvent(name, {
    // dispatchEvent(new CustomEvent(name, {
    liveTemplateHtmlView?.dispatchEvent(new CustomEvent(name, {
      detail,
      bubbles: true,
      cancelable: true,
      composed: true // makes the event jump shadow DOM boundary, needed to go above web-component build
    }));
    // svelteDispatch(name, detail);
  };

	// export let template;
  //  TODO : code some 'survey-svelte'
  // or do with 'survey-jquery' using 'knockout' template patterns...
  // RAW template export props is not enough to be a knockout template...
  //  bind:this={template} on html root element is not enough

  let liveTemplateHtmlView;
  let templateHtmlView;
  // $: templateHtmlView = liveTemplateHtmlView ?? templateHtmlView;
  // $: templateHtmlView = liveTemplateHtmlView ? liveTemplateHtmlView : templateHtmlView;

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
      console.debug("Reload ignore invisible : ", templateHtmlView);
    }
  }

  onMount(async () => {
    templateHtmlView = liveTemplateHtmlView ? liveTemplateHtmlView : templateHtmlView;
    console.debug("Having templateHtmlView : ", templateHtmlView);

    // TIPS : Get real object references using Events instead of
    //        serialized data from web-component attributes
    // // The web-component methode ? already defined...
    // dispatchEvent('mws-msg-template-choice-item-ready', {
    dispatch('mws-msg-template-choice-item-ready', {
      detail: {
        bindQuestion: (q) => question = q
      },
    });
  });
</script>

<div bind:this={liveTemplateHtmlView} class="sv-tagbox__item-text">
  <!-- // TIPS : will be called multiple time even if click on parent ...
  // reloadOffer() should work on value change instead... -->
  <span class="sv-string-viewer" on:mousedown={
    () => (null)
  }>
    {#if item}
      {item?.templateCategorySlug} &gt; {item?.templateNameSlug}
    {:else}
      --
    {/if}
    <slot />
  </span>
</div>
