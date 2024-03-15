<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";

  // export let users:any[] = []; // TODO : not Typescript ?
  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let locale;
  export let viewTemplate;
  export let form;

  console.debug(locale);
  console.debug(viewTemplate);
  console.debug(form);
</script>

<Base {copyright}>
  <div class="flex flex-wrap">
    <div class="label">
      Nouveau du profil :
    </div>
  </div>
  {@html form}

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