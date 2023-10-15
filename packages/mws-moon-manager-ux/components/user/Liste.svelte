<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from 'fos-router';
  // TODO : namespace
  import Base from '../layout/Base.svelte';

  // export let users:any[] = []; // TODO : not Typescript ?
  export let users = [];
  export let filterForm = ''; // injected raw html
  export let paginator = ''; // injected raw html

  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
      var txt = document.createElement("textarea");
      txt.innerHTML = html;
      return txt.value;
  }

  // filterForm = decodeURI(filterForm);f
  filterForm = decodeHtml(filterForm);
  paginator = decodeHtml(paginator);
  console.log(filterForm);
  console.log(paginator);
</script>

<Base>
  <div>{@html filterForm }</div>
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