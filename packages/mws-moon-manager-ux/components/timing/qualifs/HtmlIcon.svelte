<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import newUniqueId from "locally-unique-id-generator";
  import sanitizeHtml from 'sanitize-html';
  const UID = newUniqueId();
  export let tooltipId = `htmlIconTooltip-${UID}`;

  let cssClass = "text-black";
  export { cssClass as class };
  // export let height = 'max-h-[1.5rem]';
  // export let width = 'max-w-[1.5rem]';
  export let height = 'h-[1.5rem]';
  export let width = 'w-[1.5rem]';
  // TODO : allow one icon to have multiples icons ? or hard to do and use list of icons ?
  export let qualif;
  // TODO : simple icon or MENU mode, circle btn overlay to show edit menu, etc
  export let isMenu = false;
  export let failbackIcon = `
    <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m17 21-5-4-5 4V3.889a.92.92 0 0 1 .244-.629.808.808 0 0 1 .59-.26h8.333a.81.81 0 0 1 .589.26.92.92 0 0 1 .244.63V21Z"/>
    </svg>
  `;

  // TODO : centralize sanitizer inside service or lib or...
  export let sanitizeClientHtml = (i) => {
    console.debug(i); // return i;
    // https://www.npmjs.com/package/sanitize-html
    const clean = sanitizeHtml(i, {
      allowedTags: sanitizeHtml.defaults
      .allowedTags.concat([ 'img' ])
      .concat([
        // SVG
        'svg', 'g', 'defs', 'linearGradient', 'stop', 'circle',
        'path'
      ]),
      // allowedAttributes: false, // For SVG
      allowedAttributes: {
        ...sanitizeHtml.defaults.allowedAttributes,
        '*': [
          'href', 'align', 'alt', 'center', 'bgcolor',
          'src', 'class', 'role', 'xmlns',
          'data*', 'aria*', 'stroke*',
          'focusable', 'viewBox', 'd', 'fill',
        ],
        iframe: [
          {
            name: 'sandbox',
            multiple: true,
            values: ['allow-popups', 'allow-same-origin', 'allow-scripts']
          }
        ],
      },
      parser: {
        // SVG elements like linearGradient into your content and 
        // notice that they're not rendering as expected
        // due to case sensitivity issues without below option :
        lowerCaseTags: false,
        lowerCaseAttributeNames: false
      },
    });

    return clean;
  };


</script>
<!-- class:pointer-events-none={!isMenu} 
 TIPS : tooltip will not show if pointer-events is none...

 TODO : why  data-tooltip-placement="top" needed, can't overflow visible at bottom ?
-->

<div
  class="inline-flex ml-1 mr-1
  justify-center items-center
  align-center text-center
  overflow-hidden
  {height} {width} {cssClass ?? ''}"
>
  <!-- // TODO : check ALL @html and ensure right sanitizer is ok... -->
  {@html sanitizeClientHtml(
    (qualif?.htmlIcon ?? '').trim().length
    ? qualif.htmlIcon
    : failbackIcon
  )}
</div>
<!-- 
  data-tooltip-target={tooltipId}
  data-tooltip-placement="top"

  <div id={tooltipId} role="tooltip" class="absolute z-40 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
  {qualif?.label}
  <div class="tooltip-arrow" data-popper-arrow></div>
</div> -->

<style global lang="scss">
  img {
    @apply object-contain;
    @apply w-full;
    @apply h-full;
  }
</style>