@extends('layout.page-with-navigation-and-header')

@section('title', 'Avatar Doll List (Admin)' )

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Avatar Doll List (Admin)' ]
    ]) }}
@endsection

@section('content')
    <admin-avatar-doll-list
        :dolls="{{ json_encode($dolls) }}"
        :invalid="{{ json_encode($invalid, JSON_FORCE_OBJECT) }}"
    >
    </admin-avatar-doll-list>
@endsection


