<!-- <script context="module" ✂prettier:content✂="CiAgLy8gaHR0cHM6Ly93d3cubnBtanMuY29tL3BhY2thZ2Uvc3ZlbHRlLXRpbWU/YWN0aXZlVGFiPXJlYWRtZSNjdXN0b20tbG9jYWxlCiAgLy8gaW1wb3J0ICJkYXlqcy9lc20vbG9jYWxlL2ZyIjsKICAvLyBpbXBvcnQgZGF5anMgZnJvbSAiZGF5anMvZXNtIjsKICBpbXBvcnQgImRheWpzL2xvY2FsZS9mciI7CiAgLy8gaW1wb3J0ICJkYXlqcy9sb2NhbGUvZW4iOwogIGRheWpzLmxvY2FsZSgiZnIiKTsgLy8gRnIgbG9jYWxlIC8vIFRPRE8gOiBnbG9iYWwgY29uZmlnIGluc3RlYWQgb2YgcGVyIG1vZHVsZSA/CiAgdmFyIHV0YyA9IHJlcXVpcmUoJ2RheWpzL3BsdWdpbi91dGMnKQogIHZhciB0aW1lem9uZSA9IHJlcXVpcmUoJ2RheWpzL3BsdWdpbi90aW1lem9uZScpIC8vIGRlcGVuZGVudCBvbiB1dGMgcGx1Z2luCiAgZGF5anMuZXh0ZW5kKHV0Yyk7CiAgZGF5anMuZXh0ZW5kKHRpbWV6b25lKTsgLy8gVE9ETyA6IHVzZXIgY29uZmlnIGZvciBzZWxmIHRpbWV6b25lLi4uIChzbG90IGlzIGNvbXB1dGVkIG9uIFVUQyBkYXRlLi4uKQogIC8vIGRheWpzLnR6LnNldERlZmF1bHQoIkV1cm9wZS9QYXJpcyIpOwogIC8vIGh0dHBzOi8vd3d3LnRpbWVhbmRkYXRlLmNvbS90aW1lL21hcC8jIWNpdGllcz0xMzYKICAvLyBodHRwczovL3d3dy50aW1lYW5kZGF0ZS5jb20vd29ybGRjbG9jay91ay9sb25kb24KICAvLyBodHRwczovL2VuLndpa2lwZWRpYS5vcmcvd2lraS9MaXN0X29mX3R6X2RhdGFiYXNlX3RpbWVfem9uZXMjTE9ORE9OCiAgZGF5anMudHouc2V0RGVmYXVsdCgiRXVyb3BlL0xvbmRvbiIpOwo=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=">{}</script> -->
<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import Base from "../layout/Base.svelte";
  import Header from "../layout/Header.svelte";
  import { onMount } from "svelte";
  import { state, stateGet } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import debounce from "lodash/debounce";
  import dayjs from "dayjs";

  export let locale;
  export let copyright = "© Monwoo 2017-2024 (service@monwoo.com)";
  export let backups = [];
  export let viewTemplate;
  export let backup;
  export let backupForm;
  export let isMobile;

  // console.debug(backup);
  let backupName;
  // TODO : slugger for svelte ? (or keep text lbl, and let backend do the slug ?)
  $: backupNameSlug = backupName;

  const jsonBackup = JSON.parse(decodeURIComponent(backup.jsonResult));
  console.debug("jsonBackup :", jsonBackup);

  let csrfBackupDelete = stateGet(get(state), "csrfBackupDelete");

  onMount(async () => {
  });
</script>

<Base
  bind:isMobile
  {copyright}
  {locale}
  {viewTemplate}
  mainClass=""
  footerClass="py-2"
>
  <!-- <div slot="mws-header-container" />
  -->
  <div class="mws-config-backup p-7">
    <h1>Importer un backup</h1>
    <div id="config-backup-form" class="detail w-full">
      {@html backupForm}
    </div>
    <h1>Exporter un backup</h1>
    <div class="w-full text-center">
      <label class="p-2" for="affiliationCode">Nom du backup :</label>
      <input
        class="text-black opacity-30 hover:opacity-100 max-w-[12rem] w-4/5"
        value={backupName ?? ''}
        type="text"
        name="affiliationCode"
        on:input={debounce(async (e) => {
          backupName = e.target.value;
        }, 300)}
        on:keydown={debounce(async (e) => {
          if ("Enter" == e.key) {
            if (document.activeElement instanceof HTMLElement)
              document.activeElement.blur();
            e.target.blur();
            // TODO : auto trigger href link
            // window.location = donateLink;
            // https://stackoverflow.com/questions/4907843/open-a-url-in-a-new-tab-and-not-a-new-window
            // window.open(donateLink, '_blank').focus();
            // TODO : can't be get, need secure POST...
            //      fetch...
          }
        }, 300)}
      />
    </div>
  
    <a>
      <button>Faire un backup pour {dayjs().format("YYYYMMDD_HHmmss")}-{backupNameSlug ?? 'MwsCrm'}.zip</button>
    </a>
    <h1>Liste des backups</h1>
    <ul>
      {#each backups ?? [] as backupDir}
        <li>
          {backupDir}
          <a>
            <button>Télécharger</button>
          </a>
          <a>
            <button
            style="--mws-primary-rgb: 255, 0, 0"
            >Supprimer</button>
          </a>      
        </li>
      {/each}
    </ul>
  </div>
</Base>

<style lang="scss">
</style>
