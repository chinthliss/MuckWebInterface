@extends('layout.page-with-navigation-and-header')

@section('title', 'Change to new Email')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => 'Change to new Email' ]
    ]) }}
@endsection

@section('content')
    <div class="container">

        <div class="row">
            <h1>Change to new Email</h1>
        </div>

        <div class="row">
            <p>Use this page to change your present email. You will also need to enter your password to authenticate
                this.</p>
            <p> Please note that after changing your email it will need
                to be verified - a link will be sent to it in order to do this.</p>
        </div>

        <form action="" method="POST">
            @csrf
            <div class="form-group">

                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right">
                        <label for="email">New Email</label>
                    </div>
                    <div class="col">
                        <input id="email" type="email" name="email"
                               @class(['form-control', 'is-invalid' => $errors->get('email')])
                               placeholder="Enter the new email address"
                        >
                        @error('email')
                        <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-3 col-form-label text-md-right"><label for="password">Password</label></div>
                    <div class="col">
                        <input id="password" type="password" name="password"
                               @class(['form-control', 'is-invalid' => $errors->get('password')])
                               placeholder="Enter your account password"
                        >
                        @error('password')
                        <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

            </div>
            <div class="form-group">
                <div class="row mt-2">
                    <div class="col">
                        <button type="submit" value="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
