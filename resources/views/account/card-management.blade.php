@extends('layout.page-with-navigation-and-header')

@section('title', 'Card Management')

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'welcome', 'label' => 'Welcome' ],
        [ 'route' => 'account', 'label' => 'Account' ],
        [ 'label' => 'Card Management' ]
    ]) }}
@endsection

@section('content')
    <account-card-mangement
        profile-id="{{ $profileId }}"
        :cards-in="{{ json_encode($cards) }}"
        :links="{{ json_encode([
            "addCard" => route('payment.cardmanagement.add'),
            "deleteCard" => route('payment.cardmanagement.delete'),
            "setDefaultCard" => route('payment.cardmanagement.setdefault'),
        ]) }}"
    ></account-card-mangement>
    <div style="float:right">
        <!--
        TODO: Restore AuthorizeNet seal at some point
        (c) 2005, 2011. Authorize.Net is a registered trademark of CyberSource Corporation
        <div class="AuthorizeNetSeal">
            <script type="text/javascript">
                let ANS_customer_id="{{ $sealId }}";
            </script>
            <script type="text/javascript" src="//verify.authorize.net/anetseal/seal.js"></script>
            <a href="https://www.authorize.net/" id="AuthorizeNetText" target="_blank">Credit Card Merchant Services</a>
        </div>
        -->
    </div>
@endsection
