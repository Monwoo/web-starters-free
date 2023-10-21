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

  export let offer;

  // TODO : format leadAt date with dayJs ?
  console.debug(offer);
</script>

<tr>
  <td>
    <a href="#see-card">
      <button class="btn btn-outline-primary p-1">Voir</button>
    </a>
    <a href="#qualify">
      <button class="btn btn-outline-success p-1">Qualifier</button>
    </a>
  </td>
  <th scope="row">[{offer.id}] {offer.currentStatusSlug}</th>
  <td>{offer.clientUsername}</td>
  <td>{offer.contact1 ?? ''}</td>
  <td>{offer.contact2 ?? ''}</td>
  <td>
    <!-- <Time timestamp={offer.leadStart} format="YYYY/MM/DD h:mm" /> -->
    <!-- https://day.js.org/docs/en/display/format -->
    {dayjs(offer.leadStart).format('YYYY/MM/DD h:mm')}
  </td>
  <td>{offer.budget ?? ''}</td>
  <td>{offer.sourceDetail?.title ?? ''}</td>
  <td>{offer.sourceDetail?.description ?? ''}</td>
</tr>
