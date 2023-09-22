@extends('layout.page-with-navigation')

@section('title', 'Avatar Doll List (Admin)' )

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'route' => 'admin.avatar', 'label' => 'Avatar Administration' ],
        [ 'label' => 'Avatar Doll List (Admin)' ]
    ]) }}
@endsection

@section('content')
    <admin-avatar-doll-list
        :dolls = "{{ json_encode($dolls) }}"
        :invalid = "{{ json_encode($invalid, JSON_FORCE_OBJECT) }}"
    >
    </admin-avatar-doll-list>
@endsection


