<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  export let name = "";
  export let source = "";
  export let title = "";
  export let contact = "";

  $: isTel = /(\d\s*?){9}/.test(contact);
  $: isMail = /\w+@\w+\.\w+/.test(contact);

  // https://stackoverflow.com/questions/8669912/add-a-contact-to-the-mobile-device-address-book-from-an-html-webpage
  //   $: vCard = `BEGIN:VCARD
  // VERSION:3.0
  // N:Smith;John;;Mr.;
  // FN:John Smith
  // ORG:Acme Corporation
  // TITLE:Sales Manager
  // TEL;TYPE=WORK,VOICE:(123) 456-7890
  // ADR;TYPE=WORK:;;123 Main St.;Anytown;CA;12345;USA
  // EMAIL:john.smith@example.com
  // END:VCARD`;
  $: vCard = `BEGIN:VCARD
VERSION:3.0
N:${source};${name};;;
FN:${source} ${name}
ORG:
TITLE:${title}
TEL;TYPE=WORK,VOICE:${contact}
END:VCARD`;

  // var url = URL.createObjectURL(new Blob(
  //     binaryData, {
  //       type: "text/plain",
  //       // download: file_path.substr(file_path.lastIndexOf('/') + 1),
  //     }
  //   ));
  //   const fName = resp.headers.get('Content-Disposition')
  //   .split('"').slice(-2)[0];
  //   downloadLink(url, fName);
  // const downloadLink = (url, filename) => {
  //     var a = document.createElement('A');
  //     a.target = "_parent";
  //     a.href = url;
  //     a.download = filename; // file_path.substr(file_path.lastIndexOf('/') + 1);
  //     document.body.appendChild(a);
  //     a.click();
  //     document.body.removeChild(a);
  //   };
  $: vCardUrl = URL.createObjectURL(
    new Blob([vCard], {
      // https://www.techdim.com/what-is-a-vcf-file/?utm_content=cmp-true
      // http://justsolve.archiveteam.org/wiki/VCard
      // .vcf or .vcard
      type: "text/vcard",
    })
  );
</script>

{#if isTel}
  <a href={vCardUrl} download="contact-{source}.vcf">
    {contact}
  </a>
{:else if isMail}
  <a href="mailto:{contact}">
    {contact}
  </a>
{:else}
  {contact}
{/if}
