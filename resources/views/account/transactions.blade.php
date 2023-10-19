@extends('layout.page-with-navigation')

@section('title', 'Account Transactions')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => 'Account Transactions' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <h1>Account Transactions</h1>
        <account-transactions
            :transactions-in="{{json_encode($transactions)}}"
        ></account-transactions>
    </div>
@endsection
