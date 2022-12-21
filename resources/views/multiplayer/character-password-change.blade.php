@extends('layout.page-multiplayer')

@section('title', 'Change Character Password')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Change Character Password' ]
    ]) }}
@endsection

@section('content')
    <div class="container">

        <div class="row">
            <h1>Change Character Password</h1>
        </div>

        <div class="row">
            <p>Use this page to change your present password.</p>
            <p>For security, you need to provide your account password in order to do this.</p>
        </div>

        <form action="" method="POST">
            @csrf
            <div class="form-group">

                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right"><label for="account_password">Account
                            Password</label>
                    </div>
                    <div class="col">
                        <input id="account_password" type="password" name="account_password"
                               @class(['form-control', 'is-invalid' => $errors->get('account_password')])
                               placeholder="Enter existing password">
                        @error('account_password')
                        <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-3 col-form-label text-md-right">
                        <label for="character">Character</label>
                    </div>
                    <div class="col">
                        <select name="character" id="character"
                            @class(['form-select', 'is-invalid' => $errors->get('character')])>
                            <option value="" selected>Select a character</option>
                            @foreach ($characters as $character)
                                <option value="{{ $character['dbref'] }}">{{ $character['name'] }}</option>
                            @endforeach
                        </select>
                        @error('character')
                        <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-3 col-form-label text-md-right">
                        <label for="character_password">Character's New Password</label>
                    </div>
                    <div class="col">
                        <input id="character_password" type="password" name="character_password"
                               @class(['form-control', 'is-invalid' => $errors->get('character_password')])
                               placeholder="Re-enter new password again">
                        @error('character_password')
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
