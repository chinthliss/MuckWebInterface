@extends('layout.page-simple-navigation')

@section('title', 'Account')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Account' ]
    ]) }}
@endsection

@section('content')
    <account
        account-created="{{ $accountCreated }}"
        subscription-status="{{ $subscriptionStatus }}"
        :emails-in="{{ json_encode($emails) }}"
        :links="{{ json_encode([
            "changepassword" => route('auth.password.change')
        ]) }}"
    ></account>
@endsection
