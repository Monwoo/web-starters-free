<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    export let title = null;
    export let currentLanguage = null;
    export let billingsLocales = [];
    export let moonManagerLocales = [];
</script>

<h1>{ title ?? "" }</h1>
<div class="nav-item dropdown">Billings :
    {currentLanguage ?? ""}
    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-language">
        {#each billingsLocales as l}
            <li><a class="dropdown-item" href={l.path}>
                { l.label }
            </a></li>
        {/each}
    </ul>
</div>
<div class="nav-item dropdown">Moon Manager :
    {currentLanguage ?? ""}
    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-language">
        {#each moonManagerLocales as l}
            <li><a class="dropdown-item" href={l.path}>
                { l.label }
            </a></li>
        {/each}
    </ul>
</div>
