@extends('layout.page-with-navigation-and-header')

@section('title', 'Form Editor')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.contribute', 'label' => 'Contribute' ],
        [ 'label' => 'Form Editor' ]
    ]) }}
@endsection

@php($links = [
    "rootUrl" => route('multiplayer.contribute.forms'),
    "helpRoot" => route('multiplayer.help')
])

@section('content')
    <div class="container">
        <div class="row">
            <h1>Form Editor</h1>
        </div>

        <div class="lead">
            Interchangeably known as monsters, infections or mutations.
            Probably need text here about how forms are integral to the game.
        </div>

        <form-editor
            :links="{{ json_encode($links) }}"
            initial-form="{{ $form }}"
        ></form-editor>

        <div class="mt-4 p-3 border rounded border-warning">
            <h2><i class="fas fa-hammer me-2"></i>Under Construction</h2>
            This page is under construction even more so than usual, with the following outstanding:
            <ul>
                <li>Responsive layout review (e.g. placing hints to the side if there's room rather than beneath, etc).
                <li>Submission isn't done yet.</li>
                <li>Submission cancelling isn't done yet.</li>
                <li>Staff side after submission (Review) isn't done yet.</li>
                <li>Deletion isn't done yet (want to make it a soft delete for 90 days or somesuch).</li>
                <li>Strongly considering removing the main preview or making it run by request.</li>
                <li>Adding a preview by each description to save scrolling around.</li>
                <li>Maybe make each area (e.g. head, skin, etc) a separate sub-tab rather than using a giant list.</li>
                <li>Not having all the help tips as a dump at the top of the parts&description tab.</li>
                <li>More links to help files.</li>
                <li>Find (another) data table library replacement, because this one is slow with large amounts of data.</li>
            </ul>
        </div>
    </div>
@endsection
