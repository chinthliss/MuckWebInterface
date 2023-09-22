@extends('layout.html-skeleton')

@section('page-navigation')
    <a class="nav-link rounded" href="{{ route('account') }}">Account</a>

    <div class="mt-2 fw-bold"><i class="fas fa-fw fa-user me-1 mt-2"></i>Single Player</div>
    <a class="nav-link rounded" href="{{ route('singleplayer.home') }}">Introduction</a>

    <div class="mt-2 fw-bold"><i class="fas fa-fw fa-users me-1 mt-2"></i>Multiplayer</div>
    <a class="nav-link rounded" href="{{ route('multiplayer.guide.starting') }}">Getting Started</a>
    <a class="nav-link rounded" href="{{ route('multiplayer.home') }}">Dashboard</a>
    <a class="nav-link rounded" href="{{ route('multiplayer.help') }}">Help</a>
    <a class="nav-link rounded" href="{{ route('multiplayer.forms') }}">Inventory</a>
    <a class="nav-link rounded" href="{{ route('multiplayer.forms') }}">Form Mastery</a>
    <a class="nav-link rounded" href="{{ route('multiplayer.character') }}">Edit Character</a>

    @Staff
    <div class="mt-2 fw-bold"><i class="fas fa-fw fa-hat-wizard me-1 mt-2"></i>Administrative</div>
    <a class="nav-link rounded" href="{{ route('admin.tickets') }}">Support / Request (Agent)</a>
    <a class="nav-link rounded" href="{{ route('admin.avatar') }}">Avatar Administration</a>
    @Admin
    <a class="nav-link rounded" href="{{ route('admin.accounts') }}">Account Browser</a>
    @endAdmin
    @SiteAdmin
    <a class="nav-link rounded" href="{{ route('admin.transactions') }}">Account Transactions</a>
    <a class="nav-link rounded" href="{{ route('admin.logs') }}">Site Logs</a>
    @endSiteAdmin
    @endStaff

@endsection
