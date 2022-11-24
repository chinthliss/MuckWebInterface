@extends('layout.page-simple-navigation')

@section('title', 'Email Changed')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => 'Email Changed' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <h1>Email changed</h1>
        </div>
        @if($verificationRequired)
            <div class="row">
                <p>A verification email has been sent to your new email. Please check your emails and action it to
                    complete the change.</p>
            </div>
        @endif
    </div>
@endsection
