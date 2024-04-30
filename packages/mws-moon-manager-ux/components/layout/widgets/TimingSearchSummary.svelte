<script context="module" lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  import dayjs from "dayjs";
  var utc = require("dayjs/plugin/utc");
  var timezone = require("dayjs/plugin/timezone"); // dependent on utc plugin
  dayjs.extend(utc);
  dayjs.extend(timezone); // TODO : user config for self timezone... (slot is computed on UTC date...)
  dayjs.tz.setDefault("Europe/Paris");

  export let timingSearchSummary = (searchLookup) => {
    return (
      (searchLookup?.searchStart && searchLookup.searchStart.length
        ? "Début-" +
          dayjs(searchLookup.searchStart).format("YYYY-MM-DD_HH:mm:ss") +
          " "
        : "") +
      (searchLookup?.searchEnd && searchLookup.searchEnd.length
        ? "Fin-" +
          dayjs(searchLookup.searchEnd).format("YYYY-MM-DD_HH:mm:ss") +
          ""
        : "") +
      (searchLookup?.searchTags && searchLookup.searchTags.length
        ? "Tags" +
          searchLookup.searchTags.reduce((acc, f) => `${acc}-${f}`, ``) +
          " "
        : "") +
      (searchLookup?.searchTagsToInclude &&
      searchLookup.searchTagsToInclude.length
        ? "Inclure" +
          searchLookup.searchTagsToInclude.reduce(
            (acc, f) => `${acc}-${f}`,
            ``
          ) +
          " "
        : "") +
      (searchLookup?.searchTagsToAvoid && searchLookup.searchTagsToAvoid.length
        ? "Exclure" +
          searchLookup.searchTagsToAvoid.reduce((acc, f) => `${acc}-${f}`, ``) +
          " "
        : "") +
      (searchLookup?.searchKeyword ? `${searchLookup.searchKeyword}` : ``)
    );
  };
</script>

<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  export let searchLookup;
</script>

<!-- TODO : no customFilters for timings ? {searchLookup.customFilters && searchLookup.customFilters.length
...  : ""} -->

<!-- TIPS : in JS only, for non empty strings
${acc} ${ idx > 0 && ',' || ''} ${f}
eq :
${acc} ${ idx > 0 ? ',' : ''} ${f} -->

<!-- {searchLookup?.searchStart && searchLookup.searchStart.length
  ? "Début-" +
    dayjs(searchLookup.searchStart).format("YYYY-MM-DD_HH:mm:ss") +
    " "
  : ""}
{searchLookup?.searchEnd && searchLookup.searchEnd.length
  ? "Fin-" +
    dayjs(searchLookup.searchEnd).format("YYYY-MM-DD_HH:mm:ss") +
    ""
  : ""}
{searchLookup?.searchTags && searchLookup.searchTags.length
  ? "Tags" +
    searchLookup.searchTags.reduce(
      (acc, f) => `${acc}-${f}`,
      ``
    ) +
    "] "
  : ""}
{searchLookup?.searchTagsToInclude && searchLookup.searchTagsToInclude.length
  ? "Inclure[" +
    searchLookup.searchTagsToInclude.reduce(
      (acc, f) => `${acc}-${f}`,
      ``
    ) +
    "]"
  : ""}
{searchLookup?.searchTagsToAvoid && searchLookup.searchTagsToAvoid.length
  ? "Exclure[" +
    searchLookup.searchTagsToAvoid.reduce(
      (acc, f) => `${acc}-${f}`,
      ``
    ) +
    "]"
  : ""}
{searchLookup?.searchKeyword ? `${searchLookup.searchKeyword}` : ``} -->
{timingSearchSummary(searchLookup)}