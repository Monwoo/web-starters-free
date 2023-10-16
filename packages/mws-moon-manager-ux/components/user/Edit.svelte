<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";

  // export let users:any[] = []; // TODO : not Typescript ?
  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let user;
  export let viewTemplate;
  export let editForm;
  export let passwordResetForm;

  // TODO : remove code duplication and put this in some 'mws-utils' package ?
  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  };

  console.debug(locale);
  console.debug(user);
  console.debug(viewTemplate);
  console.debug(editForm);
  console.debug(passwordResetForm);
</script>

<Base {copyright}>
  <div class="flex flex-wrap">
    <div class="label">
      Edition du profil :
    </div>
    <div class="detail">
      { user.username }
    </div>
  </div>
  {@html decodeHtml(editForm)}
  {@html decodeHtml(passwordResetForm)}

  <!-- TODO : remove button, or avoid issue, only tag as trashed ? ? -->
  <a href="{ Routing.generate('mws_user_show', {
    '_locale': locale,
    'id': user.id,
    'viewTemplate': viewTemplate,
  }) }">
    <button class="btn btn-outline-primary p-1">Voir</button>
  </a>
  <a href="{ Routing.generate('mws_user_list', {
    '_locale': locale,
    'filterTags': viewTemplate,
  }) }">
    <button class="btn btn-outline-secondary p-1">Revenir à la liste</button>
  </a>
</Base>

<!-- <style lang="scss">
  // TODO : post CSS syntax allowed in svelte scss ?
  // Done in packages/mws-moon-manager/assets/styles/app.scss
  // .label {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
  // .detail {
  //   @apply md:1/4 lg:1/4 text-right;
  // }
</style> -->