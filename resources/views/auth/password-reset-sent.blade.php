@extends('layout.page-with-navigation')

@section('title', 'Password Reset Request Sent')

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
                If there is an account associated with that email then a message containing a reset link has now been sent to it. Please check your email for the link.
            </div>
        </div>
    </div>
@endsection
