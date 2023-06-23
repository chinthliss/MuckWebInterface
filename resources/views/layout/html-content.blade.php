<!-- Javascript check -->
<noscript>
    <div class="p-3 mb-2 bg-danger text-light rounded">
        This page requires javascript enabled in order to work.
    </div>
</noscript>

<!-- Site Notice -->
@SiteNoticeExists
<div class="p-3 mb-2 bg-warning text-dark rounded">
    <?php
    $filePath = public_path('site-notice.txt');
    echo(implode('<br/>', file($filePath, FILE_IGNORE_NEW_LINES)));
    ?>
</div>
@endSiteNoticeExists

<!-- Flashed Messages -->
@if ($message = Session::get('message-success'))
    <div class="my-2 alert alert-success alert-dismissible fade show" role="alert">
        <strong>{{ $message }}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if ($message = Session::get('message-warning'))
    <div class="my-2 alert alert-warning alert-dismissible fade show" role="alert">
        <strong>{{ $message }}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if ($message = Session::get('message-danger'))
    <div class="my-2 alert alert-danger alert-dismissible fade show" role="alert">
        <strong>{{ $message }}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif


<script type="application/javascript">

    //Code for the navigation toggle when mobile
    const siteNavigationButton = document.getElementById("site_navigation_button");
    if (siteNavigationButton) {
        siteNavigationButton.addEventListener('click', () => {
            document.getElementById("site_navigation_left").classList.toggle("site_navigation_force_show");
        });
    }

    <!-- Lexicon configuration -->
    const mwiSiteLexicon = @json(Lex::toArray());

</script>

@yield('breadcrumbs')

<main id="app" class="py-2 mb-3">

    @yield('content')

    @hasSection('links')
        <div class="container">
            <h2 class="mt-2">Further Pages</h2>
            @yield('links')
        </div>
    @endif

    <!-- Vue Components that we need to ensure are in the Vue app element -->

    <off-canvas-character-select
        :links="{{ json_encode([
            "getState" => route('multiplayer.character.state'),
            "setCharacter" => route('multiplayer.character.set'),
            "createCharacter" => route('multiplayer.character.create'),
            "buySlot" => route('multiplayer.character.buyslot')
        ]) }}"
    ></off-canvas-character-select>

    <modal-request-error id="modal-site-error" error=""

    ></modal-request-error>
</main>
