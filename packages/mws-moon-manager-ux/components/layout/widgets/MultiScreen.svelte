<script>
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // https://flowbite.com/docs/components/device-mockups/
  export let capture = {
    title: "Test 1",
    mobile:
      "https://flowbite.s3.amazonaws.com/blocks/marketing-ui/hero/mockup-2-light.png",
    desktop:
      "https://flowbite.s3.amazonaws.com/docs/device-mockups/screen-image-imac.png",
    dark: {
      mobile:
        "https://flowbite.s3.amazonaws.com/blocks/marketing-ui/hero/mockup-2-dark.png",
      desktop:
        "https://flowbite.s3.amazonaws.com/docs/device-mockups/screen-image-imac-dark.png",
    },
  };
  let cssClass = "w-full md:w-1/3 p-7";
  export {cssClass as class};
</script>

{#if capture?.desktop}
  <div class={cssClass}>
    <div
      class="relative mx-auto border-gray-800 dark:border-gray-800 bg-gray-800 border-[14px] rounded-[2.5rem] h-[600px] w-[300px] shadow-xl"
    >
      <div
        class="w-[148px] h-[18px] bg-gray-800 top-0 rounded-b-[1rem] left-1/2 -translate-x-1/2 absolute"
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
      <div
        class="rounded-[2rem] overflow-hidden w-[272px] h-[572px] bg-white dark:bg-gray-800"
      >
        <img
          src={capture?.mobile}
          class="dark:hidden w-[272px] h-[572px]"
          alt={capture?.title}
        />
        <img
          src={capture?.dark?.mobile ?? capture?.mobile}
          class="hidden dark:block w-[272px] h-[572px]"
          alt={capture?.title}
        />
      </div>
    </div>
  </div>
{/if}
{#if capture?.desktop}
  <div class="w-full md:w-1/3 p-7">
    <div
      class="relative mx-auto border-gray-800 dark:border-gray-800 bg-gray-800 border-[16px] rounded-t-xl h-[172px] max-w-[301px] md:h-[294px] md:max-w-[512px]"
    >
      <div class="rounded-xl overflow-hidden h-[140px] md:h-[262px]">
        <img
          src={capture?.desktop}
          class="dark:hidden h-[140px] md:h-[262px] w-full rounded-xl"
          alt={capture?.title}
        />
        <img
          src={capture?.dark?.desktop ?? capture?.desktop}
          class="hidden dark:block h-[140px] md:h-[262px] w-full rounded-xl"
          alt={capture?.title}
        />
      </div>
    </div>
    <div
      class="relative mx-auto bg-gray-900 dark:bg-gray-700 rounded-b-xl h-[24px] max-w-[301px] md:h-[42px] md:max-w-[512px]"
    />
    <div
      class="relative mx-auto bg-gray-800 rounded-b-xl h-[55px] max-w-[83px] md:h-[95px] md:max-w-[142px]"
    />
  </div>
{/if}
 