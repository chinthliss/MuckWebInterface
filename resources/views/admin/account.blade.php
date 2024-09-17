@extends('layout.page-with-navigation-and-header')

@section('title', 'Account ' . $account['id'] )

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'route' => 'admin.accounts', 'label' => 'Accounts' ],
        [ 'label' => 'Account ' . $account['id'] ]
    ]) }}
@endsection

@section('content')
    <admin-account
        :account="{{ json_encode($account) }}"
        :history-in="{{ json_encode($history) }}"
        api-url="{{ route('admin.account.api', ['accountId' => $account['id']]) }}"
    >
    </admin-account>
@endsection


