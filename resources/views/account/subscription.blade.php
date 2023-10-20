@extends('layout.page-with-navigation-and-header')

@section('title', 'Account Subscription')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => "Subscription {$subscription['id']}" ]
    ]) }}
@endsection

@section('content')
    <account-subscription
        :subscription-in="{{ json_encode($subscription) }}"
        :transactions-in="{{ json_encode($transactions) }}"
    ></account-subscription>
@endsection
