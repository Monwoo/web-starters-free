<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  export let bottom = 'bottom-[-1.25rem]';
  export let right = 'right-[-1.25rem]';
  let cssClass = '';
  export {cssClass as class};
</script>

<!-- TIPS p-5 is 1.25rem, so subtract to fit bottom 
  
  -->
<div
  class="sticky {bottom} {right} w-full
inline-flex pointer-events-none justify-end content-end
print:fixed print:bottom-0 print:right-0
{cssClass}
">
  <span
    class="p-1 max-w-[50%]
  rounded-ss-md font-medium 
  bg-gray-100 text-gray-700"
  >
    Données confidentielles. Merci de conserver le secret professionnel et
    privé.
  </span>
</div>
