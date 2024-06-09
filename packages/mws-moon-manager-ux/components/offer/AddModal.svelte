<!-- <svelte:options
	customElement={{
		tag: 'mws-offer-add-modal',
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
    import _ from "lodash";
    import Routing from "fos-router";
    import Loader from "../layout/widgets/Loader.svelte";
    const UID = newUniqueId();
    export let mwsAddOfferForm;
    export let modalId = `offerAddModal-${UID}`;
    export let isOpen = false;
    export let eltModal;

    export let surveyModel = {};
    export let sourceDetailView; // HTML view of source to help fill form....
    export let showSourceDetail = true;
    export let syncOfferWithBackend; // If defined, will ajax call instead of html submit refresh
    export let isLoading = false;

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
                // window.jQuery('html, body, main').scrollTop( 0 );
                window.jQuery(".mws-add-modal").scrollTop(0);
                // surveyModel = {...surveyModel};
                surveyModel = surveyModel;
                console.log("modal is shown");
            },
            onToggle: () => {
                console.log("modal has been toggled");
            },
        };

        setTimeout(() => {
            // TODO : wait for surveyJs on load event instead of empiric timings
            const jQuery = window.$; // TODO : no missing 'window' with SF controllers ways ?
            let surveyWrapper = jQuery(".survey-js-wrapper", htmlModalRoot);
            // surveyModel = {
            //     ...surveyWrapper.data("surveyModel"),
            //     ...surveyModel
            // };

            // TIPS : surveyWrapper.data("surveyModel") hold model instance, should be keep for reactivity with SurveyJs...            
            surveyModel = _.merge(surveyWrapper.data("surveyModel"), surveyModel);
            console.debug("Add Modal onMount surveyModel : ", surveyModel);
            if (syncOfferWithBackend) {
                jQuery('.mws-offer-add-modal form[name="mws_survey_js"]').on(
                    "submit",
                    async (e) => {
                        isLoading = true;
                        console.debug('Did submit add offer :', e, surveyModel.data);
                        e.stopPropagation();
                        e.preventDefault();
                        // const offer = surveyModel.dataTransformer
                        // ? surveyModel.dataTransformer(surveyModel.data) : surveyModel.data;

                        // TODO : convert back to offer format or do it server side
                        // since server drive json survey model ? => detect 2 data model, to reduce in same classique db offer model...
                        const offer = surveyModel.data;

                        await syncOfferWithBackend(offer, (offer) => {
                            // Clean and close popup :
                            surveyModel.data = null;
                            surveyModel.clear();
                            eltModal.hide();
                        }, (offer, error) => {
                            surveyModel.clear();
                            // TIPS : nothing updated if error, below useless ? :
                            // https://vscode.dev/github/Monwoo/web-starters-free/blob/main/packages/mws-moon-manager-ux/components/offer/EditOfferTrigger.svelte#L84
                            // => useful since previous 'clear' did empty form...
                            surveyModel.data = offer;
                        });
                        isLoading = false;
                    }
                );
            }
        }, 400); // TODO : timeout for quick hack, find right event position for this code to run instead...

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
    class="mws-offer-add-modal fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden
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
                    {surveyModel?.data?.id
                        ? "Éditer l'offre " + surveyModel?.data?.id
                        : "Ajouter une offre"}
                </h3>
                <Loader {isLoading} />
                <Loader class="fixed top-4 right-4" {isLoading} />
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
            {#if sourceDetailView && sourceDetailView.length}
                <button
                    on:click={() => (showSourceDetail = !showSourceDetail)}
                    type="button"
                    class="text-gray-500 bg-white hover:bg-gray-100
                fixed z-20 right-0 top-[10dvh]"
                    >{showSourceDetail ? "Cacher" : "Voir"}
                </button>
            {/if}

            {#if sourceDetailView && sourceDetailView.length && showSourceDetail}
                <div
                    class="pt-12 p-6 bg-sky-200 overflow-scroll rounded-md space-y-6 fixed z-10 right-0 w-[30dvw] h-[60dvh] top-[10dvh]"
                >
                    <div>
                        {@html sourceDetailView}
                    </div>
                </div>
            {/if}
            <!-- Modal body -->
            <div
                class="p-1 space-y-6 text-black text-left"
                class:mr-[25dvw]={sourceDetailView &&
                    sourceDetailView.length &&
                    showSourceDetail}
            >
                {@html mwsAddOfferForm}
            </div>
            <!-- Modal footer -->
            <div
                class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600"
            >
                <button
                    on:click={eltModal?.hide()}
                    type="button"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                >
                    Annuler
                </button>

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
                <Loader {isLoading} />
            </div>
        </div>
    </div>
</div>
