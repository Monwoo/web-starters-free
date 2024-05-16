<script lang="ts">
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import HomePage from "mws-moon-manager-ux/components/home/HomePage.svelte";
    export let title = null;
    export let viewTemplate = null;
    export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
    export let locale = null;
    export let currentLanguage = null;
    export let billingsLocales = [];
    export let moonManagerLocales = [];
</script>

<HomePage
{title} {currentLanguage} {locale} {copyright} {viewTemplate}
{billingsLocales} {moonManagerLocales}
></HomePage>