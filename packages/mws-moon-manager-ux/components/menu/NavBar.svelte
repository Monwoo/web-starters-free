<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // inspired from https://flowbite.com/docs/components/navbar/
  // TIPS : use paied version of flowbite ?
  import Routing from "fos-router";

  export let locale;
  export let viewTemplate;

  // TODO : assets routes from fos-router ?
  const baseHref = window && window.baseHref;
  // TODO : from DB configs ?
  const crmLogo = `${baseHref}/bundles/moonmanager/medias/MoonManagerLogo.png`;
</script>

<!-- <nav class="bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-700"> -->
<nav class="border-gray-200 dark:border-gray-700">
  <div
    class="overflow-scroll max-h-[100vh] flex flex-wrap items-center justify-between mx-auto md:p-4"
  >
    <span class="hidden md:inline">
      <a
      href={Routing.generate("mws_offer_lookup", {
        _locale: locale ?? "",
        viewTemplate: viewTemplate ?? null,
      })}
        class="flex items-center"
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

    <button
      data-collapse-toggle="navbar-dropdown"
      type="button"
      class="inline-flex items-center p-2 w-10 h-10 justify-center
      text-sm text-gray-500 rounded-lg md:hidden
       hover:bg-gray-100 focus:outline-none focus:ring-2
        focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700
         dark:focus:ring-gray-600"
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
    <div class="hidden w-full md:block md:w-auto" id="navbar-dropdown">
      <ul
        class="flex flex-col font-medium p-4 md:p-0 mt-4 border
         border-gray-100 rounded-lg
          md:flex-row md:space-x-8 md:mt-0 md:border-0
         dark:border-gray-700"
      >
        <li>
          <span class="inline md:hidden">
            <a
            href={Routing.generate("mws_offer_lookup", {
              _locale: locale ?? "",
              viewTemplate: viewTemplate ?? null,
            })}
              class="flex items-center"
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
          <a href={Routing.generate("app_home", undefined, true)} aria-current="page">
            <button>Accueil</button>
          </a>
        </li>
        <li>
          <!-- // TODO : only if app_pdf_billings route exist ? depend of targeted app.. -->
          <a
            href={Routing.generate("app_pdf_billings", {
              '_locale': locale ?? '',
            }, true)}
            class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent"
          >
            <button>Services</button>
          </a>
        </li>
        <li>
          <a
            href={Routing.generate("mws_timings_report", {
              '_locale': locale ?? '',
            }, true)}
            class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent"
          >
            <button>Timings</button>
          </a>
        </li>
        <li>
          <a
            href="#contact"
            class="block py-2 pl-3 pr-4 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 dark:text-white md:dark:hover:text-blue-500 dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent"
          >
            <button>Contact</button>
          </a>
        </li>
        <li>
          <button
            id="dropdownNavbarLink"
            data-dropdown-toggle="dropdownNavbar"
            class="flex items-center justify-between
            w-full
            md:border-0 md:w-auto"
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
            class="z-10 hidden font-normal bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600"
          >
            <ul
              class="py-2 text-sm text-gray-700 dark:text-gray-400"
              aria-labelledby="dropdownLargeButton"
            >
              <li>
                <a
                  href={Routing.generate("mws_user_list")}
                  class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  Users
                </a>
              </li>              
              <li>
                <a
                  href={Routing.generate("mws_offer_tags")}
                  class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  Offers Tags</a
                >
              </li>
              <li>
                <a
                  href={"#TODO-CalendarEventTags" || Routing.generate("mws_offer_tags")}
                  class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  Calendar Tags</a
                >
              </li>
              <li>
                <a
                  href={Routing.generate("mws_message_list")}
                  class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  Messages
                </a>
              </li>
              <li>
                <a
                  href={Routing.generate("mws_message_tchat_upload_list")}
                  class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  Tchat Medias</a
                >
              </li>
              <li>
                <a
                  href="#theming-config"
                  class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  Themings</a
                >
              </li>
            </ul>
            <div class="py-1">
              <a
                href={Routing.generate("mws_user_logout")}
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white"
                >Logout out</a
              >
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>
