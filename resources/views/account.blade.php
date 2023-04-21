@extends('layout.page-with-navigation')

@section('title', 'Account')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Account' ]
    ]) }}
@endsection

@section('content')
    <account
        :account-in="{{ json_encode($account) }}"
        :links="{{ json_encode([
            "changepassword" => route('auth.password.change'),
            "changeemail" => route('auth.email.change'),
            "newemail" => route('auth.email.new'),
            "cardmanagement" => route('payment.cardmanagement'),
            "transactions" => route('account.transactions'),
        ]) }}"
    ></account>
@endsection
