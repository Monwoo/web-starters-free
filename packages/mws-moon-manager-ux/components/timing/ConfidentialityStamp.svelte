<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  export let bottom = 'bottom-[-1.25rem]';
  export let right = 'right-[-1.25rem]';
  export let fixedBottom = false;
  // export let isMobile = true;
  // class="p-1 {isMobile ? 'max-w-[100%]' : 'max-w-[50%]'}

  let cssClass = '';
  export {cssClass as class};
</script>

<!-- TIPS p-5 is 1.25rem, so subtract to fit bottom 
  select-none if pointer-events-none not enough...
  -->
<div
  class="sticky {bottom} {right} w-full select-none
  inline-flex pointer-events-none justify-end content-end
  print:fixed print:bottom-0 print:right-0 z-50
  !wide:sticky
  {cssClass}
  "
  class:sticky={!fixedBottom}
  class:fixed={fixedBottom}
>
  <span
    class="px-1 md:py-1 max-w-[100%] md:max-w-[50%]}
  rounded-ss-md font-xs md:font-normal
  text-[0.60rem] md:text-base pointer-events-none
  bg-gray-100/90 md:bg-gray-100 text-gray-700
  wide:text-[0.60rem] wide:font-xs
  "
  >
    Données confidentielles. Merci de conserver le secret professionnel et
    privé.
  </span>
</div>
