<script lang="ts">
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import { onMount } from "svelte";
    import newUniqueId from "locally-unique-id-generator";
    import { tick } from "svelte";
    import { Modal } from "flowbite";
    //   import { Modal } from 'flowbite-svelte'; // TODO : still having issue with typescript...
    const UID = newUniqueId();
    export let modalId = `offerFunnelModal-${UID}`;
    export let isOpen = false;
    export let opener;
    export let funnelModal;

    $: {
        if (isOpen) {
            funnelModal.show();
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
                console.log("modal is shown");
            },
            onToggle: () => {
                console.log("modal has been toggled");
            },
        };
        const modalElement = document.querySelector(`#${modalId}`);

        funnelModal = funnelModal || new Modal(modalElement, modalOptions);
        return;
        // TODO : below dead code to clean after knowledge review
        await tick();
        if (isOpen) {
            // opener.click();
            // https://stackoverflow.com/questions/18059860/manually-trigger-touch-event
            // var e = document.createEvent('MouseEvent');
            // e.initMouseEvent("mousestart", true, true, window, 1, screenX, screenY, clientX, clientY,
            //     ctrlKey, altKey, shiftKey, metaKey, button, relatedTarget);
            // e.initMouseEvent("mousestart");
            // e.initMouseEvent("touchstart");
            // https://stackoverflow.com/questions/27480049/custom-swipe-event-indicated-by-touchmove-event
            // const e = document.createEvent('TouchEvent');
            // e.initTouchEvent("touchend");
            // opener.dispatchEvent(e);
            // https://stackoverflow.com/questions/18059860/manually-trigger-touch-event
            // const e = new Event('touchend');
            // opener.dispatchEvent(e);

            // TODO : direct click from Svelte not working, need to use Jquery for quick hack :
            // opener.click();
            const $ = window.$;
            // $(() => {
            //     $(`[data-modal-target="${modalId}"]`).click()
            // })
            setTimeout(() => {
                const btn = $(`[data-modal-target="${modalId}"]`);
                console.log(btn);
                btn.click();
                // btn[0].click();
            }, 300);
        }
    });

</script>

<!-- // TODO : should be doable with stuff like :
<div id="defaultModal" tabindex="-1" aria-hidden={!isOpen ? "true" : null}
aria-modal={isOpen ? "true" : null}
class:hidden={!isOpen}
class:flex={isOpen}
role={isOpen ? "dialog" : null}
class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden
overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
// BUT will break next click events etc... so keep it with id...

// TIPS : OK if using js side of flowbite as import and :
    on:click={modal.show()}
    data-modal-target={modalId}
// instead of 
    data-modal-toggle={modalId}

-->

<button
    bind:this={opener}
    on:click={funnelModal?.show()}
    class="block text-white bg-blue-700 hover:bg-blue-800
focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium
rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600
dark:hover:bg-blue-700 dark:focus:ring-blue-800"
    type="button"
>
    Toggle modal
</button>

<!-- TIPS : only to init flowbite component, need some 
        data-modal-target={modalId} to init the modal...
    BUT DO AVOID it you use self components mountings,
    otherwise some configs might get overwritten by global flowbite js main import

    same for hide :
        on:click={funnelModal?.hide()}
    Instead of
        data-modal-hide={modalId}

-->
<!-- <div data-modal-target={modalId}>
</div> -->

<!-- Main modal -->
<div
    id={modalId}
    tabindex="-1"
    aria-hidden="true"
    class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden
overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full"
>
    <div class="relative w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div
                class="flex items-start justify-between p-4 border-b rounded-t dark:border-gray-600"
            >
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Terms of Service
                </h3>
                <button
                    type="button"
                    class="text-gray-400 bg-transparent
                    hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8
                    h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600
                    dark:hover:text-white"
                    on:click={funnelModal?.hide()}
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
            <div class="p-6 space-y-6">
                <p
                    class="text-base leading-relaxed text-gray-500 dark:text-gray-400"
                >
                    With less than a month to go before the European Union
                    enacts new consumer privacy laws for its citizens, companies
                    around the world are updating their terms of service
                    agreements to comply.
                </p>
                <p
                    class="text-base leading-relaxed text-gray-500 dark:text-gray-400"
                >
                    The European Union’s General Data Protection Regulation
                    (G.D.P.R.) goes into effect on May 25 and is meant to ensure
                    a common set of data rights in the European Union. It
                    requires organizations to notify users as soon as possible
                    of high-risk data breaches that could personally affect
                    them.
                </p>
            </div>
            <!-- Modal footer -->
            <div
                class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600"
            >
                <button
                    on:click={funnelModal?.hide()}
                    type="button"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                    >I accept</button
                >
                <button
                    on:click={funnelModal?.hide()}
                    type="button"
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                    >Decline</button
                >
            </div>
        </div>
    </div>
</div>
