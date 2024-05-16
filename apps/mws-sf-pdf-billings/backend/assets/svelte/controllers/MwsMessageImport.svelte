<script lang="ts">
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import ImportView from "mws-moon-manager-ux/components/message/ImportView.svelte";
    export let title = null;
    export let viewTemplate = null;
    export let locale = null;
    export let format = null;
    export let reportSummary = '';
    export let uploadForm;
    const decodeHtml = (html) => {
        var txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    };
    reportSummary = decodeHtml(reportSummary);
    uploadForm = decodeHtml(uploadForm);
</script>

<ImportView
{title} {locale} {format} {reportSummary} {uploadForm}
></ImportView>