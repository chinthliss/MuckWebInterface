@extends('layout.page-no-navigation')

@section('title', 'Account')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Account' ]
    ]) }}
@endsection

@section('content')
    <div>
        To be completed.
    </div>
@endsection


