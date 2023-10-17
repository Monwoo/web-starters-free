<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";

  export let locale;
  export let viewTemplate;
</script>

<!-- TODO : menu on roles, or menu items on roles or both or simple button menu ? -->
<a href="{ Routing.generate('mws_offer_lookup', {
  '_locale': locale ?? '',
  'viewTemplate': viewTemplate ?? '',
}) }">
  <button class="btn btn-outline-primary p-1">Rechercher une offre</button>
</a>

<slot />
