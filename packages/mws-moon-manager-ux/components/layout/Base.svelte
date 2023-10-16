<svelte:options accessors />

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2022-2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Footer from "./Footer.svelte";
  // TIPS : <svelte:options accessors /> needed for external end-to-end test
  //        (and standalone component export....)
  import Header from "./Header.svelte";
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
</script>

<slot name="mws-body">
  <div class="flex flex-col h-screen">
    <header class="py-5 bg-gray-700 text-white text-center">
      <Header>
        <slot name="mws-header" />
      </Header>
    </header>
    <main class="flex-1 overflow-y-auto p-5">
      <slot />
    </main>
    <footer class="py-5 bg-gray-700 text-center text-white">
      <Footer {copyright}>
        <slot name="mws-footer" />
      </Footer>
    </footer>
  </div>
</slot>
