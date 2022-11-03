@extends('layout.page-no-navigation')

@section('title', 'Account Locked')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Account Locked' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                This content is unavailable due to the presently logged in account being locked.

                <!--TODO Update content on account locked page -->
            </div>
        </div>
    </div>
@endsection
