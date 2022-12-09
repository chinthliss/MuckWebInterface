<!-- Javascript check -->
<noscript>
    <div class="p-3 mb-2 bg-danger text-light rounded">
        This page requires javascript enabled in order to work.
    </div>
</noscript>

@yield('breadcrumbs')
<main id="app" class="py-2 mb-3">
    @yield('page-content')
    <!-- Character select offcanvas. Here to ensure it's in the Vue app element -->
    <off-canvas-character-select
        :links="{{ json_encode([
            "getCharacters" => route('multiplayer.characters'),
            "setCharacter" => route('multiplayer.character.set')
        ]) }}"
    ></off-canvas-character-select>
</main>
