<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import ListItem from "./tags/ListItem.svelte";
  import AddModal from "./tags/AddModal.svelte";
  import Routing from "fos-router";
  import Loader from "../layout/widgets/Loader.svelte";

  export let locale;
  export let availableFormat = [
    { format:'json', label:'JSON' },
    { format:'yaml', label:'YAML' },
    { format:'csv', label:'CSV' },
  ];
  export let format = 'yaml';
  export let timingLookup = null;
  export let isLoading = false;

  const submit = async (e) => {
    if (isLoading) return;
    isLoading = true;
    // Wait to show is loading feedback if fast answer :
    await new Promise((r) => setTimeout(r, 100));

    const formData = new FormData(e.target);
    if (timingLookup) {
      console.debug('Will export timings with timing filters :', timingLookup);
      formData.append(`timingLookup`, JSON.stringify(timingLookup));
    }

    const formJson = Object.fromEntries(formData.entries());
    console.log('Should export timings : ', formJson);

    var file_path = Routing.generate("mws_timing_export", {
        _locale: locale,
        ...formJson,
    });
    const downloadLink = (url, filename) => {
      var a = document.createElement('A');
      a.target = "_parent";
      a.href = url;
      a.download = filename; // file_path.substr(file_path.lastIndexOf('/') + 1);
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    };

    // downloadLink(file_path);
    const resp = await fetch(
            file_path,
            {
                method: "GET",
                credentials: "same-origin",
                redirect: "error",
            }
        )
            .then(async (resp) => {
                console.log(resp.url, resp.ok, resp.status, resp.statusText);
                if (!resp.ok) {
                    throw new Error("Not 2xx response", { cause: resp });
                } else {
                  // var url = URL.createObjectURL(await resp.arrayBuffer());
                  // const content = await resp.arrayBuffer();
                  const content = await resp.text();
                  // https://stackoverflow.com/questions/27120757/failed-to-execute-createobjecturl-on-url
                  var binaryData = [];
                  binaryData.push(content);
                  var url = URL.createObjectURL(new Blob(
                    binaryData, {
                      type: "text/plain",
                      // download: file_path.substr(file_path.lastIndexOf('/') + 1),
                    }
                  ));
                  const fName = resp.headers.get('Content-Disposition')
                  .split('"').slice(-2)[0];
                  downloadLink(url, fName);
                }
            })
            .catch((e) => {
                console.error(e);
                // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
                const shouldWait = confirm("Echec de l'enregistrement.");
            });
            isLoading = false;
}
</script>

<form on:submit|preventDefault={submit} class="mws-export-timings-form">
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
    <option value="null" selected>Format d'export</option>
    {#each availableFormat as fmt}
      <option value={`${fmt.format}`}>{fmt.label}</option>
    {/each}
  </select>
  <span>
    <input type="checkbox" name="attachThumbnails" />
    <label for="attachThumbnails">Joindre les miniatures</label>
  </span>
  <span>
    <input type="number" name="thumbnailsSize" />
    <label for="thumbnailsSize">Taille des miniatures</label>
  </span>

  <button type="submit" class=" m-1">
    Exporter les temps
  </button>
  <!-- <input type="submit" class="btn btn-outline-primary p-1 m-1"/>     -->

  <Loader {isLoading} />
</form>
