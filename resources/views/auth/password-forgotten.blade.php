@extends('layout.page-simple-navigation')

@section('title', 'Password Reset Request')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Password Reset' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>Password Reset</h1>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <p>If you're forgotten your password you can use this page to request a reset by entering your email
                    below.</p>
                <p>If there is an account associated with the email given, it will be sent a link to reset the password
                    with.</p>
            </div>
        </div>
        <form class="row row-cols-lg-auto g-2" method="POST">
            @csrf
            <div class="col-12 d-flex align-items-center">
                <label for="email">Email address</label>
            </div>
            <div class="col-12">
                <input type="email" name="email" id="email"
                       @class(['form-control', 'is-invalid' => $errors->get('email')]) placeholder="Enter email">
            </div>

            <div class="col-12">
                <button type="submit" value="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
        @error('email')
        <div class="invalid-feedback d-block" role="alert">{{ $message }}</div>
        @enderror
    </div>
@endsection
