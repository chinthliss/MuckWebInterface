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
            <h1>Password changed</h1>
        </div>
        <div class="row">
            <p>You should now be able to login with the new password.</p>
        </div>
    </div>
@endsection