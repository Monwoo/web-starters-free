<script lang="ts">
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import ListView from "mws-moon-manager-ux/components/message/ListView.svelte";
    export let title = null;
    export let locale = null;
    export let viewTemplate;
    export let messages = '[]';
    export let messagesHeaders = '{}';
    export let addMessageForm = '';
    export let offersPaginator;
    export let csrfMessageDelete;
    export let csrfMessageTchatUpload;

    const decodeHtml = (html) => {
        var txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    }

    messages = JSON.parse(decodeHtml(messages));
    messagesHeaders = JSON.parse(decodeHtml(messagesHeaders));
    offersPaginator = decodeHtml(offersPaginator);
    addMessageForm = decodeHtml(addMessageForm);
</script>

<ListView
{title} {locale} {viewTemplate} {messages} {messagesHeaders}
{offersPaginator} {addMessageForm} {csrfMessageDelete} {csrfMessageTchatUpload}
></ListView>