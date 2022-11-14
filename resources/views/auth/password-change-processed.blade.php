@extends('layout.page-simple-navigation')

@section('title', 'Password Changed')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Password Changed' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                You should now be able to login with the new password.
            </div>
        </div>
    </div>
@endsection
