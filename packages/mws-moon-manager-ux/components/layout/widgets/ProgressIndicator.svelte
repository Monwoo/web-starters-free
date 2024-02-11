<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2022-2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // Inspired from :
  // https://tailwindcomponents.com/component/circular-progress-bar-1
  export let r = 21;
  export let sw = 10;
  export let circumference = ((2 * 22) / 7) * r;
  export let textClass = "text-2xl";
  export let textRenderer = (percent) => `${(percent * 100).toFixed(0)} %`;
  export let percent = 0.0;

</script>

<span class="flex items-center justify-center relative">
  <svg class="transform -rotate-90"
  style:width={(r + sw)*2}
  style:height={(r + sw)*2}
  >
    <circle
      cx={r + sw}
      cy={r + sw}
      {r}
      stroke="currentColor"
      stroke-width={sw}
      fill="transparent"
      class="text-gray-700"
    />

    <circle
      cx={r + sw}
      cy={r + sw}
      r={r}
      stroke="currentColor"
      stroke-width={sw}
      fill="transparent"
      stroke-dasharray={circumference}
      stroke-dashoffset={circumference - percent * circumference}
      class="text-blue-500"
    />
  </svg>
  <span class="absolute {textClass}">
    {textRenderer(percent)}
  </span>
</span>
