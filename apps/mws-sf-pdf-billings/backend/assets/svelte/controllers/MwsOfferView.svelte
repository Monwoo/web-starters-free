<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import View from "mws-moon-manager-ux/components/offer/View.svelte";

  export let copyright = "© Monwoo 2023 (service@monwoo.com)";
  export let locale;
  export let offer = '{}';
  export let viewTemplate;

  // TODO : remove code duplication and put this in some 'mws-utils' package ?
  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }

  offer = JSON.parse(decodeHtml(offer));
  // console.debug(offer);
</script>

<!-- <View {...$$props} /> ??? -->
<View {offer} {copyright}
{locale} {viewTemplate}
></View>
