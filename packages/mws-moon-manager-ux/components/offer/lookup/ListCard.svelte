<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";

  export let offer;
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
  <td>{offer.sourceDetail?.title ?? ''}</td>
  <td>{offer.sourceDetail?.description ?? ''}</td>
</tr>
