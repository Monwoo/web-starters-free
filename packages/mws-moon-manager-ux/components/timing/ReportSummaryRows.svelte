<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import ReportSummaryRows from "./ReportSummaryRows";
  import MwsTimeSlotIndicator from "../layout/widgets/MwsTimeSlotIndicator.svelte";

  export let haveChild=true;
</script>
Hello
{#if haveChild}
<ReportSummaryRows haveChild={false}>

</ReportSummaryRows> 
{/if}