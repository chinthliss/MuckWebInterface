@extends('layout.page-with-navigation-and-header')

@section('title', 'Singleplayer')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Singleplayer' ]
    ]) }}
@endsection

@section('content')
    <div class="container">

        <h1>Singleplayer</h1>

        <p class="lead">Progress, news and downloads of the singleplayer version are available from <a
                href="https://blog.flexiblesurvival.com"
            >blog.flexiblesurvival.com</a></p>

        <div>TODO: Consider having more content here, maybe direct links to downloads? Or do we scrap this page entirely
            and have the front splash go to the blog directly.
        </div>
    </div>
@endsection


