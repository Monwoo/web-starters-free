<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import NavBar from "../menu/NavBar.svelte";

  export let locale;
  export let viewTemplate;
  export let inlineOpener;

  // TODO : select navBar depending of User roles....
</script>

<!-- TODO : menu on roles, or menu items on roles or both or simple button menu ? -->
<NavBar {locale} {viewTemplate} {inlineOpener}>
  <slot />
</NavBar>
