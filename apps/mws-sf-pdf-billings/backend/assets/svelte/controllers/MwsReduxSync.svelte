<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import ReduxStorage from "mws-moon-manager-ux/components/_data/ReduxStorage.svelte";
    export let stateUpdate = null;

        // TODO : remove code duplication and put this in some 'mws-utils' package ?
    // https://stackoverflow.com/questions/5796718/html-entity-decode
    // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
    const decodeHtml = (html) => {
        var txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    }
    console.debug('MwsReduxSync', stateUpdate);
    stateUpdate = JSON.parse(decodeHtml(stateUpdate || 'null'));
</script>
<ReduxStorage {stateUpdate} />