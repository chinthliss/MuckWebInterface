@extends('layout.page-with-navigation')

@section('title', 'Avatar Doll Test (Admin)' )

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Avatar Doll Test (Admin)' ]
    ]) }}
@endsection

@section('content')
    <admin-avatar-doll-tester
        :drawing-steps="{{ json_encode($drawingSteps) }}"
        :dolls="{{ json_encode($dolls) }}"
        :gradients="{{ json_encode($gradients) }}"
        initial-code="{{ $code }}"
        base-url="{{ route('admin.avatar.dolltest') }}"
        render-url="{{ route('admin.avatar.render') }}"
        :avatar-width-in="{{ $avatarWidth }}"
        :avatar-height-in="{{ $avatarHeight }}"
    >
    </admin-avatar-doll-tester>
@endsection


