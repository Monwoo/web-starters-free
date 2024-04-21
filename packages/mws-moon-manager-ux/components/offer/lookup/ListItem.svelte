<script context="module">
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  dayjs.locale("fr"); // Fr locale // TODO : global config instead of per module ?
</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : why "svelte-time" not working ?
  // import Time from "svelte-time";
  import TagsInput from "../tags/TagsInput.svelte";
  import { state, offerTagsByKey } from "../../../stores/reduxStorage.mjs";
  import { onMount } from "svelte";
  import sanitizeHtml from "sanitize-html";

  export let locale;
  export let viewTemplate;
  export let offer;
  export let messages;
  export let addModal;
  export let funnelModal;
  export let yScrollable;
  export let reportScale = 100;

  // TODO : centralize sanitizer inside service or lib or...
  export let sanitizeClientHtml = (i) => {
    // console.debug(i); // return i;
    // https://www.npmjs.com/package/sanitize-html
    const clean = sanitizeHtml(i, {
      allowedTags: sanitizeHtml.defaults.allowedTags.concat(["img"]).concat([
        // SVG
        "svg",
        "g",
        "defs",
        "linearGradient",
        "stop",
        "circle",
        "path",
      ]),
      // allowedAttributes: false, // For SVG
      allowedAttributes: {
        ...sanitizeHtml.defaults.allowedAttributes,
        "*": [
          "href",
          "align",
          "alt",
          "center",
          "bgcolor",
          "src",
          "class",
          "role",
          "xmlns",
          "data*",
          "aria*",
          "stroke*",
          "focusable",
          "viewBox",
          "d",
          "fill",
        ],
        iframe: [
          {
            name: "sandbox",
            multiple: true,
            values: ["allow-popups", "allow-same-origin", "allow-scripts"],
          },
        ],
      },
      parser: {
        // SVG elements like linearGradient into your content and
        // notice that they're not rendering as expected
        // due to case sensitivity issues without below option :
        lowerCaseTags: false,
        lowerCaseAttributeNames: false,
      },
    });

    return clean;
  };

  // TODO : format leadAt date with dayJs ?
  console.debug("LIST ITEM OFFER : ", offer);
  console.debug("messages :", messages);

  // TODO : configurable services, use generic data connectors instead of :
  // TODO : remove code duplication :
  let myOfferId = offer.sourceDetail?.monwooOfferId ?? null;
  myOfferId = myOfferId ? "offers/" + myOfferId.split("_").slice(-1)[0] : null;

  // TODO : lots of stuff for scrollspy when svelte do it...
  // https://preline.co/plugins/html/scrollspy.html
  // data-hs-scrollspy="#scrollspy-1" data-hs-scrollspy-scrollable-parent="#scrollspy-scrollable-parent-1"

  export let isScrolling = false;
  export let isFirstColVisible = false;
  export let isSecondColVisible = false;
  export let isThirdColVisible = false;
</script>

<tr>
  <td
    class="sticky left-0 w-[3em] z-10
    hover:bg-white/90 hover:opacity-100"
    class:opacity-0={isFirstColVisible}
  >
    <a
      href={Routing.generate("mws_offer_view", {
        _locale: locale ?? "fr",
        viewTemplate: viewTemplate ?? "",
        offerSlug: offer.slug,
      })}
    >
      <img
        width="64"
        src={offer.contacts[0].avatarUrl ?? ""}
        alt={offer.contacts[0].username ?? ""}
      />
    </a>
    <!-- <a href="#qualify">
      TIPS : short cuted by 'status' update, will qualify depending of logic
             linked to new status change
      <button class="btn btn-outline-success p-2 m-3">Qualifier</button>
    </a> -->
  </td>
  <th
    scope="row"
    class="sticky left-[4em] w-[6em] z-10
    hover:bg-white/90 hover:opacity-100"
    class:opacity-0={isSecondColVisible}
  >
    [{offer.slug}]
    <div
      class="p-2 m-1 rounded"
      style:color={offerTagsByKey($state, offer.currentStatusSlug)?.textColor ||
        "black"}
      style:background-color={offerTagsByKey($state, offer.currentStatusSlug)
        ?.bgColor || "lightgrey"}
    >
      <!-- {JSON.stringify($state)}
      {JSON.stringify(offer.currentStatusSlug)}
      {JSON.stringify(offerTagsByCatSlugAndSlug($state, offer.currentStatusSlug))} -->
      {offerTagsByKey($state, offer.currentStatusSlug)?.label ||
        offer.currentStatusSlug}
    </div>
    [{offer.sourceDetail?.projectOffersAnswered}
    ..
    {offer.sourceDetail?.projectOffersViewed}] /
    {offer.sourceDetail?.projectOffers}
  </th>
  <td
    class="sticky left-[9em] w-[6em] z-10
    hover:bg-white/90 hover:opacity-100
      overflow-x-scroll
    "
    class:opacity-0={isThirdColVisible}
  >
    <TagsInput {funnelModal} bind:tags={offer.tags} {offer} {locale} />
  </td>
  <!-- TODO : ? <td>{(offer.sourceDetail?.projectStatus || '').trim()}</td> -->
  <td>
    {offer.clientUsername} <br />
    {offer.contacts[0].sourceDetail?.status} <br />
    {offer.contacts[0].sourceDetail?.nbProjects} projet(s) <br />
    depuis : {offer.contacts[0].sourceDetail?.membershipStart} <br />
  </td>
  <td>
    {offer.contact1 ?? ""}<br />
    {offer.contact2 ?? ""}
  </td>
  <td class="max-w-[30dvw]">
    <button
      class="md:m-3"
      on:click={() => {
        // addModal.surveyModel.data = null; // Ensure data is empty before show...
        addModal.surveyModel.data = {
          // TODO : not shown in target modal, wrong format or wrong way to set data ? :
          projectId: offer.slug.split("-").slice(-1).join(""),
          destId: offer.sourceName,
          isDraft: true,
          // TODO : also link CRM Users ?
          sourceId: "MoonManagerUI-" + new Date().getTime(),
        }; // Ensure data is empty before show...
        // TODO: remove code duplication with message list :
        addModal.sourceDetailView = `
        <h1>${offer.title}</h1>
        <p>${offer.contact1 ?? ""}</p>
        <p>${offer.contact2 ?? ""}</p>      
        <p>${offer.budget ?? ""}</p>      
        <p>${dayjs(offer.leadStart).format("YYYY/MM/DD HH:mm")}</p>      
        <a href="${
          offer.clientUrl ?? "#not-found"
        }" target="_blank" rel="noreferrer">
          <button class="">Publié par : ${offer.clientUsername}</button>
        </a>    
        <p>${offer.description ?? ""}</p>      
        ${
          myOfferId && offer.sourceUrl
            ? `
          <a href="${
            offer.sourceUrl
          }/${myOfferId}" target="_blank" rel="noreferrer">
            <button class="">Source des messages</button>
          </a><br/>
          Proposition : ${offer.sourceDetail.monwooOfferAmount ?? ""}<br/>
          Délais : ${offer.sourceDetail.monwooOfferDelay ?? ""}<br/>
          `
            : ``
        }
        ${(offer.sourceDetail?.messages ?? []).reduce(
          (html, msg) =>
            html + // TODO : factorise code duplication
            msg
              .replaceAll('src="/', `src="https://${offer.sourceName}/`)
              .replaceAll('href="/', `href="https://${offer.sourceName}/`)
              .replaceAll(`https://${offer.sourceName}/http`, `http`),
          ``
        )}
      `;
        addModal.eltModal.show();
      }}>Ajouter un message.</button
    >
    <div class="crm-messages">
      {#each messages ?? [] as message}
        <button
          class="md:m-3"
          on:click={() => {
            console.debug("Will edit :", message);
            addModal.surveyModel.data = message;
            // TODO: remove code duplication with message list :
            addModal.sourceDetailView = `
          <h1>${offer.title}</h1>
          <p>${offer.contact1 ?? ""}</p>
          <p>${offer.contact2 ?? ""}</p>      
          <p>${offer.budget ?? ""}</p>      
          <p>${dayjs(offer.leadStart).format("YYYY/MM/DD HH:mm")}</p>      
          <a href="${
            offer.clientUrl ?? "#not-found"
          }" target="_blank" rel="noreferrer">
            <button class="">Publié par : ${offer.clientUsername}</button>
          </a>    
          <p>${offer.description ?? ""}</p>      
          ${
            myOfferId && offer.sourceUrl
              ? `
            <a href="${
              offer.sourceUrl
            }/${myOfferId}" target="_blank" rel="noreferrer">
              <button class="">Source des messages</button>
            </a><br/>
            Proposition : ${offer.sourceDetail.monwooOfferAmount ?? ""}<br/>
            Délais : ${offer.sourceDetail.monwooOfferDelay ?? ""}<br/>
          `
              : ``
          }
          ${(offer.sourceDetail?.messages ?? []).reduce(
            (html, msg) =>
              html + // TODO : factorise code duplication
              msg
                .replaceAll('src="/', `src="https://${offer.sourceName}/`)
                .replaceAll('href="/', `href="https://${offer.sourceName}/`)
                .replaceAll(`https://${offer.sourceName}/http`, `http`),
            ``
          )}
        `;
            addModal.eltModal.show();
          }}
        >
          Éditer le message.
        </button>
      {/each}
    </div>
    <div class="overflow-auto max-h-[8em]">
      <div class="sended-messages">
        Proposition : {offer.sourceDetail.monwooOfferAmount ?? ""}<br />
        Délais : {offer.sourceDetail.monwooOfferDelay ?? ""}<br />

        <!-- TODO : .reverse() not working with reduce ?
          {@html (offer.sourceDetail?.messages ?? []).reverse().reduce( FAIL
          did change order of reduce function for now
        -->
        {@html sanitizeClientHtml(
          (offer.sourceDetail?.messages ?? []).reduce(
            (html, msg) =>
              `` + // TODO : factorise code duplication
              msg
                .replaceAll('src="/', `src="https://${offer.sourceName}/`)
                .replaceAll('href="/', `href="https://${offer.sourceName}/`)
                .replaceAll(`https://${offer.sourceName}/http`, `http`) +
              html,
            ``
          )
        )}
      </div>
    </div>
  </td>
  <td>
    <!-- <Time timestamp={offer.leadStart} format="YYYY/MM/DD HH:mm" /> -->
    <!-- https://day.js.org/docs/en/display/format -->
    {dayjs(offer.leadStart).format("YYYY/MM/DD HH:mm")}
  </td>
  <td>{offer.budget ?? ""}</td>
  <td>
    {#if myOfferId && offer.sourceUrl}
      <a
        href={`${offer.sourceUrl}/${myOfferId}`}
        target="_blank"
        rel="noreferrer"
      >
        {offer.sourceDetail?.title ?? "Voir l'offre"}
      </a>
    {:else}
      <a
        href={`${offer.sourceUrl}`}
        target="_blank"
        rel="noreferrer"
      >
        {offer.sourceDetail?.title ?? "Voir l'offre"}
      </a>
    {/if}
  </td>
  <td>
    <div class="overflow-auto max-h-[10em]">
      {offer.sourceDetail?.description ?? ""}
    </div>
  </td>
</tr>
