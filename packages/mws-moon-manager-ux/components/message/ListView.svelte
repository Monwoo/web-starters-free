<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import MessageView from './MessageView.svelte';
  import Routing from "fos-router";
  export let locale;
  export let viewTemplate;

  export let title = null;
  export let currentLanguage = null;
  export let messages = [];

  console.debug("Having msg : ", messages);
</script>

<div>
  <a href={ Routing.generate('mws_message_import', {
    '_locale': locale ?? '',
    'viewTemplate': viewTemplate ?? '',
  }) }>
    <button class="btn btn-outline-primary p-1">Importer des messages.</button>
  </a>    
</div>

{#each messages as message}
  <MessageView {message}></MessageView>
{/each}

