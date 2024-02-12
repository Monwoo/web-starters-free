<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  export let locale;
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let lookup;
  export let timings = [];
  export let timeQualifs = [];
  export let timingsPaginator;
  export let timingsHeaders = {}; // injected raw html
  export let viewTemplate;
  export let lookupForm;
  const urlParams = new URLSearchParams(window.location.search);
  const pageNumber = urlParams.get("page") ?? "1";

  console.debug('Report having timings', timings);
</script>

<a
href={Routing.generate("mws_timings_qualif", {
  '_locale': locale ?? '',
}, true)}
class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent"
>
<button>Timings Qualifications</button>
</a>
<div class="mws-timing-report">
  <!-- {JSON.stringify(timings)} -->
  <div>{@html timingsPaginator}</div>

</div>