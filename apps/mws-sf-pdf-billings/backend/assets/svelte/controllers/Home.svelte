<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import Landing from "mws-moon-manager-ux/components/home/Landing.svelte";
    export let title = null;
    export let currentLanguage = null;
    export let billingsLocales = [];
    export let moonManagerLocales = [];
</script>

<Landing
{title} {currentLanguage}
{billingsLocales} {moonManagerLocales}
></Landing>