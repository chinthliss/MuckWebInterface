<?php

namespace App\Http\Controllers;

use App\User as User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function showWelcome(Request $request): View
    {
        if ($request->has('refer')) {
            $request->session()->put('account.referral', $request->input('refer'));
        }
        return view('welcome');
    }

    public function showLocked(): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->guard()->user();
        if (!$user || !$user->getLockedAt()) return redirect()->route('welcome');

        return view('auth.locked');
    }

    public function getCharacters(): array
    {
        /** @var User $user */
        $user = auth()->guard()->user();
        return array_map(function($character) {
            return $character->toPlayerArray();
        }, $user->getCharacters());

    }

}
