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

    public function processAccountChange(Request $request, int $accountId): string
    {
        if (!$request->has('operation')) abort(400);
        $target = User::find($accountId);
        if (!$target) abort(404);

        /** @var User $reporter */
        $reporter = auth()->user();
        $reporterCharacter = $reporter->getCharacter();
        if (!$reporterCharacter || !$reporterCharacter->isAdmin()) abort(400, 'You need to be logged on as an admin character');

        $operation = $request->get('operation');
        switch($operation) {
            case 'lock':
                $target->setIsLocked(true);
            break;

            case 'unlock':
                $target->setIsLocked(false);
            break;

            case 'addAccountNote':
                $note = $request->get('note');
                if (!$note) abort(400, 'No note text provided.');
                $target->addAccountNote($reporterCharacter->name, $note);
            break;

            default:
                abort(400,'Unrecognized operation requested.');
        }
        return 'OK';
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
