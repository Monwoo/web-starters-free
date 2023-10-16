<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

    // TODO : keep in MoonManager bundle :
    // "entrypoints": [ // No effect, building and using build output for the root one ?
    //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
    // ]

    import Liste from "mws-moon-manager-ux/components/user/Liste.svelte";
    
    // TODO : remove code duplication and put this in some 'mws-utils' package ?
    // https://stackoverflow.com/questions/5796718/html-entity-decode
    // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
    const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
    }

    export let users = '[]';
    export let filterForm;
    export let paginator;
    export let copyright = "© Monwoo 2023 (service@monwoo.com)";
</script>

<!-- <Liste {...$$props}></Liste> -->
<Liste users={JSON.parse(decodeHtml(users))} {filterForm} {paginator} {copyright}></Liste>
