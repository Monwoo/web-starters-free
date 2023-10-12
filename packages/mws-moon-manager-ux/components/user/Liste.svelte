<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from 'fos-router';
  // TODO : namespace
  import Base from '../layout/Base.svelte';

  // export let users:any[] = []; // TODO : not Typescript ?
  export let users = [];
  export let paginator = ''; // injected raw html
</script>

<Base>
  <div>{@html paginator }</div>
  <div>
    TODO : list users
    {#each users as u}
      { u }
      {@debug u}
    {/each}
    yes
  </div>
</Base>