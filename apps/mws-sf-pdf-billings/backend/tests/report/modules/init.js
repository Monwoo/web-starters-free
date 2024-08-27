// 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

console.debug("Init module : ", import.meta.url); // URL du script

// import jQuery from 'https://code.jquery.com/jquery-3.6.0.min.js';
// await import('https://code.jquery.com/jquery-3.6.0.min.js');
await import('https://code.jquery.com/jquery-3.6.0.min.js');
// import 'https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js';
// import 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js';
await import('https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js');
await import('https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js');

console.log("Init script");

$(async ()  => {
  const mwsReport = await $.getJSON('../_output/mws-report.json');
  console.log("Having report : ", mwsReport);

  const reportBoard = $('#mws-report-board');

  for (let idx = 0; idx < mwsReport.length; idx++) {
    const reportItem = mwsReport[idx];
    reportBoard.append(`
      <h1>${reportItem.type}</h1>
      <p>${reportItem.data.name}</p>
    `);
  }
})

