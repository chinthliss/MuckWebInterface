@extends('layout.page-simple-navigation')

@section('title', 'Terms of Service')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'label' => 'Terms of Service' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>Terms of Service</h1>
            </div>
        </div>
        <div>
            @foreach ($termsOfService as $line)
                {{ $line }} <br/>
            @endforeach
        </div>
        @auth
            @if ($agreed)
                <div class="p-2 mb-2 bg-primary text-dark">You've agreed to this previously.</div>
            @else
                <div class="border border-primary rounded p-3 text-center">
                    <form action="{{ route('auth.terms-of-service') }}" method="POST">
                        @csrf
                        <input type="hidden" name="_hash" value="{{ $hash }}">

                        <button type="submit" value="submit" class="btn btn-primary">
                            Click here to agree to the Terms of Service
                        </button>
                    </form>
                </div>
            @endif
        @endauth
    </div>
@endsection
