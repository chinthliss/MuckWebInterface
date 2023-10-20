@extends('layout.page-with-navigation-and-header')

@section('title', 'Account Browser')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Account Browser' ]
    ]) }}
@endsection

@section('content')
    <admin-accounts
        api-url="{{ route('admin.accounts.api') }}"
        account-root="{{ route('admin.account', ['accountId' => 'DUMMY']) }}"
    ></admin-accounts>
@endsection


