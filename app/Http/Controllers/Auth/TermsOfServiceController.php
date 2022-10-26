<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\TermsOfService;
use App\User as User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TermsOfServiceController extends Controller
{
    public function showTermsOfService(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('auth.terms-of-service')->with([
            'termsOfService' => TermsOfService::getTermsOfService(),
            'agreed' => $user && $user->getAgreedToTermsOfService(),
            'hash' => TermsOfService::getTermsOfServiceHash()
        ]);
    }

    public function acceptTermsOfService(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$user) abort(500, "User should have been set before this call");

        $hash = $request->has('_hash') ? $request['_hash'] : null;

        Log::debug("TermsOfService recording accepted hash for $user: $hash");

        $user->setAgreedToTermsOfService($hash);
        return redirect()->intended(route('welcome'));
    }

}
