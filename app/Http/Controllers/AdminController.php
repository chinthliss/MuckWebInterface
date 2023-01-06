<?php

namespace App\Http\Controllers;

use App\MuckWebInterfaceUserProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function showHome(): View
    {
        return view('admin.home');
    }

    public function showAccount(int $accountId): View
    {
        $user = User::find($accountId);
        if (!$user) abort(404);

        return view('admin.account', [
            'account' => $user->toArray('all')
        ]);
    }

    public function showAccountBrowser(): View
    {
        return view('admin.accounts');
    }

    public function findAccounts(Request $request, MuckWebInterfaceUserProvider $userProvider): array
    {
        $searchCharacterName = $request->input('character');
        $searchEmail = $request->input('email');
        $searchCreatedAfter = $request->has('createdAfter') ? new Carbon($request->input('createdAfter')) : null;
        $searchCreatedBefore = $request->has('createdBefore') ? new Carbon($request->input('createdBefore')) : null;

        if (!$searchCharacterName && !$searchEmail && !$searchCreatedAfter && !$searchCreatedBefore)
            abort(400, 'No criteria specified');

        $results = [];

        if ($searchCharacterName) {
            $nextResults = $userProvider->searchByCharacterName($searchCharacterName);
            $results = $nextResults;
        }

        if ($searchEmail) {
            $nextResults = $userProvider->searchByEmail($searchEmail);
            if (count($results))
                $results = array_intersect($results, $nextResults);
            else
                $results = $nextResults;
        }

        if ($searchCreatedAfter || $searchCreatedBefore) {
            $nextResults = $userProvider->searchByCreationDateRange($searchCreatedAfter, $searchCreatedBefore);
            if (count($results))
                $results = array_intersect($results, $nextResults);
            else
                $results = $nextResults;
        }

        return array_map(function($account) {
            return $account->toArray('all');
        }, $results);
    }
}
