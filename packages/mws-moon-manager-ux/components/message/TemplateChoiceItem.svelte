<svelte:options customElement="s-mws-msg-template-choice-item" accessors />

<script lang="ts">
  import { onMount } from "svelte";
  import { createEventDispatcher } from 'svelte';
  import { get_current_component } from 'svelte/internal';

  // https://svelte.dev/docs/custom-elements-api
	export let name = 'world';
	export let question;
	export let item;
	let itemData;

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

  itemData = (item && item != 'undefined') ? JSON.parse(decodeURIComponent(item)) : null;
  console.log('Custom view INIT : ', question, itemData);

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
      bindQuestion: (q, item) => {
        // TODO : question?.remove('change', () => { if already setup and in dispose...
        question = q;
        // question?.on('change', () => {
        //   alert('Reload modal survey data with template values...')
        // })
        console.log('Custom view for question : ', question);

        // https://surveyjs.answerdesk.io/ticket/details/t6188/adding-custom-event-validation-for-single-question
        // TODO : clean listener .remove ?
        question?.survey.onValueChanged.add(function(sender, options){
          console.log('Survey value did change in template choice from ', sender);
          // TODO : not really efficient having ALL choice item to listen to value changes, should be root question only once ?
          if((!!options.question) && options.question.validateOnValueChanged) {
              options.question.hasErrors(true);
          }
          // TIPS : check is object to avoid submit survey values with string instead of object
          if (question?.name == "templatePreload" &&  options.value?.destId) {
            console.log('Reload modal survey data with new template values...', options, question?.value);

            // function removeEmpty(obj) {
            //   return Object.fromEntries(
            //     Object.entries(obj)
            //       .filter(([_, v]) => v != null)
            //       .map(([k, v]) => [k, v === Object(v) ? removeEmpty(v) : v])
            //   );
            // } // TODO : remove empty recursively + loadash deep merge ?

            if (options.value.messages) {
              // tag incoming template message as 'can be send'
              options.value.messages = options.value.messages.map((tchat) => {
                tchat.haveBeenSent = false;
                return tchat;
              });
            }

            question.survey.data = {
              ...(question.survey.data ?? {}),
              // https://stackoverflow.com/questions/286141/remove-blank-attributes-from-an-object-in-javascript
              ...Object.fromEntries(Object.entries(
                options.value, // String value sometime ?
              ).filter(([_, v]) => v != null)),
              // will not update to null if null, sync behavior :
              // monwooAmount: options.value.monwooAmount ?? question.survey.data?.monwooAmount,

              // ...(item.jsonObj ?? {}),
              // templatePreload: options.value, // TODO : nop, not filling templatePreload with last selected value...
              // TIPS : ok not setting value, othewise, can't detect value change of specific question...
              // templatePreload: options.value ?? item.jsonObj, // TODO : nop, not filling templatePreload with last selected value...
              // id: null, // force new id creation ? NOP, based on projectId lookup...
              // Keep existing id fields
              id: question.survey.data?.id ?? null,
              destId: question.survey.data?.destId ?? null,
              sourceId: question.survey.data?.sourceId ?? null,
              projectId: question.survey.data?.projectId,
              isDraft: question.survey.data?.isDraft,
              isTemplate: question.survey.data?.isTemplate,
              templateNameSlug: question.survey.data?.templateNameSlug,
              templateCategorySlug : question.survey.data?.templateCategorySlug ,
            };
            console.log('New survey data :', question.survey.data);
          }
        });
      },
      fromValue: item,
    });
  });
</script>

<div bind:this={liveTemplateHtmlView} class="sv-tagbox__item-text mws-choice-item">
  <!-- // TIPS : will be called multiple time even if click on parent ...
  // reloadOffer() should work on value change instead... -->
  <span class="sv-string-viewer" on:click={
    () => (null)
  }>
    {#if itemData}
      {itemData.templateCategorySlug} &gt; {itemData.templateNameSlug}
    {:else}
      --
    {/if}
    <slot />
  </span>
  {#if itemData}
    <div class="mws-choice-detail">
      <div class="relative w-full flex flex-wrap justify-start items-start">
        <div>{itemData.templateNameSlug}</div>
        <div>Proposition : { itemData?.monwooAmount ?? '' } { itemData?.monwooAmountType ?? '' }</div>
        <div>Délais : { itemData?.projectDelayInOpenDays ?? '' } jour(s) ouvrés</div>
        {@html (itemData?.messages ?? []).reduce(
          // <pre>${msg.text}</pre>
          (html, msg) => `
              <div>${ msg.billingSourceFile ? msg.billingSourceFile[0]?.name : 'No billings'}</div>
              <div class="max-w-full">${msg.text?.replaceAll('\n', '<br/>')}</div>
            `
            + html,
          ``
        )}  
      </div>
    </div>
  {/if}
</div>

<style lang="scss">
  .mws-choice-item {
    position: relative;
    overflow: visible;
    @apply p-2;

    .mws-choice-detail {
      // position: fixed;
      // display: none;
      display: flex;
      opacity: 0;
      pointer-events: none;
      flex-direction: column;
      flex-wrap: wrap;
      font-size: 9pt;
      line-height: 9pt;
      // z-index: 2;
      // overflow: hidden;
      // word-wrap: break-word !important;
      // word-wrap: break-word !important;
      // word-wrap: break-word !important;

      // http://stylescss.free.fr/v2-properties/word-break-wrap.php
      // word-wrap: anywhere;
      // word-break: break-all;
      word-wrap: break-word;
      word-break: break-word;
      white-space: break-spaces;
      // @apply  whitespace-break-spaces;

      @apply p-6;
      @apply bg-gray-200;
      @apply text-black;
      @apply overflow-scroll; // No effect since no pointer events...
      @apply rounded-md;
      @apply space-y-6;
      @apply fixed;
      @apply z-50; // TODO hierarchy issue, do not go over detail view... => service to open menu detail popup instantiated in parent ?
      @apply right-[2dvh];
      @apply w-[30dvw];
      @apply h-[42dvh];
      @apply bottom-[2dvh];
      @apply justify-start;
      @apply items-start;

      // &:hover {
      //     opacity: 0.8;
      // }
    }
    &:hover {
      .mws-choice-detail {
        opacity: 0.9;
      }
    }
  }
</style>