<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // https://flowbite.com/docs/components/device-mockups/
  export let capture = {
    title: "Test 1",
    mobile:
      "https://flowbite.s3.amazonaws.com/blocks/marketing-ui/hero/mockup-2-light.png",
    desktop:
      "https://flowbite.s3.amazonaws.com/docs/device-mockups/screen-image-imac.png",
    laptop:
      "https://flowbite.s3.amazonaws.com/docs/device-mockups/laptop-screen.png",
    dark: {
      mobile:
        "https://flowbite.s3.amazonaws.com/blocks/marketing-ui/hero/mockup-2-dark.png",
      desktop:
        "https://flowbite.s3.amazonaws.com/docs/device-mockups/screen-image-imac-dark.png",
      laptop:
        "https://flowbite.s3.amazonaws.com/docs/device-mockups/laptop-screen-dark.png",
    },
  };
  export let extraClass;
  let cssClass = "flex flex-col items-center justify-center w-full md:w-1/3 p-7 rounded-md";
  export { cssClass as class };
  export let useLaptop = true;
</script>

<div on:click class="mws-multi-screen relative {cssClass} {extraClass ?? ''}">
  <h1 class="hidden absolute z-40
  w-full justify-center
  bg-black/50
  text-white text-xl md:text-2xl font-extrabold
  whitespace-break-spaces
  p-2 top-0 left-0">
    {capture?.title ?? ''}
  </h1>
  <h1 class="hidden absolute z-40
  w-full justify-center
  bg-black/50
  text-white text-xl md:text-2xl font-extrabold
  whitespace-break-spaces
  p-2 bottom-0 left-0">
    {capture?.title ?? ''}
  </h1>
  <!-- {capture.title} -->
  {#if capture?.mobile}
    <!-- class="relative mx-auto border-gray-800 dark:border-gray-800 bg-gray-800 border-[14px] rounded-[2.5rem] h-[600px] -->
    <div
      class="relative mx-auto border-gray-800 dark:border-gray-800 bg-gray-800 border-[14px] rounded-[2.5rem]
      w-full max-w-[300px] shadow-xl"
    >
      <div
        class="w-[50%] max-w-[148px] h-[18px] bg-gray-800 top-0 rounded-b-[1rem] left-1/2 -translate-x-1/2 absolute"
      />
      <div
        class="h-[46px] w-[3px] bg-gray-800 absolute -start-[17px] top-[124px] rounded-s-lg"
      />
      <div
        class="h-[46px] w-[3px] bg-gray-800 absolute -start-[17px] top-[178px] rounded-s-lg"
      />
      <div
        class="h-[64px] w-[3px] bg-gray-800 absolute -end-[17px] top-[142px] rounded-e-lg"
      />
      <!-- class="rounded-[2rem] overflow-hidden w-[100%] max-w-[272px] h-[572px] bg-white dark:bg-gray-800" -->
      <div
        class="rounded-[2rem] overflow-hidden w-[100%] max-w-[272px] bg-white dark:bg-gray-800"
      >
        <!-- class="hidden dark:block w-[100%] max-w-[272px] h-[572px]" -->
        <img
          src={capture?.mobile}
          class="dark:hidden w-[100%] max-w-[272px] aspect-[272/572]"
          alt={capture?.title}
        />
        <img
          src={capture?.dark?.mobile ?? capture?.mobile}
          class="hidden dark:block w-[100%] max-w-[272px] aspect-[272/572]"
          alt={capture?.title}
        />
      </div>
    </div>
  {/if}
  {#if capture?.desktop}
    {#if useLaptop}
      <div
        class="relative mx-auto border-gray-800 dark:border-gray-800 
        bg-gray-800 border-[8px] rounded-t-xl 
        w-[301px]
        md:w-[512px]
        max-w-[85%]
        md:max-w-[85%]"
      >
        <!-- h-[156px] md:h-[195px]  h-[156px] md:h-[195px] -->
        <div
          class="rounded-lg overflow-hidden bg-white dark:bg-gray-800"
        >
          <img
            src={capture?.laptop ?? capture?.desktop}
            class="dark:hidden w-full rounded-xl aspect-[285/160]"
            alt={capture?.title}
          />
          <img
            src={capture?.dark?.laptop ??
              capture?.dark?.desktop ??
              capture?.desktop}
            class="hidden dark:block w-full rounded-lg aspect-[285/160]"
            alt={capture?.title}
          />
        </div>
      </div>
      <div
        class="w-full relative mx-auto bg-gray-700 dark:bg-gray-700 rounded-b-xl rounded-t-sm h-[17px] max-w-[351px] md:h-[21px] md:max-w-[597px]"
      >
        <div
          class="absolute left-1/2 top-0 -translate-x-1/2 rounded-b-xl w-[56px] h-[5px] md:w-[96px] md:h-[8px] bg-gray-500"
        />
      </div>
    {:else}
      <!-- h-[172px] md:h-[294px] -->
      <div
        class="relative mx-auto border-gray-800 dark:border-gray-800 bg-gray-800 border-[16px] rounded-t-xl max-w-[301px] md:max-w-[512px]"
      >
        <!-- h-[140px] md:h-[262px] -->
        <div class="rounded-xl overflow-hidden">
          <img
            src={capture?.desktop}
            class="dark:hidden w-full rounded-xl aspect-[285/160]"
            alt={capture?.title}
          />
          <img
            src={capture?.dark?.desktop ?? capture?.desktop}
            class="hidden dark:block w-full rounded-xl aspect-[285/160]"
            alt={capture?.title}
          />
        </div>
      </div>
      <!-- md:h-[42px] -->
      <div
        class="w-full relative mx-auto bg-gray-900 dark:bg-gray-700 rounded-b-xl h-[21px] max-w-[301px] md:max-w-[512px]"
      />
      <!-- md:h-[95px] -->
      <div
        class="w-full relative mx-auto bg-gray-800 rounded-b-xl h-[42px] max-w-[83px] md:max-w-[142px]"
      />
    {/if}
  {/if}
</div>

<style lang="scss">
  .mws-multi-screen {
    &:hover {
      h1 {
        @apply flex;
      }
    }
  }
</style>