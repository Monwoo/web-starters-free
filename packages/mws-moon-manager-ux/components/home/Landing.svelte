<script>
    // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import Routing from 'fos-router';

    export let title = null;
    export let currentLanguage = null;
    export let billingsLocales = [];
    export let moonManagerLocales = [];
    export let moonManagerUserLocales = [
        {
            label: 'en',
            path: Routing.generate('mws_moon_manager', {
                _locale: 'en'
            })
        }, {
            label: 'fr',
            path: Routing.generate('mws_moon_manager', {
                _locale: 'fr'
            })
        }
    ];
</script>

{currentLanguage ?? ""}

<h1>{ title ?? "" }</h1>
<div class="nav-item dropdown">Billings :
    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-language">
        {#each billingsLocales as l}
            <li><a class="dropdown-item" href={l.path}>
                { l.label }
            </a></li>
        {/each}
    </ul>
</div>
<div class="nav-item dropdown">Moon Manager :
    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-language">
        {#each moonManagerLocales as l}
            <li><a class="dropdown-item" href={l.path}>
                { l.label }
            </a></li>
        {/each}
    </ul>
</div>
<div class="nav-item dropdown">Moon Manager Users :
    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-language">
        {#each moonManagerUserLocales as l}
            <li><a class="dropdown-item" href={l.path}>
                { l.label }
            </a></li>
        {/each}
    </ul>
</div>
