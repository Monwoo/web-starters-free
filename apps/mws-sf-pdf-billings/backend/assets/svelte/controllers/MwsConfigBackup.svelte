<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

  // TODO : keep in MoonManager bundle :
  // "entrypoints": [ // No effect, building and using build output for the root one ?
  //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
  // ]

  import Backup from "mws-moon-manager-ux/components/config/Backup.svelte";

  export let locale;
  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let viewTemplate;
  export let backups;
  export let backup;
  export let backupForm;

  // TODO : remove code duplication and put this in some 'mws-utils' package ?
  // https://stackoverflow.com/questions/5796718/html-entity-decode
  // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
  const decodeHtml = (html) => {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }
  
  backups = JSON.parse(decodeHtml(backups ?? '[]'));
  backupForm = decodeHtml(backupForm);
  backup = JSON.parse(decodeHtml(backup ?? null));

</script>

<!-- <Liste {...$$props}></Liste> -->
<Backup {backups} {copyright} {backup}
{locale} {viewTemplate} {backupForm}
></Backup>
