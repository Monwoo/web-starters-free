<!-- <svelte:options
	customElement={{
		tag: 'mws-import-report-modal',
		// shadow: 'none',
		// props: {
		// 	name: { reflect: true, type: 'Number', attribute: 'element-index' }
		// },
    }}
/> -->
<svelte:options accessors />

<script lang="ts">
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import { onMount } from "svelte";
    import newUniqueId from "locally-unique-id-generator";
    import { tick } from "svelte";
    import { Modal } from "flowbite";
    import Routing from "fos-router";
    //   import { Modal } from 'flowbite-svelte'; // TODO : still having issue with typescript...
    const UID = newUniqueId();
    export let modalId = `importReportModal-${UID}`;
    export let isOpen = false;
    // export let opener;
    // export let locale;
    export let eltModal;

    export let htmlReport;

    let htmlModalRoot;

    $: {
        if (isOpen) {
            eltModal.show();
        }
    }

    onMount(async () => {
        const modalOptions = {
            // placement: "bottom-right",
            backdrop: "dynamic",
            backdropClasses:
                "bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40",
            onHide: () => {
                console.log("modal is hidden");
                window.location.reload();
            },
            onShow: () => {
                // TIPS : force refresh, since public accessor
                // update the value but do not trigger reactive refresh...
                // (since set htmlReport.data inside it instead of re-assign)
                // surveyModel = surveyModel;
                console.log("modal is shown");
            },
            onToggle: () => {
                console.log("modal has been toggled");
            },
        };

        const modalElement = document.querySelector(`#${modalId}`);

        eltModal = eltModal || new Modal(modalElement, modalOptions);
        return;
    });

</script>

<div
    bind:this={htmlModalRoot}
    id={modalId}
    tabindex="-1"
    aria-hidden="true"
    class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden
overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full mws-add-modal"
>
    <div class="relative w-full max-w-full max-h-full m-3">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal headder -->
            <div
                class="flex items-start justify-between p-4 border-b rounded-t dark:border-gray-600"
            >
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {"Rapport d'importation"}
                </h3>
                <button
                    type="button"
                    class="text-gray-400 bg-transparent
                    hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8
                    h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600
                    dark:hover:text-white"
                    on:click={eltModal?.hide()}
                >
                    <svg
                        class="w-3 h-3"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 14 14"
                    >
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"
                        />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class='p-7'>
              {@html htmlReport}
            </div>
            <!-- Modal body -->
            <!-- Modal footer -->
            <div
                class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600"
            >
                <button
                    on:click={eltModal?.hide()}
                    type="button"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                    >Fermer</button
                >
            </div>
        </div>
    </div>
</div>
