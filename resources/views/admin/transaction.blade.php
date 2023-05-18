@extends('layout.page-with-navigation')

@section('title', 'Account Transaction (Admin)')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'route' => 'admin.transactions', 'label' => 'Account Transactions (Admin)' ],
        [ 'label' => "Transaction {$transaction['id']} (Admin)" ]
    ]) }}
@endsection

@section('content')
    <account-transaction
        :transaction-in="{{json_encode($transaction)}}"
    ></account-transaction>
@endsection
