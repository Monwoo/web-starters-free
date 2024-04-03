<script>
    // 🌖🌖 Copyright Monwoo 2023-2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com
    import Routing from 'fos-router';
    // TODO : namespace
    import Base from '../layout/Base.svelte';
import GitLogsChart from '../layout/widgets/GitLogsChart.svelte';
import Jumbotron from '../layout/widgets/Jumbotron.svelte';
import MultiScreen from '../layout/widgets/MultiScreen.svelte';

    export let title = null;
    export let currentLanguage = null;
    export let billingsLocales = [];
    export let moonManagerLocales = [];
    export let moonManagerUserLocales = [
        {
            label: 'Users list in english',
            path: Routing.generate('mws_user', {
                _locale: 'en'
            })
        }, {
            label: 'Liste des utilisateurs en anglais',
            path: Routing.generate('mws_user', {
                _locale: 'fr'
            })
        }
    ];
</script>

<Base>
    <!-- {currentLanguage ?? ""} -->
    <MultiScreen>
        
    </MultiScreen>

    <Jumbotron>
        
    </Jumbotron>
    <Jumbotron>
        
    </Jumbotron>
    <Jumbotron>
        
    </Jumbotron>

    <GitLogsChart></GitLogsChart>

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
</Base>