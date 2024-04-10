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
  <div class="w-1/3 px-2 text-left text-gray-300">
    <div>
      Reset : {
        dayjs().format("YYYY/MM/DD h:mm")
      }
    </div>
      - {
        dayjs().add(3.9, "hour").diff(dayjs(), 'hour', true)
      }
      heures
    <div>
    </div>
  </div>
  <a class="w-1/3 text-xs md:text-sm "
  href="https://www.monwoo.com" target="_blank" rel="noopener">
    {copyright}
  </a>
  <div class="w-1/3 text-right px-3 text-gray-400">
    [ {$state.packageName} v-{$state.packageVersion} ]
  </div>
</div>