<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import newUniqueId from "locally-unique-id-generator";
  import { Modal } from "flowbite";
  import { onMount } from "svelte";
  import AddModal from "../tags/AddModal.svelte";

  const UID = newUniqueId();
  let cssClass;
  export { cssClass as class };
  export let modalId = `confirmUpdateOrNew-${UID}`;
  export let qualif;
  export let syncQualifWithBackend;
  export let newName;
  export let isOpen = false;
  export let eltModal;
  export let locale;

  $: {
    if (isOpen) {
      eltModal?.show();
    } else {
      eltModal?.hide();
    }
  }

  onMount(async () => {
    const modalOptions = {
      // placement: "bottom-right",
      backdrop: "static",
      // TODO : when timing slot view in fullscreen,
      // it go over popup disallowing user events inside the popup...
      backdropClasses: "invisible pointer-events-none",
      // backdropClasses:
      //   "bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40",
      onHide: () => {
        console.log("modal is hidden");
      },
      onShow: () => {
        // TIPS : force refresh, since public accessor
        // update the value but do not trigger reactive refresh...
        // (since set surveyModel.data inside it instead of re-assign)
        // surveyModel = surveyModel;
        console.log("modal is shown");
      },
      onToggle: () => {
        console.log("modal has been toggled");
      },
    };

    const modalElement = document.querySelector(`#${modalId}`);
    eltModal = eltModal || new Modal(modalElement, modalOptions);
    return () => {
      // destructors... ?
    };
  });

</script>

<!-- <button 
data-modal-target="{modalId}"
data-modal-toggle="{modalId}"
class="block text-white bg-primary-700
hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300
font-medium rounded-lg text-sm px-5 py-2.5 text-center 
dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
 type="button">
  Show new
</button> -->

<div
  id={modalId}
  tabindex="-1"
  aria-hidden="true"
  class="hidden overflow-y-auto overflow-x-hidden 
  fixed top-0 right-0 left-0 z-50 justify-center items-center
  w-full md:inset-0 h-modal md:h-full {cssClass}"
>
  <div class="relative p-4 w-full max-w-md h-full md:h-auto">
    <!-- Modal content -->
    <div
      class="relative p-4 text-center bg-white rounded-lg shadow
       dark:bg-gray-800 sm:p-5"
    >
      <slot name="mws-confirm-header">
        <div class="w-full flex content-end">
          <!-- data-modal-toggle={modalId} is opening another back drop ? -->
          <button
            type="button"
            class="text-gray-400 m-5 bg-transparent
          hover:bg-gray-200 hover:text-gray-900 rounded-lg 
          text-sm p-1.5 ml-auto inline-flex items-center 
          dark:hover:bg-gray-600 dark:hover:text-white"
            on:click={() => eltModal.hide()}
          >
            <svg
              aria-hidden="true"
              class="w-5 h-5"
              fill="currentColor"
              viewBox="0 0 20 20"
              xmlns="http://www.w3.org/2000/svg"
              ><path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              /></svg
            >
            <span class="sr-only">Close modal</span>
          </button>
        </div>
      </slot>
      <slot>
        <!-- <svg
          class="text-gray-400 dark:text-gray-500 w-11 h-11 mb-3.5 mx-auto"
          aria-hidden="true"
          fill="currentColor"
          viewBox="0 0 20 20"
          xmlns="http://www.w3.org/2000/svg"
          ><path
            fill-rule="evenodd"
            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
            clip-rule="evenodd"
          /></svg
        > -->
        <p class="mb-4 text-gray-500 text-lg font-extrabold dark:text-gray-300">
          <!-- Do you want to update or add new item? -->
          Voulez-vous renommer ou ajouter une qualification de [ {qualif?.label ??
            ""} ] vers [ {newName} ] ?
        </p>
      </slot>
      <slot name="mws-confirm-actions">
        <div class="flex justify-center items-center space-x-4">
          <!-- data-modal-toggle={modalId} -->
          <button
            on:click={() => eltModal.hide()}
            type="button"
            class="py-2 px-3 text-sm font-medium text-gray-500
            bg-white rounded-lg border border-gray-200 
            hover:bg-gray-100 focus:ring-4 focus:outline-none
              focus:ring-primary-300 hover:text-gray-900 
              focus:z-10 dark:bg-gray-700 dark:text-gray-300
              dark:border-gray-500 dark:hover:text-white
                dark:hover:bg-gray-600 dark:focus:ring-gray-600"
          >
            Non, annuler
          </button>
          <button
            type="submit"
            class="py-2 px-3 text-sm font-medium text-center 
            text-white bg-red-600 rounded-lg hover:bg-red-700 
            focus:ring-4 focus:outline-none focus:ring-red-300
            dark:bg-red-500 dark:hover:bg-red-600 flex flex-wrap
            dark:focus:ring-red-900"
            style="--mws-primary-rgb: 255, 0, 0"
            on:click={async () => {
              eltModal?.hide();
              // qualif.id = null; // Nop, not on self, will be added
              // qualif.label = newName;
              await syncQualifWithBackend({
                ...qualif,
                id: null,
                label: newName,
                _isNewId: true,
              });
            }}
          >
            <svg
              class="w-full h-7 text-gray-800 dark:text-white"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              fill="none"
              viewBox="0 0 24 24"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 7.757v8.486M7.757 12h8.486M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
              />
            </svg>
            Ajouter une qualif
          </button>
          <button
            type="submit"
            class="py-2 px-3 text-sm font-medium text-center 
            text-white bg-red-600 rounded-lg hover:bg-red-700 
            focus:ring-4 focus:outline-none focus:ring-red-300
            dark:bg-red-500 dark:hover:bg-red-600 flex flex-wrap
            dark:focus:ring-red-900"
            style="--mws-primary-rgb: 255, 0, 0"
            on:click={async () => {
              eltModal?.hide();
              qualif.label = newName;
              await syncQualifWithBackend(qualif);
            }}
          >
            <svg
              class="w-full h-7 text-gray-800 dark:text-white"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              fill="none"
              viewBox="0 0 24 24"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"
              />
            </svg>
            Renommer la qualif {qualif?.label ?? ""}
          </button>
        </div>
      </slot>
    </div>
  </div>
</div>
