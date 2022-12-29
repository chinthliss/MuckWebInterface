@extends('layout.page-with-navigation')

@section('title', 'Password Reset Request Processed')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Password Reset' ]
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
