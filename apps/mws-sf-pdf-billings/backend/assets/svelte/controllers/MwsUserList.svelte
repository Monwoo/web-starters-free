<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

    // TODO : keep in MoonManager bundle :
    // "entrypoints": [ // No effect, building and using build output for the root one ?
    //     "../../../../packages/mws-moon-manager/assets/svelte/controllers/MwsUserList.svelte"
    // ]

    import Liste from "mws-moon-manager-ux/components/user/Liste.svelte";
    
    export let users;
    export let filterForm;
</script>

<!-- <Liste {...$$props}></Liste> -->
<Liste {users} {filterForm}></Liste>
