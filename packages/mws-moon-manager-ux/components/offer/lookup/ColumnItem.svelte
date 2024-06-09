<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // TODO : namespace
  import _ from "lodash";
  import dayjs from "dayjs";
  import { flip } from "svelte/animate";
  import {
    state,
    stateGet,
    stateUpdate,
  } from "../../../stores/reduxStorage.mjs";
  import ContactLink from "../../layout/widgets/ContactLink.svelte";
  import Routing from "fos-router";
  import Loader from "../../layout/widgets/Loader.svelte";
  import { get } from "svelte/store";
  import debounce from "lodash/debounce";
  import EditOfferTrigger from "../EditOfferTrigger.svelte";
  import TagsInput from "../tags/TagsInput.svelte";

  export let locale;
  export let viewTemplate;
  export let offer;
  export let reportScale = 100;
  export let isMobile;
  export let isWide;
  export let isLoading = false;
  export let isTrackingsCollapsed = true;

  // TODO : centralize web services ?
  // or differ since != usage even if similar to tags/TagsInput.svelte
  export let addComment = async (offer, comment, offerStatusSlug = null) => {
    if (!comment || !comment.length) {
      console.debug("addComment is missing comment");
      return;
    }
    if (isLoading) {
      console.debug("isLoading in progress, addComment avoided");
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
            // TIPS : sync trackings : done by reactivity, cf '$:'
            // trackings = data.sync?.mwsOfferTrackings?.toReversed() ?? [];
            // trackings = offer?.mwsOfferTrackings?.toReversed() ?? [];
            // TODO : BIND system not enough ? avoid _.merge that might break reactivity... need to merge to update PARENT LIST. BAD to merge ?
            _.merge(offer, data.sync); // Svelte reactive done by other ways ok ? this one will not trigger refresh
            // TIPS : BAD IDEA to use _.merge, use bind: instead, triky too, use REDUX pattern with writables at minimum ?...
            offer = data.sync; // TIPS : only this one do not update source offer array...
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

  // BELOW OK :
  // let trackings = offer?.mwsOfferTrackings?.toReversed() ?? [];

  // TODO : BELOW FAIL : SINCE OFFER REFRESH to ORIGINAL non updated offer
  //         (reactive columns rebuild re-using not updated offers ?)
  //          => SHOULD WORK with right offer selection
  $: trackings = offer?.mwsOfferTrackings?.toReversed() ?? [];
  // $: offer = (offer.id === $state.newOffer?.id) ? {...offer, ...$state.newOffer} : offer;
  $: offer = (offer.id === $state.newOffer?.id) ? $state.newOffer : offer;

  // let trackings;
  // $: offer, trackings = offer?.mwsOfferTrackings?.toReversed() ?? [];
  // TODO : animate inside component alternative ? deep anim ways ?
  // animate:flip={{ duration: flipDurationMs }}
  // <div bind:animate class="p-2"> // Will still error at top component, transfert binding not working for animate...
  // let animKey = {};
  // {#key animKey}

  // TIPS : below will refresh all offer dependency (rebuilding columns from scratch... cf Columns reactive statements)
  // <textarea class="w-full" bind:value={offer.newComment} />
  // => lose column hidden status due to REBUILD or Dnd-zone lib doing deep copy not in sync with update columns after dndZone inits ?
  // TIPS : avoid reactivity refresh by using components props instead of target obj property triggering reactivity...
  export let newComment; 

  // TODO : remove code duplication :
  Number.prototype.toPrettyNum = function (
    this: Number,
    length: number,
    maxLength = null
  ) {
    if (maxLength === null) maxLength = length;
    var s = this;
    const splited = s
      .toFixed(maxLength)
      .replace(new RegExp(`0{0,${maxLength - length}}$`), "")
      // https://stackoverflow.com/questions/5025166/javascript-number-formatting-min-max-decimals
      // .replace(/0{0,2}$/, "")
      // .toLocaleString('en-US', { // TODO : centralize toPrettyNum and use locals formatings ?
      //   minimumFractionDigits: 2,
      //   maximumFractionDigits: 4
      // })
      .replace(".", ",")
      .split(",");
    return (
      (splited[0] ?? "").replace(/\B(?=(\d{3})+(?!\d))/g, " ") +
      (length >= 1 ? "," : "") +
      (splited[1] ?? "")
    );
  };

</script>

<div class="p-2">
  <!-- // TIPS : avoid margin for % height compute to fit contents without overflows -->
  <div
    class="p-2 border-2 border-gray-700 rounded-md
          flex flex-col items-center justify-center text-center"
  >
    <EditOfferTrigger bind:offer />
    <p>{dayjs(offer.leadStart).format("YYYY/MM/DD HH:mm")}</p>
    <a
      href={Routing.generate("mws_offer_view", {
        _locale: locale ?? "fr",
        viewTemplate: viewTemplate ?? "",
        offerSlug: offer.slug,
      })}
      class="flex flex-col items-center justify-center text-center"
      target="_blank"
      title={offer.description ?? ""}
    >
      {#if (offer.contacts ?? [])[0]?.avatarUrl ?? false}
        <img
          width={64 - 50 * (1 - reportScale / 100)}
          src={(offer.contacts ?? [])[0]?.avatarUrl}
          alt="Avatar"
        />
      {/if}
      {offer.clientUsername}
    </a>
    <div>
      {offer.budget ?? ""}
    </div>
    <div class="flex flex-wrap justify-center">
      <TagsInput bind:tags={offer.tags} {offer} {locale} />
    </div>

    <h1 class="font-bold text-lg">
      <a href={`${offer.sourceUrl}`} target="_blank" rel="noreferrer">
        {offer.sourceDetail?.title ?? "Voir l'offre"}
      </a>
    </h1>
    <div>
      {#each offer.timingTags ?? [] as tag}
        <!-- TODO : componentize tag button, code factorization... -->
        <a
          href={Routing.generate("mws_timings_report", {
            _locale: locale ?? "fr",
            searchTagsToInclude: [ tag.slug ],
          })}
          class="inline-flex
          text-xs font-medium p-1 text-center
          border border-blue-800 "
        >
          {tag.label} {
            tag.pricePerHr ? `[${tag.pricePerHr.toPrettyNum(2)} €/hr]` : ''
          }
        </a>
      {/each}  
    </div>

    <div class="offer-trackings">
      <textarea class="w-full" bind:value={newComment} />
      <button
        on:click={debounce(async () => {
          await addComment(offer, newComment);
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
    <ContactLink
      source={offer.slug + " " + dayjs(offer.leadStart).format("YYYY-MM-DD")}
      name={offer.clientUsername}
      title={offer.title}
      contact={offer.contact1 ?? ""}
    />
    <ContactLink
      source={offer.slug + " " + dayjs(offer.leadStart).format("YYYY-MM-DD")}
      name={offer.clientUsername}
      title={offer.title}
      contact={offer.contact2 ?? ""}
    />
  </div>
</div>
