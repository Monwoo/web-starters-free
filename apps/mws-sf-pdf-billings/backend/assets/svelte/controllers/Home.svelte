<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import HomePage from "mws-moon-manager-ux/components/home/HomePage.svelte";
    export let title = null;
    export let currentLanguage = null;
    export let billingsLocales = [];
    export let moonManagerLocales = [];
</script>

<HomePage
{title} {currentLanguage}
{billingsLocales} {moonManagerLocales}
></HomePage>