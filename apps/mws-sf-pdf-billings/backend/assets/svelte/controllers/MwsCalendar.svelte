<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import CalendarView from "mws-moon-manager-ux/components/calendar/CalendarView.svelte";
    export let title = null;
    export let currentLanguage = null;
</script>

<CalendarView
{title} {currentLanguage}
></CalendarView>