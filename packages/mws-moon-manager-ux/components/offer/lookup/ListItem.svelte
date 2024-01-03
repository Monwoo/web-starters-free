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

  // TODO : format leadAt date with dayJs ?
  console.debug("LIST ITEM OFFER : ", offer);

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
  <td>

X
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
