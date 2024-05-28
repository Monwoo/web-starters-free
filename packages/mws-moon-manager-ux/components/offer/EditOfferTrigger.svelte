<script context="module">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import dayjs from "dayjs";

  export const offerToSurveyJsTransformer = (offer) => ({
    ...offer,
    // leadStart: dayjs(offer.leadStart).format("DD/MM/YYYY HH:mm"),
    leadStart: offer.leadStart
      ? dayjs(offer.leadStart).format("YYYY-MM-DDTHH:mm")
      : null,
    tags: offer.tags?.map((t) => t.categorySlug + tagSlugSep + t.slug),
    // TODO : why using | inside dropdown label forbidden ? need escape ? solved by using replace for now.
    currentStatusSlug: offer.currentStatusSlug?.replace("|", " > "),
    sourceDetail: [
      {
        ...(offer.sourceDetail ?? {}),
        messages: (offer.sourceDetail?.messages ?? []).map((m) => ({
          msg: m,
        })),
      },
    ],
  });
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
          offer = {
            ...offer, // TIPS : keep current frontend extra param injections
            ...offerToSurveyJsTransformer(data.sync), // sync with backend results
          };

          // if (data.didDelete) {
          // } else {
          // }

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
  <EditOutline class="text-2xl" />
</button>
