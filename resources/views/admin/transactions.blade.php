@extends('layout.page-with-navigation-and-header')

@section('title', 'Account Transactions (Admin)')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Account Transactions (Admin)' ]
    ]) }}
@endsection

@section('content')
    <admin-account-transactions
        api="{{ route('admin.transactions.api') }}"
    ></admin-account-transactions>
@endsection
