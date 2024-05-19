<script lang="ts">
    // 🌖🌖 Copyright Monwoo 2023-2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import Routing from "fos-router";
    // TODO : namespace
    import Base from "../layout/Base.svelte";
    import DonateFunnel from "../layout/widgets/DonateFunnel.svelte";
    import GitLogsChart from "../layout/widgets/GitLogsChart.svelte";
    import Jumbotron from "../layout/widgets/Jumbotron.svelte";
    import MultiScreen from "../layout/widgets/MultiScreen.svelte";
    import Separator from "../layout/widgets/Separator.svelte";
    import Video from "../layout/widgets/Video.svelte";
    // import { goto } from "$app/navigation"; // TODO : not from encore bundle ?
    const goto = (url) => {
        window.location = url;
    };

    export let title = null;
    export let locale = null;
    export let viewTemplate = null;
    export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
    export let currentLanguage = null;
    export let billingsLocales = [];
    export let moonManagerLocales = [];
    export let affiliationCode;
    // export let moonManagerUserLocales = [
    //     {
    //         label: "Users list in english",
    //         path: Routing.generate("mws_user", {
    //             _locale: "en",
    //         }),
    //     },
    //     {
    //         label: "Liste des utilisateurs en anglais",
    //         path: Routing.generate("mws_user", {
    //             _locale: "fr",
    //         }),
    //     },
    // ];
    const baseHref = window && window.baseHref;
</script>

<svelte:head>
    <title>
        Accueil {affiliationCode ? `via ${affiliationCode}` : ``}
    </title>
</svelte:head>

<Base {copyright} {locale} {viewTemplate}>
    <!-- {currentLanguage ?? ""} -->
    <!-- <h1>{ title ?? "" }</h1> -->
    <div class="flex flex-wrap items-center justify-center content-center">
        <h1
            class="text-gray-900 dark:text-white text-3xl md:text-5xl font-extrabold m-2"
        >
            Monwoo Web Starter - PDF Billings
        </h1>
        <Separator />
        <div class="w-full flex flex-wrap">
            <MultiScreen
                extraClass="hover:cursor-pointer hover:shadow-lg  hover:bg-black/50"
                on:click={() => {
                    goto(
                        Routing.generate("app_pdf_billings", {
                            _locale: locale ?? "fr",
                        })
                    );
                }}
                capture={{
                    title: "Facturation PDF",
                    mobile: `${baseHref}/bundles/moonmanager/medias/home/pdf-billings-mobile.jpg`,
                }}
            />
            <MultiScreen
                extraClass="hover:cursor-pointer hover:shadow-lg  hover:bg-black/50"
                on:click={() => {
                    goto(
                        Routing.generate("mws_offer_lookup", {
                            _locale: locale ?? "fr",
                            viewTemplate: viewTemplate ?? null,
                        })
                    );
                }}
                capture={{
                    title: "Recherche d'offres",
                    mobile: `${baseHref}/bundles/moonmanager/medias/home/offers-lookup-mobile.jpg`,
                }}
            />
            <MultiScreen
                extraClass="hover:cursor-pointer hover:shadow-lg  hover:bg-black/50"
                on:click={() => {
                    goto(
                        Routing.generate("mws_timings_report", {
                            _locale: locale ?? "fr",
                        })
                    );
                }}
                capture={{
                    title: "Suivi des temps",
                    mobile: `${baseHref}/bundles/moonmanager/medias/home/timings-report-mobile.jpg`,
                }}
            />
            <MultiScreen
                extraClass="hover:cursor-pointer hover:shadow-lg  hover:bg-black/50"
                on:click={() => {
                    goto(
                        Routing.generate("app_pdf_billings", {
                            _locale: locale ?? "fr",
                        })
                    );
                }}
                capture={{
                    title: "Facturation PDF",
                    desktop: `${baseHref}/bundles/moonmanager/medias/home/pdf-billings-laptop.jpg`,
                }}
            />
            <MultiScreen
                extraClass="hover:cursor-pointer hover:shadow-lg  hover:bg-black/50"
                on:click={() => {
                    goto(
                        Routing.generate("mws_offer_lookup", {
                            _locale: locale ?? "fr",
                            viewTemplate: viewTemplate ?? null,
                        })
                    );
                }}
                capture={{
                    title: "Recherche d'offres",
                    desktop: `${baseHref}/bundles/moonmanager/medias/home/offers-lookup-laptop.jpg`,
                }}
            />
            <MultiScreen
                extraClass="hover:cursor-pointer hover:shadow-lg  hover:bg-black/50"
                on:click={() => {
                    goto(
                        Routing.generate("mws_timings_report", {
                            _locale: locale ?? "fr",
                        })
                    );
                }}
                capture={{
                    title: "Suivi des temps",
                    desktop: `${baseHref}/bundles/moonmanager/medias/home/timings-report-laptop.jpg`,
                }}
            />
        </div>
        <Separator />
        <DonateFunnel bind:affiliationCode />
        <Separator />

        <Jumbotron
            title="Facturation PDF"
            content="Devis public pré-remplissable par un tiers."
        />
        <div class="w-full flex justify-center items-center">
            <a
                href={Routing.generate(
                    "app_pdf_billings",
                    {
                        _locale: locale ?? "fr",
                    },
                    true
                )}
                class="
            text-lg font-medium inline-flex items-center
            px-4 pt-6 pb-12"
            >
                <button>Services</button>
            </a>
        </div>
        <!-- // TODO démo vidéo per domains ? -->
        <!-- <Video class="max-w-[80%]" youtubeId="pGW_DdicUW4" /> -->

        <Jumbotron title="Recherche d'offres" />
        <div class="w-full flex justify-center items-center">
            <a
                href={Routing.generate("mws_offer_lookup", {
                    _locale: locale ?? "fr",
                    viewTemplate: viewTemplate ?? null,
                })}
                class="
            text-lg font-medium inline-flex items-center
            px-4 pt-6 pb-12"
            >
                <button>Rechercher une offre</button>
            </a>
        </div>
        <!-- <Video class="max-w-[80%]" youtubeId="pGW_DdicUW4" /> -->

        <Jumbotron title="Suivi des temps" />
        <div class="w-full flex flex-wrap justify-center items-center">
            <a
                href={Routing.generate("mws_timings_report", {
                    _locale: locale ?? "fr",
                })}
                class="
            text-lg font-medium inline-flex items-center
            px-4 pt-6 pb-4"
            >
                <button>Timings</button>
            </a>

            <a
                class="
            text-lg font-medium inline-flex items-center
            px-4 pt-6 pb-4"
                href={Routing.generate("mws_timings_qualif", {
                    _locale: locale ?? "fr",
                })}
            >
                <button> Qualification des temps </button>
            </a>
            <a
                class="
          text-lg font-medium inline-flex items-center
          px-4 pt-6 pb-4"
                href={Routing.generate("mws_timing_tag_list", {
                    _locale: locale ?? "fr",
                    viewTemplate: viewTemplate ?? "",
                })}
            >
                <button>Tags des temps</button>
            </a>
        </div>
        <!-- <Video class="max-w-[80%]" youtubeId="pGW_DdicUW4" /> -->

        <Separator />

        <GitLogsChart />

        <DonateFunnel bind:affiliationCode />
    </div>
    <!-- <h1>{title ?? ""}</h1>
    <div class="nav-item dropdown">
        Billings :
        <ul
            class="dropdown-menu dropdown-menu-right"
            aria-labelledby="dropdown-language"
        >
            {#each billingsLocales as l}
                <li>
                    <a class="dropdown-item" href={l.path}>
                        {l.label}
                    </a>
                </li>
            {/each}
        </ul>
    </div>
    <div class="nav-item dropdown">
        Moon Manager :
        <ul
            class="dropdown-menu dropdown-menu-right"
            aria-labelledby="dropdown-language"
        >
            {#each moonManagerLocales as l}
                <li>
                    <a class="dropdown-item" href={l.path}>
                        {l.label}
                    </a>
                </li>
            {/each}
        </ul>
    </div>
    <div class="nav-item dropdown">
        Moon Manager Users :
        <ul
            class="dropdown-menu dropdown-menu-right"
            aria-labelledby="dropdown-language"
        >
            {#each moonManagerUserLocales as l}
                <li>
                    <a class="dropdown-item" href={l.path}>
                        {l.label}
                    </a>
                </li>
            {/each}
        </ul>
    </div> -->
</Base>
