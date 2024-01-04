<script>
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import { onMount, tick } from "svelte";

  export let message = {};
  export let addModal;
  // let surveyModel;

  const showEditMsg = () => {
    // addModal.surveyModel.getQuestionByName('projectId').value = 'Forced PID';
    addModal.surveyModel.data = message;
    addModal.eltModal.show();
  };

  // TODO : reactive on message update ? :
  // TODO : check message.destId for specific urls schema injections ?
  let sourceUrl = `https://${message.destId}/projects`;

  onMount(async () => {
    // await tick();
    // await tick();
    // await tick();
    // await tick();
    // await tick();
    // await tick();
    // await tick();
    // setTimeout(() => {
    //   // TODO : wait for surey js on load event instead of empiric timings
    //   const jQuery = window.$; // TODO : no missing 'window' with SF controllers ways ?
    //   let surveyWrapper = jQuery(".survey-js-wrapper");
    //   surveyModel = surveyWrapper.data("surveyModel");

    //   console.debug("surveyModel : ", surveyModel);
    // }, 500);
  });

</script>

<!-- {@debug message} -->

<tr>
  <td>
    <!-- <a
      href={Routing.generate("mws_message_view", {
        _locale: locale ?? "",
        viewTemplate: viewTemplate ?? "",
        offerSlug: offer.slug,
      })}
    >
      voir
    </a> -->
    <button class="btn btn-outline-primary p-1" on:click={() => showEditMsg()}
      >Éditer le message.</button
    >
  </td>
  <th scope="row">
    <a href="{`${sourceUrl}/${message.projectId}`}" target="_blank" rel="noreferrer">
      {message.projectId}
    </a>
  </th>
  <td>
    {message.destId}
  </td>
  <td>
    {message.monwooAmount}
  </td>
  <td>
    {message.projectDelayInOpenDays}
  </td>
  <td>
    {message.asNewOffer}
  </td>
  <td>
    {message.isDraft}
  </td>
  <td>
    {message.sourceId}
  </td>
  <td>
    {JSON.stringify(message.crmLogs)}
  </td>
  <td>
    {JSON.stringify(message.messages)}
  </td>
  <td>
    {message.owner.username}
  </td>
</tr>
