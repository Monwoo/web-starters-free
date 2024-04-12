<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  export let url;
  let cssClass = ``;
  export { cssClass as class };
  // Colors come from backgrounds :
  export let svgClass = `bg-white hover:bg-gray-500`;
  export let forcedClass = `!w-[2.8rem]`;
  export let shadowEnabled = true;
  export let shadowClass = `bg-[var(--tw-shadow-color)]`;
</script>

{#if url}
<div class="flex justify-center items-center relative {cssClass}">
  <!--
    TIPS : 
      [text-shadow:_1_1px_4_var(--tw-shadow-color)]
      Ok in tailwind, but mask do not highlight shadow...
  -->
  {#if shadowEnabled}
    <div
      class="svg-icon {shadowClass}"
      style={`
        mask-image: url(${url});
        -webkit-mask-image: url(${url});
      `}
    />
    <div
    class="svg-icon absolute {shadowClass}"
    style={`
      mask-image: url(${url});
      -webkit-mask-image: url(${url});
      zoom: 0.7;
    `}
    />
  {/if}
  <div
    class="svg-icon absolute {svgClass} {forcedClass}
  "
    style={`
    mask-image: url(${url});
    -webkit-mask-image: url(${url});
    ${shadowEnabled ? `zoom: 0.9;`: ``}
  `}
  />
</div>
{/if}

<style lang="scss">
  .svg-icon {
    mask-size: 100%;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    mask-position: center;
    width: 3rem;
    height: 3rem;
  }
</style>