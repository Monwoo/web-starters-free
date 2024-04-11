<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023-2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import { state } from "../../stores/reduxStorage.mjs";
  import dayjs from "dayjs";

  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";

  Number.prototype.toPrettyNum = function (this: Number, length: number, maxLength = null) {
    if (maxLength === null) maxLength = length;
    var s = this;
    const splited = s
      .toFixed(maxLength).replace(new RegExp(`0{0,${maxLength - length}}$`), "")
      // https://stackoverflow.com/questions/5025166/javascript-number-formatting-min-max-decimals
      // .replace(/0{0,2}$/, "")
      // .toLocaleString('en-US', { // TODO : centralize toPrettyNum and use locals formatings ?
      //   minimumFractionDigits: 2,
      //   maximumFractionDigits: 4
      // })
      .replace(".", ",")
      .split(',');
    return (splited[0] ?? '').replace(/\B(?=(\d{3})+(?!\d))/g, " ")
    + (length >= 1 ? "," : "") 
    + (splited[1] ?? '');
  };

  declare interface Number {
    toPrettyNum(length: number): string;
  }

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
  <div class="w-1/3 px-3 text-left text-gray-300 flex flex-wrap">
    {#if $state.gdprLastCleanDate && $state.gdprNextCleanDate}
      <div class="px-1">
        Reset :
        <!-- {
          dayjs($state.gdprNextCleanDate).format("YYYY/MM/DD HH:mm")
        } -->
      </div>
      <div>
        {
          dayjs()
          .diff(dayjs($state.gdprNextCleanDate), 'hour', true)
          .toPrettyNum(2)
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