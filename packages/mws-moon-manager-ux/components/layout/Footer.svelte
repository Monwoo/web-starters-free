<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import { state } from "../../stores/reduxStorage.mjs";

  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
</script>

<!-- Tailwind is Awesome 😎 -->
<p>
  <a href="https://www.monwoo.com" target="_blank" rel="noopener">
    {copyright}
  </a>
</p>
<p class="text-right px-3 text-gray-400">
  [ {$state.packageName} v-{$state.packageVersion} ]
</p>
