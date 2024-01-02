<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import CalendarPage from "mws-moon-manager-ux/components/calendar/CalendarPage.svelte";
    export let title = null;
    export let currentLanguage = null;
    export let billingsLocales = [];
    export let moonManagerLocales = [];
</script>

<CalendarPage
{title} {currentLanguage}
{billingsLocales} {moonManagerLocales}
></CalendarPage>