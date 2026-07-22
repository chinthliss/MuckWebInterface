@extends('layout.page-with-navigation-and-header')

@section('title', 'Communication Logs Viewer')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'label' => 'Communication Logs Viewer' ]
    ]) }}
@endsection

@section('content')

    <div class="container">
        <div class="row">
            <h1>Communication Logs Viewer</h1>
        </div>

        <p class="lead">
            This page is only provided to investigate allegations in disputes. Usage is recorded in the site logs.
        </p>
        <p>
            One of the following roles is required to view this page: 'communicationlogs' or 'siteadmin'.
            <br/>
            Data is pulled straight from the database, so dbrefs aren't validated.
        </p>

        <admin-communication-logs-viewer
        ></admin-communication-logs-viewer>
@endsection
