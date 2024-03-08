<!-- <svelte:options
	customElement={{
		tag: 'mws-timing-tag-add-modal',
		// shadow: 'none',
		// props: {
		// 	name: { reflect: true, type: 'Number', attribute: 'element-index' }
		// },
    }}
/> -->
<svelte:options accessors />

<script lang="ts">
    // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import { onMount } from "svelte";
    import newUniqueId from "locally-unique-id-generator";
    import { tick } from "svelte";
    import { Modal } from "flowbite";
    import Routing from "fos-router";
    import {
        state,
        stateGet,
        stateUpdate,
    } from "../../../stores/reduxStorage.mjs";
    import { get } from "svelte/store";

    //   import { Modal } from 'flowbite-svelte'; // TODO : still having issue with typescript...
    const UID = newUniqueId();
    export let addMessageForm;
    export let modalId = `msgAddModal-${UID}`;
    export let isOpen = false;
    // export let opener;
    // export let locale;
    export let eltModal;

    export let surveyModel;
    export let sourceDetailView; // HTML view of source to help fill form....
    export let showSourceDetail = true;
    export let surveyJsHtmlWrapper;
    export let surveyData = null;
    export let allTags = null;
    export let locale;

    let htmlModalRoot;

    $: {
        if (isOpen) {
            eltModal.show();
        }
    }

    $: allTagsAsSurveyJsChoices = allTags.map((t) => ({
        text: t.self.label,
        // value: { slug: t.self.slug, id: t.self.id },
        value: t.self.slug,
        // value: t.self,
    }));

    export let updateTimingTag = async (timeTag) => {
        const $ = window.$;
        const modalBtn = $(`[data-modal-target="${modalId}"]`);
        console.log(modalBtn);
        modalBtn.click();

        const data = {
            _csrf_token: stateGet(get(state), "csrfTimingTagUpdate"),
            timeTag: JSON.stringify(timeTag),
        };
        const formData = new FormData();
        for (const name in data) {
            formData.append(name, data[name]);
        }
        const resp = await fetch(
            Routing.generate("mws_timing_tag_update", {
                _locale: locale,
            }),
            {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                redirect: "error",
            }
        )
            .then(async (resp) => {
                console.log(resp);
                if (!resp.ok) {
                    throw new Error("Not 2xx response", { cause: resp });
                } else {
                    const data = await resp.json();
                    console.debug("Did update tag", data);
                    window.location.reload();

                    stateUpdate(state, {
                        csrfTimingTagUpdate: data.newCsrf,
                    });
                }
            })
            .catch((e) => {
                console.error(e);
                // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
                const shouldWait = confirm("Echec de l'enregistrement.");
                addedTagKey = "null";
            });
    };

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
            // let surveyWrapper = jQuery(".survey-js-wrapper", htmlModalRoot);
            // surveyModel = surveyWrapper.data("surveyModel");
            const surveyDataModel = {
                description: "Ajouter",
                fitToContainer: true,
                completeText: {
                    en: "Save tag",
                    fr: "Enregistrer le tag",
                },
                focusFirstQuestionAutomatic: false,
                showQuestionNumbers: false,
                elements: [
                    {
                        name: "slug",
                        title: "Slug du tag",
                        type: "text",
                    },
                    {
                        name: "label",
                        title: "Libellé du tag",
                        type: "text",
                    },
                    {
                        name: "description",
                        title: "Description du tag",
                        type: "comment",
                    },
                    {
                        name: "category",
                        title: "Category du tag",
                        type: "dropdown",
                        showNoneItem: false,
                        showOtherItem: false,
                        choices: allTagsAsSurveyJsChoices,
                        // valueName: "{panel.self}",
                        // titleName: "{panel.label}",
                        // valueName: "panel.self.label",
                        // valueName: "panel.self.label",
                        path: "self",
                        titleName: "title",
                        searchEnabled: false,
                        validators: [],
                        minWidth: "20%",
                        isRequired: false,
                    },
                    {
                        name: "pricePerHr",
                        title: "Prix par heure",
                        type: "text",
                    },
                    {
                        type: "paneldynamic",
                        minPanelCount: 0,
                        name: "pricePerHrRules",
                        title: "Régles de prix par heure",
                        panelAddText: "Ajouter une règle",
                        panelCount: 0,
                        templateElements: [
                            {
                                name: "price",
                                title: "Prix par heure",
                                type: "text",
                            },
                            {
                                name: "maxLimitPriority",
                                title: "Priorité de la limite max",
                                type: "text",
                            },
                            {
                                name: "withTags",
                                title: "Tags",
                                description: "Ajouter des tags à filtrer.",
                                type: "tagbox",
                                // valueName: "self",
                                // labelName: "self.label",
                                choices: allTagsAsSurveyJsChoices,
                                validators: [],
                                minWidth: "20%",
                                isRequired: false,
                            },
                        ],
                    },
                ],
            };

            surveyModel = new Survey.Model(surveyDataModel);
            surveyModel.locale = "fr";
            surveyModel.showCompletedPage = false;
            surveyModel.onComplete.add(async (sender, options) => {
                // const responseData = JSON.stringify(sender.data, null, 3);
                const responseData = sender.data;
                await updateTimingTag(responseData);
                console.log("Will sync response :", responseData);
                // surveyDataInput.val(encodeURIComponent(responseData));
                // surveyForm.submit();
                console.debug("Add Modal onMount surveyModel : ", surveyModel);
                eltModal?.hide();
                surveyModel.data = null;
                surveyModel.clear();
            });
            surveyModel.data = surveyData;
            // let surveyWrapper = $(".survey-js-wrapper", surveyForm);
            let surveyWrapper = jQuery(surveyJsHtmlWrapper);
            surveyWrapper.Survey({
                model: surveyModel,
            });
        }, 500);

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
                    {surveyModel?.data.destId ? "Edit Message" : "Add Message"}
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
            {#if sourceDetailView &&
                sourceDetailView.length &&
                showSourceDetail}
                <div
                    class="p-6 bg-sky-200 overflow-scroll rounded-md space-y-6 fixed z-10 right-0 w-[35vw] h-[60vh] top-[10vh]"
                >
                    {@html sourceDetailView}
                </div>
            {/if}
            <!-- Modal body -->
            <div
                class="p-1 space-y-6"
                class:mr-[25vw]={sourceDetailView &&
                    sourceDetailView.length &&
                    showSourceDetail}
            >
                <div bind:this={surveyJsHtmlWrapper} />
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
