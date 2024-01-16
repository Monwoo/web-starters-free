/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// TODO : integrate with some 'app' encore existing builds and configs... best way to extend ?

// import 'flowbite/dist/flowbite.min.js';
// import 'flowbite/dist/flowbite.js';
// import 'flowbite';
// https://flowbite.com/docs/getting-started/quickstart/#init-functions
import { initFlowbite } from 'flowbite'
initFlowbite();
// TODO : improve flowbite integration ? really better or in dev lib ? :
// https://flowbite.com/docs/getting-started/svelte/

// TIPS : should be init BEFORE registerSvelteControllerComponents that use it
import Routing from 'fos-router';

// TIPS : load ROUTES BEFORE adjustment, or will be rewrite by json content....
// <script src="{{ asset('bundles/fosjsrouting/js/router.min.js') }}"></script>
// <script src="{{ path('fos_js_routing_js', { callback: 'fos.Router.setData' }) }}"></script>
// Above not working... trying the JSON way :
// https://github.com/FriendsOfSymfony/FOSJsRoutingBundle/blob/master/Resources/doc/usage.rst
const routes = require('../../../apps/mws-sf-pdf-billings/backend/assets/fos-routes.json');
console.log(routes);
Routing.setRoutingData(routes);

// TODO : no need to unparse ? done by webpack ?
// const baseUrlFull = JSON.parse(process.env.BASE_HREF_FULL ?? "null");
const baseUrlFull = process.env.BASE_HREF_FULL ?? null;
Routing.setBaseUrl(baseUrlFull);
// const baseUrlPort = JSON.parse(process.env.BASE_HREF_PORT ?? "null");
const baseUrlPort = process.env.BASE_HREF_PORT ?? null;
if (baseUrlPort) { // TIPS : for absolute url to work in dev
  Routing.setPort(baseUrlPort);
}

import {
  registerSvelteControllerComponents
} from '@symfony/ux-svelte';
import './bootstrap.js';

registerSvelteControllerComponents(require.context('./svelte/controllers', true, /\.svelte$/));

// // TODO : remove code duplication and put this in some 'mws-utils' package ?
// // https://stackoverflow.com/questions/5796718/html-entity-decode
// // const decodeHtml = (html:string) => { // TODO : fail to load typescript syntax ? only modern js ?
// const decodeHtml = (html) => {
//   var txt = document.createElement("textarea");
//   txt.innerHTML = html;
//   return txt.value;
// }

// https://svelte.dev/docs/custom-elements-api (
// TODO : auto load from Svelte should be ok instead of injected ?)
// https://surveyjs.answerdesk.io/ticket/details/t8664/display-icon-to-dropdown-possible-answers
// https://surveyjs.io/form-library/documentation/customize-question-types/third-party-component-integration-react

// customElements.define('mws-msg-template-choice-item', TemplateChoiceItem.element);
// customElements.define('mws-msg-template-choice-item', () => (new TemplateChoiceItem({})).element);
import TemplateChoiceItem from 'mws-moon-manager-ux/components/message/TemplateChoiceItem.svelte';
window.customElements.get('s-mws-msg-template-choice-item') ||
  window.customElements.define('s-mws-msg-template-choice-item', TemplateChoiceItem.element);
// customElements.define('mws-msg-template-choice-item',  TemplateChoiceItem.element);

// https://stackoverflow.com/questions/60529034/svelte-custom-element-api
// customElement: true // Needed in svelte config, for .element to exist on component

// https://developer.mozilla.org/en-US/docs/Web/API/CustomElementRegistry/define
// customElements.define(
//   "my-customized-built-in-element",
//   MyCustomizedBuiltInElement,
//   {
//     extends: "p",
//   },
// );
// <p is="my-customized-built-in-element"></p>

// TODO : ensure custom element ok for hot reloads ? need page refresh for now
// https://nuclia.com/developers/how-to-run-svelte-custom-elements-in-dev-mode/


// Connect SurveyJs via JQuery and add to client window context :
////////////////////////////////////////
import * as jQuery from "jquery";
const $ = window.$ ?? jQuery;
window.$ = $;

import * as SurveyLib from "survey-core";
const Survey = window.Survey ?? SurveyLib; // use Survey from CDN ?
window.Survey = Survey;

// TODO : un-comment below will NOT be same as JS CDN injection
//in base.html.twig... WHY ?
import "survey-jquery";

// https://surveyjs.answerdesk.io/ticket/details/t2638/tagbox-not-visible
import "select2";

import {
  inputmask
} from "surveyjs-widgets";
import dayjs from 'dayjs';

window.dayjs = dayjs;


// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// https://stackoverflow.com/questions/178325/how-do-i-check-if-an-element-is-hidden-in-jquery/32182004
// $('div').is(':offscreen');
$.expr.filters.offscreen = function (el) {
  var rect = el.getBoundingClientRect();
  const dx = 0;
  const dy = 100;
  return (
    (rect.x + rect.width) < dx ||
    (rect.y + rect.height) < dy ||
    (rect.x > (window.innerWidth - dx) || rect.y > (window.innerHeight - dy))
  );
};

// XPath selection outside of chrome console :
// $x is used by chrome console only, document.evaluate is more generic
// https://stackoverflow.com/questions/6453269/jquery-select-element-by-xpath
window.$x = (STR_XPATH) => {
  var xresult = document.evaluate(STR_XPATH, document, null, XPathResult.ANY_TYPE, null);
  var xnodes = [];
  var xres;
  while (xres = xresult.iterateNext()) {
    xnodes.push(xres);
  }
  return xnodes;
}

// TODO : below fail on SOURCE map or postCss issue ? ...
// import "survey-core/defaultV2.css"; // TIPS : better inside app.scss
// import "survey-jquery/defaultV2.min.css";
import "survey-core/survey.i18n.js";

import {
  surveyTheme
} from './survey-js/_theme.json.js';

// https://knockoutjs.com/documentation/component-overview.html
// Survey.ko.components.register('mws-msg-template-choice-item', {
//   viewModel: function(params) {
//       // Data: value is either null, 'like', or 'dislike'
//       this.chosenValue = params.value;

//       // Behaviors
//       this.like = function() { this.chosenValue('like'); }.bind(this);
//       this.dislike = function() { this.chosenValue('dislike'); }.bind(this);
//   },
//   template:
//       '<div class="like-or-dislike" data-bind="visible: !chosenValue()">\
//           <button data-bind="click: like">Like it</button>\
//           <button data-bind="click: dislike">Dislike it</button>\
//       </div>\
//       <div class="result" data-bind="visible: chosenValue">\
//           You <strong data-bind="text: chosenValue"></strong> it\
//       </div>'
// });

// https://knockoutjs.com/documentation/component-custom-elements.html
// TODO : why regular web component not accessible + why knockout available when using jquery version ?

Survey.ko.components.register('mws-msg-template-choice-item', {
  viewModel: function (params) {
    console.log('Msg template item params : ', params);
    // Data: value is either null, 'like', or 'dislike'
      // this.chosenValue = params.item.jsonObj.templateCategorySlug;
      // this.chosenValue += ' => ' + params.item.jsonObj.templateNameSlug;
    this.chosenValue = encodeURIComponent(JSON.stringify(
      params.item.jsonObj
    ));
  },
  template:
    `<s-mws-msg-template-choice-item
    data-bind="attr: {item : chosenValue}"
    ></s-mws-msg-template-choice-item>`
});

// TODO : mirror knockout components with svelte to do so ? or need custom lib ? :
// Survey.ko.components.register('s-mws-msg-template-choice-item',  TemplateChoiceItem.element);


const surveyFactory = (surveyForm, dataModel) => {
  Survey
    .StylesManager
    .applyTheme("modern");

  // https://surveyjs.io/form-library/examples/control-data-entry-formats-with-input-masks/jquery#content-code
  // window['surveyjs-widgets'].inputmask(Survey);
  // https://surveyjs.io/form-library/examples/control-data-entry-formats-with-input-masks/vuejs#content-code
  // https://surveyjs.io/form-library/examples/questiontype-matrixdropdown/vuejs
  inputmask(Survey);
  console.debug("Survey dataModel :", dataModel);
  const surveyDataInput = $('[name$="[jsonResult]"]', surveyForm);

  const surveyData = JSON.parse(
    decodeURIComponent(surveyDataInput.val())
  );

  const surveyModel = new Survey.Model(dataModel);
  surveyModel.applyTheme(surveyTheme({}));
  // TODO: below from dataModel ? default if not in dataModel ?
  surveyModel.locale = 'fr';
  // https://github.com/surveyjs/survey-library/issues/86
  // TODO : fix submit button on all field fieled
  // BUT no effect, we only want to have button, to allow auto-submit
  // surveyModel.cssType = "bootstrap";
  surveyModel.sendResultOnPageNext = true;
  surveyModel.showCompletedPage = false;
  surveyModel.onComplete.add((sender, options) => {
    const responseData = JSON.stringify(sender.data, null, 3);
    console.log("Will sync response :", responseData);
    surveyDataInput.val(encodeURIComponent(responseData));
    surveyForm.submit();
  });
  // https://surveyjs.io/form-library/examples/file-upload/jquery#content-code
  surveyModel.onUploadFiles.add(async (_, options) => {
    const formData = new FormData();
    options.files.forEach((file) => {
      formData.append(file.name, file);
    });
    const token = window.mwsTchatUpTok;
    formData.append('_csrf_token', token);
    formData.append('mws_message_tchat_upload[submit]', token);
    console.debug("Will Upload with : ", formData, token);

    try {
      const resp = await fetch(Routing.generate('mws_message_tchat_upload', {
        _locale: 'fr' // TODO : work on it, why all those repetitions ?
      }), {
        method: "POST",
        body: formData,
        credentials: "same-origin", // Mandatory for csrf test, based on cookies
        redirect: 'error', // Needed if redirect to login screen instead of data answer
      });
      const respClone = resp.clone(); // In case .json fail, will have already
      // read the body and no way to show text error

      console.debug('Upload resp : ', resp);
      if (resp.ok) {
        const data = await resp.json();
        window.mwsTchatUpTok = data.renewToken;

        if (data.success == 'ok') {
          options.callback(
            'success',
            options.files.map((file) => {
              return {
                file: file,
                content: "http://localhost:8000/bundles/moonmanager/medias/MoonManagerLogo.png" // + data[file.name]
              };
            })
          );
          return;
        }
      }
      console.log("Resp error :");
      const txt = await respClone.text();
      console.log(txt);

    } catch (e) {
      console.warn('Upload file error :', e);
    }

    alert('Fail to upload file');

    options.callback('error', // TODO : no error text feedback allowed here ?
      ['An error occurred during file upload.'])

    // // const validFiles = [];
    // options.callback('error', // TODO : no error text feedback allowed here ?
    //   ['An error occurred during file upload.'], 
    //   // ['An error occurred during file upload.']
    //   // options.files.map((file) => {
    //   //   return {
    //   //     file: file,
    //   //     content: "An error occurred during file upload." // + data[file.name]
    //   //   };options.callback('error',
    //   // })
    // );
  });
  surveyModel.data = surveyData;

  // let surveyWrapper = $(".survey-js-wrapper", surveyForm);
  let surveyWrapper = $("#hack-test-sjs", surveyForm);

  if (!surveyWrapper.length) {
    surveyWrapper = $("<div class='survey-js-wrapper'></div>");
    surveyForm.prepend(surveyWrapper);
  }
  surveyWrapper.Survey({
    model: surveyModel
  });
  console.debug("Survey surveyWrapper :", surveyWrapper, surveyModel);
  // surveyModel.getQuestionByName('projectId').value = 'Forced PID';
  surveyWrapper.data('surveyModel', surveyModel);
  return surveyWrapper;
}

$(() => { // Wait for page load
  // Connect all page surveys :
  // setTimeout(() => { // TODO : Why survey js not loaded ? controller view output still not ready ?
  $('.mws-survey-js-form').each((idx, htmlSurveyForm) => {
    // WARNING : $(this) will work with js function, not lambda function...
    // const surveyForm = $(this);
    const surveyForm = $(htmlSurveyForm);
    // When having multiple forms, form take prefix for name ? sound like buggy behavior if target element is not surveyjs form, bugging all possible next forms....
    // const surveyModel = decodeURIComponent($('[name$="[surveyJsModel]"], [name$="mws_survey_js[surveyJsModel]"]', surveyForm).val());
    const surveyModel = decodeURIComponent($('[name$="[surveyJsModel]"]', surveyForm).val());
    // surveyFactory(surveyForm, JSON.parse(decodeHtml(surveyModel)));
    // TIPS : use '|raw' filter twig side to avoid html entities decode
    const surveyWrapper = surveyFactory(surveyForm, JSON.parse(surveyModel));
  });
  // }, 200);
})

// // TODO : Highlight search keyworkds :
// import Mark from 'mark.js';
// if (searchKeyword) {
//   let markFactory = new Mark(filterMarkTarget); // HTML target element
//   // markFactory.mark(keyword [, options]);
//   // Mark do not goes over multiple words with space, so did remove
//   // those for highlight to work :
//   markFactory.mark(searchKeyword.replace(" ", ""), {
//     accuracy: 'partially',
//     separateWordSearch: false, // if space, do not split highlight, highlight all match from start to end...
//     // TODO : can't highlight over multiples spaces...
//     // ok Query side, but will display all phone NUMBER without space to avoid missing highlight on spaced phone numbers...
//   });
// }