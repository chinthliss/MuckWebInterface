@extends('layout.page-with-navigation-and-header')

@section('title', 'Delete Account')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => 'Delete Account' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <callout>
            <p>This page is under construction - the functionality isn't wired up yet, this is just for reviewing the content.</p>
        </callout>
        <p>At your request, we'll delete your data from our system. More information of what we do is available below.</p>

        <p>There is a cool-off period after requesting deletion - after you've started the process, you'll need to wait 24hrs before continuing it. The request will also be cancelled if you don't continue it within 3 days.</p>

        <form action="{{ route('account.delete') }}" method="POST">
            @csrf
            <button type="submit" value="submit" class="btn btn-primary">Start Request</button>
        </form>
        <hr/>
        <h2>Further Information</h2>

        <h3>What we delete</h3>

        <h4>Characters</h4>
        <p>Your characters in the game will be entirely deleted.</p>

        <h4>Account</h4>
        <p>The account and all associated records with it be entirely deleted.</p>

        <h4>Chat / room logs</h4>
        <p>We'll delete anything you've said directly over the following methods:</p>
        <ul>
            <li>Pages</li>
            <li>Channels</li>
            <li>Rooms (ooc or ic)</li>
        </ul>
        <callout>
            <p>This does not include somebody else's copy of a conversation, or other's conversation on a channel.</p>
            <p>If you believe there is additional content that needs to be deleted (e.g. if somebody has leaked your personal information) then please raise a ticket with us to ensure this is actioned.</p>
        </callout>

        <h3>What we don't delete</h3>
        <p>We maintain a record of your request to delete your account, and what that account was. This is required of us to prove accountability.</p>

        <h3>What we can't delete</h3>
        <p>In some instances, we're not the data controllers and your data is maintained by somebody else:</p>
        <ul>
            <li><b>Discord Chat</b> - If you've used the game's discord server, Discord will maintain their own copy of anything you've done there.</li>
            <li><b>Payment Services</b> - If you've made any payments, then payment providers will keep their own records on these.</li>
            <li><b>Emails</b> - Our email provider will maintain data in their own backups.</li>
        </ul>
    </div>

@endsection
