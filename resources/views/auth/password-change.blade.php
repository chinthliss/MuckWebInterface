@extends('layout.page-simple-navigation')

@section('title', 'Change Password')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Change Password' ]
    ]) }}
@endsection

@section('content')
    <div class="container">

        <div class="row">
            <h1>Change Password</h1>
        </div>

        <div class="row">
            <p>Use this page to change your present password.</p>
        </div>

        <form action="" method="POST">
            @csrf
            <div class="form-group">

                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right"><label for="oldpassword">Existing
                            Password</label>
                    </div>
                    <div class="col">
                        <input id="oldpassword" type="password" name="oldpassword"
                               @class(['form-control', 'is-invalid' => $errors->get('oldpassword')])
                               placeholder="Enter existing password">
                        @error('oldpassword')
                        <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-2">
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

                <div class="row mt-2">
                    <div class="col-md-3 col-form-label text-md-right">
                        <label for="password_confirmation">Confirm New Password</label>
                    </div>
                    <div class="col">
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               @class(['form-control', 'is-invalid' => $errors->get('password_confirmation')])
                               placeholder="Re-enter new password again">
                        @error('password_confirmation')
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
