<script context="module">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import _ from "lodash";
  import dayjs from "dayjs";

  export const offerToSurveyJsTransformer = (offer) =>
    offer
      ? {
          ...offer,
          // leadStart: dayjs(offer?.leadStart).format("DD/MM/YYYY HH:mm"),
          leadStart: offer?.leadStart
            ? dayjs(offer?.leadStart).format("YYYY-MM-DDTHH:mm")
            : null,
          tags: offer?.tags?.map((t) => t.categorySlug + tagSlugSep + t.slug),
          timingTags: offer?.timingTags?.map((t) => t.slug),
          // TODO : why using | inside dropdown label forbidden ? need escape ? solved by using replace for now.
          currentStatusSlug: offer?.currentStatusSlug?.replace("|", " > "),
          sourceDetail: [
            {
              ...(offer?.sourceDetail ?? {}),
              messages: ((offer?.sourceDetail?.messages || null) ?? []).map((m) => ({
                msg: m,
              })),
            },
          ],
        }
      : null;
</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";

  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
  import { EditOutline } from "flowbite-svelte-icons";
  import { get } from "svelte/store";

  export let locale;
  export let offer;
  // callback on sync offer ok for refresh :
  export let syncOfferOk = (o) => null;
  // callback on sync offer fail (ko) for refresh :
  export let syncOfferKo = (o, err) => null;

  // TODO : factorize, cf php controller :
  const tagSlugSep = " > ";

  // TODO : as module ? or inside Service manager component, param
  //        config component with all web services ?
  export const syncOfferWithBackend = async (
    offer,
    syncOfferOkCallback,
    syncOfferKoCallback
  ) => {
    !offer && console.debug("WRONG syncOfferWithBackend, null offer");
    const data = {
      _csrf_token: stateGet(get(state), "csrfOfferSync"),
      offer: JSON.stringify(offer),
    };
    let headers = {};
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_offer_sync", {
        _locale: locale ?? "",
      }),
      {
        method: "POST",
        headers,
        body: formData,
        credentials: "same-origin",
        redirect: "error",
      }
    )
      .then(async (resp) => {
        console.log(resp.url, resp.ok, resp.status, resp.statusText);
        if (!resp.ok) {
          // make the promise be rejected if we didn't get a 2xx response
          throw new Error("Not 2xx response");
        } else {
          // got the desired response
          const data = await resp.json();

          const newOffer = {
            ...offer, // TIPS : keep current frontend extra param injections
            ...data.sync, // sync with backend results
            // ...offerToSurveyJsTransformer(data.sync)
          };

          // TODO : DOC : MUST bind:offer to _.merge to propagate to source list ? Not enough yet => need rebuild from change props on updated list like ColumnItem 'comment' service ?
          // TODO : BIND system not enough ? need to merge to update PARENT LIST. BAD to merge ?
          // _.merge(offer, newOffer); // Svelte reactive done by other ways ok ? this one will not trigger refresh
          // TIPS : prefer to listen to reactive store for other components update
          // => avoid _.merge to avoid svelte reactivity breackups...
          $state.newOffer = newOffer;

          // Self component update
          // TIPS : DO it in REACTIVITY statement, in case of multiple updates ?
          // offer = newOffer;

          $state.addOfferModal.surveyModel.data =
            offerToSurveyJsTransformer(offer);

          // if (data.didDelete) {
          // } else {
          // }

          // TODO or TIPS : below is same as ? :
          // $state.csrfOfferSync = data.newCsrf ?
          stateUpdate(state, {
            csrfOfferSync: data.newCsrf,
          });
          // Let component use callback to trigger the refresh
          syncOfferOk && (await syncOfferOk(offer));
          syncOfferOkCallback && (await syncOfferOkCallback(offer));
          // or use web service response if accessible :
          return offer;
        }
      })
      .catch(async (e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement."); // TODO : html non bloquant popup of breadcumb is better...
        syncOfferKo && (await syncOfferKo(offer, e));
        syncOfferKoCallback && (await syncOfferKoCallback(offer, e));
        return null;
      });
    return resp;
  };

  $: $state.addOfferModal?.syncOfferWithBackend = syncOfferWithBackend;
  // $: offer = $state.newOffer;
  // offer should ensure object for SurveyJs binds to work...
  $: (offer = offer
    ? offer?.id === $state.newOffer?.id
      ? $state.newOffer
      : offer
    : {}),
    console.debug("EditOfferTrigger offer :", offer);
</script>

<button
  class="m-2"
  on:click={() => {
    if (offer && $state.addOfferModal.surveyModel) {
      // TODO : factorize with offerExportSJDataNormalizer / offerImportSJDataNormalizer
      $state.addOfferModal.surveyModel.data = offerToSurveyJsTransformer(offer); // Ensure data is in sync
    } else {
      $state.addOfferModal.surveyModel.data = null;
    }
    $state.addOfferModal.eltModal.show();
  }}
>
  <slot>
    <EditOutline class="text-2xl" />
  </slot>
</button>
