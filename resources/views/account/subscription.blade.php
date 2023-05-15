@extends('layout.page-with-navigation')

@section('title', 'Account Transactions')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => "Subscription {$subscription['id']}" ]
    ]) }}
@endsection

@section('content')
    <account-subscription
        :subscription-in= "@json($subscription)"
        :transactions-in= "@json($transactions)"
    ></account-subscription>
@endsection
