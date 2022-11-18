@extends('layout.page-simple-navigation')

@section('title', 'Verify Email')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => 'Verify Email' ]
    ]) }}
@endsection

@section('content')
    <div class="container">

        <div class="row">
            <div class="col">
                <h1>Verify Email</h1>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <p>Before proceeding, please check your email for a verification link.</p>
            </div>
        </div>
        @if (session('resent'))
            <div class="row">
                <div class="col alert alert-success" role="alert">
                    A fresh verification link has been sent to your email address.
                </div>
            </div>
        @else
            <div class="row">
                <div class="col">
                    <p>If you did not receive the email, <a href="{{ route('auth.email.resendVerification') }}">click
                            here to request another</a>.</p>
                    <p>If you made your account on the old website, it won't have sent the email
                        and you will need to use the link above to send an email.</p>
                </div>
            </div>
        @endif
    </div>
@endsection
