@extends('layout.page-with-navigation-and-header')

@section('title', Lex::get('accountcurrency') . ' History')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => Lex::get('accountcurrency') . ' History' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>{{ Lex::get('accountcurrency') }} History</h1>
        </div>

        <account-history
            :history-in="{{ json_encode($history) }}"
        ></account-history>
    </div>
@endsection
