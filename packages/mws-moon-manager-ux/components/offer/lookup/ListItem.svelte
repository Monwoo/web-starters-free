<script context="module">
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  dayjs.locale("fr"); // Fr locale
</script>
<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  // TODO : why "svelte-time" not working ?
  // import Time from "svelte-time";
  import TagsInput from "../tags/TagsInput.svelte";
  import { state, offerTagsByKey } from "../../../stores/reduxStorage.mjs";

  export let locale;
  export let viewTemplate;
  export let offer;
  export let messages;
  export let addModal;

  // TODO : format leadAt date with dayJs ?
  console.debug("LIST ITEM OFFER : ", offer);
  console.debug("messages :", messages);

  // TODO : configurable services, use generic data connectors instead of :
  // TODO : remove code duplication :
  let myOfferId = offer.sourceDetail?.monwooOfferId ?? null;
  myOfferId = myOfferId ? 'offers/' + myOfferId.split('_').slice(-1)[0] : null;

</script>

<tr>
  <td>
    <a href={ Routing.generate('mws_offer_view', {
      '_locale': locale ?? '',
      'viewTemplate': viewTemplate ?? '',
      'offerSlug': offer.slug,
    }) }>
      <img width='64' src="{ offer.contacts[0].avatarUrl ?? '' }" alt="{ offer.contacts[0].username ?? '' }"/>
    </a>
    <!-- <a href="#qualify">
      TIPS : short cuted by 'status' update, will qualify depending of logic
             linked to new status change
      <button class="btn btn-outline-success p-2 m-3">Qualifier</button>
    </a> -->
  </td>
  <th
  scope="row"
  >
    [{offer.slug}]
    <div class="p-2 m-1 rounded"
    style:color={(offerTagsByKey($state, offer.currentStatusSlug)?.textColor)||"black"}
    style:background-color={(offerTagsByKey($state, offer.currentStatusSlug)?.bgColor)||"lightgrey"}
      >
      <!-- {JSON.stringify($state)}
      {JSON.stringify(offer.currentStatusSlug)}
      {JSON.stringify(offerTagsByCatSlugAndSlug($state, offer.currentStatusSlug))} -->
      {(offerTagsByKey($state, offer.currentStatusSlug)?.label)
      || offer.currentStatusSlug}
  </div>
  </th>
  <td><TagsInput bind:tags={offer.tags} {offer} {locale}></TagsInput></td>
  <!-- TODO : ? <td>{(offer.sourceDetail?.projectStatus || '').trim()}</td> -->
  <td>{offer.clientUsername}</td>
  <td>
    {offer.contact1 ?? ''}<br/>
    {offer.contact2 ?? ''}
  </td>
  <td class="max-w-[30vw]">
    <button
    class="btn btn-outline-primary m-3"
    on:click={() => {
      // addModal.surveyModel.data = null; // Ensure data is empty before show...
      addModal.surveyModel.data = {
        // TODO : not shown in target modal, wrong format or wrong way to set data ? :
        projectId: offer.slug.split('-').slice(-1).join(''),
        destId: 'codeur.com',
        isDraft: true,
        // TODO : also link CRM Users ?
        sourceId: 'MoonManagerUI-' + (new Date()).getTime(),
      }; // Ensure data is empty before show...
      // TODO: remove code duplication with message list :
      addModal.sourceDetailView = `
        <h1>${offer.title}</h1>
        <p>${offer.contact1 ?? ''}</p>
        <p>${offer.contact2 ?? ''}</p>      
        <p>${offer.budget ?? ''}</p>      
        <p>${dayjs(offer.leadStart).format('YYYY/MM/DD h:mm')}</p>      
        <a href="${ offer.clientUrl ?? "#not-found"}" target="_blank" rel="noreferrer">
          <button class="btn btn-outline-primary p-1">Publié par : ${offer.clientUsername}</button>
        </a>    
        <p>${ offer.description ?? '' }</p>      
        ${ myOfferId && offer.sourceUrl ? `
          <a href="${offer.sourceUrl}/${myOfferId}" target="_blank" rel="noreferrer">
            <button class="btn btn-outline-primary p-1">Source des messages</button>
          </a><br/>
          Proposition : ${ offer.sourceDetail.monwooOfferAmount ?? '' }<br/>
          Délais : ${ offer.sourceDetail.monwooOfferDelay ?? '' }<br/>
          `: `` }
        ${ (offer.sourceDetail?.messages ?? []).reduce(
            (html, msg) => html // TODO : factorise code duplication
              + msg.replaceAll('src="/', `src="https://${offer.sourceName}/`)
              .replaceAll('href="/', `href="https://${offer.sourceName}/`)
              .replaceAll(`https://${offer.sourceName}/http`, `http`),
            ``
            )
        }
      `;
      addModal.eltModal.show();
    }}
    >Ajouter un message.</button>
    <div class="overflow-auto max-h-[8em]">
      <div class="sended-messages">
        Proposition : { offer.sourceDetail.monwooOfferAmount ?? '' }<br/>
        Délais : { offer.sourceDetail.monwooOfferDelay ?? '' }<br/>
  
        <!-- TODO : .reverse() not working with reduce ?
          {@html (offer.sourceDetail?.messages ?? []).reverse().reduce( FAIL
          did change order of reduce function for now
        -->
        {@html (offer.sourceDetail?.messages ?? []).reduce(
          (html, msg) => ``// TODO : factorise code duplication
            + msg.replaceAll('src="/', `src="https://${offer.sourceName}/`)
            .replaceAll('href="/', `href="https://${offer.sourceName}/`)
            .replaceAll(`https://${offer.sourceName}/http`, `http`)
            + html,
          ``
        )}
      </div>
      <div class="crm-messages">
        {#each messages ?? [] as message }
        <button
        class="btn btn-secondary m-3"
        on:click={() => {
          console.debug("Will edit :", message);
          addModal.surveyModel.data = message;
                // TODO: remove code duplication with message list :
          addModal.sourceDetailView = `
            <h1>${offer.title}</h1>
            <p>${offer.contact1 ?? ''}</p>
            <p>${offer.contact2 ?? ''}</p>      
            <p>${offer.budget ?? ''}</p>      
            <p>${dayjs(offer.leadStart).format('YYYY/MM/DD h:mm')}</p>      
            <a href="${ offer.clientUrl ?? "#not-found"}" target="_blank" rel="noreferrer">
              <button class="btn btn-outline-primary p-1">Publié par : ${offer.clientUsername}</button>
            </a>    
            <p>${ offer.description ?? '' }</p>      
            ${ myOfferId && offer.sourceUrl ? `
              <a href="${offer.sourceUrl}/${myOfferId}" target="_blank" rel="noreferrer">
                <button class="btn btn-outline-primary p-1">Source des messages</button>
              </a><br/>
              Proposition : ${ offer.sourceDetail.monwooOfferAmount ?? '' }<br/>
              Délais : ${ offer.sourceDetail.monwooOfferDelay ?? '' }<br/>
            `: `` }
            ${ (offer.sourceDetail?.messages ?? []).reduce(
                (html, msg) => html // TODO : factorise code duplication
                  + msg.replaceAll('src="/', `src="https://${offer.sourceName}/`)
                  .replaceAll('href="/', `href="https://${offer.sourceName}/`)
                  .replaceAll(`https://${offer.sourceName}/http`, `http`),
                ``
                )
            }
          `;
          addModal.eltModal.show();
        }}
        >
          Éditer le message.
        </button>
    
        {/each}
      </div>
    </div>
  </td>
  <td>
    <!-- <Time timestamp={offer.leadStart} format="YYYY/MM/DD h:mm" /> -->
    <!-- https://day.js.org/docs/en/display/format -->
    {dayjs(offer.leadStart).format('YYYY/MM/DD h:mm')}
  </td>
  <td>{offer.budget ?? ''}</td>
  <td>
    {#if myOfferId && offer.sourceUrl}
      <a href="{`${offer.sourceUrl}/${myOfferId}`}" target="_blank" rel="noreferrer">
        {offer.sourceDetail?.title ?? 'Voir les messages'}
      </a>
    {:else }
      {offer.sourceDetail?.title ?? ''}
    {/if}
  </td>
  <td>
    <div class="overflow-auto max-h-[10em]">
      {offer.sourceDetail?.description ?? ''}
    </div>
  </td>
</tr>
