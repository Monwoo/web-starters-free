<script lang="ts">
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

    // TODO : keep in MoonManager bundle :
    // "entrypoints": [ // No effect, building and using build output for the root one ?
    //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
    // ]

    import TagEdit from "mws-moon-manager-ux/components/offer/TagEdit.svelte";
    
    // TODO : remove code duplication and put this in some 'mws-utils' package ?
    // https://stackoverflow.com/questions/5796718/html-entity-decode
    // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
    const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
    }

    export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
    export let locale;
    export let tag = '{}';
    export let viewTemplate;
    export let form;

    tag = JSON.parse(decodeHtml(tag));
    form = decodeHtml(form);
</script>

<!-- <Liste {...$$props}></Liste> -->
<TagEdit {tag} {copyright}
{locale} {viewTemplate} {form}
></TagEdit>
