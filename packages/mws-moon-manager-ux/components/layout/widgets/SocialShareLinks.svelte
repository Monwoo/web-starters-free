<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  export let description;
  export let pictureLink;
  export let sourceLink;
  export let formats = {
    WhatsApp: (src) => {
      const params = new URLSearchParams();
      params.set(
        'text', `
          ${pictureLink ?? ''}${
            pictureLink ?? '\n\n'
          }${description ?? ''}${
            description ?? '\n\n'
          }${sourceLink ?? ''}${
            sourceLink ?? '\n\n'
          }
        `.trim()
      );
      return `whatsapp://send?` + params;
    },
  };

  // TIPS : direct dependencies conditional tests to
  //        force shareLinks refresh on inputs... native svelte.
  // TODO : better use Rxjs style or equivalent ?
  $: shareLinks = description && pictureLink && sourceLink
  && Object.keys(formats).reduce((acc, fKey) => {
    const formatHandler = formats[fKey];
    acc[fKey] = formatHandler();
    return acc;
  }, formats);

</script>

<!-- <svelte:self bind:this={component} outer={false}/> -->

<a href="mailto:{contact}">
  
</a>
