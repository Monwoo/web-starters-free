<!-- <svelte:options
	customElement={{
		tag: 'mws-message-add-modal',
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
    import _ from "lodash";
    import { Modal } from "flowbite";
    import Routing from "fos-router";
    //   import { Modal } from 'flowbite-svelte'; // TODO : still having issue with typescript...
    const UID = newUniqueId();
    export let addMessageForm;
    export let modalId = `msgAddModal-${UID}`;
    export let isOpen = false;
    // export let opener;
    // export let locale;
    export let eltModal;

    export let surveyModel = {};
    export let sourceDetailView; // HTML view of source to help fill form....
    export let showSourceDetail = true;

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
            },
            onShow: () => {
                // TIPS : force refresh, since public accessor
                // update the value but do not trigger reactive refresh...
                // (since set surveyModel.data inside it instead of re-assign)
                // surveyModel = {...surveyModel}; // PB with survey js that will miss auto refresh then....
                surveyModel = surveyModel; // TODO : no effect right ? no change no update ? better use writable design patterns...
                console.log("modal is shown");
            },
            onToggle: () => {
                console.log("modal has been toggled");
            },
        };

        setTimeout(() => {
            // TODO : wait for surveyJs on load event instead of empiric timings
            const jQuery = window.$; // TODO : no missing 'window' with SF controllers ways ?

            // TIPS : surveyWrapper.data("surveyModel") hold model instance, should be keep for reactivity with SurveyJs...            
            let surveyWrapper = jQuery(".survey-js-wrapper", htmlModalRoot);
            // surveyModel = {
            //     ...surveyWrapper.data("surveyModel"),
            //     ...surveyModel
            // };
            surveyModel = _.merge(surveyWrapper.data("surveyModel"), surveyModel);

            console.debug("Add Modal onMount surveyModel : ", surveyModel);
        }, 400);

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
                    {surveyModel?.data?.destId ? "Edit Message" : "Add Message"}
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
            {#if sourceDetailView}
                <button
                on:click={() => (showSourceDetail = !showSourceDetail)}
                type="button"
                class="text-gray-500 bg-white hover:bg-gray-100
                fixed z-20 right-0 top-[10dvh]"
                >{showSourceDetail
                    ? "Cacher"
                    : "Voir"}
                </button>
            {/if}

            {#if sourceDetailView &&
                sourceDetailView.length &&
                showSourceDetail}
                <div
                    class="pt-12 p-6 bg-sky-200 overflow-scroll rounded-md space-y-6 fixed z-10 right-0 w-[30dvw] h-[60dvh] top-[10dvh]"
                >
                    <div>
                        {@html sourceDetailView}
                    </div>
                </div>
            {/if}
            <!-- Modal body -->
            <div class="p-1 space-y-6"
            class:mr-[25dvw]={sourceDetailView &&
                sourceDetailView.length &&
                showSourceDetail}>
                {@html addMessageForm}
            </div>
            <!-- Modal footer -->
            <div
                class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600"
            >
                <!-- <button
                    on:click={addMessage}
                    type="button"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                    >Ajouter</button
                > // NO need, surveyJs submit will do the job -->
                <button
                    on:click={eltModal?.hide()}
                    type="button"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                    >Annuler</button
                >

                {#if sourceDetailView && sourceDetailView.length}
                    <button
                        on:click={() => (showSourceDetail = !showSourceDetail)}
                        type="button"
                        class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                        >{showSourceDetail
                            ? "Cacher les détails"
                            : "Voir les détails"}</button
                    >
                {/if}
            </div>
        </div>
    </div>
</div>
