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
  import { onMount } from "svelte";
  import sanitizeHtml from "sanitize-html";
  import {
    state,
    offerTagsByKey,
    stateGet,
    stateUpdate,
  } from "../../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import debounce from "lodash/debounce";
  import Loader from "../../layout/widgets/Loader.svelte";
  import ContactLink from "../../layout/widgets/ContactLink.svelte";
  import { flip } from "svelte/animate";

  export let locale;
  export let viewTemplate;
  export let offer;
  export let messages;
  export let trackings;
  export let addModal;
  export let funnelModal;
  export let yScrollable;
  export let reportScale = 100;
  export let newComment;

  $: trackings = offer?.mwsOfferTrackings?.toReversed() ?? [];

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
  console.debug("trackings :", trackings);

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

  export let isTrackingsCollapsed = true;

  let isLoading = false;

  export let addComment = async (comment, offerStatusSlug = null) => {
    if (!comment || !comment.length) {
      console.debug("addComment is missing comment");
      return;
    }
    isLoading = true;
    try {
      const data = {
        _csrf_token: stateGet(get(state), "csrfOfferAddComment"),
        offerSlug: offer.slug,
        comment, // TODO : allow optional comment on status switch ?
        offerStatusSlug,
      };
      // let headers:any = {}; // { 'Content-Type': 'application/octet-stream', 'Authorization': '' };
      let headers = {
        Accept: "application/json",
      };

      // https://stackoverflow.com/questions/35192841/how-do-i-post-with-multipart-form-data-using-fetch
      // https://muffinman.io/uploading-files-using-fetch-multipart-form-data/
      // Per this article make sure to NOT set the Content-Type header.
      // headers['Content-Type'] = 'application/json';
      const formData = new FormData();
      for (const name in data) {
        formData.append(name, data[name]);
      }
      const resp = await fetch(
        // TODO : build back Api, will return new csrf to use on success, will error othewise,
        // if error, warn user with 'Fail to remove tag. You are disconnected, please refresh the page...'
        Routing.generate("mws_offer_add_comment", {
          _locale: locale,
        }),
        {
          method: "POST",
          headers,
          // body: JSON.stringify(data), // TODO : no automatic for SF to extract json in ->request ?
          body: formData,
          // https://stackoverflow.com/questions/34558264/fetch-api-with-cookie
          credentials: "same-origin",
          // https://javascript.info/fetch-api
          redirect: "error",
        }
      )
        .then(async (resp) => {
          console.log(resp.url, resp.ok, resp.status, resp.statusText);
          if (!resp.ok) {
            // make the promise be rejected if we didn't get a 2xx response
            throw new Error("Not 2xx response"); // , {cause: resp});
          } else {
            const data = await resp.json();
            // TODO : sync trakings :
            // trackings = data.sync?.mwsOfferTrackings?.toReversed() ?? [];
            offer = data.sync; // Svelte reactive will take care of 'trackings'
            stateUpdate(state, {
              csrfOfferAddComment: data.newCsrf,
            });
          }
        })
        .catch((e) => {
          console.error(e);
          // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
          const shouldWait = confirm("Echec de l'enregistrement.");
        });
    } catch (e) {
      console.error(e);
    }
    isLoading = false;
  };

  export let deleteTracking = async (trackingId) => {
    isLoading = true;
    try {
      const data = {
        _csrf_token: stateGet(get(state), "csrfOfferDeleteTracking"),
        trackingId,
      };
      // let headers:any = {}; // { 'Content-Type': 'application/octet-stream', 'Authorization': '' };
      let headers = {
        Accept: "application/json",
      };

      // https://stackoverflow.com/questions/35192841/how-do-i-post-with-multipart-form-data-using-fetch
      // https://muffinman.io/uploading-files-using-fetch-multipart-form-data/
      // Per this article make sure to NOT set the Content-Type header.
      // headers['Content-Type'] = 'application/json';
      const formData = new FormData();
      for (const name in data) {
        formData.append(name, data[name]);
      }
      const resp = await fetch(
        // TODO : build back Api, will return new csrf to use on success, will error othewise,
        // if error, warn user with 'Fail to remove tag. You are disconnected, please refresh the page...'
        Routing.generate("mws_offer_delete_tracking", {
          _locale: locale,
        }),
        {
          method: "POST",
          headers,
          // body: JSON.stringify(data), // TODO : no automatic for SF to extract json in ->request ?
          body: formData,
          // https://stackoverflow.com/questions/34558264/fetch-api-with-cookie
          credentials: "same-origin",
          // https://javascript.info/fetch-api
          redirect: "error",
        }
      )
        .then(async (resp) => {
          console.log(resp.url, resp.ok, resp.status, resp.statusText);
          if (!resp.ok) {
            // make the promise be rejected if we didn't get a 2xx response
            throw new Error("Not 2xx response"); // , {cause: resp});
          } else {
            const data = await resp.json();
            // TODO : sync trakings :
            // trackings = data.sync?.mwsOfferTrackings?.toReversed() ?? [];
            offer = data.sync; // Svelte reactive will take care of 'trackings'
            stateUpdate(state, {
              csrfOfferDeleteTracking: data.newCsrf,
            });
          }
        })
        .catch((e) => {
          console.error(e);
          // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
          const shouldWait = confirm("Echec de l'enregistrement.");
        });
    } catch (e) {
      console.error(e);
    }
    isLoading = false;
  };
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
    <ContactLink
    source={offer.slug}
    name={offer.clientUsername}
    title={offer.title}
    contact={offer.contact1 ?? ""}
    ></ContactLink>
    <br />
    <ContactLink
    source={offer.slug}
    name={offer.clientUsername}
    title={offer.title}
    contact={offer.contact2 ?? ""}
    ></ContactLink>
  </td>
  <td class="max-w-[30dvw]">
    <div class="offer-trackings">
      <textarea class="w-full" bind:value={newComment} />
      <button
        on:click={debounce(async () => {
          await addComment(newComment);
        })}
        class=""
        style="--mws-primary-rgb: 0,200,0;"
      >
        Commenter
      </button>
      <Loader {isLoading} />
      <div
        class="mws-offer-trackings-title flex w-full  cursor-pointer"
        on:click={(e) => (isTrackingsCollapsed = !isTrackingsCollapsed)}
      >
        <div class="w-full font-bold">
          [{(trackings ?? [])[0]?.updatedAt
            ? dayjs(trackings[0].updatedAt).format("YYYY/MM/DD")
            : "--"}] {(trackings ?? [])[0]?.comment ?? "--"}
        </div>
      </div>
      {#if !isTrackingsCollapsed}
        <div
          class="mws-offer-tracking-list flex flex-wrap w-full cursor-pointer"
        >
          {#each trackings ?? [] as tracking, idx (idx)}
            <div animate:flip class="mws-offer-tracking w-full p-1">
              {#if $state.user?.roles?.includes("ROLE_MWS_ADMIN")}
                <button
                  on:click={debounce(async () => {
                    if (
                      confirm(
                        `Supprimer le suivi de [${
                          tracking.id ?? "--"
                        }] du ${dayjs(tracking.updatedAt).format(
                          "YYYY/MM/DD HH:mm"
                        )}`
                      )
                    ) {
                      await deleteTracking(tracking.id);
                    }
                  })}
                  class=""
                  style="--mws-primary-rgb: 255,0,0;"
                >
                  x
                </button>
              {/if}

              [{dayjs(tracking.updatedAt).format(
                "YYYY/MM/DD HH:mm"
              )}][{tracking.id ?? "--"}]
              {#if tracking.offerStatusSlug && "null" != tracking.offerStatusSlug}
                [{tracking.offerStatusSlug}]
              {/if}
              <span class="font-bold">
                {tracking.comment ?? "--"}
              </span>
            </div>
          {/each}
        </div>
      {/if}
    </div>

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
        <a href="${offer.sourceUrl}" target="_blank" rel="noreferrer">
          <h1>${offer.title}</h1>
        </a>
        <p>${offer.contact1 ?? ""}</p>
        <p>${offer.contact2 ?? ""}</p>      
        <p>${offer.budget ?? ""}</p>      
        <p>${dayjs(offer.leadStart).format("YYYY/MM/DD HH:mm")}</p>      
        <a href="${
          offer.clientUrl ?? "#not-found"
        }" target="_blank" rel="noreferrer">
          <button class="">Publié par : ${offer.clientUsername}</button>
        </a>
        <div class="mws-last-trakings p-3">
          ${(offer.mwsOfferTrackings.toReversed().slice(0,3) ?? []).reduce(
            (acc, traking) => {
              acc += `
              <div class="w-full">
                [${
                  traking.updatedAt
                    ? dayjs(traking.updatedAt).format("YYYY/MM/DD")
                    : "--"
                }] ${traking.comment ?? "--"}
              </div>
            `;
              return acc;
            },
            ""
          )}
        </div>

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
      }}>Ajouter un message prévisionnel.</button
    >
    <div class="crm-messages">
      {#each messages ?? [] as message, idx}
        <button
          class="md:m-3"
          on:click={() => {
            console.debug("Will edit :", message);
            addModal.surveyModel.data = message;
            // TODO: remove code duplication with message list :
            addModal.sourceDetailView = `
              <a href="${offer.sourceUrl}" target="_blank" rel="noreferrer">
                <h1>${offer.title}</h1>
              </a>
              <p>${offer.contact1 ?? ""}</p>
              <p>${offer.contact2 ?? ""}</p>      
              <p>${offer.budget ?? ""}</p>
              <p>${dayjs(offer.leadStart).format("YYYY/MM/DD HH:mm")}</p>      
              <a href="${
                offer.clientUrl ?? "#not-found"
              }" target="_blank" rel="noreferrer">
                <button class="">Publié par : ${offer.clientUsername}</button>
              </a>
              <div class="mws-last-trakings p-3">
                ${(offer.mwsOfferTrackings.toReversed().slice(0,3) ?? []).reduce(
                  (acc, traking) => {
                    acc += `
                    <div class="w-full">
                      [${
                        traking.updatedAt
                          ? dayjs(traking.updatedAt).format("YYYY/MM/DD")
                          : "--"
                      }] ${traking.comment ?? "--"}
                    </div>
                  `;
                    return acc;
                  },
                  ""
                )}
              </div>

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
          Éditer le message [{idx}].
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
    <div class="overflow-auto max-h-[20em]">
      {offer.sourceDetail?.description ?? ""}
    </div>
  </td>
  <td>
    <!-- // TIPS : at end to avoid sticky overlay of table start 
      => avoid scroll back to be able to click link instead
      of sticky actions
    -->
    <!-- TODO : remove code duplications -->
    {#if myOfferId && offer.sourceUrl}
      <!-- TODO : offert dest slug rewrite system ... -->
      <a
        href={`${offer.sourceUrl}/${myOfferId}`}
        target="_blank"
        rel="noreferrer"
      >
        {offer.sourceDetail?.title ?? "Voir l'offre"}
      </a>
    {:else}
      <a href={`${offer.sourceUrl}`} target="_blank" rel="noreferrer">
        {offer.sourceDetail?.title ?? "Voir l'offre"}
      </a>
    {/if}
  </td>
</tr>
