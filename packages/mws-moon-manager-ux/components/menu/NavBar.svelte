<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // inspired from https://flowbite.com/docs/components/navbar/
  // TIPS : use paied version of flowbite ?
  import Routing from "fos-router";
  import { state } from "../../stores/reduxStorage.mjs";
  import { fly, scale, slide } from "svelte/transition";
  import { quintOut } from "svelte/easing";
  import { create_in_transition, tick } from "svelte/internal";
  import AddModal from "../offer/AddModal.svelte";
  // TODO : try multiple theme with other icon packages ?
  // https://svelte-svg-icons.codewithshin.com/
  import {
    CirclePlusSolid
  } from "flowbite-svelte-icons";
  import EditOfferTrigger from "../offer/EditOfferTrigger.svelte";
  export let locale;
  export let viewTemplate;
  export let inlineOpener = false;
  export let addOfferModal;

  let cssClass;
  export { cssClass as class };
  // let inlineOpener = false;
  let dropdown;
  let intro;

  // TODO : hide logout if not logged... cf user in redux store ?

  // TODO : assets routes from fos-router ?
  const baseHref = window && window.baseHref;
  // TODO : from DB configs ?
  const crmLogo = `${baseHref}/bundles/moonmanager/medias/MoonManagerLogo.png`;
  console.debug("inlineOpener", inlineOpener);

  const capitalize = (s) => (s?.charAt(0).toUpperCase() ?? '') + (s?.slice(1) ?? '');

  const appName = capitalize(window?.baseHref?.split('/').filter(s => s?.length)[0] ?? null);
  const hostSplit = window?.location.hostname.split('.');
  const appHost = (
    hostSplit?.length > 1
    ? hostSplit.slice(0, -1) ?? null
    : hostSplit
  ).join(' ');

  let uniqueKey = {};

  // TODO : domain for bg components ? inside layout instead navbar can reuse multiple times... ?
  $: $state.addOfferModal = addOfferModal;
</script>

<AddModal bind:this={addOfferModal} mwsAddOfferForm={$state.mwsAddOfferForm} />

<!-- <nav class="bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-700"> -->
<nav
  class={`border-gray-200 dark:border-gray-700 w-full
  rounded-e-lg mws-nav-bar ${cssClass ?? ''}
  `}
>
  <!-- // TIPS : max-h-[70dvh] have some meanings for md screen only since needed only if 
  // inside content size down instead of filling bottom ... -->
  <div
    class="overflow-scroll md:max-h-[80dvh]
    flex flex-wrap items-center justify-between mx-auto"
    class:md:p-2={!inlineOpener}
    class:wide:p-0={!inlineOpener}
  >
    <span class="inline-flex items-center">
      <span class="hidden" class:md:inline={!inlineOpener}>
        <a
          href={Routing.generate("mws_offer_lookup", {
            _locale: locale ?? "fr",
            viewTemplate: viewTemplate ?? null,
          })}
          class="flex items-center pb-2 mws-list-offers"
        >
          <img src={crmLogo} class="h-8 mr-3" alt="Flowbite Logo" />
          <span
            class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white"
          >
            <button class="inline-flex flex-wrap justify-center items-center w-min px-4 mws-search-offers">
              <span class="w-full text-sm wide:text-xs">
                Rechercher une offre
              </span>
              <span class="w-full text-[0.69rem] leading-[0.69rem] text-gray-300">
                sur : 
                <span class="">
                  { appName } { appHost }
                </span>
              </span>
            </button>
          </span>
        </a>
        <span>
          <slot />
        </span>
      </span>
      <span>
        <!-- // TODO : else close ? lighter webservice open to add offers ONLY from outside ? (add email user field to track non connected user source as hints...) -->
        {#if $state.user}
          <EditOfferTrigger
            class='mws-add-offer'
            syncOfferOk={async (o) => {
              alert(`Ajout de l'offre ok pour ${o.clientUsername}. [${o.slug}]`)
            }}
          >
            <CirclePlusSolid class="text-2xl" />        
          </EditOfferTrigger>
        {/if}
      </span>
    </span>
    <!-- focus:ring-2
    focus:ring-gray-200
    dark:focus:ring-gray-600 -->
    <span
      class="max-w-full md:hidden px-3 text-xs
    whitespace-nowrap overflow-hidden text-ellipsis"
    >
      <span class="">
        { appName } { appHost }
      </span>
      {$state.user?.userIdentifier ? ` | ` : ``}
      {$state.user?.userIdentifier ?? ``}
    </span>
    <button
      data-collapse-toggle="navbar-dropdown"
      type="button"
      class="inline-flex items-center p-2 w-10 h-10 justify-center
      text-sm text-gray-500 rounded-lg
       hover:bg-gray-100 hover:text-black focus:outline-none
        dark:text-gray-400 dark:hover:bg-gray-700
         "
      class:md:hidden={!inlineOpener}
      aria-controls="navbar-dropdown"
      aria-expanded="false"
    >
      <span class="sr-only">Open main menu</span>
      <svg
        class="w-5 h-5"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 17 14"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M1 1h15M1 7h15M1 13h15"
        />
      </svg>
    </button>
    <!-- class:hidden={!inlineOpener} -->
    <div
      class="hidden w-full"
      id="navbar-dropdown"
      class:md:w-auto={!inlineOpener}
      class:md:block={!inlineOpener}
    >
      <ul
        class="flex flex-wrap flex-col font-medium p-2 mt-0 border-0
         border-gray-100 rounded-lg
          {!inlineOpener
          ? 'md:p-0 md:flex-row md:space-x-8 md:mt-0 md:border-0'
          : ''}
         dark:border-gray-700"
      >
        <li>
          <span class="inline" class:md:hidden={!inlineOpener}>
            <a
              href={Routing.generate("mws_offer_lookup", {
                _locale: locale ?? "fr",
                viewTemplate: viewTemplate ?? null,
              })}
              class="flex flex-wrap items-center justify-center pb-2"
            >
              <img src={crmLogo} class="h-8 mr-3" alt="Logo" />
              <span
                class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white"
              >
                <button class="">Rechercher une offre</button>
              </span>
            </a>
            <span>
              <slot />
            </span>
          </span>
        </li>
        <li class="wide:!ml-2">
          <!-- // TIPS: SEO : fail relative link on empty locale 
          since url start with '//' => filesystemp path.. with :
          Routing.generate("app_home", undefined, true)
          Hopefully, locale is never null :
          -->
          <a
            href={Routing.generate("app_home", {
              _locale: locale ?? "fr",
            })}
            class="text-center block py-1 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100
           {!inlineOpener
              ? `md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white
           md:dark:hover:text-blue-500 md:dark:hover:bg-transparent`
              : ``}
           dark:hover:bg-gray-700 dark:hover:text-white
           "
            aria-current="page"
          >
            <button class="">Accueil</button>
          </a>
        </li>
        <li class="wide:!ml-2">
          <!-- // TODO : only if app_pdf_billings route exist ? depend of targeted app.. -->
          <a
            href={Routing.generate("app_pdf_billings", {
              _locale: locale ?? "fr",
            })}
            class="text-center block py-1 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100
            {!inlineOpener
              ? `md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white
            md:dark:hover:text-blue-500 md:dark:hover:bg-transparent`
              : ``}
            dark:hover:bg-gray-700 dark:hover:text-white
            "
          >
            <button>Services</button>
          </a>
        </li>
        <li class="wide:!ml-2">
          <a
            href={Routing.generate("mws_timings_report", {
              _locale: locale ?? "fr",
            })}
            class="text-center block py-1 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100
            {!inlineOpener
              ? `md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white
            md:dark:hover:text-blue-500 md:dark:hover:bg-transparent`
              : ``}
            dark:hover:bg-gray-700 dark:hover:text-white
            "
          >
            <button>Timings</button>
          </a>
        </li>
        <!-- <li>
          <a
            href="#contact"
            class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent"
          >
            <button>Contact</button>
          </a>
        </li> -->
        <li class="wide:!ml-2">
          <button
            id="dropdownNavbarLink"
            data-dropdown-toggle="dropdownNavbar"
            class="flex flex-wrap items-center justify-center
            w-full mt-2 md:mt-1
            {!inlineOpener ? `md:border-0 md:w-auto` : ``}"
            on:click={async () => {
              uniqueKey = {};
              if (!intro) {
                // await tick();
                // await tick();
                // intro = create_in_transition(dropdown, slide, {
                // intro = create_in_transition(dropdown, fly, {
                //   y: -200,
                //   delay: 0,
                //   duration: 1000,
                //   easing: quintOut,
                // });
                // intro = create_in_transition(dropdown, scale, {
                intro = create_in_transition(dropdown, fly, {
                  y: -200,
                  delay: 0,
                  duration: 1000,
                  // easing: quintOut,
                });
              }
              intro.start();
              intro = null; // redo animation on each expand
            }}
          >
            { ! $state.user?.userIdentifier && `Paramètres` || ''}
            
            <span
              class="max-w-full md:max-w-[7rem] px-1
              whitespace-nowrap overflow-hidden text-ellipsis"
              class:mws-user-connected={$state.user ?? false}
            >
              {$state.user?.userIdentifier ?? ``}
            </span>
            <svg
              class="w-2.5 h-2.5 ml-2.5"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 10 6"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="m1 1 4 4 4-4"
              />
            </svg>
          </button>
        </li>
      </ul>
    </div>
    <!-- Dropdown menu TODO : inline or scroll bottom if go over end page...-->
    <!-- {#key uniqueKey}
      out:slide={{
        delay: 0,
        duration: 3000,
        easing: quintOut,
        axis: "y",
      }}
  -->
    <div
      bind:this={dropdown}
      id="dropdownNavbar"
      class="z-[100] hidden
              font-normal bg-white divide-y divide-gray-100
              w-full
              !relative
              rounded-lg shadow w-44 dark:bg-gray-700
              dark:divide-gray-600"
    >
      <ul
        class="py-2 text-sm text-gray-700 dark:text-gray-400"
        aria-labelledby="dropdownLargeButton"
      >
        <!-- // TODO isAdmin pre=computed field from db ? $state.user?.roles?.includes("ROLE_MWS_ADMIN") + secu server side... -->
        {#if $state.user?.roles?.includes("ROLE_MWS_ADMIN")}
          <li>
            <a
              href={Routing.generate("mws_user_list", {
                _locale: locale ?? "fr",
              })}
              class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              Utilisateurs
            </a>
          </li>
        {/if}
        <li>
          <a
            href={Routing.generate("mws_offer_tags", {
              _locale: locale ?? "fr",
            })}
            class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            Tags des offres</a
          >
        </li>
        <!-- <li>
                  <a
                    href={"#TODO-CalendarEventTags" || Routing.generate("mws_offer_tags")}
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    Calendar Tags</a
                  >
                </li> -->
        <li>
          <a
            href={Routing.generate("mws_message_list", {
              _locale: locale ?? "fr",
            })}
            class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            Messages
          </a>
        </li>
        <li>
          <a
            href={Routing.generate("mws_message_tchat_upload_list", {
              _locale: locale ?? "fr",
            })}
            class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            Medias des tchats
          </a>
        </li>
        <li>
          <a
            href={Routing.generate("mws_timings_qualif", {
              _locale: locale ?? "fr",
            })}
            class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            Qualification des temps
          </a>
        </li>
        <li>
          <a
            href={Routing.generate("mws_timing_tag_list", {
              _locale: locale ?? "fr",
              viewTemplate: viewTemplate ?? "",
            })}
            class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            Tags des temps
          </a>
        </li>
        <!-- <li>
                  <a
                    href="#theming-config"
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    Themings</a
                  >
                </li> -->
        {#if $state.user?.roles?.includes("ROLE_MWS_ADMIN")}
          <li>
            <a
              href={Routing.generate("mws_config_backup", {
                _locale: locale ?? "fr",
              })}
              class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
            >
              Backup
            </a>
          </li>
        {/if}
      </ul>
      <div class="p-2 text-black">
        {#if $state.user}
          <div>
            <!-- [ { JSON.stringify($state.user?.roles) }] -->
            {$state.user?.roles?.includes("ROLE_MWS_ADMIN") ? "[ Admin ]" : ""}
            {$state.user?.userIdentifier}
          </div>
          <a
            href={Routing.generate("mws_user_logout", {
              _locale: locale ?? "fr",
            })}
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white"
            >Se déconnecter
          </a>
        {:else}
        <a
          href={Routing.generate("mws_user_login", {
            _locale: locale ?? "fr",
          })}
          class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white"
          >Se connecter
        </a>
        {/if}
          <div>
          [ {$state.packageName} v-{$state.packageVersion} ]
        </div>
      </div>
    </div>
    <!-- {/key} -->
  </div>
</nav>

<style lang="scss">
  // $mws-media-phone-w: 768px; // TODO : global injection from tailwind 'md' config ?
  // style="transform: none !important;"  md:!absolute
  #dropdownNavbar {
    // position: relative !important;
    // @media (max-width: $mws-media-phone-w) {
    transform: none !important;
    // }
  }
  button {
    &:hover {
      @apply opacity-80;
    }
  }
</style>
