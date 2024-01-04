<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import ListView from "mws-moon-manager-ux/components/message/ListView.svelte";
    export let title = null;
    export let locale = null;
    export let messages = '[]';

    const decodeHtml = (html) => {
        var txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    }

    messages = JSON.parse(decodeHtml(messages));

</script>

<ListView
{title} {locale} {messages}
></ListView>