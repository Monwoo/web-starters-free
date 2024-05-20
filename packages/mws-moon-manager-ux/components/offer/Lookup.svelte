<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : namespace
  import Base from "../layout/Base.svelte";
  import List from "./lookup/List.svelte";
  import { onMount } from "svelte";
  import Loader from "../layout/widgets/Loader.svelte";
  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import debounce from "lodash/debounce";

  // export let users:any[] = []; // TODO : not Typescript ?
  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let locale;
  export let lookup;
  export let offers = [];
  export let messagesByProjectId = {};
  export let offersPaginator;
  export let offersHeaders = {}; // injected raw html
  export let viewTemplate;
  export let lookupForm;
  export let addMessageForm = "";
  export let isMobile;
  export let isWide;
  export let reportScale = 100;

  $: reportScale = isMobile ? 85 : 100;

  console.debug(locale);
  console.debug(lookup);
  console.debug(offers);
  console.debug(offersPaginator);
  console.debug(viewTemplate);
  console.debug(lookupForm);

  const searchLookup = JSON.parse(decodeURIComponent(lookup.jsonResult));
  console.debug("searchLookup :", searchLookup);
  // TODO : basehref ? => NOP, use Routing from fos-routing instead...
  const baseHref = "/mws";
  // const respUrl = `${baseHref}/${locale}/mws-offer/fetch-root-url?url=`
  // + encodeURIComponent(searchLookup.sourceRootLookupUrl);
  export let isLoading = false; // TODO : show loader when showDetails or showPictures is loading...

  const deleteAllOffers = async () => {
    if (isLoading) return;
    isLoading = true;
    // TODO : Wait for loading animation to show
    // await tick();
    // await new Promise(r => setTimeout(r, 500));
    // Or use HTML modal instead of native blocking UI alert
    await new Promise((r) => setTimeout(r, 100));

    if (confirm("Are you sure you want to delete all offers ?")) {
      const data = {
        _csrf_token: stateGet(get(state), "csrfOfferDeleteAll"),
      };
      const formData = new FormData();
      for (const name in data) {
        formData.append(name, data[name]);
      }
      const resp = await fetch(
        Routing.generate("mws_offer_delete_all", {
          _locale: locale,
        }),
        {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          redirect: "error",
          headers: {
            Accept: "application/json",
          },
        }
      )
        .then(async (resp) => {
          console.log(resp.url, resp.ok, resp.status, resp.statusText);
          if (!resp.ok) {
            throw new Error("Not 2xx response");
          } else {
            const data = await resp.json();
            console.debug("Did remove all offers, resp :", data);
            // TODO : remove self from DOM instead of isHidden ?
            // tags = [];
            stateUpdate(state, {
              csrfOfferDeleteAll: data.newCsrf,
            });
            window.location.reload();
          }
        })
        .catch((e) => {
          console.error(e);
          // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
          const shouldWait = confirm("Echec de l'enregistrement.");
        });
    }
    isLoading = false;
  };

  onMount(async () => {
    // // NOP, not common, will have security errors this way :
    // const htmlResp = await fetch(respUrl);
    // console.debug(htmlResp);
    // // const win = window.open(searchLookup.sourceRootLookupUrl, "Title", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));
    // const win = window.open('', "Title", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));
    // // win.document.body.innerHTML = "HTML";
    // win.document.body.innerHTML = await htmlResp.text();
    const $ = window.$;
    // TIPS opti : use svelte html node ref and pass to jquery ?
    const htmlLookup = $(lookupForm);
    // console.log(htmlLookup);
    const lookupSurveyJsFormData = Object.fromEntries(
      new FormData(htmlLookup[0]).entries()
    );
    const lookupSurveyJsData = JSON.parse(
      decodeURIComponent(
        lookupSurveyJsFormData["mws_survey_js[jsonLookup]"] ?? "{}"
      )
    ); // TODO : from param or config
    // TIPS : same as jsonLookup, updated by survey js or other if using ref element instead of raw string... :
    console.log("lookupSurveyJsData : ", lookupSurveyJsData);
  });

  console.log(Routing.generate("mws_offer_import"));
</script>

<Base bind:isMobile bind:isWide {copyright} {locale} {viewTemplate}>
  <div class="p-3 flex flex-wrap">
    <Loader {isLoading} />
    <a
      class="pb-2"
      href={Routing.generate("mws_offer_import", {
        _locale: locale ?? "",
        viewTemplate: viewTemplate ?? "",
      })}
    >
      <button class="">Importer des offres.</button>
    </a>
    <span on:click={deleteAllOffers}>
      <button class="mx-2"
      style="--mws-primary-rgb: 255, 0, 0"
      >Supprimer toutes les offres.</button>
    </span>
  </div>
  <div class="p-3 flex flex-wrap">
    <div class="label">
      <button
        data-collapse-toggle="search-offer-lookup"
        type="button"
        class="rounded-lg "
        aria-controls="search-offer-lookup"
        aria-expanded="false"
      >
        Filtres de recherche
      </button>
    </div>
    <div id="search-offer-lookup" class="detail w-full hidden z-50">
      {@html lookupForm}
    </div>
  </div>
  <div class="summary">
    <!-- // TODO : code factorization, inside component ? -->
    {@html searchLookup.searchStart && searchLookup.searchStart.length
      ? "<strong>Depuis le : </strong>" +
        dayjs(searchLookup.searchStart).format("YYYY-MM-DD HH:mm:ss") +
        "<br/>"
      : ""}
    {@html searchLookup.searchEnd && searchLookup.searchEnd.length
      ? "<strong>Jusqu'au : </strong>" +
        dayjs(searchLookup.searchEnd).format("YYYY-MM-DD HH:mm:ss") +
        "<br/>"
      : ""}
    {@html searchLookup.searchTags && searchLookup.searchTags.length
      ? "<strong>Tags : </strong>" +
        searchLookup.searchTags.reduce(
          (acc, f) => `
          ${acc} [${f}]
        `,
          ``
        ) +
        "<br/>"
      : ""}
    <!-- // TODO : code factorization, indide component ? -->
    {@html searchLookup.searchTagsToInclude &&
    searchLookup.searchTagsToInclude.length
      ? "<strong>Tags à inclure : </strong>" +
        searchLookup.searchTagsToInclude.reduce(
          (acc, f) => `
            ${acc} [${f}]
          `,
          ``
        ) +
        "<br/>"
      : ""}
    {@html searchLookup.searchTagsToAvoid &&
    searchLookup.searchTagsToAvoid.length
      ? "<strong>Tags à éviter : </strong>" +
        searchLookup.searchTagsToAvoid.reduce(
          (acc, f) => `
            ${acc} [${f}]
          `,
          ``
        ) +
        "<br/>"
      : ""}
    {@html searchLookup.searchKeyword
      ? `<strong>Mots clefs : </strong>${searchLookup.searchKeyword}`
      : ``}
    {@html offersPaginator}
  </div>

  <!-- { JSON.stringify(offers) } -->
  <div class="flex items-start w-full pt-3 pb-4 md:opacity-10 hover:opacity-100 print:hidden">
    <div class="fill-white/70 text-white/70 w-full">
      <!-- // TODO : userDelay instead of 400 ? not same for all situation,
      //         might need bigDelay or short or medium ?
      //         or too specific, keep number easyer than multiples var or const ? -->
      <input
        value={reportScale}
        on:change={debounce((e) => (reportScale = e.target.value), 200)}
        id="report-scale"
        type="range"
        class="w-full h-8 bg-gray-200/50 rounded-lg
          appearance-none cursor-pointer outline-none
          "
      />
    </div>
  </div>

  <div class="mws-offer-lookup"
>
    <List
      {reportScale}
      {locale}
      {isMobile} {isWide}
      {offers}
      {offersHeaders}
      {viewTemplate}
      {addMessageForm}
      {messagesByProjectId}
    />
  </div>
  <div>{@html offersPaginator}</div>
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
