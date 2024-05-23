<script context="module">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // let sdkPaypalMissing = !(window.PayPal ?? false);
  let sdkPaypalMissing = true;
</script>
<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import { onMount } from "svelte";
  import debounce from "lodash/debounce";
  import newUniqueId from "locally-unique-id-generator";
  // import dayjs from "dayjs";

  // https://medium.com/william-joseph/things-i-wish-id-known-when-starting-website-projects-9133a5188878
  /*
  Working towards this budget split is ideal:
    Total website budget = rebuild + retainer (support + enhancements)
    Rebuild — the Big project that includes discovery, design and build.
    Support — the hosting and ‘lights on’ package to keep your website running and deal with bug fixing
    Enhancements — ongoing development of the features and functionality, keeping the site fresh and learning from data
  */
  // https://page.funnel.io/elevate-your-reporting
  // https://charityfunnel.io/ => TODO : more advanced than custom paypal ? only for specific known domains ?
  //
  const urlParams = new URLSearchParams(window.location.search);
  const UID = newUniqueId();

  export let affiliationCode = urlParams.get("affiliationCode") ?? null;
  export let userDelay = 200;
  export let usePaypalBtn = false;
  const htmlId = `paypal-donate-button-container-${UID}`;

  // https://github.com/sveltejs/kit/issues/10502
  // But async load might still over loads ?
  let shouldAddPaypalSdk = !sdkPaypalMissing;
  let isPaypalSdkLoaded = false;
  let didMount = false;
  sdkPaypalMissing = false;

  const syncPaypal = () => {
    console.debug('Did sync PaypalSdk');
    // https://developer.paypal.com/sdk/donate/
    const PayPal = window.PayPal;

    PayPal.Donation.Button({
      // env: 'sandbox', // Will need sandbox btn id...
      env: "production",
      hosted_button_id: "F3F9EBQKT3TDG",
      // https://www.paypal.com/mc/cshelp/article/o%C3%B9-se-trouve-mon-identifiant-de-marchand-s%C3%A9curis%C3%A9-sur-mon-compte-paypal%C2%A0-help538
      // https://www.paypal.com/businessmanage/account/aboutBusiness
      // business: 'YOUR_LIVE_EMAIL_OR_PAYERID',
      // https://developer.paypal.com/api/nvp-soap/ipn/IPNandPDTVariables/
      // https://developer.paypal.com/api/nvp-soap/paypal-payments-standard/integration-guide/Appx-websitestandard-htmlvariables/
      cn: "MWS-PDF-Billings via " + (affiliationCode ?? ""), // TODO : reactive sync ?
      image: {
        // https://developer.paypal.com/beta/apm-beta/additional-information/method-icons/
        src: "https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif",
        title: "PayPal - Faire un don à Monwoo",
        alt: "Don pour Monwoo",
      },
      onComplete: function (params) {
        // Your onComplete handler
      },
    }).render(`#${htmlId}`);
  }

  $: {
    if (isPaypalSdkLoaded && didMount) {
      syncPaypal();
    }
  }

  $: donateLink = `https://www.monwoo.com/don${
        affiliationCode
          ? // TODO : other ways than paypal btn ?
            // ? `?affiliationCode=${encodeURIComponent(affiliationCode)}`
            `?item_name=${
              encodeURIComponent(affiliationCode).replaceAll("%20", "+")
            }&cn=${
              // TODO :  GET param config encoding for paypal btn ?
              // &targetMeta=eyJ6b2lkVmVyc2lvbiI6IjlfMF81OCIsInRhcmdldCI6IkRPTkFURSIsInNka1ZlcnNpb24iOiIwLjguMCJ9
              // Paypal ignore %20 and use '+' for space encode char
              // + ENCODED '+' char as %2B do not get translated (stay as %2B)
              encodeURIComponent("MWS-PDF-Billings via " + (affiliationCode ?? "")
              ).replaceAll("%20", "+")
            }`
          : `?item_name=${
            encodeURIComponent("MWS-PDF-Billings")
          }&cn=${
            encodeURIComponent("MWS-PDF-Billings")
          }`
      }
  `;
  $: {
    const urlParams = new URLSearchParams(window.location.search);
    if (affiliationCode) {
      console.debug("Affiliation :", affiliationCode);
      urlParams.set("affiliationCode", encodeURIComponent(affiliationCode));
    } else {
      urlParams.delete("affiliationCode");
    }
      // TIPS : refresh page :
      // window.location.search = urlParams;
      const newUrl =
      window.location.origin + window.location.pathname + "?" + urlParams;
      history.pushState({}, null, newUrl); // No refresh
  }


  onMount(async () => {
    // syncPaypal();
    didMount = true;
    if (!shouldAddPaypalSdk && usePaypalBtn) {
      // TODO : should wait for window.PayPal ?
      await new Promise(r => setTimeout(r, 500));
      isPaypalSdkLoaded = true;
    }
  });
</script>

<svelte:head>
  <!-- TIPS : only one injection if already injected ? -->
  <!-- https://github.com/sveltejs/kit/issues/10502 -->
  {#if shouldAddPaypalSdk && usePaypalBtn}
    <script
    on:load={() => isPaypalSdkLoaded = true}
    src="https://www.paypalobjects.com/donate/sdk/donate-sdk.js"
    charset="UTF-8"></script>
  {/if}
</svelte:head>

<div class="w-full flex flex-wrap items-center justify-center">
  <h1
    class="p-8 text-gray-900 dark:text-white text-3xl md:text-5xl font-extrabold mb-2"
  >
    Soutenir le projet
  </h1>
  <div class="w-full text-center">
    <label class="p-2" for="affiliationCode">Code d'affiliation :</label>
    <input
      class="text-black opacity-30 hover:opacity-100 max-w-[12rem] w-4/5"
      value={affiliationCode}
      type="text"
      name="affiliationCode"
      on:input={debounce(async (e) => {
        affiliationCode = e.target.value;
      }, userDelay * 4)}
      on:keydown={debounce(async (e) => {
        if ("Enter" == e.key) {
          if (document.activeElement instanceof HTMLElement)
            document.activeElement.blur();
          e.target.blur();
          // TODO : auto trigger href link
          // window.location = donateLink;
          // https://stackoverflow.com/questions/4907843/open-a-url-in-a-new-tab-and-not-a-new-window
          window.open(donateLink, '_blank').focus();
        }
      }, userDelay)}
    />
  </div>

  <!-- // TODO : affiliation code input auto fill for paypal reference trakings ? -->

  {#if usePaypalBtn}
    <div id={htmlId}></div>
  {:else}
    <a
      class="p-7 text-2xl hover:cursor-pointer hover:no-underline"
      href={donateLink}
      target="_blank"
    >
      {affiliationCode ? affiliationCode + " : " : ""}
      www.monwoo.com/don
    </a>
  {/if}
</div>
