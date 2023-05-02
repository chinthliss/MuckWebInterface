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
    <account-transactions
        :transactions-in= @json($transactions)
    ></account-transactions>
@endsection
