<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";
  import { state, offerTagsByCatSlugAndSlug } from "../../stores/reduxStorage.mjs";

  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let locale;
  export let tag;
  export let viewTemplate;
  export let form;

  console.debug(locale);
  console.debug(tag);
  console.debug(viewTemplate);
  console.debug(form);
</script>

<Base {copyright} {locale} {viewTemplate}>
  <div class="flex flex-wrap">
    <div class="label">
      Edition du tag :
    </div>
    <div class="detail rounded-sm p-3"
    style:color={(offerTagsByCatSlugAndSlug($state, tag.categorySlug, tag.slug)?.textColor)||"black"}
    style:background-color={(offerTagsByCatSlugAndSlug($state, tag.categorySlug, tag.slug)?.bgColor)||"lightgrey"}
    >
      [{ tag.slug }] { tag.label }
    </div>
  </div>
  {@html form}

  <a href="{ Routing.generate('mws_offer_tags', {
    '_locale': locale,
    'filterTags': viewTemplate,
  }) }">
    <button class="btn btn-outline-secondary p-1">Revenir à la liste</button>
  </a>
</Base>