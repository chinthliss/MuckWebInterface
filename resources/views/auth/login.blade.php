@extends('layout.page-no-navigation')

@section('title', 'Login')

@section('content')
    <div class="container">
        <form action="" method="POST">
            @csrf

            <div class="row justify-content-center">

                <div class="col-md-4 col-lg-3">
                    <label for="email" class="form-label">Email</label>
                    <input @class(['form-control', 'is-invalid' => $errors->get('email')]) id="email" name="email"
                           autocomplete="email" autofocus
                           placeholder="Enter a valid email address or the name of a character.">
                    @error('email')
                    <div class="invalid-feedback" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 col-lg-3">
                    <label for="password" class="form-label">Password</label>
                    <input @class(['form-control', 'is-invalid' => $errors->get('password')]) id="password"
                           name="password"
                           type="password" autocomplete="password"
                           placeholder="Enter password.">
                    @error('password')
                    <div class="invalid-feedback" role="alert">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            @error('login')
            <div class="row justify-content-md-center mt-2">
                <div class="col-md-6">
                    <div class="text-center invalid-feedback d-block">
                        {{ $message }}
                    </div>
                </div>
            </div>
            @enderror

            <div class="row justify-content-md-center mt-2">
                <div class="col-md-6">
                    <div class="text-center">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" value="" id="forget">
                            <label class="form-check-label" for="forget">
                                Don't remember login (e.g. for public computers)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col">
                    <div class="text-center">
                        <button class="btn btn-primary" type="submit" name="action" value="login">Login</button>
                        <button class="btn btn-primary" type="submit" name="action" value="create">Create Account
                        </button>
                    </div>
                </div>

            </div>

            <div class="row mt-2">
                <div class="col">
                    <div class="text-center">
                        <a href="{{ route('auth.password.forgot') }}">Reset a forgotten password.</a>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col">
                    <div class="text-center">
                        <div>By logging in, you agree that you are a legal adult wherever you live.</div>
                        <div>If you are not a legal adult, please close this site now.</div>
                        <div>You will be required to agree to the terms of service before creating or playing a
                            character.
                        </div>
                        <div>
                            These can be viewed before creating an account from the following link:
                            <a href="{{ route('auth.terms-of-service') }}" target="_blank">View Terms of Service</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
