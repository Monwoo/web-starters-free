<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import { onMount } from "svelte";
  import debounce from 'lodash/debounce';
  // import dayjs from "dayjs";

  // https://medium.com/william-joseph/things-i-wish-id-known-when-starting-website-projects-9133a5188878
  /*
  Working towards this budget split is ideal:
    Total website budget = rebuild + retainer (support + enhancements)
    Rebuild — the Big project that includes discovery, design and build.
    Support — the hosting and ‘lights on’ package to keep your website running and deal with bug fixing
    Enhancements — ongoing development of the features and functionality, keeping the site fresh and learning from data
  */
  // https://page.funnel.io/elevate-your-reporting
  // https://charityfunnel.io/ => TODO : more advanced than custom paypal ? only for specific known domains ?
  //
  const urlParams = new URLSearchParams(window.location.search);

  export let affiliationCode = urlParams.get("affiliationCode") ?? null;
  export let userDelay = 200;

  onMount(() => {});
</script>

<div class="w-full flex flex-wrap items-center justify-center">
  <h1
    class="p-8 text-gray-900 dark:text-white text-3xl md:text-5xl font-extrabold mb-2"
  >
    Soutenir le projet
  </h1>
  <div class="w-full text-center">
    <label class="p-2" for="affiliationCode">Code d'affiliation :</label>
    <input
    class="text-black opacity-30 hover:opacity-100 max-w-[12rem] w-4/5"
    value={affiliationCode}
    type="text"
    name="affiliationCode"
    on:input={debounce(async (e) => {
      affiliationCode = e.target.value;
    }, userDelay * 4)}
    on:keydown={debounce(async (e) => {
      if ("Enter" == e.key) {
        if (document.activeElement instanceof HTMLElement)
          document.activeElement.blur();
        e.target.blur();
        // TODO : auto trigger href link
      }
    }, userDelay)}
    />
  </div>

  <!-- // TODO : affiliation code input auto fill for paypal reference trakings ? -->
  <a
    class="p-7 text-2xl hover:cursor-pointer hover:no-underline"
    href={`https://www.monwoo.com/don${
      affiliationCode
      ? `?affiliationCode=${encodeURIComponent(affiliationCode)}`
      : ``
    }`}
    target="_blank"
  >
    { affiliationCode ? affiliationCode + ' : ' : ''}
    www.monwoo.com/don
  </a>
</div>
