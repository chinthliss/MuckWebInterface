@extends('layout.page-with-navigation')

@section('title', 'Account Transactions')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'route' => 'account.transactions', 'label' => 'Transactions' ],
        [ 'label' => "Transaction {$transaction['id']}" ]
    ]) }}
@endsection

@section('content')
    TODO Implement Account Transaction Page
@endsection
