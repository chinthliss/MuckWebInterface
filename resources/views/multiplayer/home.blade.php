@extends('layout.page-multiplayer')

@section('title', 'Multiplayer')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Multiplayer' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        Test body content

        <!-- Here until finding a better place for such -->
        <div class="row">
            <a class="btn btn-secondary" href="{{ route('multiplayer.character.changepassword') }}">Reset Character Password</a>
        </div>
    </div>

@endsection


