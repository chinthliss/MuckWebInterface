@extends('layout.page-with-navigation')

@section('title', 'Account Transactions (Admin)')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Account Transactions' ]
    ]) }}
@endsection

@section('content')
    TODO Implement Admin Account Transactions Page
@endsection
