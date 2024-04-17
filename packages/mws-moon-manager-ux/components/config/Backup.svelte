<!-- <script context="module" ✂prettier:content✂="CiAgLy8gaHR0cHM6Ly93d3cubnBtanMuY29tL3BhY2thZ2Uvc3ZlbHRlLXRpbWU/YWN0aXZlVGFiPXJlYWRtZSNjdXN0b20tbG9jYWxlCiAgLy8gaW1wb3J0ICJkYXlqcy9lc20vbG9jYWxlL2ZyIjsKICAvLyBpbXBvcnQgZGF5anMgZnJvbSAiZGF5anMvZXNtIjsKICBpbXBvcnQgImRheWpzL2xvY2FsZS9mciI7CiAgLy8gaW1wb3J0ICJkYXlqcy9sb2NhbGUvZW4iOwogIGRheWpzLmxvY2FsZSgiZnIiKTsgLy8gRnIgbG9jYWxlIC8vIFRPRE8gOiBnbG9iYWwgY29uZmlnIGluc3RlYWQgb2YgcGVyIG1vZHVsZSA/CiAgdmFyIHV0YyA9IHJlcXVpcmUoJ2RheWpzL3BsdWdpbi91dGMnKQogIHZhciB0aW1lem9uZSA9IHJlcXVpcmUoJ2RheWpzL3BsdWdpbi90aW1lem9uZScpIC8vIGRlcGVuZGVudCBvbiB1dGMgcGx1Z2luCiAgZGF5anMuZXh0ZW5kKHV0Yyk7CiAgZGF5anMuZXh0ZW5kKHRpbWV6b25lKTsgLy8gVE9ETyA6IHVzZXIgY29uZmlnIGZvciBzZWxmIHRpbWV6b25lLi4uIChzbG90IGlzIGNvbXB1dGVkIG9uIFVUQyBkYXRlLi4uKQogIC8vIGRheWpzLnR6LnNldERlZmF1bHQoIkV1cm9wZS9QYXJpcyIpOwogIC8vIGh0dHBzOi8vd3d3LnRpbWVhbmRkYXRlLmNvbS90aW1lL21hcC8jIWNpdGllcz0xMzYKICAvLyBodHRwczovL3d3dy50aW1lYW5kZGF0ZS5jb20vd29ybGRjbG9jay91ay9sb25kb24KICAvLyBodHRwczovL2VuLndpa2lwZWRpYS5vcmcvd2lraS9MaXN0X29mX3R6X2RhdGFiYXNlX3RpbWVfem9uZXMjTE9ORE9OCiAgZGF5anMudHouc2V0RGVmYXVsdCgiRXVyb3BlL0xvbmRvbiIpOwo=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=" ✂prettier:content✂="e30=">{}</script> -->
<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import Base from "../layout/Base.svelte";
  import Header from "../layout/Header.svelte";
  import { onMount } from "svelte";
  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
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
  export let csrfBackupDownload;
  export let isLoading = false;

  const base = process.env.BASE_HREF_FULL ?? "";
  const urlParams = new URLSearchParams(window.location.search);
  const useAsGdprResetOk = urlParams.has('useAsGdprResetOk') ? 
  JSON.parse(urlParams.get('useAsGdprResetOk')) : null;
  if (useAsGdprResetOk !== null) {
    // Cleanup to ensure no multiple alerts...
    urlParams.delete('useAsGdprResetOk');
    const newUrl =
    window.location.origin + window.location.pathname + "?" + urlParams;
    history.pushState({}, null, newUrl);
    alert(useAsGdprResetOk ? 'Choix du nouveau reset OK.' : 'Echec du reset, re-essayez ou choisisez en un autre.')
  }

  // console.debug(backup);
  let backupName;

  const jsonBackup = JSON.parse(decodeURIComponent(backup.jsonResult));
  console.debug("jsonBackup :", jsonBackup);

  let csrfBackupDelete = stateGet(get(state), "csrfBackupDelete");

  // TIPS : downloadBackup not used,
  // forsee Svelte pre-rendering html only available actions...
  const downloadBackup = async () => {
    // JS way will need JS to work...
    // (well, without pre-rendering, svelte also need JS...)
    if (isLoading) return;
    // TODO : await confirm and update tags dependencies if so...
    console.debug("downloadBackup");
    isLoading = true;

    // TODO : Wait for loading animation to show
    // Or use HTML modal instead of native blocking UI alert
    await new Promise((r) => setTimeout(r, 100));

    csrfBackupDelete = stateGet(get(state), "csrfBackupDelete");
    const data = {
      _csrf_token: csrfBackupDelete,
      backupName,
    };
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_config_backup_download", {
        _locale: locale,
      }),
      {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        redirect: "error",
      }
    )
      .then(async (resp) => {
        console.log(resp.url, resp.ok, resp.status, resp.statusText);
        if (!resp.ok) {
          throw new Error("Not 2xx response");
        } else {
          const data = await resp.blob();
          console.debug("Did remove tag, resp :", data);

          // stateUpdate(state, {
          //   csrfTimingMigrateTo: data.newCsrf,
          // });
          // Need self refresh for merged data values :
          window.location.reload();
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
    isLoading = false;
  };

  // TODO : Symfony slugger equivalent ? é to e ? meanfull
  //        words conversion (ex : chiness char to latin EN...)
  // Quick algo for now :
  // TODO : factorize in lib or service or ...
  // https://svelte.dev/repl/b130be5e485441a1842ae97e4ce4f244?version=4.2.3
  function slugify(str, keepLowerCase = true) {
    let resp = String(str)
      .replace(/\s+/g, "-") // replace spaces with hyphens
      .replace(/-+/g, "-") // remove consecutive hyphens
      .trim() // trim leading or trailing whitespace
      .normalize("NFKD") // split accented characters into their base characters and diacritical marks
      .replace(/[\u0300-\u036f]/g, "") // remove all the accents, which happen to be all in the \u03xx UNICODE block.
      // .toLowerCase() // convert to lowercase
      .replace(/[^A-Za-z0-9 -]/g, ""); // remove non-alphanumeric characters
      // TIPS : below ok to trim end, but will remove last space
      //        too, and usability to transform space to '-' get off...
      // .replace(/(^-)|(-$)/g, ""); // remove start '-' and end '-'
    if (!keepLowerCase) {
      resp = resp.toLowerCase();
    }
    return resp;
  }

  onMount(async () => {});
</script>

<Base
  bind:isMobile
  {copyright}
  {locale}
  {viewTemplate}
  mainClass=""
>
  <!-- <div slot="mws-header-container" />
  -->
  <div
    class="mws-config-backup p-3 flex flex-wrap
  justify-center items-center text-center"
  >
    <h1 class="w-full p-4">Importer un backup</h1>
    <div id="config-backup-form" class="detail w-full">
      {@html backupForm}
    </div>
    <h1 class="w-full p-4">
      Exporter un backup<br/> [Max {$state.config.backupTotalSize ?? "--"}]
    </h1>
    <div class="w-full text-lg">
      <p class="w-full">
        Taille du dossier <strong>upload</strong> : [{$state.config.uploadsTotalSize ?? "--"}]
      </p>
      <p class="w-full">
        Taille des <strong>données</strong> : [{$state.config.databasesTotalSize ?? "--"}]
      </p>
      <p class="w-full">
        Taille du reset <strong>GDPR</strong> : [{$state.config.gdprBackupSize ?? "--"}]
      </p>  
    </div>

    <!-- onsubmit="return confirm('Êtes vous sur de vouloir faire et télécharger un backup ?');" -->
    <div class="p-4 w-full flex flex-wrap items-center justify-center">
      <form
        action={Routing.generate("mws_config_backup_download", {
          _locale: locale ?? "",
          viewTemplate: viewTemplate ?? "",
        })}
        name="mainBackup"
        method="POST"
      >
        <div class="w-full text-center">
          <label class="p-2" for="backupName">Nom du backup :</label>
          <input
            class="text-black opacity-30 hover:opacity-100 max-w-[12rem] w-4/5"
            value={backupName ?? ""}
            type="text"
            name="backupName"
            on:input={debounce(async (e) => {
              backupName = slugify(e.target.value);
              e.target.value = backupName;
              console.debug(
                "Update backup name : ",
                e.target.value,
                backupName
              );
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
        <select
        name="backupType"
        class="opacity-30 hover:opacity-100 
        bg-gray-50 border border-gray-300 text-gray-900 
        text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
        inline-flex w-[10rem] p-1 m-2 dark:bg-gray-700 dark:border-gray-600 
        dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
        dark:focus:border-blue-500">
          <!-- <option value="null" selected>Type de backup</option> -->
          {#each [
            {format:'db', label:'Données'},
            {format:'db-zip', label:'Données compressés'},
            {format:'light', label:'Données et uploads', selected: 'on'},
            // {format:'full', label:'Tout le CRM'},
          ] as fmt}
            <option
            value={`${fmt.format}`}
            selected={fmt.selected}>
              {fmt.label}
            </option>
          {/each}
        </select>  
        <div class="p-4 w-full flex items-center justify-center">
          <input type="hidden" name="_csrf_token" value={csrfBackupDownload} />
          <button class=" m-2" style:--tw-shadow-color="#FF0000" type="submit">
            Télécharger un backup
            {
              (backupName && ' pour ') ?? ''
            }{
              backupName ?? ''
            }</button
          >
        </div>
      </form>

      <!-- <form on:submit|preventDefault={submit} class="mws-import-tags-form">
        <input type="file" name="importFile" accept={formatToMime[format]} />
        <br />
        <select
          bind:value={format}
          name="format"
          class="opacity-30 hover:opacity-100 
        bg-gray-50 border border-gray-300 text-gray-900 
        text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 
        inline-flex w-[10rem] p-1 m-2 dark:bg-gray-700 dark:border-gray-600 
        dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
        dark:focus:border-blue-500"
        >
          <option value="null" selected>Format d'import</option>
          {#each availableFormat as fmt}
            <option value={`${fmt.format}`}>{fmt.label}</option>
          {/each}
        </select>
        <!-- <input type="checkbox" name="shouldOverwrite" checked /> -- >
        <span class="inline-flex flex-col">
          <span>
            <input type="checkbox" name="shouldOverwrite" />
            <label for="shouldOverwrite">Forcer la surcharge</label>
          </span>
          <span>
            <input type="checkbox" name="shouldRecomputeAllOtherTags" />
            <label for="shouldOverwrite">Mettre à jour les tags des temps</label>
          </span>
        </span>
      
        <button type="submit" class=" m-1">
          Importer les tags
        </button>
        <!-- <input type="submit" class="btn btn-outline-primary p-1 m-1"/>     -- >
        <Loader {isLoading} />
      </form> -->
    </div>
    <h1 class="w-full p-4">
      Liste des backups<br/> [Max {$state.config.backupsTotalSize ?? "--"}]
    </h1>
    <ul class="w-full">
      {#each backups ?? [] as backupDir, idx}
        {@const internalName = backupDir.split(' [')[0]}
        <div
          class="p-2 w-full
        flex flex-wrap items-center justify-center"
        >
          <div
            class="w-1/2 px-4
          flex flex-wrap items-start justify-end"
          >
            <form
              method="post"
              action={Routing.generate("mws_config_backup_internal_download", {
                // _locale: locale ?? "",
                // viewTemplate: viewTemplate ?? "",
                // csrf: $state.csrfConfigBackupInternalDownload,
                // internalName: [ backupDir.split(' [')[0] ]
              })}
            >
              <input type="hidden" name="_csrf_token[{idx}]" value={$state.csrfConfigBackupInternalDownload} />
              <input type="hidden" name="internalName[{idx}]" value={backupDir.split(' [')[0]} />
              <button name="submit_ib_{idx}" class=" m-2" type="submit">
                Télécharger
              </button>
            </form>
            <form
              method="post"
              onsubmit="return confirm('Êtes vous sur de vouloir remplacer les données et/ou uploads avec le backup {internalName} ?');"
              action={Routing.generate("mws_config_backup_internal_import", {
                // _locale: locale ?? "",
                // viewTemplate: viewTemplate ?? "",
                // csrf: $state.csrfConfigBackupInternalDownload,
                // internalName: [ backupDir.split(' [')[0] ]
              })}
            >
            <!-- // TODO  : name do not need to be unique, right ? remove [{idx}] ?
            Bug was about upload / die without end lifecycle of returned resp,
             no name clash issues ?
            -->
              <input type="hidden" name="_csrf_token[{idx}]" value={$state.csrfConfigBackupInternalImport} />
              <input type="hidden" name="internalName[{idx}]" value={internalName} />
              <button name="submit_ib_{idx}" class=" m-2" type="submit">
                Importer
              </button>
            </form>
            <form
              method="post"
              onsubmit="return confirm('Êtes vous sur de vouloir supprimer le backup interne {internalName} ?');"
              action={Routing.generate("mws_config_backup_internal_remove", {
              })}
            >
              <input type="hidden" name="_csrf_token" value={$state.csrfConfigBackupInternalRemove} />
              <input type="hidden" name="internalName" value={internalName} />
              <button style="--mws-primary-rgb: 255, 0, 0">Supprimer</button>
            </form>
            <form
              method="post"
              onsubmit="return confirm('Êtes vous sur de vouloir definir le backup interne {internalName} comme base de reset GDPR ?');"
              action={Routing.generate("mws_config_backup_internal_use_as_gdpr_reset", {
              })}
            >
              <input type="hidden" name="_csrf_token" value={$state.csrfConfigBackupInternalUseAsGdprReset} />
              <input type="hidden" name="internalName" value={internalName} />
              <button style="--mws-primary-rgb: 255, 0, 0">Pour reset GDPR</button>
            </form>
          </div>
          <div
            class="w-1/2
          flex flex-wrap items-start justify-start"
          >
            {backupDir}
          </div>
        </div>
      {/each}
    </ul>
    <h1 class="w-full p-4">
      Liste des uploads<br/> [{$state.config.uploadsTotalSize ?? "--"}]
    </h1>
    <div class="w-full">
      {#each $state.config.uploadedFiles ?? [] as f}
        {@const upUrl = `${f.split(" [")[0] ?? ""}`}
        <div
          class="p-2 w-full
        flex flex-wrap items-center justify-center"
        >
          <div
            class="w-full px-4
          flex flex-wrap items-center justify-center"
          >
            <a
              href={upUrl}
              target="_blank"
            >
              <button>{upUrl.split("/").slice(-1)[0] ?? ""}</button>
            </a>
          </div>
          <div
            class="w-1/2
          flex flex-wrap items-start justify-start"
          />
        </div>
      {/each}
    </div>
    <h1 class="w-full p-4">Thumbnails</h1>
    <p class="w-full">
      Nombre de thumbnails en base : [{$state.config.thumbnailsCount ?? "--"}]
    </p>
    <div
      class="w-full p-2
        flex flex-wrap items-center justify-center"
    >
      <form
        method="post"
        onsubmit="return confirm('Êtes vous sur de vouloir supprimer toutes les miniatures de la base de données ?');"
        action={Routing.generate("mws_config_backup_thumbnails_remove", {
        })}
      >
        <input type="hidden" name="_csrf_token" value={$state.csrfConfigBackupThumbnailsRemove} />
        <button style="--mws-primary-rgb: 255, 0, 0">Supprimer tous les thumbnails</button>
      </form>
    </div>
  </div>
</Base>

<style lang="scss">
  h1 {
    @apply text-2xl;
    @apply font-extrabold;
    @apply p-2;
  }
</style>
