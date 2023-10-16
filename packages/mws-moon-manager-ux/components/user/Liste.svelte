<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";

  // export let users:any[] = []; // TODO : not Typescript ?
  export let users = [];
  export let filterForm = ""; // injected raw html
  export let paginator = ""; // injected raw html
  export let headers = {}; // injected raw html
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let filterTags = null;

  // TODO : remove code duplication and put this in some 'mws-utils' package ?
  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  };

  // filterForm = decodeURI(filterForm);f
  filterForm = decodeHtml(filterForm);
  paginator = decodeHtml(paginator);
  console.log(filterForm);
  console.log(paginator);
  console.log(users);
</script>

<Base {copyright}>
  <div>{@html filterForm}</div>
  <div>{@html paginator}</div>
  <div class="overflow-y-auto">
    <table>
      <thead>
        <tr class="users-table-info">
          <th scope="col">Actions</th>
          <th scope="col">[Id] Status</th>
          <th scope="col">
            {@html headers.username ?? "Nom d'utilisateur"}
          </th>
        </tr>
      </thead>
      <tbody>
        <!-- // TIPS : comming from symfony muti-select query, entity is at index 0 -->
        {#each users as uBulk}
          {@const u = uBulk[0]}
          <!-- { JSON.stringify(u) } -->
          <!-- {@debug u} -->
          <td>
            <a href="{ Routing.generate('user_show', {
              'id': u.id,
              'viewTemplate': filterTags,
            }) }">
              <button class="btn btn-outline-primary p-1">Voir</button>
            </a>
            <a href="{ Routing.generate('user_edit', {
                'id': u.id,
                'viewTemplate': filterTags
            }) }">
              <button class="btn btn-outline-success p-1">Modifier</button>
            </a>
          </td>
          <th scope="row">{u.id}</th>
          <td>{u.username}</td>
        {/each}
      </tbody>
    </table>
  </div>
  <div>{@html paginator}</div>
</Base>
