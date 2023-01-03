<?php

namespace App\Http\Controllers;

use App\MuckWebInterfaceUserProvider;
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
        return view('admin.account');
    }

    public function showAccountBrowser(): View
    {
        return view('admin.accounts');
    }

    public function findAccounts(Request $request, MuckWebInterfaceUserProvider $userProvider): array
    {
        $searchCharacterName = $request->input('character');
        $searchEmail = $request->input('email');
        $searchCreatedAfter = $request->input('createdAfter');
        $searchCreatedBefore = $request->input('createdBefore');

        if (!$searchCharacterName && !$searchEmail && !$searchCreatedAfter && !$searchCreatedBefore)
            abort(400);

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
