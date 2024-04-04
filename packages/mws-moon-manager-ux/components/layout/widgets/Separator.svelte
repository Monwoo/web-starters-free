<script>
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // https://flowbite.com/docs/components/timeline/
  // https://flowbite.com/docs/typography/hr/
  // https://flowbite.com/docs/components/indicators/
  // https://flowbite.com/blocks/marketing/hero/

  export let text;
</script>

<div class="relative inline-flex items-center justify-center w-full">
  <hr class="w-full h-px my-8 bg-gray-200 border-0 dark:bg-gray-700" />
  <span
    class="absolute px-3 font-medium text-gray-900 -translate-x-1/2 left-1/2 dark:text-white dark:bg-gray-900"
    >{text ?? ''}</span
  >
</div>
