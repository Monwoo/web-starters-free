<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  let cssClass;
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
</script>

<div
  class="inline-flex ml-1 mr-1 {height} {width} {cssClass ?? ''}"
  class:pointer-events-none={!isMenu}
>
  {@html qualif?.htmlIcon ?? failbackIcon}
</div>

<style global lang="scss">
  img {
    @apply object-contain;
    @apply w-full;
    @apply h-full;
  }
</style>