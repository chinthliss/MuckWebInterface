@extends('layout.page-simple-navigation')

@section('title', 'Password Reset')

@section('content')
    <div class="container">

        <div class="row">
            <div class="col">
                <p>Enter a new password here.</p>
            </div>
        </div>

        <form action="" method="POST">
            @csrf
            <div class="form-group">
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right"><label for="password">New Password</label></div>
                    <div class="col">
                        <input id="password" type="password" name="password"
                               @class(['form-control', 'is-invalid' => $errors->get('password')])
                               placeholder="Enter new password">
                        @error('password')
                        <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right"><label for="password_confirmation">New Password
                            Again</label></div>
                    <div class="col">
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               @class(['form-control', 'is-invalid' => $errors->get('password_confirmation')])
                               placeholder="Re-enter new password">
                        @error('password-confirmation')
                        <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col text-center">
                        <button type="submit" value="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
