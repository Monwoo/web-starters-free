<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import Routing from "fos-router";
  import { state, stateGet, stateUpdate } from "../../stores/reduxStorage.mjs";
  import { get } from "svelte/store";
  import { tweened } from "svelte/motion";
  import ProgressIndicator from "../layout/widgets/ProgressIndicator.svelte";
  import newUniqueId from "locally-unique-id-generator";
  // import dayjs from "dayjs"; // TIPS : MODULE import will be useless if double import... ( not configured new instance)
  // https://www.npmjs.com/package/svelte-time?activeTab=readme#custom-locale
  // import "dayjs/esm/locale/fr";
  // import dayjs from "dayjs/esm";
  import "dayjs/locale/fr";
  // import "dayjs/locale/en";
  import dayjs from "dayjs";
  import TagsInput from "./tags/TagsInput.svelte";
  import AddModal from "../message/AddModal.svelte";
  import _ from "lodash";

  import PhotoSwipeGallery from "svelte-photoswipe";
  import Loader from "../layout/widgets/Loader.svelte";
  import QuickList from "./qualifs/QuickList.svelte";
  import { draggable } from "svelte-agnostic-draggable";
  // https://svelte.dev/repl/f696ca27e6374f2cab1691727409a31d?version=3.38.2
  import { swipe, pan } from 'svelte-gestures';
  // import CaretLeft from "mws-moon-manager-ux/medias/flowbite/caret-left.svg"
  // import CaretLeft from "../../medias/flowbite/caret-left.svelte"
  import caretLeft from "../../medias/flowbite/caret-left.svg"
  import caretRight from "../../medias/flowbite/caret-right.svg"

  // console.debug('CaretRight', CaretRight);
  console.debug('CaretRight', caretRight);
  // import mapTouchToMouseFor from "svelte-touch-to-mouse";
  // TODO : Pull request with fixed code ?
  // => did change Target.matches => Target.closest
  function mapTouchToMouseFor (Selector:string):void {
    function TouchEventMapper (originalEvent:TouchEvent):void {
      let Target = originalEvent.target as Element
      if (! Target.closest(Selector)) { return }
      console.debug('mapTouchToMouseFor', originalEvent);

      let simulatedEventType
      switch (originalEvent.type) {
        case 'touchstart':  simulatedEventType = 'mousedown'; break
        case 'touchmove':   simulatedEventType = 'mousemove'; break
        case 'touchend':    simulatedEventType = 'mouseup';   break
        case 'touchcancel': simulatedEventType = 'mouseup';   break
        default:            return
      }

      let firstTouch = originalEvent.changedTouches[0]

      let clientX = firstTouch.clientX, pageX = firstTouch.pageX, PageXOffset = window.pageXOffset
      let clientY = firstTouch.clientY, pageY = firstTouch.pageY, PageYOffset = window.pageYOffset
      if (
        (pageX === 0) && (Math.floor(clientX) > Math.floor(pageX)) ||
        (pageY === 0) && (Math.floor(clientY) > Math.floor(pageY))
      ) {
        clientX -= PageXOffset
        clientY -= PageYOffset
      } else if (
        (clientX < pageX - PageXOffset) || (clientY < pageY - PageYOffset)
      ) {
        clientX = pageX - PageXOffset
        clientY = pageY - PageYOffset
      }

      let simulatedEvent = new MouseEvent(simulatedEventType, {
        bubbles:true, cancelable:true,
        screenX:firstTouch.screenX, screenY:firstTouch.screenY,
        // @ts-ignore we definitely want "pageX" and "pageY"
        clientX, clientY, pageX, pageY, buttons:1, button:0,
        ctrlKey:originalEvent.ctrlKey, shiftKey:originalEvent.shiftKey,
        altKey:originalEvent.altKey, metaKey:originalEvent.metaKey
      })

      firstTouch.target.dispatchEvent(simulatedEvent)
      //    originalEvent.preventDefault()
    }

    document.addEventListener('touchstart',  TouchEventMapper, true)
    document.addEventListener('touchmove',   TouchEventMapper, true)
    document.addEventListener('touchend',    TouchEventMapper, true)
    document.addEventListener('touchcancel', TouchEventMapper, true)
    return () => {
      document.removeEventListener('touchstart',  TouchEventMapper, true)
      document.removeEventListener('touchmove',   TouchEventMapper, true)
      document.removeEventListener('touchend',    TouchEventMapper, true)
      document.removeEventListener('touchcancel', TouchEventMapper, true)
    }
  }
  import { onMount, tick } from "svelte";
  import Base from "../layout/Base.svelte";
  import { Tooltip } from 'flowbite'
  import HtmlIcon from "./qualifs/HtmlIcon.svelte";
  import debounce from "lodash/debounce";
  import Svg from "../layout/widgets/Svg.svelte";

  // https://day.js.org/docs/en/timezone/set-default-timezone
  // https://day.js.org/docs/en/plugin/timezone
  var utc = require("dayjs/plugin/utc");
  var timezone = require("dayjs/plugin/timezone"); // dependent on utc plugin
  dayjs.extend(utc);
  dayjs.extend(timezone); // TODO : user config for self timezone... (slot is computed on UTC date...)
  // dayjs.tz.setDefault("Europe/Paris");
  dayjs.tz.setDefault("Europe/Paris");

  let classNames = "";
  export { classNames as class };
  export let sizeStyle;
  export let timingSlot;
  export let isFullScreen = false;
  export let moveSelectedIndex;
  export let movePageIndex;
  export let lastSelectedIndex = 0;
  export let timings;
  // TODO : add selectionStartIndex inside query +
  //        backend's toggle service compute with it too to shift all items
  //        => will need refactor or complement param for 'Page number/page limit...'
  //        => or do full client side solution, sending list of ids ?
  export let selectionStartIndex;
  export let timeQualifs = [];
  export let locale;
  export let isHeaderExpanded = false;
  export let fullscreenClass = "";
  export let allTagsList;
  export let slotHeader;
  export let slotView;
  export let isWide;
  export let isMobile;
  // TIPS : For reactiv, MUST pass ref in params to trigger Svelte reactivity
  // const computeStartRange = () => isWide ? 27 : isMobile ? 30 : 70; // WRONG
  const computeStartRange = (isWide, isMobile) => isWide ? 21 : isMobile ? 48 : 72;
  export let zoomStartRange = computeStartRange(isWide, isMobile); // SSR size no items change
  const computeZoomRange = (initialZoom) => {
    let newRange = initialZoom;
    if (newRange > 80) {
      // Higher bigger zoom
      const deltaMax = newRange - 80;
      newRange = 80 + deltaMax * 10;
    }
    return newRange;
  };
  export let zoomRangeUI = zoomStartRange;
  export let zoomRange = computeZoomRange(zoomRangeUI);
  export let quickQualifTemplates; // Injected by qualif/QuickList.svelte
  export let htmlRoot;
  export let viewWrapper;

  $: vWidth = viewWrapper?.offsetWidth ?? 0;

  $: zoomStartRange = computeStartRange(isWide, isMobile); // Need one change to update...
  $: zoomRangeUI = zoomStartRange; // Ok due to Svelte reactivity : only update if zoomStartRange change...
  $: zoomRange = computeZoomRange(zoomRangeUI); // Ok due to Svelte reactivity : only update if zoomStartRange change...

  const Default = {
    placement: 'top',
    triggerType: 'hover',
    onShow: function () { },
    onHide: function () { },
    onToggle: function () { },
  };

  const swipeImage = (e) => {
    return; // TODO swipeImage with big scroll picture
    // is strange (will scroll before swipe, then swipe
    // image will scrolling, and heavier than simple click
    // since need to CLICK and then move to do the Pan event...
    const direction = e.detail.direction;
    console.debug("Swipe ", direction);
    if ('left' == direction) {
      movePageIndex(-1);
    }
    if ('right' == direction) {
      movePageIndex(1);
    }
  }

  let xLast = null;
  let xDirection = 0;
  let xDirectionLast = null;
  const imagePanDelayMs = 0;
  // const imagePanDelayMs = 200;
  // 2 sec delay to detect swipe ?
  // (2 * 1000) / imagePanDelayMs;
  // => But not ok, so empirique value sound like :
  const sameDirCountMax = 1;
  let sameDirCount = 0;
  let allowPanNavigation = false;
  const imagePanHandler = (e) => {
    return; // TODO swipeImage with big scroll picture
    // is strange (will scroll before swipe, then swipe
    // image will scrolling, and heavier than simple click
    // since need to CLICK and then move to do the Pan event...

    if (!allowPanNavigation) return;

    const x = event.detail.x;
    const y = event.detail.y;
    const target = event.detail.target;
    // console.debug("Pan ", x, y);
    if (null === xLast) {
      xLast = x;
    }
    xDirection = xLast - x;
    xDirection = xDirection
    ? xDirection / Math.abs(xDirection)
    : xDirectionLast;
    // console.debug("Swipe X ", xLast, x, xDirection, sameDirCountMax);
    console.debug("Swipe X ", xDirection, sameDirCountMax);
    if (null === xDirectionLast) {
      xDirectionLast = xDirection;
    }
    if (xDirectionLast === xDirection) {
      sameDirCount++;
    } else {
      sameDirCount = 0;
    }
    if (sameDirCount > sameDirCountMax) {
      console.debug("Swipe to ", xDirection);
      moveSelectedIndex(xDirection);
      sameDirCount = 0;
      allowPanNavigation = false;
    }
    xLast = x;
    xDirectionLast = xDirection;
  }
  let lastTouchY;

  const imageTouchstartHandler = (event) =>
  {
    allowPanNavigation = true;
    // TODO :improve with :
    // https://stackoverflow.com/questions/56844807/svelte-long-press
    // 
    // console.log('imageTouchstartHandler');
    // if (event.touches.length === 2)
    // {
    //   // on mac, with 2 finger, simulate click :
    //   console.log('Pan started with 2 finger');
    //   event.target.click();
    // }
  };

  // const imageTouchmoveHandler = (event) =>
  // {
  //   if (event.touches.length === 2)
  //   {
  //     event.preventDefault();
  //     const delta = lastTouchY - event.touches[0].clientY;
  //     lastTouchY = event.touches[0].clientY;
  //     element.scrollTop += delta;
  //   }
  // };


  const refreshTooltips = ()=> {
    // let myDiv = getElementById("myDiv");
    // myDiv.querySelectorAll(":scope > .foo");
    // const tooltipElements = document.querySelectorAll(`[role="tooltip"]`);
    htmlRoot?.querySelectorAll('[data-tooltip-target]').forEach(function ($triggerEl) {
        var tooltipId = $triggerEl.getAttribute('data-tooltip-target');
        var $tooltipEl = document.getElementById(tooltipId);
        if ($tooltipEl) {
            var triggerType = $triggerEl.getAttribute('data-tooltip-trigger');
            var placement = $triggerEl.getAttribute('data-tooltip-placement');
            let t = $triggerEl.getAttribute('data-tooltip-ref');
            if (!t) {
              t = new Tooltip($tooltipEl, $triggerEl, {
                placement: placement ? placement : Default.placement,
                triggerType: triggerType
                    ? triggerType
                    : Default.triggerType,
              });
              $triggerEl.setAttribute('data-tooltip-ref', t);
            }
            // https://github.com/themesberg/flowbite/issues/121
            // t.destroy(); ??
            // t.dispose(); ??
        }
        else {
            console.error("The tooltip element with id \"".concat(tooltipId, "\" does not exist. Please check the data-tooltip-target attribute."));
        }
    });
  }

  allTagsList = allTagsList ?? stateGet(get(state), "allTagsList");

  // TODO : to slow to init all flowbite for tooltips reloads ?
  // TODO : not enough for fullscreen mode ? need tick ?
  // $: currentTimeSlotQualifs, isFullScreen, initFlowbite()
  let flowbiteInSync = false;
  $: {
    // TODO : debounce async init function ?
    // TODO 2 : too slow to init initFlowbite, plus must WAIT end
    //          of async last call before triggering again ? will only need to reset tooltips...
    // currentTimeSlotQualifs, isFullScreen, (async () => {
    // isFullScreen, (async () => {
    // TIPS : for mobile, in fullscreen need to reload on each presentations ?
    lastSelectedIndex, isFullScreen, 
    debounce(async () => {
      // if (flowbiteInSync || !isFullScreen) return; // OK for one fullscreen mode only...
      // flowbiteInSync = true;

      // TIPS : tick() to wait for html changes
      await tick();
      // TODO : ok if out of lifecycle ? async call to wait for UI refresh and new computed size
      // initFlowbite(); // TOO slow
      await refreshTooltips();
      // flowbiteInSync = false; // Infinite loop...
    }, 300)();
  }
  
  // TODO : opti, only for tooltips reloads... 
  // $: isFullScreen, initFlowbite(); // No effect...

  // Timer start time. Use it to ensure delay,
  // example : 507 page of 124 items
  //          => 10 minutes per page = 5070 minutes for all items
  //          = 5070/60 = 84.5 hours
  //          => SMIC fr 35hr/week
  //          = 84.5 / 35 = 2.4 week for SMIC peoples
  //
  // BUT ALL this is based on 10 minutes per page :
  //          => May be SMIC peoples will not have skills to qualify
  //          => 35hr/week with human life need time to go toilette etc...
  //          => 1 page is 124 items to qualify in 10 minutes
  //          = 124 / 10 = 12.4 items per minutes
  //          = 12.4 / 60 = 0.21 items per secondes
  //          => around 5 secondes to qualify one item...
  export let timerStart = 5;
  let timer = tweened(timerStart);

  let tInterval = null;
  const startTimer = () => {
    tInterval && clearInterval(tInterval);
    $timer = timerStart;
    tInterval = setInterval(() => {
      if ($timer > 0) {
        $timer--;
      } else {
        $timer = 0;
        clearInterval(tInterval);
      }
    }, 1000);
  };
  // startTimer();
  dayjs.locale("fr"); // Fr locale // TODO : global config instead of per module ?
  dayjs.tz.setDefault("Europe/Paris");

  // $: minutes = Math.floor($timer / 60);
  $: lastSelectedIndex, startTimer();

  let isLoading = false;
  const urlParams = new URLSearchParams(window.location.search);
  const pageNumber = urlParams.get("page") ?? "1";

  Number.prototype.toPrettyNum = function (this: Number, length: number, maxLength = null) {
    if (maxLength === null) maxLength = length;
    var s = this;
    const splited = s
      .toFixed(maxLength).replace(new RegExp(`0{0,${maxLength - length}}$`), "")
      // https://stackoverflow.com/questions/5025166/javascript-number-formatting-min-max-decimals
      // .replace(/0{0,2}$/, "")
      // .toLocaleString('en-US', { // TODO : centralize toPrettyNum and use locals formatings ?
      //   minimumFractionDigits: 2,
      //   maximumFractionDigits: 4
      // })
      .replace(".", ",")
      .split(',');
    return (splited[0] ?? '').replace(/\B(?=(\d{3})+(?!\d))/g, " ")
    + (length >= 1 ? "," : "") 
    + (splited[1] ?? '');
  };

  declare interface Number {
    toPrettyNum(length: number): string;
  }

  // const hackyRefresh = (data) => {
  //   // TODO : better sync all in-coming props from 'needSync' attr ?
  //   // TODO : + why not working simpliy with :
  //   // timingSlot?.maxPath = data.sync.maxPath;
  //   // timingSlot?.maxPriceTag = data.sync.maxPriceTag;
  //   // => might be related to bind:timeQualifs ? bind for update
  //   //    from view is wrong way ? always need to update :
  //   //      - self for self view
  //   //      - parent list to propagate outside (parent + children + etc...)
  //   // Hacky or regular solution ? :
  //   // TIPS : USING _.merge keep existing references and avoid
  //   // messing up Svelte reactivity like above ? (BUT OK for TagsInput component, why ?)
  //   if (timingSlot?.maxPath) {
  //     // Clean initial values 'inPlace' :
  //     // https://stackoverflow.com/questions/1232040/how-do-i-empty-an-array-in-javascript
  //     // timingSlot?.maxPath.length = 0;
  //     // https://stackoverflow.com/questions/19316857/removing-all-properties-from-a-object
  //     Object.keys(timingSlot?.maxPath).forEach(
  //       (key) => delete timingSlot.maxPath[key]
  //     );
  //     //   timingSlot?.maxPath = _.merge(timingSlot?.maxPath, data.sync.maxPath);
  //     //   timingSlot?.maxPriceTag = _.merge(
  //     //     timingSlot?.maxPriceTag,
  //     //     data.sync.maxPriceTag
  //     //   );
  //     //   // if (data.sync.maxPath) {
  //     //   //   _.merge(timingSlot?.maxPath, data.sync.maxPath);
  //     //   //   _.merge(timingSlot?.maxPriceTag, data.sync.maxPriceTag);
  //     //   // } else {
  //     //   //   timingSlot?.maxPath = {};
  //     //   //   timingSlot?.maxPriceTag = data.sync.maxPriceTag;
  //     //   // }
  //     // } else {
  //     //   timingSlot?.maxPath = data.sync.maxPath;
  //     //   timingSlot?.maxPriceTag = data.sync.maxPriceTag;
  //   }
  //   timingSlot?.maxPath = _.merge(
  //     {}, // Create new instance OBJECT, otherwise 
  //     // will share max between multiple time slots...
  //     timingSlot?.maxPath, data.sync.maxPath
  //   );
  //   // timingSlot?.maxPath = data.sync.maxPath;
  //   timingSlot?.maxPriceTag = _.merge(
  //     {}, // Create new instance OBJECT, otherwise 
  //     // will share max between multiple time slots...
  //     timingSlot?.maxPriceTag,
  //     data.sync.maxPriceTag
  //   );
  //   // timingSlot?.maxPriceTag = data.sync.maxPriceTag;
  //   // assign self to trigger reactive refresh :
  //   // timingSlot = timingSlot;
  // };
  export let toggleQualif = async (q, targetSlot) => {
    // const syncTiming = targetSlot; // TIPS : to avoid async change of targetSlot
    const syncStartIdx = lastSelectedIndex;
    if (undefined !== selectionStartIndex) {
      // avoid bulk process stop on early selectionStartIndex switch...
      // Toggle qualif of all previous or next qualifs :
      let delta = selectionStartIndex - syncStartIdx;
      let step = delta > 0 ? -1 : 1;
      while (delta !== 0) {
        const timingTarget = timings[syncStartIdx + delta];
        const newT = await toggleQualifByTiming(q, timingTarget);
        newT && (timings[syncStartIdx + delta] = newT);
        console.log("Selection side qualif for " + timingTarget.sourceStamp);
        delta += step;
      }
    }

    // TIPS : targetSlot could have change due to reactivity on lastSelectedIndex
    // // targetSlot = toggleQualifByTiming(q, syncTiming);
    // syncStartIdx is a const on original index, it's our target even if
    // lastSelectedIndex did change
    // TODO : ok only if timings do not change for x reason, and index mismatch... (not our use case for now...)
    const newT = await toggleQualifByTiming(q, timings[syncStartIdx]);
    newT && (timings[syncStartIdx] = newT);
    // TODO : Refactor all to this way, + improve to fix if timings change
    //  (map filter new list on existing ids in current qualif list...)
  }

  export let toggleQualifByTiming = async (qualif, targetSlot) => {
    isLoading = true;
    const data = {
      _csrf_token: stateGet(get(state), "csrfTimingToggleQualif"),
      timeSlotId: targetSlot?.id,
      qualifId: qualif.id,
    };
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_timing_qualif_toggle", {
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
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          const data = await resp.json();
          // const data = await resp.text();
          // console.debug("resp", data);
          // targetSlot?.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          // // // TODO : better sync all in-coming props from 'needSync' attr ?
          // targetSlot?.maxPath = data.sync.maxPath;
          // targetSlot?.maxPriceTag = data.sync.maxPriceTag;

          // targetSlot = {
          const newSlot = {
            ...targetSlot,
            tags: Object.values(data.newTags),
            maxPath: data.sync.maxPath,
            maxPriceTag: data.sync.maxPriceTag,
          };

          // targetSlot = newSlot; // Will only change local param ref, not parent caller reactivity...
          // timings[lastSelectedIndex] = newSlot;
          // hackyRefresh(data);

          // TODO : NO reactivity for targetSlot?.maxPath ?
          //        => missing live price lookup update at top of SlotView when changing tags,
          //           but : ok on full page refresh...
          // targetSlot = {...targetSlot}; // TIPS : FORCE Svelte reactivity rebuild, since props check is not deep checked
          lastSelectedIndex = lastSelectedIndex; // Svelte reactive force reloads
          console.debug("Did toggle qualif, updated tags : ", targetSlot?.tags);
          stateUpdate(state, {
            csrfTimingToggleQualif: data.newCsrf,
          });

          return newSlot;
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
      isLoading = false;

      return resp;
  };
  export let removeAllTags = async () => {
    isLoading = true;
    await new Promise((r) => setTimeout(r, 200)); // Wait for isLoading anim
    if (
      !confirm(
        "Êtes vous sur de vouloir supprimer tous les tags du segment sélectionné ?"
      )
    ) {
      isLoading = false;
      return;
    }
    // const syncTiming = timingSlot;
    const syncStartIdx = lastSelectedIndex;
    if (undefined !== selectionStartIndex) {
      // avoid bulk process stop on early selectionStartIndex switch...
      // TODO : factorize Toggle qualif of all previous or next qualifs :
      let delta = selectionStartIndex - syncStartIdx;
      let step = delta > 0 ? -1 : 1;
      while (delta !== 0) {
        const timingTarget = timings[syncStartIdx + delta];
        await removeAllTagsByTiming(timingTarget);
        console.log("Selection side qualif for " + timingTarget.sourceStamp);
        delta += step;
      }
    }
    await removeAllTagsByTiming(timings[syncStartIdx]);
    isLoading = false;
  }

  export let removeAllTagsByTiming = async (timingTarget) => {
    const data = {
      _csrf_token: stateGet(get(state), "csrfTimingTagRemoveAll"),
      timeSlotId: timingTarget?.id,
    };
    const formData = new FormData();
    for (const name in data) {
      formData.append(name, data[name]);
    }
    const resp = await fetch(
      Routing.generate("mws_timing_tag_remove_all", {
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
          throw new Error("Not 2xx response", { cause: resp });
        } else {
          const data = await resp.json();
          timingTarget?.tags = Object.values(data.newTags); // A stringified obj with '1' as index...
          // // TODO : better sync all in-coming props from 'needSync' attr ?
          timingTarget?.maxPath = data.sync.maxPath;
          timingTarget?.maxPriceTag = data.sync.maxPriceTag;
          // hackyRefresh(data);

          // timingTarget = timingTarget; // NICE try, but won't work...
          timingSlot = timingSlot; // TIPS +++++ : ok, refresh current slot view UI and deps
          lastSelectedIndex = lastSelectedIndex; // Svelte reactive force reloads (only one well placed is enough ;)
          console.debug("Did sync tags", timingTarget?.tags);
          stateUpdate(state, {
            csrfTimingTagRemoveAll: data.newCsrf,
          });
        }
      })
      .catch((e) => {
        console.error(e);
        // TODO : in secure mode, should force redirect to login without message ?, and flush all client side data...
        const shouldWait = confirm("Echec de l'enregistrement.");
      });
  };

  $: slotPath = timingSlot?.source?.path
    ? Routing.generate("mws_timing_fetchMediatUrl", {
        // encodeURI('file://' + timingSlot?.source.path)
        url: "file://" + timingSlot?.source.path,
        keepOriginalSize: true,
      })
    : null;

  let qualifTemplates = timeQualifs.map((q, qIdx) => {
    q.toggleQualif = async () => {
      console.log(qIdx + ": Toggle qualif " + q.label, q);
      await toggleQualif(q, timingSlot);
    };
    return q;
  });

  // TODO : factorize code with SlotTHumbnail etc...
  let fetchQualifsFor = (timing) => {
    let allQualifsFor = [];
    console.debug("SlotView checkQualifs for", timing, quickQualifTemplates);
    quickQualifTemplates?.forEach((q) => {
      const filteredArray = timing.tags?.filter(tt => q.timeTags?.filter(
        (qt) => tt.slug === qt.slug
      ).length > 0);
      if (filteredArray.length === q.timeTags?.length) {
        allQualifsFor.push(q);
      }
    });
    // quickQualifTemplates = quickQualifTemplates;
    return allQualifsFor;
  }

  let currentTimeSlotQualifs;
  // TIPS, use 'quickQualifTemplates, ' to ensure currentTimeSlotQualifs
  //       also get refreshed if quickQualifTemplates did change
  $: quickQualifTemplates, currentTimeSlotQualifs = fetchQualifsFor(timingSlot);

  $: qualifShortcut = (quickQualifTemplates ?? []).reduce(
    (acc, qt) => {
      const charCode = String.fromCharCode(qt.shortcut).charCodeAt(0);
      if (!acc[charCode]) {
        // only pick FIRST same shortcut in user ordered quicklist qualif templates
        acc[charCode] = qt.toggleQualif;
      } // TODO : show msg about multiple key ignored ? 
      // (or cycle shift on key instead of toggle ?)
      return acc;
    },
    {
      // TIPS : not so used, and eat shortcut in the middle of the keyboard :...
      // ["d".charCodeAt(0)]: removeAllTags,
    }
  );

  // TODO : refactor meanings : will add shortcut to 'GROUPS of Qualifs'
  // => allow end user to select shortcut himself
  // TODO : config backend for backup upload /download + save connected user shortcuts...

  const haveNoExtraKey = (k) => 
  // Tips : testing 'k.shiftKey ||' will break '>' since need shift to do this one
    !(k.altKey || k.metaKey || k.altKey);

  const isKey = {
    space: (k) => haveNoExtraKey(k) && k.keyCode == 32,
    return: (k) => haveNoExtraKey(k) && k.keyCode == 13,
    zoomLower: (k) => haveNoExtraKey(k) && k.key == "<",
    zoomHigher: (k) => haveNoExtraKey(k) && k.key == ">",
    qualifShortcut: (k) => {
      return haveNoExtraKey(k) && qualifShortcut[k.key.charCodeAt(0)] ?? null
    },
  };

  const onKeyDown = async (e) => {
    // console.debug("Key down : ", e);
    console.debug("Key down : ", e.code, e);
    if (isKey.space(e)) {
      isFullScreen = !isFullScreen;
      e.preventDefault();
    }
    const zoomStep = 5;
    if (isKey.zoomLower(e)) {
      zoomRangeUI = zoomRangeUI >= zoomStep ? zoomRangeUI - zoomStep : zoomRangeUI;
      e.preventDefault();
    }
    if (isKey.zoomHigher(e)) {
      zoomRangeUI =
      (zoomRangeUI <= 100 - zoomStep) ? zoomRangeUI + zoomStep : zoomRangeUI;
      e.preventDefault();
    }
    if (isKey.return(e)) {
      // Toggle selection start (will qualif over multiples pages)
      // => juste hit 'enter' to start selection at current index
      // then all qualif will go on all selected slots between
      // start selection and current selection...
      if (null == selectionStartIndex) {
        selectionStartIndex = lastSelectedIndex;
      } else {
        selectionStartIndex = undefined;
      }
      e.preventDefault();
    }
    if (isKey.qualifShortcut(e)) {
      await isKey.qualifShortcut(e)(); // TODO : should not block event flow ? no await ?
      e.preventDefault();
    }
  };

  let moveResp = moveSelectedIndex(0);
  // TIPS : in conjunction with tests on lastSelectedIndex, will refresh move position:
  $: lastSelectedIndex, (moveResp = moveSelectedIndex(0));

  // compute from element size at onMount
  let Height, minHeight, maxHeight;
  let initialHeight, initialY;
  let resizing = false;

  // {left: 0, top: -28}left: 0top: -28[[Prototype]]: Object
  let avoidFirstStrangeTop = false; // In fullscreen, before move, wrong -20 position...

  function onDragMove(Event) {
    // https://stackoverflow.com/questions/5429827/how-can-i-prevent-text-element-selection-with-cursor-drag
    // Event.stopPropagation(); // Try to avoid text selections
    // Event.preventDefault();
    // This one is CUSTOM event, catch from parent is ok with :
    // on:mousedown|stopPropagation|preventDefault

    const deltaY = Event.detail.position.top - initialY;
    console.debug("Drag pos", Event.detail.position);
    if (avoidFirstStrangeTop) {
      avoidFirstStrangeTop = false;
      // Might not be a bug ? because my data go over screen height
      // and first height set move all pos ? (in fullscreen mode only)
      initialY = Event.detail.position.top;
      return;
    }
    console.debug("Drag to", deltaY, "from", initialHeight);
    if (resizing) {
      Height = Math.max(minHeight, Math.min(maxHeight, initialHeight + deltaY));
    } else {
      console.debug("Drag start", Event);
      initialY = Event.detail.position.top;
      // initialHeight = slotHeader.getBoundingClientRect().height; // slotHeader.offsetHeight;
      initialHeight = slotHeader.offsetHeight;
      console.debug("Init height", initialHeight);
      avoidFirstStrangeTop = true;
      resizing = true;
    }
  }
  function onDragStop(Event) {
    // // https://stackoverflow.com/questions/5429827/how-can-i-prevent-text-element-selection-with-cursor-drag
    // Event.stopPropagation(); // Try to avoid text selections (on:mousemove etc...)
    // Event.preventDefault();
    // This one is CUSTOM event, catch from parent is ok with :
    // on:mousedown|stopPropagation|preventDefault
    resizing = false;
    console.debug("Stop height", slotHeader.offsetHeight);
  }

  // compute from element size at onMount
  let slotHeight, slotMinHeight, slotMaxHeight;
  let slotInitialHeight, slotInitialY;
  let slotResizing = false;

  // {left: 0, top: -28}left: 0top: -28[[Prototype]]: Object
  let slotAvoidFirstStrangeTop = false; // In fullscreen, before move, wrong -20 position...

  const onSlotDragMove = (e) => {
    const deltaY = e.detail.position.top - slotInitialY;
    console.debug("Drag pos", e.detail.position);
    if (slotAvoidFirstStrangeTop) {
      slotAvoidFirstStrangeTop = false;
      // Might not be a bug ? because my data go over screen height
      // and first height set move all pos ? (in fullscreen mode only)
      slotInitialY = e.detail.position.top;
      return;
    }
    console.debug("Drag to", deltaY, "from", slotInitialHeight);
    if (slotResizing) {
      zoomRange = zoomStartRange; // Only att range zoomStartRange for scrolls
      slotHeight = Math.max(
        slotMinHeight,
        Math.min(slotMaxHeight, slotInitialHeight + deltaY)
      );
    } else {
      console.debug("Drag start", e);
      slotInitialY = e.detail.position.top;
      // slotInitialHeight = slotView.getBoundingClientRect().height; // slotView.offsetHeight;
      slotInitialHeight = slotView.offsetHeight;
      console.debug("Init height", slotInitialHeight);
      slotAvoidFirstStrangeTop = true;
      slotResizing = true;
    }
  };
  const onSlotDragStop = (e) => {
    slotResizing = false;
    console.debug("Stop height", slotView.offsetHeight);
  };

  onMount(async () => {
    // Height = slotHeader.offsetHeight; // TIPS : do not setup on mount, will have fixed size otherwise
    minHeight = 42;
    // TIPS : ok to go over screen size since we have multi-scrolls
    maxHeight = Infinity; // window.screen.height;

    // slotHeight = slotView.offsetHeight; // TIPS : do not setup on mount, will have fixed size otherwise
    slotMinHeight = 42;
    slotMaxHeight = Infinity; // window.screen.height;

    // await new Promise((r) => setTimeout(r, 500));

    // https://svelte.dev/repl/cfd1b8c9faf94ad5b7ca035a21f4dbd1?version=4.2.12
    // https://github.com/rozek/svelte-touch-to-mouse
    return mapTouchToMouseFor(".draggable");
    // mapTouchToMouseFor("div"); // TODO : not working on my Android phone, other error ?

    // TIPS : for computed height after css transformations :
    // Height = slotHeader.getBoundingClientRect().height;
    // DOMRect {
    //   bottom: 177,
    //   height: 54.7,
    //   left: 278.5,​
    //   right: 909.5,
    //   top: 122.3,
    //   width: 631,
    //   x: 278.5,
    //   y: 122.3,
    // }
  });

  let detailIsHovered = false;

  // $: {
  //   isFullScreen, (async () => {
  //     // TODO : For if switch, await tick enough ? 
  //     await new Promise((r) => setTimeout(r, 200));
  //     // https://svelte.dev/repl/cfd1b8c9faf94ad5b7ca035a21f4dbd1?version=4.2.12
  //     // https://github.com/rozek/svelte-touch-to-mouse
  //     mapTouchToMouseFor(".draggable"); // PB ? can be called only once ?
  //     // mapTouchToMouseFor("div"); // TODO : not working on my Android phone, other error ?
  //   })();
  // }

  let headerScroll = 0;
  const headerScrollHandler = (e) => {
    // console.log(e);
    // https://css-tricks.com/styling-based-on-scroll-position/
    headerScroll = e.target.scrollTop;
  }

</script>

<svelte:window on:keydown={onKeyDown} />

<!-- class:opacity-80={isLoading}
  style:pointer-events={isLoading ? "none" : "auto"}
  style:opacity={isLoading ? 0.8 : 1} -->
<div
  bind:this={htmlRoot}
  class="mws-timing-slot-view overflow-y-auto
  text-xs md:text-base
  flex flex-row flex-wrap content-start
  {classNames}"
  style={sizeStyle}
  class:pointer-events-none={isLoading}
>
  <!-- {JSON.stringify(timingSlot)} -->
  <div class="pr-2 sticky top-0 left-0 bg-white/95 z-20">
    {dayjs(timingSlot?.sourceTimeGMT)
      .tz("Europe/Paris")
      .format("YYYY-MM-DD HH:mm:ss")}
    [{timingSlot?.rangeDayIdxBy10Min ?? "--"}]
    <!-- // TODO : strange : tags are reactive, but not the maxPath etc ? -->
    {#if timingSlot}
      <span
        class="border"
        class:border-gray-600={!timingSlot?.tags?.length}
        class:border-green-400={timingSlot?.tags?.length}
      >
        {timingSlot.maxPath?.maxValue ?? 0}/hr [{(timingSlot.maxPath &&
          (timingSlot.maxPath?.maxValue
            ? (timingSlot.maxPath.maxValue * 10) / 60
            : null
          )?.toPrettyNum(2)) ??
          "--"} €]
      </span>
    {/if}
  </div>
  <div class="sticky top-0 left-0 bg-white/95 z-20">
    {timingSlot?.sourceStamp?.split("/").slice(-1) ?? "--"}
  </div>

  <!-- {timingSlot?.sourceStamp}
      class:z-20={!isFullScreen}
      class:z-40={isFullScreen}
      // z-20 TOO low for side list (absolut of it will go over inside modals...)
  -->
  <div
    class="full-screen-container bg-black text-white fill-white
    rounded-se-lg overflow-scroll z-[51] md:z-40
    {fullscreenClass}"
    class:fixed={isFullScreen}
    class:top-0={isFullScreen}
    class:bottom-0={isFullScreen}
    class:left-0={isFullScreen}
    class:right-0={isFullScreen}
  >
    <!-- <div class="max-h-[7rem] overflow-hidden
  hover:max-h-fit hover:overflow-scroll"> -->
    <div
      on:click|stopPropagation
      bind:this={slotHeader}
      class="mws-timing-slot-header overflow-scroll relative"
      class:is-fullscreen={isFullScreen}
      class:h-[7rem]={!isHeaderExpanded}
      class:wide:h-[6rem]={!isHeaderExpanded}
      style={Height
        ? `
        height: ${Height}px;
      `
        : ""}
      on:mouseover={() => (detailIsHovered = true)}
      on:mouseleave={() => (detailIsHovered = false)}
      on:mouseout={() => (detailIsHovered = false)}
      on:scroll={headerScrollHandler}
      data-scroll={headerScroll}
    >
      <span
        class="tags-details float-left max-w-[70%] md:max-w-[75%]
        rounded-md z-40 inline-flex flex-wrap
        ml-1 mr-1 text-xs md:text-base
        pointer-events-none md:pointer-events-none
        "
        class:!max-w-[50%]={isFullScreen && !isMobile}
        class:!max-w-[80%]={isFullScreen && isMobile}
        class:top-0={!isFullScreen}
        class:sticky={!isFullScreen}
        class:fixed={isFullScreen}
        class:bottom-0={isFullScreen}
        class:left-0={isFullScreen}
      >
        <span
          class="border mt-1 p-0 bg-black"
          class:border-gray-600={!timingSlot?.tags?.length}
          class:border-green-400={timingSlot?.tags?.length}
        >
          {timingSlot?.maxPath?.maxValue ?? 0}/hr [{(timingSlot?.maxPath &&
            (timingSlot?.maxPath?.maxValue
              ? (timingSlot?.maxPath?.maxValue * 10) / 60
              : null
            )?.toPrettyNum(2)) ??
            "--"} €]
        </span>
        {#each timingSlot?.tags ?? [] as tag}
          <span
            class="ml-1 mr-1 mt-1 text-white bg-black
          border-blue-600 border rounded-sm p-0"
          >
            {tag.label}
          </span>
        {/each}
      </span>
      <!-- <span class="float-right right-0 top-0 m-1 sticky
    pointer-events-none opacity-75 hover:opacity-100"> -->
      <span
        class="float-right m-1 mt-2 cursor-context-menu hover:opacity-90"
        on:click|stopPropagation={() => {
          Height = null;
          slotHeight = null;
          isHeaderExpanded = !isHeaderExpanded;
          zoomRange = zoomStartRange;
        }}
      >
        <!-- TIPS : why $timer is tweended and will have FLOAT values : -->
        <!-- {$timer} -->
        <!--
          https://tr.javascript.info/bubbling-and-capturing#capturing
          https://svelte.dev/repl/1ff9d07bc2fc45349874b1b1b2c013e4?version=3.29.4
          https://stackoverflow.com/questions/1369035/how-do-i-prevent-a-parents-onclick-event-from-firing-when-a-child-anchor-is-cli
          on:click|capture|stopPropagation // Will stop the event, not clicking elt below
          TODO : any way to do same like pointer-event-none with hover allowed ?
        -->
        <ProgressIndicator
          percent={1 - $timer / timerStart}
          textRenderer={(percent) => `${$timer.toFixed(0)}`}
        />
        <span
          class="top-0 left-0 flex
        bg-black pointer-events-none
          rounded z-50"
          class:opacity-25={isFullScreen && detailIsHovered}
          class:w-[6em]={!isFullScreen}
          class:w-[14em]={isFullScreen}
          class:left-0={isFullScreen}
          class:fixed={isFullScreen}
        >
          {dayjs(timingSlot?.sourceTimeGMT)
            .tz("Europe/Paris")
            .format("YYYY-MM-DD HH:mm:ss")}
          [{timingSlot?.rangeDayIdxBy10Min ?? "--"}]
        </span>
      </span>
      <span
        class="max-w-[7em] p-1 hover:opacity-100
        rounded z-50 cursor-pointer"
        class:opacity-25={isFullScreen && detailIsHovered}
        class:right-12={true}
        class:absolute={!isFullScreen}
        class:top-0={!isFullScreen}
        class:top-10={isFullScreen}
        class:left-0={false}
        class:fixed={isFullScreen}
      >
        <span class="bg-black"
          on:click={() => {
            if (undefined === selectionStartIndex) {
              selectionStartIndex = lastSelectedIndex;
            } else {
              selectionStartIndex = undefined;
            }
          }}
        >
          [{pageNumber}-{
            undefined !== selectionStartIndex ?
            `${selectionStartIndex}..` : ''}{lastSelectedIndex}]
        </span>
      </span>

      {#if isFullScreen}
        <!-- // TIPS : no need, z-index for lookup page more higher
        text-xs md:text-base float-right -->
        <!-- <span class="right-14 top-0 z-40 fixed flex">
          {#if moveResp.isFirst && pageNumber > 1}
            <button
              class="float-right m-1"
              on:click|stopPropagation={() => movePageIndex(-1)}
            >
              Prev. Page
            </button>
          {/if}
          <button
            class="float-right m-1"
            style:opacity={!moveResp.isFirst ? 1 : 0.7}
            on:click|stopPropagation={() => (moveResp = moveSelectedIndex(-1))}
          >
            Prev.
          </button>
          <button
            class="float-right m-1"
            style:opacity={!moveResp.isLast ? 1 : 0.7}
            on:click|stopPropagation={() => (moveResp = moveSelectedIndex(1))}
          >
            Next.
          </button>
          {#if moveResp.isLast}
            <button
              class="float-right m-1"
              on:click|stopPropagation={() => movePageIndex(1)}
            >
              Next. Page
            </button>
          {/if}
        </span>
        <span class="float-right w-[14rem] h-7" /> -->
      {/if}
      <span class="float-right flex flex-wrap justify-end items-start p-2">
        <TagsInput 
        bind:timings
        bind:lastSelectedIndex
        bind:selectionStartIndex
        bind:tags={timingSlot.tags} timing={timingSlot} {locale} />
      </span>
      <!-- {#each qualifTemplates ?? [] as qt, idx}
        <button
          class="float-right m-1"
          on:click|stopPropagation={qt.toggleQualif}
        >
          [{String.fromCharCode(qt.shortcut)}] {qt.label}
        </button>
      {/each} -->
      <QuickList
        {allTagsList}
        bind:isHeaderExpanded
        bind:qualifTemplates
        bind:quickQualifTemplates
        {locale}
      />
      <button
        class="bg-red-500 float-right m-2"
        on:click|stopPropagation={removeAllTags}
      >
        Supprimer tous les tags
      </button>
      <Loader {isLoading} />

      <!-- 
        class:hidden={!isHeaderExpanded}
      -->
      <!-- 
        TOO tricky with inner auto scroll at bottom ?
        <div class="draggable absolute bottom-0 left-0
       fill-white text-white bg-black/50 z-40 p-2"
      use:draggable={{
        helper:'clone', revert:true
      }}
        on:drag:move={onDragMove} on:drag:stop={onDragStop}
      >
        <svg class="w-7 h-7" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 20V7m0 13-4-4m4 4 4-4m4-12v13m0-13 4 4m-4-4-4 4"/>
        </svg>      
      </div>   -->
    </div>
    <!-- TODO : solve clone or elmt get crasy, for now hide to hide bug with :
    class:hidden={resizing}

    class:bg-red-500={resizing}  
    -->
    <div
      class="overflow-visible sticky top-0 flex items-end h-[0px] 
    fill-white/70 text-white/70 bg-black/50 z-40"
      class:hidden={resizing}
      on:click|stopPropagation|preventDefault
    >
      <div
        class:bg-red-500={false}
        class="draggable pl-4"
        use:draggable={{
          helper: "clone", // TODO: handler is going faster than mouse on Y...?

          // https://svelte.dev/repl/cfd1b8c9faf94ad5b7ca035a21f4dbd1?version=4.2.12
          // TODO : handler way, this one is out of the div to resize ? so
          //        top resized box messup thumb pointer position ?
          // use:draggable={{
          //   handle:'.Note-Titlebar', containment:'parent', cursor:'grabbing'
          // }}
          cursor: "grabbing",
          // handle:'.mws-timing-slot-header',
          revert: true,
        }}
        on:drag:move={onDragMove}
        on:drag:stop={onDragStop}
        on:click|stopPropagation|preventDefault
      >
        <svg
          class="w-7 h-7 ml-1"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 20V7m0 13-4-4m4 4 4-4m4-12v13m0-13 4 4m-4-4-4 4"
          />
        </svg>
      </div>
    </div>
    <!-- // TODO : remove max-h-[85%]={isFullScreen} + work with flex grow ?
    + https://stackoverflow.com/questions/15999760/load-image-asynchronous
    (but load this one first...)

      class:h-[95dvh]={isFullScreen && isHeaderExpanded}
      class:mb-[1rem]={isFullScreen} // bp : will add double scroll

      // => not needed any more, use full width for image
      // user can resize part of screen if want to fit part of it...
      class:h-[95dvh]={isFullScreen && isHeaderExpanded}

      use:panzoom={{ render, width: image.width, height: image.height }}
    -->
    <div
      class="overflow-visible flex items-end sticky top-0
    z-30 w-full"
    >
      <div class="fill-white/70 text-white/70 bg-black/50 w-full">
        <input
          bind:value={zoomRangeUI}
          on:change={(e) => {
            slotHeight = null;
            // Below no need, will be done by svelte reactive call, cf $:
            // zoomRange = computeZoomRange(e.target.value);
          }}
          id="zoom-range"
          type="range"
          class="w-full h-1 bg-gray-200/50 rounded-lg
          appearance-none cursor-pointer outline-none
           "
        />
      </div>
      <!-- {#if !isFullScreen } -->
      <div class="sticky z-40 bottom-16
      inline-flex right-0 bg-white overflow-visible min-h-[2.4rem]"
      class:p-1={currentTimeSlotQualifs?.length}
      >
        {#each currentTimeSlotQualifs?? [] as q}
          {@const tooltipId = `htmlIconTooltip-${newUniqueId()}`}
          <div class="inline-flex justify-center items-center
          border-b-4 border-t-4 object-contain overflow-hidden"
          data-tooltip-target={tooltipId}
          data-tooltip-placement="top"        
          style={`
            border-color: ${q.primaryColorHex};
          `}>
            <HtmlIcon qualif={q}></HtmlIcon>
          </div>
          <div id={tooltipId} role="tooltip" class="absolute z-50 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
            {q?.label}
            <div class="tooltip-arrow" data-popper-arrow></div>
          </div>
        {/each}
      </div>
      <!-- {/if} -->
    </div>
    <!-- class:h-[80dvh]={!slotHeight &&
      zoomRange == zoomStartRange &&
      isFullScreen &&
      !isHeaderExpanded}
    
    
    https://developer.mozilla.org/en-US/docs/Web/CSS/touch-action
    https://tailwindcss.com/docs/touch-action
    https://www.npmjs.com/package/svelte-gestures
      // TODO : swipe stop propagation events or css events none
      is blocking click ?
      use:swipe={{ timeframe: 300, minSwipeDistance: 42, touchAction: 'touch-pan-y' }}
      on:swipe={swipeImage}

      TIPS : on:dblclick will be useful with pan since click is used by pan too...
      TIPS :  w-max => width: max-content; will size to fit
      content and place absolut at end instead of allowed end
    -->
    <div class="relative flex"
      bind:this={viewWrapper}
      style:--tw-shadow-color="#000000"
    >
      <div
      class="float-left sticky left-0 h-auto z-50 w-0">
        <button
        class="nav-btn float-left min-w-[10dvw] h-full bg-transparent
        { lastSelectedIndex <= 0
          ? `opacity-0 hover:opacity-0`
          : `opacity-10 hover:opacity-20`
        }
        flex items-center justify-start"
          on:click={() => moveSelectedIndex(-1)}
        >
          <!-- <CaretLeft></CaretLeft> -->
          <!-- <img scr={caretLeft} /> -->
          <!-- <object data={caretLeft}></object> -->
          <!-- https://dev.to/hasantezcan/how-to-colorize-svg-image-1kc8 -->
          <!-- <div class="svg-icon bg-[var(--tw-shadow-color)]
          "
          style={`
            mask-image: url(${caretLeft});
            -webkit-mask-image: url(${caretLeft});
          ` } />
          <div class="svg-icon bg-white absolute
          hover:bg-gray-500 !w-[2.8rem]
          "
          style={`
            mask-image: url(${caretLeft});
            -webkit-mask-image: url(${caretLeft});
          ` } /> -->
          <Svg url={caretLeft}></Svg>
        </button>  
      </div>
      <object
        use:pan="{{delay:imagePanDelayMs}}"
        on:pan="{imagePanHandler}"  
        on:click={imageTouchstartHandler}
        on:mousedown={imageTouchstartHandler}
        on:touchstart={imageTouchstartHandler}
        bind:this={slotView}
        on:dblclick={() => (resizing ? null : (isFullScreen = !isFullScreen))}
        class="object-contain select-none border-solid border-4 w-full m-auto"
        draggable="false"
        class:border-gray-600={!timingSlot?.tags?.length}
        class:border-green-400={timingSlot?.tags?.length}
        data={"screenshot" == timingSlot?.source?.type ? slotPath : ""}
        type="image/png"
        style={`
        ${
          slotHeight && zoomRange == zoomStartRange
            ? `
            height: ${slotHeight}px;
          `
            : ""
        } /* transform: scale(${(2 * zoomRange) / 100});
        width: ${2 * zoomRange}%; */
        min-width: ${(2 * vWidth * zoomRange / 100).toFixed(0)}px;
        max-width: ${(2 * vWidth * zoomRange / 100).toFixed(0)}px;
        `}
      >
        <img class="w-full object-contain select-none m-auto" loading="eager"
        src={timingSlot.thumbnailJpeg}
        />
      </object>
      <div
      class="float-right sticky right-0 h-auto z-50 w-0">
        <button
        class={`nav-btn float-right h-full bg-transparent
        ${ moveResp.isLast
          ? `opacity-0 hover:opacity-0`
          : `opacity-10 hover:opacity-20`
        }
        flex items-center justify-end`}
        class:min-w-[10dvw]={isMobile}
        class:min-w-[5dvw]={!isMobile}
        on:click={() => moveSelectedIndex(1)}
        >
        <Svg url={caretRight}></Svg>
      </button>
      </div>
    </div>
    <!-- // Tips : z-[60] : Need to z on parent too :
      should go OVER 'previous timing' btn click area...
      // TIPS : add margin bottom to allow scroll above sticky bottom page elements...
      // TODO : service or state update of max top footer element, then align with it ?
    -->
    <div
      class="overflow-visible sticky top-0 flex items-end h-[0px]
    fill-white/70 text-white/70 bg-black/50 z-[60] mb-12"
      class:hidden={slotResizing}
      on:click|stopPropagation|preventDefault
    >
      <!-- // Tips : z-[60] : should go OVER 'previous timing' btn click area... -->
      <div
        class="draggable pl-4
        z-[60]
        "
        use:draggable={{
          helper: "clone", // TODO: clone is going faster than mouse on Y...?
          cursor: "grabbing",
          revert: true,
        }}
        on:drag:move={onSlotDragMove}
        on:drag:stop={onSlotDragStop}
        on:click|stopPropagation|preventDefault
      >
        <svg
          class="w-7 h-7 ml-1"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 20V7m0 13-4-4m4 4 4-4m4-12v13m0-13 4 4m-4-4-4 4"
          />
        </svg>
      </div>
    </div>


    <!-- <div
      class="overflow-visible flex items-end
    z-40 w-full"
    >
      <div class="fill-white text-white bg-black w-full">
        <label for="default-range" class="block mb-2 text-sm font-medium"
          >Zoom range {zoomRange}</label
        >
      </div>
    </div> -->

    <!-- TIPS : add bottom margin to allow height size increase
     (other wise, no bottom space to scroll up to...) -->
    <div class:mb-[5rem]={!isFullScreen} />

    <!-- <div
    class="object-contain border-solid border-4"
    class:w-full={isFullScreen}
    class:max-h-[85%]={isFullScreen && !isHeaderExpanded}
    class:border-gray-600={!timingSlot?.tags?.length}
    class:border-green-400={timingSlot?.tags?.length}
    >
      <!-- https://mvolfik.github.io/svelte-photoswipe/ -- >
      <PhotoSwipeGallery images={[
        {
          src: "screenshot" == timingSlot?.source?.type ? slotPath : "",
          // width: 3000,
          // height: 4000,
          alt: "Screenshot", // optional
          cropped: false, // optional, default=false; see https://photoswipe.com/v5/docs/ 
          // thumbnail: { src: slotPath, width: 300, height: 400 },
        }
      ]} styling="flex" />    
    </div> -->
  </div>
</div>

<!-- // TODO : <style global lang="scss">
       working or just request feature ?
       https://github.com/sveltejs/svelte/issues/6186
       https://github.com/sveltejs/svelte/issues/5492 -->
<style lang="scss">
  :global(object) {
    transition: transform 0.2s;
  }
  :global(.draggable) {
    // https://github.com/rozek/svelte-touch-to-mouse
    // for the MouseEvent consumers to work as expected :
    -webkit-touch-callout: none;
    -ms-touch-action: none;
    touch-action: none;
    // added in https://svelte.dev/repl/cfd1b8c9faf94ad5b7ca035a21f4dbd1?version=4.2.12 :
    -moz-user-select: none;
    -webkit-user-select: none;
    -ms-user-select: none;
    user-select: none;
  }

  .mws-timing-slot-header {
    .tags-details {
      @apply opacity-100;
    }
    &:hover {
      &:not(.is-fullscreen):not([data-scroll='0']) {
        .tags-details {
          @apply opacity-0;
        }
        
      }
    }

  }

  // https://www.freecodecamp.org/news/use-svg-images-in-css-html/
  // https://dev.to/hasantezcan/how-to-colorize-svg-image-1kc8
  .svg-icon {
    mask-size: 100%;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    mask-position: center;
    width: 3rem;
    height: 3rem;

    .nav-btn:hover & {
      @apply bg-gray-500;
    }
  }
  // .nav-btn {
  //   &:hover {
  //     .svg-icon {
  //       @apply bg-gray-500;
  //     }
  //   }
  // }

  :global(.nav-btn:hover .svg-icon) {
    @apply bg-gray-500;
  }

</style>
