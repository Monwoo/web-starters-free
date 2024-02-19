<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2022-2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // Inspired from :
  // https://tailwindcomponents.com/component/circular-progress-bar-1
  export let r = 18;
  export let sw = 10;
  export let circumferenceAm = ((2 * 22) / 7) * r;
  export let circumferencePm= ((2 * 22) / 7) * (r - sw);
  export let maxSlots = 24 * 60 / 10;
  export let dashSizeAm = 2 * circumferenceAm / maxSlots;
  export let dashSizePm = 2 * circumferencePm / maxSlots;
  export let dasharrayAm = Array(maxSlots / 2).fill(dashSizeAm.toFixed(2));
  export let dasharrayPm = Array(maxSlots / 2).fill(dashSizePm.toFixed(2));
  export let svgAmDasharray = ''; // Morning from 0 to 12
  export let svgPmDasharray = ''; // Afternoon from 12 to 24
  
  export let textClass = "text-2xl";
  // export let textRenderer = (percent) => `${(percent * 100).toFixed(0)} %`;
  export let slots = {};

  $: svgAmDasharray = dasharrayAm.reduce((acc, dSize, idx) => {
    return acc + ((true === (slots[idx] ?? false)) ? ` 0 ${dSize}` :  ` ${dSize} 0`);
  }, '');
  $: svgPmDasharray = dasharrayPm.reduce((acc, dSize, idx) => {
    return acc + ((true === (slots[idx + maxSlots / 2] ?? false)) ? ` 0 ${dSize}` :  ` ${dSize} 0`);
  }, '');

  // console.debug('Slots for indicator : ', slots);
</script>

<span class="inline-flex items-center justify-center relative">
  <svg
    class="transform -rotate-90 text-center"
    style:width={(r + sw) * 2}
    style:height={(r + sw) * 2}
  >
    <circle
      cx={r + sw}
      cy={r + sw}
      {r}
      stroke="currentColor"
      stroke-width={sw}
      fill="transparent"
      class="text-blue-500"
    />
    <circle
      cx={r + sw}
      cy={r + sw}
      {r}
      stroke="currentColor"
      stroke-width={sw}
      fill="transparent"
      stroke-dasharray={svgAmDasharray}
      class="text-gray-700"
    />
    <circle
      cx={r + sw}
      cy={r + sw}
      r={r - sw}
      stroke="currentColor"
      stroke-width={sw}
      fill="transparent"
      class="text-blue-500"
    />
    <circle
      cx={r + sw}
      cy={r + sw}
      r={r - sw}
      stroke="currentColor"
      stroke-width={sw}
      fill="transparent"
      stroke-dasharray={svgPmDasharray}
      class="text-gray-700"
    />
  </svg>
  <span class="absolute {textClass}">
    <!-- {textRenderer(percent)} -->
  </span>
</span>
