<script lang="ts">
  import { onMount } from "svelte";
  import dayjs from "dayjs";

  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // https://flowbite.com/docs/plugins/charts/
  // https://apexcharts.com/
  import ApexCharts from "apexcharts";

  /*
  Generate logs :

  git log --pretty=$'x\tx\t%ai' --numstat \
  -i --grep='\[mws-pdf-billings\]' \
  --grep='\[mws-moon-manager\]' \
  --branches --tags --remotes --full-history \
  --date-order --date=iso-local> git-logs.tsv

  */
  // import gitLogs from "../../../../../apps/mws-sf-pdf-billings/backend/git-logs.tsv"; // TIPS : ok only if you enable dsv-loader with webpack...
  import gitLogsV1 from "dsv-loader?rows=0&delimiter=\t!../../../../../apps/mws-sf-pdf-billings/backend/git-logs.monwoo-moon-manager-v1.tsv";
  import gitLogsV2 from "dsv-loader?rows=0&delimiter=\t!../../../../../apps/mws-sf-pdf-billings/backend/git-logs.tsv";

  const gitLogs = gitLogsV2.concat(gitLogsV1);
  console.log(gitLogs);

  let byCategories = {};
  let currentCommitTime = null;

  gitLogs.forEach((log) => {
    if ("x" == (log[0] ?? null)) {
      // it's Day Log
      currentCommitTime = dayjs(log[2]);
    } else {
      if (
        null !== (log[0] ?? null) &&
        log[0].length &&
        log[1].length &&
        !isNaN(Number(log[0])) &&
        !isNaN(Number(log[1]))
        // NaN !== Number(log[1])
      ) {
        // currentCommitTime.format("YYYY-MM-DD HH:mm:ss")
        const catKey = currentCommitTime.format("YYYY-MM");
        let cat = byCategories[catKey] ?? null;
        if (!cat) {
          cat = [0, 0];
          byCategories[catKey] = cat;
        }
        cat[0] += Number(log[0]);
        cat[1] += Number(log[1]);
      }
    }
  });

  let addedTotal = 0;
  let removedTotal = 0;
  let [addedTotals, removedTotals] = Object.keys(byCategories).reduce(
    (acc, catKey) => {
      const cat = byCategories[catKey];
      acc[0].push(cat[0]);
      acc[1].push(cat[1]);
      addedTotal += cat[0];
      removedTotal += cat[1];
      return acc;
    },
    [[], []]
  );

  // Number.prototype.toPrettyNum = (length: number) => {
  Number.prototype.toPrettyNum = function (
    this: Number,
    length: number,
    maxLength = null
  ) {
    if (maxLength === null) maxLength = length;
    var s = this;
    const splited = s
      .toFixed(maxLength)
      .replace(new RegExp(`0{0,${maxLength - length}}$`), "")
      // https://stackoverflow.com/questions/5025166/javascript-number-formatting-min-max-decimals
      // .replace(/0{0,2}$/, "")
      // .toLocaleString('en-US', { // TODO : centralize toPrettyNum and use locals formatings ?
      //   minimumFractionDigits: 2,
      //   maximumFractionDigits: 4
      // })
      .replace(".", ",")
      .split(",");
    return (
      (splited[0] ?? "").replace(/\B(?=(\d{3})+(?!\d))/g, " ") +
      (length >= 1 ? "," : "") +
      (splited[1] ?? "")
    );
  };

  const options = {
    series: [
      {
        name: "Ajouts",
        color: "#31C48D",
        data: addedTotals,
      },
      {
        name: "Suppressions",
        data: removedTotals,
        color: "#F05252",
      },
    ],
    chart: {
      sparkline: {
        enabled: false,
      },
      type: "bar",
      width: "100%",
      height: 800,
      toolbar: {
        show: false,
      },
    },
    fill: {
      opacity: 1,
    },
    plotOptions: {
      bar: {
        horizontal: true,
        columnWidth: "100%",
        borderRadiusApplication: "end",
        borderRadius: 6,
        dataLabels: {
          position: "top",
        },
      },
    },
    legend: {
      show: true,
      position: "bottom",
    },
    dataLabels: {
      enabled: false,
    },
    tooltip: {
      shared: true,
      intersect: false,
      formatter: function (value) {
        // return "$" + value;
        return value.toPrettyNum(0);
      },
    },
    xaxis: {
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: "text-xs font-normal fill-gray-500 dark:fill-gray-400",
        },
        formatter: function (value) {
          // return "$" + value;
          return value.toPrettyNum(0);
        },
      },
      categories: Object.keys(byCategories),
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
    yaxis: {
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: "text-xs font-normal fill-gray-500 dark:fill-gray-400",
        },
      },
    },
    grid: {
      show: true,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: -20,
      },
    },
    fill: {
      opacity: 1,
    },
  };

  onMount(() => {
    if (document.getElementById("bar-chart")) {
      const chart = new ApexCharts(
        document.getElementById("bar-chart"),
        options
      );
      chart.render();
    }
  });
</script>

<!-- <svelte:head>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</svelte:head> -->

<h1
  class="text-gray-900 dark:text-white text-3xl md:text-5xl font-extrabold mb-2"
>
  Statistiques Git logs
</h1>
<a
  href="https://github.com/Monwoo/web-starters-free"
  target="_blank"
  class="uppercase text-sm font-semibold inline-flex
 items-center rounded-lg text-blue-600 hover:text-blue-700
  dark:hover:text-blue-500  hover:bg-gray-100
   dark:hover:bg-gray-700 dark:focus:ring-gray-700
    dark:border-gray-700 px-3 py-2 float-right"
>
  Mettre une étoile via Github
  <svg
    class="w-2.5 h-2.5 ms-1.5 rtl:rotate-180"
    aria-hidden="true"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 6 10"
  >
    <path
      stroke="currentColor"
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="m1 9 4-4-4-4"
    />
  </svg>
</a>

<div class="w-full rounded-lg shadow dark:bg-gray-800 p-4 md:p-6">
  <div
    class="flex justify-between border-gray-200 border-b dark:border-gray-700 pb-3"
  >
    <dl>
      <dt class="text-base font-normal text-gray-500 dark:text-gray-400 pb-1">
        Delta
      </dt>
      <dd class="leading-none text-3xl font-bold text-gray-900 dark:text-white">
        {(addedTotal - removedTotal).toPrettyNum(0)}
      </dd>
    </dl>
    <!-- <div>
      <span
        class="bg-green-100 text-green-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-green-900 dark:text-green-300"
      >
        <svg
          class="w-2.5 h-2.5 me-1.5"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 10 14"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13V1m0 0L1 5m4-4 4 4"
          />
        </svg>
        Profit rate 23.5%
      </span>
    </div> -->
  </div>

  <div class="grid grid-cols-2 py-3">
    <dl>
      <dt class="text-base font-normal text-gray-500 dark:text-gray-400 pb-1">
        Ajouts
      </dt>
      <dd
        class="leading-none text-xl font-bold text-green-500 dark:text-green-400"
      >
        {addedTotal.toPrettyNum(0)}
      </dd>
    </dl>
    <dl>
      <dt class="text-base font-normal text-gray-500 dark:text-gray-400 pb-1">
        Suppressions
      </dt>
      <dd class="leading-none text-xl font-bold text-red-600 dark:text-red-500">
        {removedTotal.toPrettyNum(0)}
      </dd>
    </dl>
  </div>

  <div id="bar-chart" />
  <div
    class="grid grid-cols-1 items-center border-gray-200 border-t dark:border-gray-700 justify-between"
  >
    <div class="flex justify-end items-center pt-5">
      <!-- <div class="flex justify-between items-center pt-5"> -->
      <!-- Button -->
      <!-- <button
        id="dropdownDefaultButton"
        data-dropdown-toggle="lastDaysdropdown"
        data-dropdown-placement="bottom"
        class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 text-center inline-flex items-center dark:hover:text-white"
        type="button"
      >
        Last 6 months
        <svg
          class="w-2.5 m-2.5 ms-1.5"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 10 6"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="m1 1 4 4 4-4"
          />
        </svg>
      </button> -->
      <!-- Dropdown menu -->
      <!-- <div
        id="lastDaysdropdown"
        class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700"
      >
        <ul
          class="py-2 text-sm text-gray-700 dark:text-gray-200"
          aria-labelledby="dropdownDefaultButton"
        >
          <li>
            <a
              href="#"
              class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
              >Yesterday</a
            >
          </li>
          <li>
            <a
              href="#"
              class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
              >Today</a
            >
          </li>
          <li>
            <a
              href="#"
              class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
              >Last 7 days</a
            >
          </li>
          <li>
            <a
              href="#"
              class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
              >Last 30 days</a
            >
          </li>
          <li>
            <a
              href="#"
              class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
              >Last 90 days</a
            >
          </li>
          <li>
            <a
              href="#"
              class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
              >Last 6 months</a
            >
          </li>
          <li>
            <a
              href="#"
              class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
              >Last year</a
            >
          </li>
        </ul>
      </div> -->
      <a
        href="https://github.com/Monwoo/web-starters-free"
        target="_blank"
        class="uppercase text-sm font-semibold inline-flex
         items-center rounded-lg text-blue-600 hover:text-blue-700
          dark:hover:text-blue-500  hover:bg-gray-100
           dark:hover:bg-gray-700 dark:focus:ring-gray-700
            dark:border-gray-700 px-3 py-2 float-right"
      >
        Mettre une étoile via Github
        <svg
          class="w-2.5 h-2.5 ms-1.5 rtl:rotate-180"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 6 10"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="m1 9 4-4-4-4"
          />
        </svg>
      </a>
    </div>
  </div>
</div>
