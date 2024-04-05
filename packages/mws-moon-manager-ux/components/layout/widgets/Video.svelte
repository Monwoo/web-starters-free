<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // https://flowbite.com/docs/components/video/
  // https://www.npmjs.com/package/svelte-youtube-embed
  import Youtube from "svelte-youtube-embed";

  let cssClass = "max-w-full";
  export { cssClass as class };
  export let youtubeId:String;
</script>

<div class="w-full flex justify-center items-center
h-[70vh] max-h-[70vh]
bg-black
border border-gray-200 rounded-lg dark:border-gray-700
{cssClass}" controls>
  <Youtube altThumb="{true}" id={youtubeId} />
</div>

<style lang="scss">
  :global(.you__tube) {
    // max-height: 70vh;
    // width: 100%;
    // .you__tube.svelte-9tw3qi.svelte-9tw3qi {
    //     position: relative;
    //     aspect-ratio: 1.76991;
    //     overflow: hidden;
    // }

    // TODO : aspect ration will never change ?
    // width: calc(176* 0.7vh);
    width: #{(176* 0.7)}dvh;
  }
</style>