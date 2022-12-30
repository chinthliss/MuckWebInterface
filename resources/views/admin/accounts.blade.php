@extends('layout.page-with-navigation')

@section('title', 'Account Browser')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Account Browser' ]
    ]) }}
@endsection

@section('content')
    <admin-accounts api-url="{{ route('admin.accounts.api') }}"></admin-accounts>
@endsection


