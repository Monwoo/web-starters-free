<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import { state } from "../../stores/reduxStorage.mjs";
  import dayjs from "dayjs";

  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
</script>

<!-- Tailwind is Awesome 😎 -->
<!-- <p>
  <a href="https://www.monwoo.com" target="_blank" rel="noopener">
    {copyright}
  </a>
</p>
<p class="text-right px-3 text-gray-400">
  [ {$state.packageName} v-{$state.packageVersion} ]
</p> -->

<div class="flex text-xs md:text-sm">
  <div class="w-1/3 px-3 text-left text-gray-300">
    {#if $state.gdprLastCleanDate && $state.gdprNextCleanDate}
      <div>
        Reset : {
          dayjs($state.gdprLastCleanDate).format("YYYY/MM/DD h:mm")
        }
      </div>
      <div>
        {
          dayjs($state.gdprLastCleanDate)
          .diff(dayjs($state.gdprNextCleanDate), 'hour', true)
        }
        heures
      </div>
    {:else}
      Données confidentiels
    {/if}
  </div>
  <a class="w-1/3 text-xs md:text-sm "
  href="https://www.monwoo.com" target="_blank" rel="noopener">
    {copyright}
  </a>
  <div class="w-1/3 text-right px-3 text-gray-400">
    [ {$state.packageName} v-{$state.packageVersion} ]
  </div>
</div>