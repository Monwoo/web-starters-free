<script>
    // 🌖🌖 Copyright Monwoo 2023-2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import Routing from "fos-router";
    // TODO : namespace
    import Base from "../layout/Base.svelte";
    import GitLogsChart from "../layout/widgets/GitLogsChart.svelte";
    import Jumbotron from "../layout/widgets/Jumbotron.svelte";
    import MultiScreen from "../layout/widgets/MultiScreen.svelte";
    import Separator from "../layout/widgets/Separator.svelte";

    export let title = null;
    export let currentLanguage = null;
    export let billingsLocales = [];
    export let moonManagerLocales = [];
    export let moonManagerUserLocales = [
        {
            label: "Users list in english",
            path: Routing.generate("mws_user", {
                _locale: "en",
            }),
        },
        {
            label: "Liste des utilisateurs en anglais",
            path: Routing.generate("mws_user", {
                _locale: "fr",
            }),
        },
    ];
</script>

<Base>
    <!-- {currentLanguage ?? ""} -->
    <!-- <h1>{ title ?? "" }</h1> -->
    <div class="flex flex-wrap">
        <h1
            class="text-gray-900 dark:text-white text-3xl md:text-5xl font-extrabold mb-2"
        >
            Monwoo Web Starter - Symfony PDF Billings
        </h1>
        <Separator />
        <div class="w-full flex flex-wrap">
            <MultiScreen
                capture={{
                    title: "Facturation PDF",
                    mobile: "https://flowbite.s3.amazonaws.com/blocks/marketing-ui/hero/mockup-2-light.png",
                }}
            />
            <MultiScreen
                capture={{
                    title: "Recherche d'offres",
                    mobile: "https://flowbite.s3.amazonaws.com/blocks/marketing-ui/hero/mockup-2-light.png",
                }}
            />
            <MultiScreen
                capture={{
                    title: "Suivi des temps",
                    mobile: "https://flowbite.s3.amazonaws.com/blocks/marketing-ui/hero/mockup-2-light.png",
                }}
            />
            <MultiScreen
                capture={{
                    title: "Facturation PDF",
                    desktop: "https://flowbite.s3.amazonaws.com/docs/device-mockups/laptop-screen.png",
                }}
            />
            <MultiScreen
                capture={{
                    title: "Recherche d'offres",
                    desktop: "https://flowbite.s3.amazonaws.com/docs/device-mockups/laptop-screen.png",
                }}
            />
            <MultiScreen
                capture={{
                    title: "Suivi des temps",
                    desktop: "https://flowbite.s3.amazonaws.com/docs/device-mockups/laptop-screen.png",
                }}
            />
        </div>
        <Separator />

        <Jumbotron title="Facturation PDF" content="Devis public pré-remplissable par un tiers." />
        <Jumbotron title="Recherche d'offres" />
        <Jumbotron title="Suivi des temps" />

        <GitLogsChart />
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
