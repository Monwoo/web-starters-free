<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // inspired from https://flowbite.com/docs/components/navbar/
  // TIPS : use paied version of flowbite ?
  import Routing from "fos-router";
  import { state } from "../../stores/reduxStorage.mjs";

  export let locale;
  export let viewTemplate;
  export let inlineOpener = false;
  // let inlineOpener = false;

  // TODO : hide logout if not logged... cf user in redux store ?

  // TODO : assets routes from fos-router ?
  const baseHref = window && window.baseHref;
  // TODO : from DB configs ?
  const crmLogo = `${baseHref}/bundles/moonmanager/medias/MoonManagerLogo.png`;
  console.debug("inlineOpener", inlineOpener);
</script>

<!-- <nav class="bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-700"> -->
<nav class="border-gray-200 dark:border-gray-700 w-full">
  <div
    class="overflow-scroll max-h-[100vh] flex flex-wrap items-center justify-between mx-auto"
    class:md:p-4={!inlineOpener}
  >
    <span class="hidden" class:md:inline={!inlineOpener}>
      <a
        href={Routing.generate("mws_offer_lookup", {
          _locale: locale ?? "fr",
          viewTemplate: viewTemplate ?? null,
        })}
        class="flex flex-wrap items-center pb-2"
      >
        <img src={crmLogo} class="h-8 mr-3" alt="Flowbite Logo" />
        <span
          class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white"
        >
          <button class="p-1">Rechercher une offre</button>
        </span>
      </a>
      <span>
        <slot />
      </span>
    </span>
    <!-- focus:ring-2
    focus:ring-gray-200
    dark:focus:ring-gray-600 -->
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
        class="flex flex-wrap flex-col font-medium p-4 mt-4 border
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
              <img src={crmLogo} class="h-8 mr-3" alt="Flowbite Logo" />
              <span
                class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white"
              >
                <button class="p-1">Rechercher une offre</button>
              </span>
            </a>
            <span>
              <slot />
            </span>
          </span>
        </li>
        <li>
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
            <button class="m-auto">Accueil</button>
          </a>
        </li>
        <li>
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
        <li>
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
        <li>
          <button
            id="dropdownNavbarLink"
            data-dropdown-toggle="dropdownNavbar"
            class="flex items-center justify-between
            w-full
            {!inlineOpener ? `md:border-0 md:w-auto` : ``}"
            >Paramètres <svg
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
          <!-- Dropdown menu -->
          <div
            id="dropdownNavbar"
            class="z-50 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600"
          >
            <ul
              class="py-2 text-sm text-gray-700 dark:text-gray-400"
              aria-labelledby="dropdownLargeButton"
            >
              <!-- // TODO isAdmin pre=computed field from db ? $state.user.roles?.includes("ROLE_MWS_ADMIN") + secu server side... -->
              {#if $state.user?.roles?.includes("ROLE_MWS_ADMIN")}
                <li>
                  <a
                    href={Routing.generate("mws_user_list", {
                      _locale: locale ?? "fr",
                    })}
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    Users
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
                  Offers Tags</a
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
                  Tchat Medias</a
                >
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
            <div class="py-1 text-black">
              {#if $state.user}
                <div>
                  <!-- [ { JSON.stringify($state.user.roles) }] -->
                  {$state.user.roles?.includes("ROLE_MWS_ADMIN") ? "[ Admin ]" : ""}
                  {$state.user.userIdentifier}
                </div>
                <a
                  href={Routing.generate("mws_user_logout", {
                    _locale: locale ?? "fr",
                  })}
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white"
                  >Logout out</a
                >
              {/if}
              <div>
                [ {$state.packageName} v-{$state.packageVersion} ]
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>
